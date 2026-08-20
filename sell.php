<?php
// sell.php - Create Laptop Listing
// Brand → Model dependent dropdown (AJAX), Condition (New/Old), duplicate-new validation.
$page_title = "Post Laptop Ad | Lapify";
require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/auth.php';

requireLogin();

$conn = getDbConnection();
$user = getCurrentUser();
$is_admin = isAdmin();

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

                            <div class="col-md-6" id="model-field-container">
                                <label for="model" class="form-label font-weight-bold">Model <span class="text-danger">*</span></label>

                                <!-- Model Dropdown (shown when brand has predefined models) -->
                                <div id="model-select-wrapper">
                                    <select name="model" id="model" class="form-select" disabled required>
                                        <option value="">Select a brand first</option>
                                    </select>
                                </div>

                                <!-- Model Text Input (automatically shown when brand has 0 models) -->
                                <div id="model-text-wrapper" class="d-none">
                                    <div class="input-group">
                                        <span class="input-group-text bg-light text-muted"><i class="bi bi-laptop"></i></span>
                                        <input type="text" name="model_custom" id="model_custom" class="form-control" placeholder="Enter laptop model (e.g. Gram 16, Blade 15, Surface Pro 9)..." maxlength="100" autocomplete="off">
                                    </div>
                                    <div class="form-text text-muted small mt-1" id="model-custom-hint">
                                        <i class="bi bi-info-circle me-1"></i>Type the model name for this brand.
                                    </div>
                                </div>

                                <div class="invalid-feedback" id="model-error"></div>
                            </div>
                        </div>

                        <!-- Classification Row -->
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label class="form-label font-weight-bold d-block">Condition <span class="text-danger">*</span></label>
                                <?php if ($is_admin): ?>
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
                                <?php else: ?>
                                    <input type="text" id="condition_display" class="form-control bg-light text-dark" value="Pre-Owned / Used Laptop" readonly style="cursor: not-allowed; font-weight: 500;">
                                    <input type="hidden" name="condition_type" id="condition_old" value="old">
                                    <div class="form-text text-muted small mt-1" style="font-size: 0.76rem;">
                                        <i class="bi bi-info-circle me-1"></i>Pre-Owned listing. Brand new laptops are cataloged by verified admins.
                                    </div>
                                <?php endif; ?>
                                <div class="invalid-feedback d-block" id="condition_type-error"></div>
                            </div>

                            <div class="col-md-4">
                                <label for="price" class="form-label font-weight-bold">Selling Price (₹) <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" min="1" name="price" id="price" class="form-control" placeholder="e.g. 45000" required>
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
                            <label for="image" class="form-label font-weight-bold">Laptop Photo <span class="text-danger">*</span> (Max 2MB)</label>
                            <input type="file" name="image" id="image" class="form-control image-preview-input" data-preview-target="img-preview" accept="image/jpeg,image/png,image/webp" required>
                            <div class="invalid-feedback" id="image-error"></div>
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



