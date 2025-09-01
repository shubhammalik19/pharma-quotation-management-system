            </div>
        </main>
    </div>
</div>

<!-- Professional Footer -->
<footer class="bg-dark text-white py-4 mt-5">
    <div class="container-fluid">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="d-flex align-items-center gap-3">
                    <div class="footer-logo">
                        <i class="bi bi-clipboard-data-fill text-warning fs-4"></i>
                    </div>
                    <div>
                        <h6 class="mb-1 text-white"><?php echo SITE_NAME; ?></h6>
                        <p class="mb-0 text-muted small">
                            <i class="bi bi-c-circle"></i> <?php echo date('Y'); ?> All rights reserved. 
                            <span class="text-warning">v<?php echo VERSION; ?></span>
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-end gap-3">
                    <div class="footer-links d-flex gap-3">
                        <a href="#" class="text-light text-decoration-none small">
                            <i class="bi bi-question-circle"></i> Help
                        </a>
                        <a href="#" class="text-light text-decoration-none small">
                            <i class="bi bi-shield-check"></i> Privacy
                        </a>
                        <a href="#" class="text-light text-decoration-none small">
                            <i class="bi bi-file-text"></i> Terms
                        </a>
                    </div>
                    <div class="developed-by">
                        <p class="mb-0 small">
                            <i class="bi bi-code-slash text-info"></i> 
                            Crafted with <i class="bi bi-heart-fill text-danger"></i> by 
                            <a href="https://bharatsoftware.com/" target="_blank" 
                               class="text-warning text-decoration-none fw-bold">
                                Bharat Software
                            </a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- System Status Bar -->
        <div class="row mt-3 pt-3 border-top border-secondary">
            <div class="col-12">
                <div class="d-flex flex-wrap justify-content-between align-items-center">
                    <div class="system-info d-flex gap-4 flex-wrap">
                        <small class="text-muted">
                            <i class="bi bi-server text-success"></i>
                            System: <span class="text-success">Online</span>
                        </small>
                        <small class="text-muted">
                            <i class="bi bi-clock text-info"></i>
                            Server Time: <span id="serverTime"><?php echo date('Y-m-d H:i:s'); ?></span>
                        </small>
                        <?php if (isAdmin()): ?>
                        <small class="text-muted">
                            <i class="bi bi-people text-warning"></i>
                            Active Users: <span class="text-warning" id="activeUsers">1</span>
                        </small>
                        <?php endif; ?>
                    </div>
                    <div class="quick-actions d-flex gap-2">
                        <?php if (hasPermission('dashboard', 'view')): ?>
                        <a href="/dashboard.php" class="btn btn-outline-light btn-sm">
                            <i class="bi bi-speedometer2"></i>
                        </a>
                        <?php endif; ?>
                        <?php if (hasModuleAccess('reports')): ?>
                        <a href="/reports.php" class="btn btn-outline-info btn-sm">
                            <i class="bi bi-graph-up"></i>
                        </a>
                        <?php endif; ?>
                        <a href="/auth/change_password.php" class="btn btn-outline-warning btn-sm">
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
    // Clear URL parameters after form submission to prevent resubmission
    if (window.location.search.includes('success') || window.location.search.includes('action')) {
        const cleanUrl = window.location.pathname;
        window.history.replaceState({}, document.title, cleanUrl);
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
        const serverTime = now.toISOString().slice(0, 19).replace('T', ' ');
        $('#serverTime').text(serverTime);
    }
    
    setInterval(updateServerTime, 60000); // Update every minute
    
    // Add smooth scroll behavior to anchor links
    $('a[href^="#"]').on('click', function(e) {
        e.preventDefault();
        const target = $(this.getAttribute('href'));
        if (target.length) {
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 600);
        }
    });
    
    // Auto-hide alerts with fade animation
    $('.alert').each(function() {
        const alert = $(this);
        setTimeout(() => {
            alert.fadeOut('slow', function() {
                $(this).remove();
            });
        }, 5000);
    });
    
    // Add loading state to buttons on click
    $('.btn').on('click', function() {
        const btn = $(this);
        if (!btn.hasClass('btn-outline') && !btn.attr('href')) {
            btn.addClass('loading');
            setTimeout(() => btn.removeClass('loading'), 2000);
        }
    });
    
    // Initialize tooltips if Bootstrap tooltips are available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Sidebar mobile toggle enhancement
    $('.navbar-toggler').on('click', function() {
        $('.sidebar').addClass('slide-in-left');
    });
    
    // Add swipe gesture support for mobile sidebar
    if ('ontouchstart' in window) {
        let startX = 0;
        let currentX = 0;
        let sidebarOpen = false;
        
        // Touch events for sidebar
        document.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            sidebarOpen = $('.sidebar').hasClass('show');
        });
        
        document.addEventListener('touchmove', function(e) {
            if (!sidebarOpen && startX < 20) { // Swipe from left edge
                currentX = e.touches[0].clientX;
                if (currentX - startX > 50) {
                    $('.sidebar').addClass('show');
                    e.preventDefault();
                }
            } else if (sidebarOpen && startX > 0) { // Swipe to close
                currentX = e.touches[0].clientX;
                if (startX - currentX > 50) {
                    $('.sidebar').removeClass('show');
                    e.preventDefault();
                }
            }
        });
        
        // Close sidebar when tapping outside on mobile
        $(document).on('touchstart', function(e) {
            if ($('.sidebar').hasClass('show') && 
                !$(e.target).closest('.sidebar, .navbar-toggler').length) {
                $('.sidebar').removeClass('show');
            }
        });
    }
    
    // Enhanced mobile navigation
    const isMobile = window.innerWidth <= 991.98;
    if (isMobile) {
        // Auto-close mobile sidebar after navigation
        $('.sidebar .nav-link').on('click', function() {
            setTimeout(() => {
                $('.sidebar').removeClass('show');
            }, 300);
        });
        
        // Add ripple effect to touch interactions
        $('.btn, .nav-link').on('touchstart', function(e) {
            const button = $(this);
            const ripple = $('<span class="ripple"></span>');
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            const x = e.originalEvent.touches[0].clientX - rect.left - size / 2;
            const y = e.originalEvent.touches[0].clientY - rect.top - size / 2;
            
            ripple.css({
                width: size,
                height: size,
                left: x,
                top: y
            }).addClass('ripple-animation');
            
            button.append(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    }
    
    // Add fade-in animation to main content
    $('main .container-fluid').addClass('fade-in');
    
    // Console welcome message for developers
    console.log('%c🚀 ' + '<?php echo SITE_NAME; ?>' + ' v<?php echo VERSION; ?>', 
                'color: #2563eb; font-size: 16px; font-weight: bold;');
    console.log('%cDeveloped by Bharat Software', 
                'color: #64748b; font-size: 12px;');
    
    // Performance monitoring (basic)
    if (performance && performance.timing) {
        const loadTime = performance.timing.loadEventEnd - performance.timing.navigationStart;
        if (loadTime > 0) {
            console.log('%cPage Load Time: ' + loadTime + 'ms', 
                        'color: #059669; font-size: 12px;');
        }
    }
});

// Global utility functions
window.showToast = function(message, type = 'info') {
    const toastHtml = `
        <div class="toast align-items-center text-white bg-${type} border-0" role="alert" aria-live="assertive" aria-atomic="true">
            <div class="d-flex">
                <div class="toast-body">${message}</div>
                <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
            </div>
        </div>
    `;
    
    let toastContainer = document.querySelector('.toast-container');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.className = 'toast-container position-fixed bottom-0 end-0 p-3';
        document.body.appendChild(toastContainer);
    }
    
    toastContainer.insertAdjacentHTML('beforeend', toastHtml);
    const toastElement = toastContainer.lastElementChild;
    const toast = new bootstrap.Toast(toastElement);
    toast.show();
    
    toastElement.addEventListener('hidden.bs.toast', () => {
        toastElement.remove();
    });
};

// Add keyboard shortcuts
document.addEventListener('keydown', function(e) {
    // Ctrl/Cmd + K for search (if search exists)
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('input[type="search"], .search-box input[type="text"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
    
    // Escape to close modals/dropdowns
    if (e.key === 'Escape') {
        const openModal = document.querySelector('.modal.show');
        if (openModal) {
            const modal = bootstrap.Modal.getInstance(openModal);
            if (modal) modal.hide();
        }
    }
});
</script>
<?php
// detect if this request is POST or has ?delete= in URL
$needsRefresh = ($_SERVER['REQUEST_METHOD'] === 'POST' || isset($_GET['delete']));

// only output the JS if needed
if ($needsRefresh):
    // build a clean base URL (remove query string for delete actions)
    $url = strtok($_SERVER['REQUEST_URI'], '?');
?>
<script>
$(function() {
    setTimeout(function() {
        window.location.href = "<?php echo $url; ?>";
    }, 2000); // 2 seconds delay
});
</script>
<?php endif; ?>

</body>
</html>
