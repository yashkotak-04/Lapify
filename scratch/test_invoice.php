<?php
// scratch/test_invoice.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../vendor/autoload.php';

$mockOrder = [
    'id' => 1,
    'order_number' => 'LPF-2026-TEST01',
    'status' => 'placed',
    'created_at' => '2026-08-17 12:00:00',
    'total_amount' => 60199.0
];
$mockCustomer = [
    'full_name' => 'Samiksha Gajera',
    'email' => 'samiksha@example.com'
];
$mockItems = [
    [
        'brand_name' => 'HP',
        'model' => 'Pavilion Plus 14',
        'price' => 70000.0,
        'quantity' => 1
    ]
];

$itemsHtml = '<tr>
    <td><strong style="color:#0f172a;">HP Pavilion Plus 14</strong></td>
    <td style="text-align:right; font-weight: 600;">Rs. 70,000</td>
    <td style="text-align:center;">1</td>
    <td style="text-align:right; font-weight: 700; color: #1d4ed8;">Rs. 70,000</td>
</tr>';

$logoHtml = '<div style="font-size: 28px; font-weight: 800; color: #ffffff; letter-spacing: -0.5px; margin-bottom: 6px;">&#9733; LAPIFY</div>';

$html = '<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <meta charset="UTF-8" />
    <title>Invoice - LPF-2026-TEST01</title>
    <style>
        body { font-family: "DejaVu Sans", "Helvetica Neue", Helvetica, Arial, sans-serif; background: #f8fafc; margin: 0; padding: 24px; color: #0f172a; line-height: 1.5; font-size: 13px; }
        .invoice-card { background: #ffffff; border-radius: 12px; overflow: hidden; border: 1px solid #cbd5e1; max-width: 800px; margin: 0 auto; }
        .invoice-header { background: #0f172a; color: #ffffff; padding: 24px 30px; }
        .invoice-header h1 { margin: 0; font-size: 24px; font-weight: 700; }
        .invoice-body { padding: 24px 30px; }
        .meta-table { width: 100%; margin-bottom: 20px; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; }
        .muted { color: #64748b; font-size: 12px; }
        table.items-table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        table.items-table th, table.items-table td { padding: 10px 8px; border-bottom: 1px solid #e2e8f0; text-align: left; font-size: 13px; }
        table.items-table th { background: #f1f5f9; font-size: 11px; text-transform: uppercase; color: #475569; font-weight: 700; }
        .total-box { margin-top: 20px; text-align: right; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #cbd5e1; }
        .badge { display: inline-block; padding: 4px 10px; border-radius: 10px; background: #dbeafe; color: #1d4ed8; font-size: 11px; font-weight: 700; }
        .footer-note { margin-top: 25px; color: #64748b; font-size: 11px; text-align: center; border-top: 1px solid #f1f5f9; padding-top: 12px; }
    </style>
</head>
<body>
    <div class="invoice-card">
        <div class="invoice-header">
            ' . $logoHtml . '
            <h1>Order Invoice</h1>
            <div style="color: rgba(255,255,255,0.85); margin-top: 4px; font-size: 13px;">Order Reference: <strong>LPF-2026-TEST01</strong></div>
        </div>
        <div class="invoice-body">
            <table class="meta-table">
                <tr>
                    <td style="vertical-align: top; width: 60%;">
                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Buyer Details</div>
                        <div style="font-weight: 700; font-size: 14px; color: #0f172a;">Samiksha Gajera</div>
                        <div class="muted">samiksha@example.com</div>
                    </td>
                    <td style="vertical-align: top; text-align: right; width: 40%;">
                        <div class="badge">PLACED</div>
                        <div class="muted" style="margin-top: 6px;">Date: Aug 17, 2026</div>
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
                <div style="font-size: 22px; font-weight: 800; color: #1d4ed8; margin: 4px 0 8px;">Rs. 60,199</div>
                <div class="muted">Payment Method: <strong style="color:#0f172a;">Cash on Delivery</strong></div>
                <div class="muted" style="margin-top: 3px;">Invoice No: <strong style="color:#0f172a;">LPF-2026-TEST01</strong></div>
            </div>

            <div class="footer-note">Lapify Marketplace &bull; Official Order Invoice &bull; Thank you for your purchase!</div>
        </div>
    </div>
</body>
</html>';

$dompdf = new \Dompdf\Dompdf(['isRemoteEnabled' => true, 'isHtml5ParserEnabled' => true]);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$output = $dompdf->output();

echo "SUCCESS! Generated PDF byte size: " . strlen($output) . PHP_EOL;
file_put_contents(__DIR__ . '/test_invoice.pdf', $output);