<script>
(function() {
    'use strict';

    const form            = document.getElementById('sell-form');
    const brandSelect     = document.getElementById('brand_id');
    const modelSelectWrap = document.getElementById('model-select-wrapper');
    const modelTextWrap   = document.getElementById('model-text-wrapper');
    const modelSelect     = document.getElementById('model');
    const modelCustom     = document.getElementById('model_custom');
    const modelCustomHint = document.getElementById('model-custom-hint');
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

    // Concise processor specifications by brand category (kept short so dropdown opens downwards)
    const APPLE_PROCESSORS = [
        'Apple M4',
        'Apple M3 Pro / Max',
        'Apple M3',
        'Apple M2 Pro / Max',
        'Apple M2',
        'Apple M1'
    ];

    const NON_APPLE_PROCESSORS = [
        'Intel Core Ultra 7 / 9',
        'Intel Core i9',
        'Intel Core i7',
        'Intel Core i5',
        'Intel Core i3',
        'AMD Ryzen 9',
        'AMD Ryzen 7',
        'AMD Ryzen 5'
    ];

    function updateProcessorsForBrand() {
        const selectedOpt = brandSelect.options[brandSelect.selectedIndex];
        const brandName = selectedOpt ? selectedOpt.text.toLowerCase().trim() : '';
        const isApple = brandName.includes('apple');
        const previousVal = processorSelect.value;

        processorSelect.innerHTML = '<option value="">-- Optional --</option>';

        if (isApple) {
            APPLE_PROCESSORS.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                if (p === previousVal) opt.selected = true;
                processorSelect.appendChild(opt);
            });
        } else if (brandName && brandSelect.selectedIndex > 0) {
            NON_APPLE_PROCESSORS.forEach(p => {
                const opt = document.createElement('option');
                opt.value = p;
                opt.textContent = p;
                if (p === previousVal) opt.selected = true;
                processorSelect.appendChild(opt);
            });
        }
    }

    function getSelectedCondition() {
        if (conditionNew && conditionNew.checked) return 'new';
        if (conditionOld) {
            return (conditionOld.value || (conditionOld.checked ? 'old' : '')).toLowerCase() || 'old';
        }
        return 'old';
    }

    function isTextInputActive() {
        return modelTextWrap && !modelTextWrap.classList.contains('d-none');
    }

    function getSelectedModelName() {
        if (isTextInputActive()) {
            return modelCustom ? modelCustom.value.trim() : '';
        }
        return modelSelect ? modelSelect.value.trim() : '';
    }

    function switchToModelTextInput() {
        if (modelSelectWrap) modelSelectWrap.classList.add('d-none');
        if (modelTextWrap) modelTextWrap.classList.remove('d-none');
        
        if (modelSelect) {
            modelSelect.removeAttribute('required');
            modelSelect.disabled = true;
        }
        if (modelCustom) {
            modelCustom.setAttribute('required', 'required');
            modelCustom.disabled = false;
            modelCustom.focus();
        }

        if (modelCustomHint) {
            modelCustomHint.innerHTML = '<i class="bi bi-stars text-primary me-1"></i><strong>New brand:</strong> Type the exact laptop model name.';
        }

        autoUpdateDescription();
    }

    function switchToModelSelect() {
        if (modelTextWrap) modelTextWrap.classList.add('d-none');
        if (modelSelectWrap) modelSelectWrap.classList.remove('d-none');

        if (modelCustom) {
            modelCustom.removeAttribute('required');
            modelCustom.disabled = true;
            modelCustom.value = '';
        }
        if (modelSelect) {
            modelSelect.setAttribute('required', 'required');
            modelSelect.disabled = false;
        }

        autoUpdateDescription();
    }

    function buildAutoDescription() {
        const brandName = brandSelect.selectedIndex > 0 ? brandSelect.options[brandSelect.selectedIndex].text.replace('-- Select Brand --', '').trim() : '';
        const modelName = getSelectedModelName();
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
        let productTitle = '';
        if (brandName && modelName) {
            productTitle = (!modelName.toLowerCase().startsWith(brandName.toLowerCase()))
                ? (brandName + ' ' + modelName)
                : modelName;
        } else if (brandName) {
            productTitle = brandName + ' Laptop';
        } else if (modelName) {
            productTitle = modelName;
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

    // 1. Brand → Model & Processor filter dependent dropdown
    brandSelect.addEventListener('change', function() {
        const brandId = this.value;

        // Dynamically update processors according to selected brand
        updateProcessorsForBrand();

        // Reset state
        if (modelCustom) modelCustom.value = '';
        if (modelSelect) {
            modelSelect.innerHTML = '<option value="">Select a brand first</option>';
            modelSelect.disabled = true;
        }
        if (modelSelectWrap) modelSelectWrap.classList.remove('d-none');
        if (modelTextWrap) modelTextWrap.classList.add('d-none');

        autoUpdateDescription();
        if (!brandId) return;

        if (modelSelect) modelSelect.innerHTML = '<option value="">Loading models…</option>';

        fetch(BASE + '/get_models.php?brand_id=' + encodeURIComponent(brandId))
        .then(resp => resp.json())
        .then(data => {
            if (!data.success || !data.models || data.models.length === 0) {
                switchToModelTextInput();
            } else {
                switchToModelSelect();
                modelSelect.innerHTML = '<option value="">-- Select Model --</option>';
                data.models.forEach(m => {
                    const opt = document.createElement('option');
                    opt.value = m.model_name;
                    opt.textContent = m.model_name + (m.year ? ' (' + m.year + ')' : '');
                    modelSelect.appendChild(opt);
                });
                modelSelect.disabled = false;
            }
            autoUpdateDescription();
        })
        .catch(() => {
            switchToModelTextInput();
        });
    });

    if (modelSelect) {
        modelSelect.addEventListener('change', autoUpdateDescription);
    }

    if (modelCustom) {
        modelCustom.addEventListener('input', autoUpdateDescription);
    }

    function syncConditionUI() {
        if (conditionNew && conditionOld && conditionNew.type === 'radio') {
            const isNew = conditionNew.checked;
            const newOption = conditionNew.closest('.condition-radio-option');
            const oldOption = conditionOld.closest('.condition-radio-option');
            if (newOption && oldOption) {
                newOption.classList.toggle('active', isNew);
                oldOption.classList.toggle('active', !isNew);
            }
        }
    }

    if (conditionNew) {
        conditionNew.addEventListener('change', function() {
            syncConditionUI();
            autoUpdateDescription();
        });
    }
    if (conditionOld && conditionOld.type === 'radio') {
        conditionOld.addEventListener('change', function() {
            syncConditionUI();
            autoUpdateDescription();
        });
    }
    priceInput.addEventListener('input', autoUpdateDescription);
    processorSelect.addEventListener('change', autoUpdateDescription);
    ramSelect.addEventListener('change', autoUpdateDescription);
    storageSelect.addEventListener('change', autoUpdateDescription);

    // Initial processor list population on load
    updateProcessorsForBrand();

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
        const model   = getSelectedModelName();
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
    function showAlert(content, type) {
        formAlert.className = 'alert alert-' + type + ' border-0 rounded-4 shadow-sm p-4 mb-4';
        if (typeof content === 'string' && content.includes('<')) {
            formAlert.innerHTML = content;
        } else {
            formAlert.textContent = content;
        }
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
            if (errDiv) {
                errDiv.textContent = msg;
                errDiv.style.display = 'block';
            }
            return;
        }
        if (fieldId === 'model') {
            const targetEl = isTextInputActive() ? modelCustom : modelSelect;
            if (targetEl) targetEl.classList.add('is-invalid');
            const errDiv = document.getElementById('model-error');
            if (errDiv) {
                errDiv.textContent = msg;
                errDiv.style.display = 'block';
            }
            if (targetEl) targetEl.focus();
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
            errDiv.style.display = 'block';
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
            el.style.display = '';
        });
    }

    // 4. Form Submit Handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();
        clearErrors();

        const finalModel = getSelectedModelName();
        if (!finalModel) {
            setFieldError('model', 'Model name is required.');
            return;
        }

        const imageInput = document.getElementById('image');
        if (!imageInput || !imageInput.files || imageInput.files.length === 0) {
            setFieldError('image', 'Please upload a photo of the laptop.');
            return;
        }

        submitBtn.disabled = true;
        const originalHtml = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Publishing Your Listing…';

        runDuplicateCheck().then(function(ok) {
            if (!ok) {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHtml;
                return;
            }

            const fd = new FormData(form);
            fd.set('model', finalModel);
            if (modelCustom && modelCustom.value.trim()) {
                fd.set('model_custom', modelCustom.value.trim());
            }

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
                    const successMessage = data.message || 'Your listing has been submitted and is awaiting admin approval!';

                    // Launch Firecrackers Celebration Animation
                    if (typeof window.launchFirecrackers === 'function') {
                        window.launchFirecrackers();
                    } else if (typeof confetti === 'function') {
                        confetti({ particleCount: 150, spread: 80, origin: { y: 0.6 }, zIndex: 10000000 });
                    }

                    // 1. Trigger colorful popup toast
                    if (typeof window.showToast === 'function') {
                        window.showToast('🎉 ' + successMessage, 'success', 3500);
                    }

                    // 2. Render smooth login-style animated success modal
                    let successOverlay = document.getElementById('sell-success-modal');
                    if (!successOverlay) {
                        successOverlay = document.createElement('div');
                        successOverlay.id = 'sell-success-modal';
                        successOverlay.className = 'auth-success-backdrop';
                        document.body.appendChild(successOverlay);
                    }

                    successOverlay.innerHTML = `
                        <div class="auth-success-card">
                            <div class="auth-success-icon-wrap">
                                <i class="bi bi-check-circle-fill"></i>
                            </div>
                            <h3 class="auth-success-title">🎉 Listing Published Successfully!</h3>
                            <p class="auth-success-text">
                                Your laptop ad has been created and submitted for admin review.<br>
                                <span class="d-inline-block mt-2 text-info-emphasis small fw-medium">
                                    <i class="bi bi-clock-history me-1"></i> Redirecting to your listings in 3 seconds...
                                </span>
                            </p>
                            <div class="auth-success-progress-track">
                                <div class="auth-success-progress-bar" id="sell-success-progress-bar" style="transition: width 3.0s linear !important;"></div>
                            </div>
                        </div>
                    `;

                    requestAnimationFrame(() => {
                        successOverlay.classList.add('active');
                        setTimeout(() => {
                            const progressBar = document.getElementById('sell-success-progress-bar');
                            if (progressBar) {
                                progressBar.style.width = '100%';
                            }
                        }, 50);
                    });

                    // 3. Reset form fields
                    form.reset();
                    isUserCustomEdited = false;
                    syncConditionUI();
                    if (modelSelectWrap) modelSelectWrap.classList.remove('d-none');
                    if (modelTextWrap) modelTextWrap.classList.add('d-none');
                    if (modelSelect) {
                        modelSelect.innerHTML = '<option value="">Select a brand first</option>';
                        modelSelect.disabled = true;
                    }
                    const imgPreview = document.getElementById('img-preview');
                    if (imgPreview) {
                        imgPreview.src = '#';
                        imgPreview.classList.add('d-none');
                    }

                    // 4. Smooth exit dissolve at 2.75s, then seamless redirect at 3.0s
                    setTimeout(() => {
                        successOverlay.classList.add('exiting');
                        document.body.classList.add('lapify-page-leaving');
                        setTimeout(() => {
                            window.location.href = BASE + '/my-listings.php';
                        }, 250);
                    }, 2750);
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