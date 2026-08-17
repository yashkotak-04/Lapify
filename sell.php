<?php
// sell.php - Create Laptop Listing
// Brand → Model dependent dropdown (AJAX), Condition (New/Old), duplicate-new validation.
$page_title = "Post Laptop Ad | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$conn = getDbConnection();
$user = getCurrentUser();

// Fetch active brands for dropdown.
$brands_res = mysqli_query($conn, "SELECT id, brand_name FROM brands WHERE status = 'active' ORDER BY brand_name ASC");

// Pre-selected values (after a failed submit, so the form is repopulated).
$old_brand_id = intval($_GET['brand_id'] ?? 0);
$old_model = sanitizeInput($_GET['model'] ?? '');
$old_condition = strtolower(trim(sanitizeInput($_GET['condition_type'] ?? 'new')));
if (!in_array($old_condition, ['new', 'old'], true)) {
    $old_condition = 'new';
}

require_once __DIR__ . '/includes/navbar.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-9">
            <div class="card border-0 shadow-lg rounded-4 overflow-hidden">
                <div class="card-header bg-primary text-white p-4">
                    <h3 class="fw-bold mb-1"><i class="bi bi-plus-circle-fill me-2"></i>Post Your Laptop Ad</h3>
                    <p class="mb-0 text-white-50">Fill in the specs to list your laptop for direct buyers</p>
                </div>
                <div class="card-body p-4 p-md-5">
                    <?php displayFlash(); ?>

                    <!-- Global error/success container (filled by JS on fetch responses) -->
                    <div id="form-alert" class="alert d-none rounded-3 shadow-sm mb-4" role="alert"></div>

                    <?php if (!empty($errors) && is_array($errors)): ?>
                        <div class="alert alert-danger border-0 rounded-3 shadow-sm mb-4">
                            <ul class="mb-0 ps-3">
                                <?php foreach ($errors as $err): ?>
                                    <li><?= escape($err) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endif; ?>

                    <form id="sell-form" action="submit_laptop.php" method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <?= renderCsrfInput() ?>

                        <!-- Basic Info Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label for="brand_id" class="form-label font-weight-bold">Brand <span class="text-danger">*</span></label>
                                <select name="brand_id" id="brand_id" class="form-select" required>
                                    <option value="">-- Select Brand --</option>
                                    <?php while ($b = mysqli_fetch_assoc($brands_res)): ?>
                                        <option value="<?= (int)$b['id'] ?>" <?= $old_brand_id === (int)$b['id'] ? 'selected' : '' ?>>
                                            <?= escape($b['brand_name']) ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                                <div class="invalid-feedback" id="brand_id-error"></div>
                            </div>

                            <div class="col-md-6">
                                <label for="model" class="form-label font-weight-bold">Model <span class="text-danger">*</span></label>
                                <select name="model" id="model" class="form-select" disabled required>
                                    <option value="">Select a brand first</option>
                                </select>
                                <div class="invalid-feedback" id="model-error"></div>
                            </div>
                        </div>

                        <!-- Classification Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold d-block">Condition <span class="text-danger">*</span></label>
                                <div class="condition-radio-container" id="condition_type_group">
                                    <label class="condition-radio-option <?= $old_condition === 'new' ? 'active' : '' ?>" for="condition_new">
                                        <input class="form-check-input condition-radio-input" type="radio" name="condition_type" id="condition_new" value="new" <?= $old_condition === 'new' ? 'checked' : '' ?>>
                                        <span class="condition-radio-text">New Laptop</span>
                                    </label>
                                    <label class="condition-radio-option <?= $old_condition === 'old' ? 'active' : '' ?>" for="condition_old">
                                        <input class="form-check-input condition-radio-input" type="radio" name="condition_type" id="condition_old" value="old" <?= $old_condition === 'old' ? 'checked' : '' ?>>
                                        <span class="condition-radio-text">Old Laptop</span>
                                    </label>
                                </div>
                                <div class="invalid-feedback d-block" id="condition_type-error"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="price" class="form-label font-weight-bold">Selling Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="1" name="price" id="price" class="form-control" placeholder="e.g. 750.00" required>
                                <div class="invalid-feedback" id="price-error"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="quantity" class="form-label font-weight-bold">Quantity in Stock <span class="text-danger">*</span></label>
                                <input type="number" min="1" name="quantity" id="quantity" class="form-control" value="1" required>
                                <div class="invalid-feedback" id="quantity-error"></div>
                            </div>
                        </div>

                        <!-- Technical Specs Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="processor" class="form-label font-weight-bold">Processor (CPU)</label>
                                <select name="processor" id="processor" class="form-select">
                                    <option value="">-- Optional --</option>
                                    <option value="Intel Core i5 12th Gen">Intel Core i5 12th Gen</option>
                                    <option value="Intel Core i7 12th Gen">Intel Core i7 12th Gen</option>
                                    <option value="Intel Core i9 14th Gen">Intel Core i9 14th Gen</option>
                                    <option value="Apple M3 Max 16-Core">Apple M3 Max 16-Core</option>
                                    <option value="Apple M1 8-Core">Apple M1 8-Core</option>
                                    <option value="AMD Ryzen 7 7840U">AMD Ryzen 7 7840U</option>
                                    <option value="AMD Ryzen 7 5800H">AMD Ryzen 7 5800H</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="ram" class="form-label font-weight-bold">RAM Memory</label>
                                <select name="ram" id="ram" class="form-select">
                                    <option value="">-- Optional --</option>
                                    <option value="8GB">8GB</option>
                                    <option value="16GB">16GB</option>
                                    <option value="32GB">32GB</option>
                                    <option value="64GB">64GB</option>
                                </select>
                            </div>

                            <div class="col-md-4">
                                <label for="storage" class="form-label font-weight-bold">Storage Capacity</label>
                                <select name="storage" id="storage" class="form-select">
                                    <option value="">-- Optional --</option>
                                    <option value="256GB SSD">256GB SSD</option>
                                    <option value="512GB SSD">512GB SSD</option>
                                    <option value="1TB SSD">1TB SSD</option>
                                    <option value="2TB SSD">2TB SSD</option>
                                </select>
                            </div>
                        </div>

                        <!-- Detailed Description -->
                        <div class="mb-4">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <label for="description" class="form-label font-weight-bold mb-0">Listing Description</label>
                                <button type="button" id="btn-auto-desc" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 shadow-sm">
                                    <i class="bi bi-magic me-1"></i>Auto-Generate from Specs
                                </button>
                            </div>
                            <textarea name="description" id="description" rows="6" class="form-control" placeholder="Select the laptop details above and the description will be automatically generated, or write your own custom notes..."></textarea>
                            <div class="form-text text-muted">
                                <i class="bi bi-info-circle me-1"></i>Automatically creates a detailed product description based on the brand, model, condition, CPU, RAM, and storage selected above.
                            </div>
                        </div>

                        <!-- Image Upload -->
                        <div class="mb-4">
                            <label for="image" class="form-label font-weight-bold">Laptop Photo (Max 2MB)</label>
                            <input type="file" name="image" id="image" class="form-control image-preview-input" data-preview-target="img-preview" accept="image/jpeg,image/png,image/webp">
                            <div class="form-text">Accepted formats: JPG, PNG, WEBP.</div>
                            <div class="mt-3">
                                <img id="img-preview" src="#" alt="Preview" class="img-thumbnail d-none" style="max-height: 180px;">
                            </div>
                        </div>

                        <hr class="my-4">

                        <div class="d-flex justify-content-end gap-3 align-items-center">
                            <a href="my-listings.php" class="btn btn-outline-secondary px-4 font-weight-bold text-white border-secondary-subtle">Cancel</a>
                            <button type="submit" id="submit-btn" class="btn btn-primary px-5 font-weight-bold py-2.5">
                                <i class="bi bi-check-circle-fill me-2"></i>Publish Listing Now
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Listing Success Celebration Modal -->
<div class="modal fade" id="listingSuccessModal" tabindex="-1" aria-labelledby="listingSuccessModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content listing-success-content border-0 rounded-4 shadow-lg text-center p-4">
            <div class="modal-body p-3">
                <div class="listing-success-icon-wrapper mb-3">
                    <div class="listing-success-glow"></div>
                    <div class="listing-success-icon">
                        <i class="bi bi-check-circle-fill"></i>
                    </div>
                </div>

                <h3 class="fw-bold text-success mb-2" id="listingSuccessModalLabel">Product Listed Successfully! 🎉</h3>
                <p class="text-secondary fs-6 mb-4 lh-base">
                    Your laptop listing has been created and submitted successfully.<br>
                    <span class="badge bg-warning-subtle text-warning-emphasis border border-warning-subtle px-3 py-2 mt-3 rounded-pill fw-semibold fs-7">
                        <i class="bi bi-clock-history me-1"></i> Pending Admin Approval
                    </span>
                    <span class="d-block text-muted mt-2 small">
                        Our admin team will review and approve your listing as soon as possible.
                    </span>
                </p>

                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="my-listings.php" class="btn btn-success btn-lg px-4 rounded-pill shadow-sm fw-bold">
                        <i class="bi bi-collection-play me-2"></i> View My Listings
                    </a>
                    <button type="button" class="btn btn-outline-secondary btn-lg px-4 rounded-pill fw-bold" id="post-another-btn" data-bs-dismiss="modal">
                        Post Another Ad
                    </button>
                </div>

                <div class="listing-redirect-bar mt-4">
                    <div class="listing-redirect-progress" id="redirect-progress"></div>
                </div>
                <small class="text-muted d-block mt-2">Redirecting to your listings in <strong id="redirect-counter">5</strong>s...</small>
            </div>
        </div>
    </div>
