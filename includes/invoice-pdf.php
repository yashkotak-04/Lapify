<?php
// includes/invoice-pdf.php - Shared invoice rendering for user/admin views

$autoload = __DIR__ . '/../vendor/autoload.php';
if (file_exists($autoload)) {
    require_once $autoload;
}

if (!function_exists('getOrderStatusLabel')) {
    function getOrderStatusLabel($status) {
        $status = strtolower((string)($status ?? 'placed'));
        $map = [
            'placed'     => 'Placed',
            'confirmed'  => 'Confirmed',
            'shipped'    => 'Shipped',
            'delivered'  => 'Delivered',
            'cancelled'  => 'Cancelled',
        ];
        return $map[$status] ?? ucfirst($status);
    }
}

if (!function_exists('getOrderStatusBadgeClass')) {
    function getOrderStatusBadgeClass($status) {
        $status = strtolower((string)($status ?? 'placed'));
        $map = [
            'placed'     => 'bg-secondary',
            'confirmed'  => 'bg-info text-dark',
            'shipped'    => 'bg-primary',
            'delivered'  => 'bg-success',
            'cancelled'  => 'bg-danger',
        ];
        return $map[$status] ?? 'bg-secondary';
    }
}

function renderOrderInvoiceHtml($order, $customer, $items) {
    $itemsHtml = '';
    $grandTotal = 0.0;

    foreach ($items as $item) {
        $price = (float)($item['price'] ?? 0);
        $qty = (int)($item['quantity'] ?? 1);
        $lineTotal = $price * $qty;
        $grandTotal += $lineTotal;
        $itemLabel = trim((string)($item['brand_name'] ?? '') . ' ' . (string)($item['model'] ?? ''));
        $itemsHtml .= '<tr>'
            . '<td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0;"><strong style="color:#0f172a;">' . htmlspecialchars($itemLabel ?: 'Laptop item', ENT_QUOTES, 'UTF-8') . '</strong></td>'
            . '<td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align:right; font-weight: 600;">Rs. ' . number_format($price, 2) . '</td>'
            . '<td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align:center;">' . htmlspecialchars((string)$qty, ENT_QUOTES, 'UTF-8') . '</td>'
            . '<td style="padding: 10px 12px; border-bottom: 1px solid #e2e8f0; text-align:right; font-weight: 700; color: #1d4ed8;">Rs. ' . number_format($lineTotal, 2) . '</td>'
            . '</tr>';
    }

    $customerName = htmlspecialchars((string)($customer['full_name'] ?? $customer['name'] ?? 'Customer'), ENT_QUOTES, 'UTF-8');
    $customerEmail = htmlspecialchars((string)($customer['email'] ?? ''), ENT_QUOTES, 'UTF-8');
    $orderNumber = htmlspecialchars((string)($order['order_number'] ?? ''), ENT_QUOTES, 'UTF-8');
    $statusLabel = htmlspecialchars(getOrderStatusLabel($order['status'] ?? 'placed'), ENT_QUOTES, 'UTF-8');
    $createdAt = !empty($order['created_at']) ? date('M j, Y', strtotime($order['created_at'])) : date('M j, Y');

    // Clean header branding without unsupported unicode glyphs
    $logoHtml = '<div style="font-size: 24px; font-weight: 800; color: #ffffff; letter-spacing: 0.5px; margin-bottom: 6px;">LAPIFY MARKETPLACE</div>';
    if (extension_loaded('gd')) {
        $logoPath = __DIR__ . '/../assets/images/lapify-logo-invoice.png';
        if (file_exists($logoPath) && filesize($logoPath) > 0) {
            $logoDataUri = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
            $logoHtml = '<img src="' . $logoDataUri . '" alt="Lapify Logo" style="height: 48px; max-height: 48px; margin-bottom: 12px; display: block; border: 0;" />';
        }
    }

    $orderTotal = (float)($order['total_amount'] ?? $grandTotal);

    return '<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8" />
    <title>Invoice - ' . $orderNumber . '</title>
    <style>
        body { font-family: "DejaVu Sans", "Helvetica Neue", Helvetica, Arial, sans-serif; background: #f8fafc; margin: 0; padding: 24px; color: #0f172a; line-height: 1.5; font-size: 13px; }
        .invoice-card { background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1; max-width: 800px; margin: 0 auto; }
        .invoice-header { background: #0f172a; color: #ffffff; padding: 24px 30px; }
        .invoice-header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .invoice-body { padding: 24px 30px; }
        .meta-table { width: 100%; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; }
        .muted { color: #64748b; font-size: 12px; }
        table.items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.items-table th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; color: #475569; font-weight: 700; padding: 10px 12px; text-align: left; border-bottom: 1px solid #e2e8f0; }
        .total-box { margin-top: 20px; text-align: right; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; }
        .badge { display: inline-block; padding: 4px 12px; border-radius: 12px; background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .footer-note { margin-top: 25px; color: #64748b; font-size: 11px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="invoice-card">
        <div class="invoice-header">
            ' . $logoHtml . '
            <h1>Order Invoice</h1>
            <div style="color: rgba(255,255,255,0.85); margin-top: 4px; font-size: 13px;">Order Reference: <strong>' . $orderNumber . '</strong></div>
        </div>
        <div class="invoice-body">
            <table class="meta-table">
                <tr>
                    <td style="vertical-align: top; width: 60%;">
                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; margin-bottom: 4px;">Buyer Details</div>
                        <div style="font-weight: 700; font-size: 14px; color: #0f172a;">' . $customerName . '</div>
                        <div class="muted">' . $customerEmail . '</div>
                    </td>
                    <td style="vertical-align: top; text-align: right; width: 40%;">
                        <div class="badge">' . $statusLabel . '</div>
                        <div class="muted" style="margin-top: 6px;">Date: ' . $createdAt . '</div>
                    </td>
                </tr>
            </table>

            <table class="items-table">
                <thead>
                    <tr>
                        <th>Product Description</th>
                        <th style="text-align:right;">Price</th>
                        <th style="text-align:center;">Qty</th>
                        <th style="text-align:right;">Total Amount</th>
                    </tr>
                </thead>
                <tbody>
                    ' . $itemsHtml . '
                </tbody>
            </table>

            <div class="total-box">
                <div class="muted">Order Grand Total (COD)</div>
                <div style="font-size: 22px; font-weight: 800; color: #1d4ed8; margin: 4px 0 8px;">Rs. ' . number_format($orderTotal, 2) . '</div>
                <div class="muted">Payment Method: <strong style="color:#0f172a;">Cash on Delivery</strong></div>
                <div class="muted" style="margin-top: 3px;">Invoice No: <strong style="color:#0f172a;">' . $orderNumber . '</strong></div>
            </div>

            <div class="footer-note">Lapify Marketplace | Official Order Invoice | Thank you for your purchase!</div>
        </div>
    </div>
</body>
</html>';
}

function generateInvoicePdf($order, $customer, $items, $filename = null) {
    $html = renderOrderInvoiceHtml($order, $customer, $items);

    while (ob_get_level()) {
        ob_end_clean();
    }

    if (class_exists('Dompdf\\Dompdf')) {
        $dompdf = new \Dompdf\Dompdf([
            'isRemoteEnabled'      => true,
            'isHtml5ParserEnabled' => true,
        ]);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $downloadName = $filename ?: 'invoice-' . ($order['order_number'] ?? 'order') . '.pdf';
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        header('Pragma: public');
        echo $dompdf->output();
        exit;
    }

    $downloadName = $filename ?: 'invoice-' . ($order['order_number'] ?? 'order') . '.html';
    header('Content-Type: text/html; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . basename($downloadName) . '"');
    echo $html;
    exit;
}
