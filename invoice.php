<?php
// invoice.php - Order Confirmation and Invoice View
ob_start();
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$conn = getDbConnection();
$current_user = getCurrentUser();
$user_id = $current_user['id'];

$order_id = intval($_GET['order_id'] ?? $_GET['id'] ?? 0);
if ($order_id <= 0) {
    renderErrorPage(404, 'Invalid order reference', 'The order you requested could not be found.');
}

// Handle PDF download FIRST, before any HTML/page output
if (isset($_GET['download_pdf']) && $_GET['download_pdf'] === '1') {
    require_once __DIR__ . '/includes/invoice-pdf.php';
    $download_stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ? AND user_id = ?");
    mysqli_stmt_bind_param($download_stmt, "ii", $order_id, $user_id);
    mysqli_stmt_execute($download_stmt);
    $download_order = mysqli_fetch_assoc(mysqli_stmt_get_result($download_stmt));
    mysqli_stmt_close($download_stmt);

    if ($download_order) {
        $download_items_stmt = mysqli_prepare($conn, "SELECT * FROM order_items WHERE order_id = ?");
        mysqli_stmt_bind_param($download_items_stmt, "i", $download_order['id']);
        mysqli_stmt_execute($download_items_stmt);
        $download_items = mysqli_fetch_all(mysqli_stmt_get_result($download_items_stmt), MYSQLI_ASSOC);
        mysqli_stmt_close($download_items_stmt);

        ob_end_clean();
        generateInvoicePdf($download_order, $current_user, $download_items);
    }
    exit; // safety: never fall through to page rendering
}

// Only now pull in the page chrome for the normal (non-download) view
$page_title = "Order Invoice | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$stmt = mysqli_prepare($conn, "SELECT * FROM orders WHERE id = ? AND user_id = ?");
mysqli_stmt_bind_param($stmt, "ii", $order_id, $user_id);
mysqli_stmt_execute($stmt);
$order = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
mysqli_stmt_close($stmt);

if (!$order) {
    renderErrorPage(404, 'Order not found', 'The order you requested could not be found or you do not have access to it.');
}

$item_stmt = mysqli_prepare($conn, "SELECT * FROM order_items WHERE order_id = ?");
mysqli_stmt_bind_param($item_stmt, "i", $order_id);
mysqli_stmt_execute($item_stmt);
$order_items = mysqli_fetch_all(mysqli_stmt_get_result($item_stmt), MYSQLI_ASSOC);
mysqli_stmt_close($item_stmt);
?>