</div>

<script>
(function() {
    'use strict';

    const form            = document.getElementById('sell-form');
    const brandSelect     = document.getElementById('brand_id');
    const modelSelect     = document.getElementById('model');
    const conditionNew    = document.getElementById('condition_new');
    const conditionOld    = document.getElementById('condition_old');
    const priceInput      = document.getElementById('price');
    const processorSelect = document.getElementById('processor');
    const ramSelect       = document.getElementById('ram');
    const storageSelect   = document.getElementById('storage');
    const descInput       = document.getElementById('description');
    const btnAutoDesc     = document.getElementById('btn-auto-desc');
    const submitBtn       = document.getElementById('submit-btn');
    const formAlert       = document.getElementById('form-alert');
    const csrfInput       = form.querySelector('input[name="csrf_token"]');
    const csrfToken       = csrfInput ? csrfInput.value : '';

    const BASE = <?= json_encode(rtrim(BASE_URL, '/')) ?>;

    let isUserCustomEdited = false;

    function getSelectedCondition() {
        if (conditionNew.checked) return 'new';
        if (conditionOld.checked) return 'old';
        return 'new';
    }

    function buildAutoDescription() {
        const brandName = brandSelect.selectedIndex > 0 ? brandSelect.options[brandSelect.selectedIndex].text.replace('-- Select Brand --', '').trim() : '';
        const modelName = modelSelect.value ? modelSelect.value.trim() : '';
        const condition = getSelectedCondition();
        const condLabel = condition === 'new' ? 'Brand New (100% Unused / Sealed)' : 'Verified Pre-Owned (Good Working Condition)';
        const priceVal = priceInput.value.trim();
        const processor = processorSelect.value.trim();
        const ram = ramSelect.value.trim();
        const storage = storageSelect.value.trim();

        if (!brandName && !modelName && !processor && !ram && !storage) {
            return '';
        }

        let lines = [];
        
        // Header
        let productTitle = brandName;
        if (modelName) {
            productTitle = (brandName && !modelName.toLowerCase().startsWith(brandName.toLowerCase()))
                ? (brandName + ' ' + modelName)
                : modelName;
        }
        if (productTitle) {
            lines.push('🌟 ' + productTitle);
        }
        
        lines.push('✨ Condition: ' + condLabel);
        if (priceVal && parseFloat(priceVal) > 0) {
            lines.push('💰 Asking Price: ₹' + Number(priceVal).toLocaleString('en-IN'));
        }
        
        lines.push('\n⚙️ Technical Specifications:');
        lines.push('• Processor: ' + (processor || 'High-Performance Multi-Core Processor'));
        lines.push('• RAM: ' + (ram || 'Standard High-Speed RAM'));
        lines.push('• Storage: ' + (storage || 'High-Speed Solid State Drive (SSD)'));

        lines.push('\n📋 Product Highlights & Included Items:');
        if (condition === 'new') {
            lines.push('• 100% genuine brand new unit in original factory packaging.');
            lines.push('• Includes original power adapter, charging cable, and user documentation.');
        } else {
            lines.push('• Thoroughly tested and verified to be in 100% full working order.');
            lines.push('• Clean cosmetic condition, screen is clear, and battery holds reliable charge.');
            lines.push('• Includes compatible power adapter.');
        }
        lines.push('• Safe and verified transaction on Lapify Marketplace with Buyer Protection.');

        return lines.join('\n');
    }

    function autoUpdateDescription() {
        if (!isUserCustomEdited || !descInput.value.trim()) {
            const text = buildAutoDescription();
            if (text) {
                descInput.value = text;
            }
        }
    }

    // 1. Brand → Model dependent dropdown
    brandSelect.addEventListener('change', function() {
        const brandId = this.value;
        modelSelect.innerHTML = '<option value="">Select a brand first</option>';
        modelSelect.disabled = true;
        autoUpdateDescription();
        if (!brandId) return;
        modelSelect.innerHTML = '<option value="">Loading models…</option>';
        fetch(BASE + '/get_models.php?brand_id=' + encodeURIComponent(brandId))
        .then(resp => resp.json())
        .then(data => {
            modelSelect.innerHTML = '<option value="">-- Select Model --</option>';
            data.models.forEach(m => {
                const opt = document.createElement('option');
                opt.value = m.model_name;
                opt.textContent = m.model_name + (m.year ? ' (' + m.year + ')' : '');
                modelSelect.appendChild(opt);
            });
            modelSelect.disabled = false;
            autoUpdateDescription();
        });
    });

    function syncConditionUI() {
        const isNew = conditionNew.checked;
        const newOption = conditionNew.closest('.condition-radio-option');
        const oldOption = conditionOld.closest('.condition-radio-option');
        if (newOption && oldOption) {
            newOption.classList.toggle('active', isNew);
            oldOption.classList.toggle('active', !isNew);
        }
    }

    modelSelect.addEventListener('change', autoUpdateDescription);
    conditionNew.addEventListener('change', function() {
        syncConditionUI();
        autoUpdateDescription();
    });
    conditionOld.addEventListener('change', function() {
        syncConditionUI();
        autoUpdateDescription();
    });
    priceInput.addEventListener('input', autoUpdateDescription);
    processorSelect.addEventListener('change', autoUpdateDescription);
    ramSelect.addEventListener('change', autoUpdateDescription);
    storageSelect.addEventListener('change', autoUpdateDescription);

    descInput.addEventListener('input', function() {
        if (descInput.value.trim() !== '') {
            isUserCustomEdited = true;
        } else {
            isUserCustomEdited = false;
        }
    });

    if (btnAutoDesc) {
        btnAutoDesc.addEventListener('click', function() {
            const text = buildAutoDescription();
            if (text) {
                descInput.value = text;
                isUserCustomEdited = false;
            }
        });
    }

    function runDuplicateCheck() {
        const brandId = brandSelect.value;
        const model   = modelSelect.value;
        const cond    = getSelectedCondition();

        if (!brandId || !model || cond !== 'new') {
            return Promise.resolve(true);
        }

        const bodyData = new URLSearchParams();
        bodyData.append('csrf_token', csrfToken);
        bodyData.append('brand_id', brandId);
        bodyData.append('model', model);
        bodyData.append('condition_type', cond);
        bodyData.append('check_only', '1');

        return fetch(BASE + '/submit_laptop.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: bodyData.toString()
        })
        .then(function(resp) { return resp.json(); })
        .then(function(data) {
            if (!data.success) {
                if (data.field) {
                    setFieldError(data.field, data.message);
                } else if (data.errors && typeof data.errors === 'object') {
                    Object.keys(data.errors).forEach(function(k) {
                        setFieldError(k, data.errors[k]);
                    });
                }
                showAlert(data.message || 'Duplicate listing check failed.', 'danger');
                return false;
            }
            return true;
        })
        .catch(function() {
            return true;
        });
    }

    // 3. UI Helpers
    function showAlert(msg, type) {
        formAlert.className = 'alert alert-' + type + ' rounded-3 shadow-sm mb-4';
        formAlert.textContent = msg;
        formAlert.classList.remove('d-none');
        formAlert.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    }

    function hideAlert() {
        formAlert.classList.add('d-none');
    }

    function setFieldError(fieldId, msg) {
        if (fieldId === 'condition_type') {
            const group = document.getElementById('condition_type_group');
            if (group) group.classList.add('is-invalid');
            const errDiv = document.getElementById('condition_type-error');
            if (errDiv) errDiv.textContent = msg;
            return;
        }
        const input = document.getElementById(fieldId);
        const errDiv = document.getElementById(fieldId + '-error');
        if (input) {
            input.classList.add('is-invalid');
            input.focus();
        }
        if (errDiv) {
            errDiv.textContent = msg;
        }
    }

    function clearErrors() {
        hideAlert();
        form.querySelectorAll('.is-invalid').forEach(function(el) {
            el.classList.remove('is-invalid');
        });
        const condGroup = document.getElementById('condition_type_group');
        if (condGroup) condGroup.classList.remove('is-invalid');
        form.querySelectorAll('.invalid-feedback').forEach(function(el) {
            el.textContent = '';
        });
    }

    // 4. Form Submit Handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors();

        submitBtn.disabled = true;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Listing Your Laptop…';

        runDuplicateCheck().then(function(ok) {
            if (!ok) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
                return;
            }

            const fd = new FormData(form);

            fetch(BASE + '/submit_laptop.php', {
                method: 'POST',
                headers: { 'X-Requested-With': 'fetch' },
                body: fd
            })
            .then(resp => resp.json())
            .then(data => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;

                if (data.success) {
                    showAlert(data.message, 'success');

                    // 1. Trigger confetti
                    if (typeof confetti === 'function') {
                        confetti({ particleCount: 160, spread: 80, origin: { y: 0.6 } });
                    }

                    // 2. Reset form
                    form.reset();
                    syncConditionUI();
                    modelSelect.innerHTML = '<option value="">Select a brand first</option>';
                    modelSelect.disabled = true;

                    // 3. Show Celebration Modal
                    const modalEl = document.getElementById('listingSuccessModal');
                    const bsModal = new bootstrap.Modal(modalEl);
                    bsModal.show();

                    // 4. Countdown redirect
                    const progressBar = document.getElementById('redirect-progress');
                    const counterEl = document.getElementById('redirect-counter');
                    let timeLeft = 5.0;

                    const interval = setInterval(function() {
                        timeLeft -= 0.1;
                        if (counterEl) counterEl.textContent = Math.max(0, Math.ceil(timeLeft));
                        if (progressBar) progressBar.style.width = Math.min(100, Math.max(0, ((5.0 - timeLeft) / 5.0) * 100)) + '%';

                        if (timeLeft <= 0) {
                            clearInterval(interval);
                            window.location.href = BASE + '/my-listings.php';
                        }
                    }, 100);

                    document.getElementById('post-another-btn').addEventListener('click', () => clearInterval(interval));
                } else {
                    showAlert(data.message || 'Please fix the highlighted errors.', 'danger');
                    if (data.errors && typeof data.errors === 'object') {
                        Object.keys(data.errors).forEach(function(key) {
                            setFieldError(key, data.errors[key]);
                        });
                    } else if (data.field) {
                        setFieldError(data.field, data.message);
                    }
                }
            })
            .catch(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
                showAlert('Network error, please try again.', 'danger');
            });
        });
    });
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>