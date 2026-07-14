/* ===================================================
   ShopAdmin — Minimal Vanilla JS
   =================================================== */

/**
 * Toggle sidebar visibility on mobile.
 */
function toggleSidebar() {
    var sidebar = document.getElementById('sidebar');
    if (sidebar) {
        sidebar.classList.toggle('open');
    }
}

/**
 * Close sidebar when clicking outside on mobile.
 */
document.addEventListener('click', function (e) {
    var sidebar = document.getElementById('sidebar');
    var toggle  = e.target.closest('[onclick*="toggleSidebar"]');

    if (sidebar && window.innerWidth < 992) {
        if (!sidebar.contains(e.target) && !toggle) {
            sidebar.classList.remove('open');
        }
    }
});

/**
 * Auto-dismiss flash alerts after 5 seconds.
 */
document.addEventListener('DOMContentLoaded', function () {
    var alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(function (alert) {
        setTimeout(function () {
            var bsAlert = bootstrap.Alert.getOrCreateInstance(alert);
            bsAlert.close();
        }, 5000);
    });
});
