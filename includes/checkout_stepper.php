<?php
// includes/checkout_stepper.php - Shared 4-step checkout stepper header.
// Usage: renderCheckoutStepper(2);  // 1=Cart, 2=Shipping, 3=Payment, 4=Confirm

function renderCheckoutStepper(int $currentStep) {
    $steps = [
        1 => ['label' => 'Cart', 'icon' => 'bi-cart3', 'url' => 'checkout_cart.php'],
        2 => ['label' => 'Shipping', 'icon' => 'bi-truck', 'url' => 'checkout_shipping.php'],
        3 => ['label' => 'Payment', 'icon' => 'bi-cash-coin', 'url' => 'checkout_payment.php'],
        4 => ['label' => 'Confirm', 'icon' => 'bi-check2-circle', 'url' => 'checkout_confirm.php'],
    ];
    ?>
    <div class="checkout-stepper" role="navigation" aria-label="Checkout progress">
        <?php foreach ($steps as $num => $step): ?>
            <?php
                $isDone = $num < $currentStep;
                $isActive = $num === $currentStep;
                $stateClass = $isDone ? 'done' : ($isActive ? 'active' : 'pending');
                $linkUrl = $isDone ? BASE_URL . '/' . $step['url'] : '#';
            ?>
            <div class="checkout-step <?= $stateClass ?>">
                <a href="<?= $linkUrl ?>" class="checkout-step-link" <?= $isActive || $isDone ? '' : 'aria-disabled="true"' ?>>
                    <span class="checkout-step-node">
                        <?php if ($isDone): ?>
                            <svg viewBox="0 0 24 24" fill="none" class="checkout-step-check" aria-hidden="true">
                                <path d="M20 6L9 17l-5-5" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        <?php else: ?>
                            <i class="bi <?= $step['icon'] ?>"></i>
                        <?php endif; ?>
                    </span>
                    <span class="checkout-step-label"><?= $step['label'] ?></span>
                </a>
                <?php if ($num < 4): ?>
                    <span class="checkout-step-line <?= $isDone ? 'done' : '' ?>"></span>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    </div>
    <?php
}