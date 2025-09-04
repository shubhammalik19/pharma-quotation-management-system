            </div>
        </main>
    </div>
</div>

<!-- Compact Professional Footer -->
<footer class="bg-dark text-white py-2 mt-4">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-4">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-clipboard-data-fill text-warning"></i>
                    <div>
                        <h6 class="mb-0 text-white small"><?php echo SITE_NAME; ?></h6>
                        <small class="text-warning">v<?php echo VERSION; ?></small>
                    </div>
                </div>
            </div>
            <div class="col-md-4 text-center">
                <div class="d-flex justify-content-center gap-3">
                    <small class="text-light">
                        <i class="bi bi-telephone text-success"></i>
                        <a href="tel:9821133250" class="text-light text-decoration-none">+91-9821133250</a>
                    </small>
                    <small class="text-light">
                        <i class="bi bi-envelope text-info"></i>
                        <a href="mailto:contact@bharatsoftware.com" class="text-light text-decoration-none">contact@bharatsoftware.com</a>
                    </small>
                </div>
            </div>
            <div class="col-md-4 text-md-end">
                <div class="d-flex flex-column align-items-md-end">
                    <small class="text-light mb-1">
                        <i class="bi bi-c-circle"></i> <?php echo date('Y'); ?> 
                        <a href="https://bharatsoftware.com/" target="_blank" 
                           class="text-warning text-decoration-none fw-bold">Bharat Software</a>
                    </small>
                    <small class="text-light">
                        <i class="bi bi-geo-alt text-warning"></i>
                        Delhi, India | 16+ Years Experience
                    </small>
                </div>
            </div>
        </div>
        
        <!-- Minimal System Status -->
        <div class="row mt-2 pt-2 border-top border-secondary">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="system-info d-flex gap-3 flex-wrap">
                        <small class="text-light">
                            <i class="bi bi-server text-success"></i>
                            <span class="text-success">Online</span>
                        </small>
                        <small class="text-light">
                            <i class="bi bi-clock text-info"></i>
                            <span id="serverTime"><?php echo date('H:i'); ?></span>
                        </small>
                        <?php if (isAdmin()): ?>
                        <small class="text-light">
                            <i class="bi bi-people text-warning"></i>
                            <span class="text-warning" id="activeUsers">1</span> users
                        </small>
                        <?php endif; ?>
                    </div>
                    <div class="footer-links d-flex gap-2">
                        <a href="#" class="text-light text-decoration-none" style="font-size: 0.75rem;">Help</a>
                        <a href="#" class="text-light text-decoration-none" style="font-size: 0.75rem;">Privacy</a>
                        <a href="/auth/change_password.php" class="text-warning text-decoration-none" style="font-size: 0.75rem;">
                            <i class="bi bi-key"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</footer>

<script>
$(document).ready(function() {
    // Clear URL parameters after form submission
    if (window.location.search.includes('success') || window.location.search.includes('action')) {
        window.history.replaceState({}, document.title, window.location.pathname);
    }
    
    // Prevent double form submission
    $('form').on('submit', function() {
        const submitBtn = $(this).find('button[type="submit"]:visible');
        submitBtn.prop('disabled', true);
        setTimeout(() => submitBtn.prop('disabled', false), 3000);
    });
    
    // Update server time every minute
    function updateServerTime() {
        const now = new Date();
        $('#serverTime').text(now.toTimeString().slice(0, 5));
    }
    setInterval(updateServerTime, 60000);
    
    // Auto-hide alerts
    $('.alert').each(function() {
        const alert = $(this);
        setTimeout(() => alert.fadeOut('slow', () => $(this).remove()), 5000);
    });
    
    // Initialize tooltips
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(el => new bootstrap.Tooltip(el));
    }
    
    // Mobile sidebar toggle
    $('.navbar-toggler').on('click', () => $('.sidebar').addClass('slide-in-left'));
    
    // Close sidebar on mobile tap outside
    $(document).on('touchstart', function(e) {
        if ($('.sidebar').hasClass('show') && !$(e.target).closest('.sidebar, .navbar-toggler').length) {
            $('.sidebar').removeClass('show');
        }
    });
    
    // Auto-close mobile sidebar after navigation
    if (window.innerWidth <= 991.98) {
        $('.sidebar .nav-link').on('click', () => {
            setTimeout(() => $('.sidebar').removeClass('show'), 300);
        });
    }
    
    // Console branding
    console.log('%c🚀 <?php echo SITE_NAME; ?> v<?php echo VERSION; ?>', 'color: #2563eb; font-weight: bold;');
    console.log('%cPowered by Bharat Software', 'color: #64748b;');
});

// Global toast function
window.showToast = function(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast"></button>
            </div>
        </div>`;
    
    let container = document.querySelector('.toast-container');
    if (!container) {
        container = document.createElement('div');
        container.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(container);
    }
    
    container.insertAdjacentHTML('beforeend', toastHtml);
    const toast = new bootstrap.Toast(container.lastElementChild);
    toast.show();
};

// Keyboard shortcuts
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"], .search-box input');
        if (searchInput) searchInput.focus();
    }
    
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            if (modal) modal.hide();
        }
    }
});
</script>

</body>
</html>
