/* 
   WasteWatch — client-side behaviour
   - Mobile nav toggle
   - Form validation (registration, report, pickup request)
   - Live username availability check (Fetch API / AJAX)
*/

document.addEventListener('DOMContentLoaded', function () {

    /*  Mobile nav toggle  */
    var toggle = document.getElementById('navToggle');
    var nav = document.getElementById('siteNav');
    if (toggle && nav) {
        toggle.addEventListener('click', function () {
            nav.classList.toggle('open');
        });
    }

    /*  Generic helper: show/clear a field error  */
    function showError(input, message) {
        clearError(input);
        var msg = document.createElement('div');
        msg.className = 'field-error';
        msg.textContent = message;
        msg.dataset.errorFor = input.id;
        input.insertAdjacentElement('afterend', msg);
        input.setAttribute('aria-invalid', 'true');
    }

    function clearError(input) {
        var existing = input.parentNode.querySelector('.field-error[data-error-for="' + input.id + '"]');
        if (existing) existing.remove();
        input.removeAttribute('aria-invalid');
    }

    /*  Registration form validation  */
    var regForm = document.getElementById('registerForm');
    if (regForm) {
        regForm.addEventListener('submit', function (e) {
            var valid = true;
            var fullName = document.getElementById('full_name');
            var username = document.getElementById('username');
            var email = document.getElementById('email');
            var password = document.getElementById('password');
            var confirm = document.getElementById('confirm_password');
            var zone = document.getElementById('zone_id');

            if (fullName.value.trim().length < 2) {
                showError(fullName, 'Please enter your full name.');
                valid = false;
            } else clearError(fullName);

            if (!/^[a-zA-Z0-9_]{4,20}$/.test(username.value.trim())) {
                showError(username, 'Username must be 4-20 characters (letters, numbers, underscore only).');
                valid = false;
            } else clearError(username);

            if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value.trim())) {
                showError(email, 'Please enter a valid email address.');
                valid = false;
            } else clearError(email);

            var phone = document.getElementById('phone');
            if (phone && phone.value.trim() !== '') {
                if (!/^\+?[0-9]{10,15}$/.test(phone.value.trim())) {
                    showError(phone, 'Please enter a valid phone number (10-15 digits).');
                    valid = false;
                } else clearError(phone);
            }

            if (password.value.length < 8) {
                showError(password, 'Password must be at least 8 characters.');
                valid = false;
            } else clearError(password);

            if (confirm.value !== password.value || confirm.value === '') {
                showError(confirm, 'Passwords do not match.');
                valid = false;
            } else clearError(confirm);

            if (zone && zone.value === '') {
                showError(zone, 'Please select your collection zone.');
                valid = false;
            }

            if (!valid) e.preventDefault();
        });

        /* Live username availability check using the Fetch API */
        var usernameInput = document.getElementById('username');
        var statusEl = document.getElementById('usernameStatus');
        var debounceTimer;
        if (usernameInput && statusEl) {
            usernameInput.addEventListener('input', function () {
                clearTimeout(debounceTimer);
                var value = usernameInput.value.trim();
                if (value.length < 4) {
                    statusEl.textContent = '';
                    return;
                }
                statusEl.textContent = 'Checking availability...';
                debounceTimer = setTimeout(function () {
                    fetch('ajax/check_username.php?username=' + encodeURIComponent(value))
                        .then(function (res) { return res.json(); })
                        .then(function (data) {
                            statusEl.textContent = data.available
                                ? '✓ Username is available'
                                : '✗ Username is already taken';
                            statusEl.style.color = data.available ? '#2E6E62' : '#B3412C';
                        })
                        .catch(function () {
                            statusEl.textContent = '';
                        });
                }, 400);
            });
        }
    }

    /*  Report an issue: validation  */
    var reportForm = document.getElementById('reportForm');
    if (reportForm) {
        reportForm.addEventListener('submit', function (e) {
            var valid = true;
            var type = document.getElementById('report_type');
            var description = document.getElementById('description');

            if (type.value === '') {
                showError(type, 'Please select an issue type.');
                valid = false;
            } else clearError(type);

            if (description.value.trim().length < 15) {
                showError(description, 'Please describe the issue in at least 15 characters.');
                valid = false;
            } else clearError(description);

            if (!valid) {
                e.preventDefault();
                return;
            }

            // Soft duplicate warning, only prompt if server flagged a potential duplicate
            var warning = reportForm.dataset.duplicateWarning;
            var confirmedField = document.getElementById('confirmed_different');
            if (warning && confirmedField && confirmedField.value !== '1') {
                e.preventDefault();
                if (window.confirm(warning)) {
                    confirmedField.value = '1';
                    reportForm.submit();
                }
            }
        });

        /* Live character counter */
        var description = document.getElementById('description');
        var counter = document.getElementById('descriptionCounter');
        if (description && counter) {
            description.addEventListener('input', function () {
                counter.textContent = description.value.length + ' characters';
            });
        }
    }

    /*  Pickup request form validation  */
    var pickupForm = document.getElementById('pickupForm');
    if (pickupForm) {
        pickupForm.addEventListener('submit', function (e) {
            var valid = true;
            var category = document.getElementById('waste_category');
            var date = document.getElementById('preferred_date');

            if (category.value === '') {
                showError(category, 'Please select a waste category.');
                valid = false;
            } else clearError(category);

            var chosen = new Date(date.value);
            var today = new Date();
            today.setHours(0, 0, 0, 0);
            if (!date.value || chosen < today) {
                showError(date, 'Please choose a valid future date.');
                valid = false;
            } else clearError(date);

            if (!valid) {
                e.preventDefault();
                return;
            }

            // Soft duplicate warning for pickup requests
            var warning = pickupForm.dataset.duplicateWarning;
            var confirmedField = document.getElementById('pickup_confirmed');
            if (warning && confirmedField && confirmedField.value !== '1') {
                e.preventDefault();
                if (window.confirm(warning)) {
                    confirmedField.value = '1';
                    pickupForm.submit();
                }
            }
        });
    }

    /*  Admin: confirm before status/destructive actions  */
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(el.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });
});
