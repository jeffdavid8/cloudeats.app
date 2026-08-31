/* MediaBrain Default Theme - JavaScript Enhancements */

document.addEventListener('DOMContentLoaded', function() {
    
    // Initialize Material Design components
    initializeMaterialComponents();
    
    // Add theme-specific enhancements
    addThemeEnhancements();
    
    // Setup responsive handlers
    setupResponsiveHandlers();
    
    console.log('Default theme initialized successfully');
});

/**
 * Initialize Materialize CSS components
 */
function initializeMaterialComponents() {
    // Initialize tooltips
    var tooltips = document.querySelectorAll('.tooltipped');
    if (tooltips.length > 0) {
        M.Tooltip.init(tooltips, {
            position: 'top',
            delay: 50
        });
    }
    
    // Initialize modals
    var modals = document.querySelectorAll('.modal');
    if (modals.length > 0) {
        M.Modal.init(modals, {
            dismissible: true,
            opacity: 0.5,
            inDuration: 300,
            outDuration: 200
        });
    }
    
    // Initialize dropdowns
    var dropdowns = document.querySelectorAll('.dropdown-trigger');
    if (dropdowns.length > 0) {
        M.Dropdown.init(dropdowns, {
            alignment: 'left',
            constrainWidth: false,
            coverTrigger: false
        });
    }
    
    // Initialize select elements
    var selects = document.querySelectorAll('select');
    if (selects.length > 0) {
        M.FormSelect.init(selects);
    }
    
    // Initialize tabs
    var tabs = document.querySelectorAll('.tabs');
    if (tabs.length > 0) {
        M.Tabs.init(tabs, {
            duration: 300,
            responsiveThreshold: 992
        });
    }
}

/**
 * Add theme-specific enhancements
 */
function addThemeEnhancements() {
    // Enhanced hover effects for dashboard cards
    addDashboardCardEffects();
    
    // Smooth scrolling for navigation
    addSmoothScrolling();
    
    // Loading states for buttons
    addButtonLoadingStates();
    
    // Auto-refresh functionality
    setupAutoRefresh();
}

/**
 * Add hover effects to dashboard statistics cards
 */
function addDashboardCardEffects() {
    const statCards = document.querySelectorAll('.dashboard-stat-card');
    
    statCards.forEach(card => {
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-3px)';
            this.style.boxShadow = '0 8px 25px rgba(0, 0, 0, 0.15)';
        });
        
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
            this.style.boxShadow = '0 2px 10px rgba(0, 0, 0, 0.08)';
        });
        
        // Add click animation
        card.addEventListener('click', function() {
            this.style.transform = 'scale(0.98)';
            setTimeout(() => {
                this.style.transform = 'translateY(-3px)';
            }, 100);
        });
    });
}

/**
 * Add smooth scrolling for anchor links
 */
function addSmoothScrolling() {
    const links = document.querySelectorAll('a[href^="#"]');
    
    links.forEach(link => {
        link.addEventListener('click', function(e) {
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                e.preventDefault();
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Add loading states to form buttons
 */
function addButtonLoadingStates() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function() {
            const submitBtn = this.querySelector('button[type="submit"], input[type="submit"]');
            if (submitBtn && !submitBtn.classList.contains('loading')) {
                const originalText = submitBtn.textContent || submitBtn.value;
                submitBtn.classList.add('loading');
                submitBtn.disabled = true;
                
                if (submitBtn.tagName === 'BUTTON') {
                    submitBtn.innerHTML = '<i class="material-icons left">hourglass_empty</i>Loading...';
                } else {
                    submitBtn.value = 'Loading...';
                }
                
                // Reset after 10 seconds (timeout)
                setTimeout(() => {
                    submitBtn.classList.remove('loading');
                    submitBtn.disabled = false;
                    if (submitBtn.tagName === 'BUTTON') {
                        submitBtn.textContent = originalText;
                    } else {
                        submitBtn.value = originalText;
                    }
                }, 10000);
            }
        });
    });
}

/**
 * Setup auto-refresh for dashboard statistics
 */
function setupAutoRefresh() {
    // Only run on admin dashboard
    if (window.location.search.includes('app=admin') && 
        (window.location.search.includes('p=dashboard') || !window.location.search.includes('p='))) {
        
        setInterval(function() {
            refreshDashboardStats();
        }, 300000); // 5 minutes
    }
}

/**
 * Refresh dashboard statistics via AJAX
 */
function refreshDashboardStats() {
    const statCards = document.querySelectorAll('.dashboard-stat-card h3');
    
    if (statCards.length === 0) return;
    
    // Add subtle loading indicator
    statCards.forEach(stat => {
        stat.style.opacity = '0.7';
    });
    
    // Make AJAX call to get updated stats
    fetch('?app=admin&api=dashboard-stats', {
        method: 'GET',
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.stats) {
            updateDashboardStats(data.stats);
        }
    })
    .catch(error => {
        console.log('Stats refresh failed:', error);
    })
    .finally(() => {
        // Remove loading indicator
        statCards.forEach(stat => {
            stat.style.opacity = '1';
        });
    });
}

