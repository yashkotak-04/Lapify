/**
 * Lapify Client-Side Form Validation Script
 */

(function () {
  'use strict';

  window.addEventListener('load', function () {
    // Fetch all forms with 'needs-validation' class
    var forms = document.getElementsByClassName('needs-validation');

    Array.prototype.filter.call(forms, function (form) {
      form.addEventListener('submit', function (event) {
        if (form.checkValidity() === false) {
          event.preventDefault();
          event.stopPropagation();
        }

        // Additional custom validations
        const pass = form.querySelector('input[name="password"]');
        const confirmPass = form.querySelector('input[name="confirm_password"]');
        
        if (pass && confirmPass && pass.value !== confirmPass.value) {
          confirmPass.setCustomValidity("Passwords do not match");
          event.preventDefault();
          event.stopPropagation();
        } else if (confirmPass) {
          confirmPass.setCustomValidity("");
        }

        form.classList.add('was-validated');
      }, false);
    });
  }, false);
})();
