<?php
// includes/order_status.php - Fully automatic order status progression.
// Status is a *derived* field driven only by elapsed time - there is NO
// admin UI to manually set it. Call advance_order_status() lazily whenever
// an order is viewed, and also from cron/update_order_statuses.php.

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

/**
 * Return the number of seconds that must elapse before the given status
 * advances to the next one.
 *
 * Timing rules (realistic demo/production feel):
 *   placed     -> confirmed : 2 hours
 *   confirmed  -> shipped   : 20 hours (next business day)
 *   shipped    -> delivered : standard = 3-5 days, express = 1-2 days
 *
 * @param string $status         Current order status.
 * @param string $shippingMethod 'standard' or 'express'.
 * @return int|null              Seconds to advance, or null if terminal.
 */
function getStatusAdvanceSeconds($status, $shippingMethod = 'standard') {
    switch ($status) {
        case 'placed':
            return 2 * 60 * 60; // 2 hours
        case 'confirmed':
            return 20 * 60 * 60; // 20 hours
        case 'shipped':
            // Pick a value in the allowed range per shipping method.
            // Standard: 3-5 days -> pick 4 days. Express: 1-2 days -> pick 2 days.
            return ($shippingMethod === 'express') ? 2 * 24 * 60 * 60 : 4 * 24 * 60 * 60;
        default:
            return null; // delivered / cancelled have no next step
    }
}

/**
 * Advance a single order's status based on elapsed time.
 *
 * @param array $order A row from the orders table (must include id, status,
 *                     status_updated_at, placed_at, shipping_method).
 * @return string      The (possibly updated) status after this call.
 */
function advanceOrderStatus($order) {
    $pdo = getPdoConnection();

    $orderId = (int)($order['id'] ?? 0);
    if ($orderId <= 0) {
        return (string)($order['status'] ?? 'placed');
    }

    $status = strtolower((string)($order['status'] ?? 'placed'));
    if (in_array($status, ['delivered', 'cancelled'], true)) {
        return $status; // terminal states never advance
    }

    $shippingMethod = strtolower((string)($order['shipping_method'] ?? 'standard'));
    if (!in_array($shippingMethod, ['standard', 'express'], true)) {
        $shippingMethod = 'standard';
    }

    $thresholdSeconds = getStatusAdvanceSeconds($status, $shippingMethod);
    if ($thresholdSeconds === null) {
        return $status;
    }

    // Determine the reference timestamp for this status.
    $referenceRaw = $order['status_updated_at'] ?? $order['placed_at'] ?? null;
    if (!$referenceRaw) {
        $referenceRaw = $order['created_at'] ?? null;
    }
    if (!$referenceRaw) {
        return $status;
    }

    $referenceTs = strtotime($referenceRaw);
    if ($referenceTs === false) {
        return $status;
    }

    $now = time();
    if ($now < $referenceTs + $thresholdSeconds) {
        return $status; // not enough time has passed yet
    }

    // Advance to the next status.
    $nextStatus = $status;
    if ($status === 'placed') {
        $nextStatus = 'confirmed';
    } elseif ($status === 'confirmed') {
        $nextStatus = 'shipped';
    } elseif ($status === 'shipped') {
        $nextStatus = 'delivered';
    }

    $nowSql = date('Y-m-d H:i:s', $now);
    try {
        $stmt = $pdo->prepare("UPDATE orders SET status = :status, status_updated_at = :now WHERE id = :id");
        $stmt->execute([
            'status' => $nextStatus,
            'now' => $nowSql,
            'id' => $orderId,
        ]);
    } catch (Throwable $e) {
        error_log('advanceOrderStatus update failed: ' . $e->getMessage());
        return $status;
    }

    return $nextStatus;
}

/**
 * Load an order row and advance its status in one call.
 * Returns the refreshed order array with the live-computed status.
 *
 * @param int $orderId Order ID.
 * @return array|null  Order row with live status, or null if not found.
 */
function getOrderWithLiveStatus($orderId) {
    $pdo = getPdoConnection();
    $stmt = $pdo->prepare("SELECT * FROM orders WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $orderId]);
    $order = $stmt->fetch();
    if (!$order) {
        return null;
    }

    $newStatus = advanceOrderStatus($order);
    if ($newStatus !== strtolower((string)($order['status'] ?? ''))) {
        $order['status'] = $newStatus;
    }

    return $order;
}

/**
 * Build the tracking timeline for an order.
 * Returns an array of steps: ['label', 'done', 'active', 'in_progress'].
 *
 * @param array $order Order row.
 * @return array       Tracking steps.
 */
function buildOrderTrackingSteps($order) {
    $status = strtolower((string)($order['status'] ?? 'placed'));

    if ($status === 'cancelled') {
        return [
            ['label' => 'Order Placed', 'done' => true, 'active' => false, 'in_progress' => false],
            ['label' => 'Cancelled', 'done' => true, 'active' => true, 'in_progress' => false],
            ['label' => 'Shipped', 'done' => false, 'active' => false, 'in_progress' => false],
            ['label' => 'Delivered', 'done' => false, 'active' => false, 'in_progress' => false],
        ];
    }

    $orderIndex = [
        'placed' => 0,
        'confirmed' => 1,
        'shipped' => 2,
        'delivered' => 3,
    ];
    $currentIndex = $orderIndex[$status] ?? 0;

    $labels = ['Order Placed', 'Confirmed by Seller', 'Shipped', 'Delivered'];
    $steps = [];
    foreach ($labels as $i => $label) {
        $steps[] = [
            'label' => $label,
            'done' => $i < $currentIndex,
            'active' => $i === $currentIndex,
            'in_progress' => $i === $currentIndex && $status !== 'delivered',
        ];
    }

    return $steps;
}

/**
 * Estimate the delivery date range for an order based on its shipping method
 * and when it was shipped (or will ship).
 *
 * @param array $order Order row.
 * @return array       ['start' => 'M j', 'end' => 'M j, Y'].
 */
function estimateDeliveryRange($order) {
    $shippingMethod = strtolower((string)($order['shipping_method'] ?? 'standard'));
    $shippedAt = $order['status_updated_at'] ?? $order['placed_at'] ?? null;

    // If not yet shipped, estimate from placed_at + 2h + 20h.
    $status = strtolower((string)($order['status'] ?? 'placed'));
    if ($status === 'shipped' || $status === 'delivered') {
        $baseTs = $shippedAt ? strtotime($shippedAt) : time();
    } else {
        $baseTs = $order['placed_at'] ? strtotime($order['placed_at']) : time();
        $baseTs += (2 + 20) * 60 * 60; // placed -> confirmed -> shipped
    }

    if ($shippingMethod === 'express') {
        $minDays = 1;
        $maxDays = 2;
    } else {
        $minDays = 3;
        $maxDays = 5;
    }

    $start = date('M j', $baseTs + $minDays * 24 * 60 * 60);
    $end = date('M j, Y', $baseTs + $maxDays * 24 * 60 * 60);

    return ['start' => $start, 'end' => $end];
}