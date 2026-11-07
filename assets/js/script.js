/* =========================================================
   BUA.COM - script.js
========================================================= */
document.addEventListener('DOMContentLoaded', function () {

    // Mobile sidebar toggle (dashboard layouts)
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.querySelector('.dashboard-sidebar');
    if (sidebarToggle && sidebar) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
        });
    }

    // Bootstrap client-side validation styling
    const forms = document.querySelectorAll('.needs-validation');
    forms.forEach(function (form) {
        form.addEventListener('submit', function (event) {
            if (!form.checkValidity()) {
                event.preventDefault();
                event.stopPropagation();
            }
            form.classList.add('was-validated');
        }, false);
    });

    // Password confirmation live check (register page)
    const pass = document.getElementById('password');
    const confirmPass = document.getElementById('confirm_password');
    if (pass && confirmPass) {
        function checkMatch() {
            if (confirmPass.value && confirmPass.value !== pass.value) {
                confirmPass.setCustomValidity('Passwords do not match');
            } else {
                confirmPass.setCustomValidity('');
            }
        }
        pass.addEventListener('input', checkMatch);
        confirmPass.addEventListener('input', checkMatch);
    }

    // Star rating widget (reviews.php)
    document.querySelectorAll('.star-input').forEach(function (widget) {
        const stars = widget.querySelectorAll('i');
        const hiddenInput = document.getElementById(widget.dataset.target);
        stars.forEach(function (star, idx) {
            star.addEventListener('click', function () {
                hiddenInput.value = idx + 1;
                stars.forEach((s, i) => {
                    s.classList.toggle('fa-solid', i <= idx);
                    s.classList.toggle('fa-regular', i > idx);
                });
            });
        });
    });

    // Image upload preview (agent add/edit maid)
    const photoInput = document.getElementById('photo');
    const photoPreview = document.getElementById('photoPreview');
    if (photoInput && photoPreview) {
        photoInput.addEventListener('change', function () {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => { photoPreview.src = e.target.result; };
                reader.readAsDataURL(file);
            }
        });
    }

    // Auto-dismiss alerts after 5s
    document.querySelectorAll('.bua-alert').forEach(function (alertEl) {
        setTimeout(function () {
            const bsAlert = bootstrap.Alert.getOrCreateInstance(alertEl);
            bsAlert.close();
        }, 6000);
    });
});
