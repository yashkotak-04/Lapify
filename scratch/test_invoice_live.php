<?php
// scratch/test_invoice_live.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/invoice-pdf.php';

$mockOrder = [
    'id' => 101,
    'order_number' => 'LPF-2026-LIVE99',
    'status' => 'confirmed',
    'created_at' => '2026-08-17 12:30:00',
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

echo "Testing renderOrderInvoiceHtml & Dompdf compatibility..." . PHP_EOL;
$html = renderOrderInvoiceHtml($mockOrder, $mockCustomer, $mockItems);
echo "HTML Length: " . strlen($html) . PHP_EOL;

if (class_exists('Dompdf\\Dompdf')) {
    $dompdf = new \Dompdf\Dompdf([
        'isRemoteEnabled'      => true,
        'isHtml5ParserEnabled' => true,
    ]);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    $pdf = $dompdf->output();
    echo "PDF generated successfully! Byte count: " . strlen($pdf) . PHP_EOL;
    file_put_contents(__DIR__ . '/sample_invoice.pdf', $pdf);
} else {
    echo "Dompdf not found!" . PHP_EOL;
}