/**
 * Update dashboard statistics with new data
 */
function updateDashboardStats(stats) {
    const statElements = {
        'total_users': document.querySelector('.dashboard-stat-card:nth-child(1) h3'),
        'recent_logins': document.querySelector('.dashboard-stat-card:nth-child(2) h3'),
        'storage_used': document.querySelector('.dashboard-stat-card:nth-child(3) h3'),
        'system_uptime': document.querySelector('.dashboard-stat-card:nth-child(4) h3')
    };
    
    Object.keys(statElements).forEach(key => {
        const element = statElements[key];
        if (element && stats[key] !== undefined) {
            animateNumberChange(element, stats[key]);
        }
    });
}

/**
 * Animate number changes in statistics
 */
function animateNumberChange(element, newValue) {
    const currentValue = parseInt(element.textContent) || 0;
    const difference = newValue - currentValue;
    
    if (difference === 0) return;
    
    const duration = 1000; // 1 second
    const steps = 30;
    const stepValue = difference / steps;
    const stepDuration = duration / steps;
    
    let currentStep = 0;
    
    const timer = setInterval(() => {
        currentStep++;
        const displayValue = Math.round(currentValue + (stepValue * currentStep));
        element.textContent = displayValue;
        
        if (currentStep >= steps) {
            clearInterval(timer);
            element.textContent = newValue;
        }
    }, stepDuration);
}

/**
 * Setup responsive handlers
 */
function setupResponsiveHandlers() {
    let resizeTimer;
    
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            // Reinitialize components that need responsive updates
            const sidenavs = document.querySelectorAll('.sidenav');
            if (sidenavs.length > 0) {
                M.Sidenav.init(sidenavs);
            }
        }, 250);
    });
}

/**
 * Theme utility functions
 */
window.DefaultTheme = {
    // Show notification with theme styling
    showNotification: function(message, type = 'info', duration = 4000) {
        const colors = {
            'success': 'green',
            'error': 'red',
            'warning': 'orange',
            'info': 'blue'
        };
        
        M.toast({
            html: `<i class="material-icons left">${this.getIconForType(type)}</i>${message}`,
            classes: colors[type] || 'blue',
            displayLength: duration
        });
    },
    
    // Get icon for notification type
    getIconForType: function(type) {
        const icons = {
            'success': 'check_circle',
            'error': 'error',
            'warning': 'warning',
            'info': 'info'
        };
        return icons[type] || 'info';
    },
    
    // Add loading overlay
    showLoading: function() {
        const loading = document.createElement('div');
        loading.id = 'theme-loading';
        loading.innerHTML = `
            <div style="position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
                        background: rgba(255,255,255,0.9); z-index: 9999; display: flex; 
                        align-items: center; justify-content: center;">
                <div class="preloader-wrapper big active">
                    <div class="spinner-layer spinner-blue">
                        <div class="circle-clipper left">
                            <div class="circle"></div>
                        </div>
                        <div class="gap-patch">
                            <div class="circle"></div>
                        </div>
                        <div class="circle-clipper right">
                            <div class="circle"></div>
                        </div>
                    </div>
                </div>
            </div>
        `;
        document.body.appendChild(loading);
    },
    
    // Remove loading overlay
    hideLoading: function() {
        const loading = document.getElementById('theme-loading');
        if (loading) {
            loading.remove();
        }
    }
};