<div class="container py-5">
    <style>
        /* Invoice-specific theme-aware overrides to avoid light-only backgrounds
           that can appear as white bars when dark theme variables aren't applied
           to every global selector. Keep printable rules scoped. */
        .invoice-printable .card,
        .invoice-printable .card-body,
        .invoice-printable .card-header,
        .invoice-printable .card-footer {
            background-color: var(--card-bg) !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        .invoice-printable .table,
        .invoice-printable .table thead th,
        .invoice-printable .table tbody td,
        .invoice-printable .table tbody tr {
            background-color: transparent !important;
            color: var(--text-primary) !important;
            border-color: var(--border-color) !important;
        }

        /* Ensure badges and small text respect theme */
        .invoice-printable .badge,
        .invoice-printable .small,
        .invoice-printable .text-muted {
            color: var(--text-muted) !important;
        }

        @media print {
            body * { visibility: hidden !important; }
            .invoice-printable, .invoice-printable * { visibility: visible !important; }
            .invoice-printable { position: absolute; left: 0; top: 0; width: 100%; }
            #download-invoice-btn, .btn, .navbar, .toast-container, .btn-close { display: none !important; }
        }
    </style>
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="invoice-printable">
                <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-3">
                    <div>
                        <img src="<?= BASE_URL ?>/assets/logo-monochrome-white.svg" alt="Lapify" style="height: 48px; width: auto; margin-bottom: 10px; display: block;">
                        <h3 class="fw-bold mb-1"><i class="bi bi-receipt-cutoff me-2"></i>Order Invoice</h3>
                        <p class="mb-0 text-white-50">Order reference <strong><?= escape($order['order_number']) ?></strong></p>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <a href="<?= BASE_URL ?>/orders.php" class="btn btn-light rounded-pill px-3 py-2">
                            <i class="bi bi-arrow-left"></i> Back to Orders
                        </a>
                        <a id="download-invoice-top-btn" href="<?= BASE_URL ?>/invoice.php?order_id=<?= (int)$order['id'] ?>&download_pdf=1" data-no-transition="1" class="btn btn-success rounded-pill px-3 py-2">
                            <i class="bi bi-download me-1"></i> Download Invoice PDF
                        </a>
                    </div>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php displayFlash(); ?>
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h5 class="fw-bold">Buyer</h5>
                            <p class="mb-1"><?= escape($current_user['full_name']) ?></p>
                            <p class="text-muted mb-0"><?= escape($current_user['email']) ?></p>
                        </div>
                        <div class="col-md-6 text-md-end mt-3 mt-md-0">
                            <span class="badge <?= getOrderStatusBadgeClass($order['status'] ?? 'pending') ?> rounded-pill text-uppercase fs-7"><?= escape(getOrderStatusLabel($order['status'] ?? 'pending')) ?></span>
                            <div class="text-muted mt-2">Placed on <?= formatDate($order['created_at']) ?></div>
                        </div>
                    </div>

                    <div class="table-responsive mb-4">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-center">Qty</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($order_items as $item): ?>
                                    <tr>
                                        <td>
                                            <div class="fw-bold"><?= escape($item['brand_name']) ?> <?= escape($item['model']) ?></div>
                                        </td>
                                        <td class="text-end"><?= formatPrice($item['price']) ?></td>
                                        <td class="text-center"><?= escape($item['quantity']) ?></td>
                                        <td class="text-end"><?= formatPrice($item['price'] * $item['quantity']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 border-top pt-3 mt-3">
                        <div class="text-start">
                            <div class="small text-muted">Order Total (COD)</div>
                            <div class="fs-4 fw-bold text-primary"><?= formatPrice($order['total_amount']) ?></div>
                        </div>
                        <div class="text-start text-md-center">
                            <div class="small text-muted">Payment Method</div>
                            <div class="fw-semibold">Cash on Delivery</div>
                        </div>
                        <div class="text-start text-md-end">
                            <div class="small text-muted">Invoice Number</div>
                            <div class="fw-semibold"><?= escape($order['order_number']) ?></div>
                        </div>
                    </div>

                    <div class="mt-4 d-flex flex-column flex-sm-row gap-2">
                        <a id="download-invoice-btn" href="<?= BASE_URL ?>/invoice.php?order_id=<?= (int)$order['id'] ?>&download_pdf=1" data-no-transition="1" class="btn btn-success rounded-3 px-4 py-2">
                            <i class="bi bi-download me-1"></i> Download Invoice
                        </a>
                        <a href="<?= BASE_URL ?>/buy.php" class="btn btn-outline-primary rounded-3 px-4 py-2">Continue Shopping</a>
                        <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-primary rounded-3 px-4 py-2">Go to Dashboard</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const downloadBtns = document.querySelectorAll('#download-invoice-btn, #download-invoice-top-btn');
    
    async function startInvoiceDownload(btn) {
        if (!btn || btn.dataset.downloading === 'true') return;
        btn.dataset.downloading = 'true';
        
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Preparing Invoice...';
        btn.style.pointerEvents = 'none';
        btn.style.opacity = '0.85';
        
        const url = btn.getAttribute('href');
        
        try {
            // Fetch PDF and wait 1 second concurrently for clean UX
            const [response] = await Promise.all([
                fetch(url, { credentials: 'same-origin' }),
                new Promise(function (resolve) { setTimeout(resolve, 1000); })
            ]);
            
            if (!response.ok) {
                throw new Error('HTTP error ' + response.status);
            }
            
            const blob = await response.blob();
            
            // Extract filename from Content-Disposition header if available
            let filename = 'invoice.pdf';
            const disposition = response.headers.get('Content-Disposition') || response.headers.get('content-disposition');
            if (disposition && disposition.indexOf('filename=') !== -1) {
                const matches = /filename[^;=\n]*=((['"]).*?\2|[^;\n]*)/.exec(disposition);
                if (matches != null && matches[1]) {
                    filename = matches[1].replace(/['"]/g, '').trim();
                }
            }
            
            // Trigger native download via object URL
            const blobUrl = window.URL.createObjectURL(blob);
            const downloadLink = document.createElement('a');
            downloadLink.style.display = 'none';
            downloadLink.href = blobUrl;
            downloadLink.download = filename;
            document.body.appendChild(downloadLink);
            downloadLink.click();
            
            setTimeout(function () {
                document.body.removeChild(downloadLink);
                window.URL.revokeObjectURL(blobUrl);
            }, 1000);
            
            btn.innerHTML = '<i class="bi bi-check2-circle me-1"></i> Download Complete';
            if (typeof window.showToast === 'function') {
                window.showToast('Invoice downloaded successfully!', 'success', 3500);
            }
        } catch (err) {
            console.error('Invoice download error:', err);
            // Fallback: direct download link
            const fallbackLink = document.createElement('a');
            fallbackLink.style.display = 'none';
            fallbackLink.href = url;
            fallbackLink.download = 'invoice.pdf';
            document.body.appendChild(fallbackLink);
            fallbackLink.click();
            setTimeout(function () { document.body.removeChild(fallbackLink); }, 1000);
            
            btn.innerHTML = '<i class="bi bi-download me-1"></i> Download Started';
        } finally {
            setTimeout(function () {
                btn.innerHTML = originalHtml;
                btn.style.pointerEvents = '';
                btn.style.opacity = '';
                btn.dataset.downloading = 'false';
            }, 2500);
        }
    }
    
    downloadBtns.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            e.stopPropagation();
            startInvoiceDownload(btn);
        });
    });

    // If redirected with auto download parameter (?autodownload=1)
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('autodownload') === '1' || urlParams.get('download') === '1') {
        const mainBtn = document.getElementById('download-invoice-btn') || downloadBtns[0];
        if (mainBtn) {
            setTimeout(function () {
                startInvoiceDownload(mainBtn);
            }, 600);
        }
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>