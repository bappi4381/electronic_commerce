// Admin Dashboard JavaScript

document.addEventListener('DOMContentLoaded', function() {
    // Get DOM elements
    const sidebar = document.getElementById('sidebar');
    const sidebarToggle = document.getElementById('sidebarToggle');
    const mainWrapper = document.getElementById('main-wrapper');
    
    // Create overlay for mobile
    const overlay = document.createElement('div');
    overlay.className = 'sidebar-overlay';
    document.body.appendChild(overlay);
    
    // Sidebar toggle functionality
    function toggleSidebar() {
        sidebar.classList.toggle('show');
        overlay.classList.toggle('show');
        
        // Add/remove body scroll lock on mobile
        if (window.innerWidth <= 991.98) {
            document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        }
    }
    
    // Event listeners
    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', toggleSidebar);
    }
    
    // Close sidebar when clicking overlay
    overlay.addEventListener('click', function() {
        if (sidebar.classList.contains('show')) {
            toggleSidebar();
        }
    });
    
    // Close sidebar on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && sidebar.classList.contains('show')) {
            toggleSidebar();
        }
    });
    
    // Handle window resize
    window.addEventListener('resize', function() {
        // Close mobile sidebar on resize to desktop
        if (window.innerWidth > 991.98) {
            sidebar.classList.remove('show');
            overlay.classList.remove('show');
            document.body.style.overflow = '';
        }
    });
    
    // Navigation link active state management
    const navLinks = document.querySelectorAll('.sidebar-nav .nav-link');
    navLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links
            navLinks.forEach(l => l.classList.remove('active'));
            
            // Add active class to clicked link
            this.classList.add('active');
            
            // Close sidebar on mobile after navigation
            if (window.innerWidth <= 991.98) {
                setTimeout(() => {
                    toggleSidebar();
                }, 150);
            }
        });
    });
    
    // Search functionality
    const searchInput = document.querySelector('input[placeholder="Search..."]');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            // Here you would implement actual search functionality
            console.log('Searching for:', searchTerm);
        });
        
        // Search on enter key
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                const searchTerm = this.value.toLowerCase();
                console.log('Search submitted:', searchTerm);
                // Implement search logic here
            }
        });
    }
    
    // Notification dropdown functionality
    const notificationDropdown = document.querySelector('[data-bs-toggle="dropdown"]');
    if (notificationDropdown) {
        notificationDropdown.addEventListener('click', function() {
            // Mark notifications as read (visual feedback)
            const badge = this.querySelector('.badge');
            if (badge) {
                setTimeout(() => {
                    badge.style.opacity = '0.5';
                }, 1000);
            }
        });
    }
    
    // Statistics cards animation on scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.style.animationPlayState = 'running';
            }
        });
    }, observerOptions);
    
    // Observe all stat cards
    const statCards = document.querySelectorAll('.stat-card');
    statCards.forEach(card => {
        card.style.animationPlayState = 'paused';
        observer.observe(card);
    });
    
    // Add hover effects for interactive elements
    const interactiveElements = document.querySelectorAll('.btn, .card, .nav-link');
    interactiveElements.forEach(element => {
        element.addEventListener('mouseenter', function() {
            this.style.transition = 'all 0.3s ease';
        });
    });
    
    // Simulate real-time data updates
    function updateStatistics() {
        const statNumbers = document.querySelectorAll('.stat-number');
        statNumbers.forEach(stat => {
            const currentValue = stat.textContent;
            // Add subtle animation to indicate data refresh
            stat.style.transform = 'scale(1.05)';
            setTimeout(() => {
                stat.style.transform = 'scale(1)';
            }, 200);
        });
    }
    
    // Update statistics every 30 seconds (simulated)
    setInterval(updateStatistics, 30000);
    
    // Quick actions functionality
    const quickActionButtons = document.querySelectorAll('.card .btn');
    quickActionButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Add loading state
            const originalText = this.innerHTML;
            this.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Processing...';
            this.disabled = true;
            
            // Simulate action completion
            setTimeout(() => {
                this.innerHTML = '<i class="bi bi-check-circle me-2"></i>Completed!';
                this.classList.remove('btn-primary', 'btn-outline-primary', 'btn-outline-secondary', 'btn-outline-info');
                this.classList.add('btn-success');
                
                // Reset after 2 seconds
                setTimeout(() => {
                    this.innerHTML = originalText;
                    this.disabled = false;
                    this.classList.remove('btn-success');
                    
                    // Restore original button class
                    if (originalText.includes('Add New User')) {
                        this.classList.add('btn-primary');
                    } else if (originalText.includes('Import Data')) {
                        this.classList.add('btn-outline-primary');
                    } else if (originalText.includes('Export Report')) {
                        this.classList.add('btn-outline-secondary');
                    } else if (originalText.includes('System Settings')) {
                        this.classList.add('btn-outline-info');
                    }
                }, 2000);
            }, 1500);
        });
    });
    
    // Activity feed auto-refresh simulation
    function addNewActivity() {
        const activityContainer = document.querySelector('.activity-item').parentNode;
        const activities = [
            {
                icon: 'bi-person-plus',
                iconBg: 'bg-primary',
                title: 'New user registered',
                description: 'Alex Smith joined the platform',
                time: 'Just now'
            },
            {
                icon: 'bi-cart-check',
                iconBg: 'bg-success',
                title: 'Order completed',
                description: 'Order #5678 has been delivered',
                time: 'Just now'
            },
            {
                icon: 'bi-bell',
                iconBg: 'bg-info',
                title: 'System notification',
                description: 'Database backup completed successfully',
                time: 'Just now'
            }
        ];
        
        const randomActivity = activities[Math.floor(Math.random() * activities.length)];
        
        const newActivityHTML = `
            <div class="activity-item d-flex align-items-center mb-3" style="opacity: 0; transform: translateY(-20px);">
                <div class="activity-icon ${randomActivity.iconBg} bg-gradient rounded-circle me-3">
                    <i class="${randomActivity.icon} text-white"></i>
                </div>
                <div>
                    <div class="fw-semibold">${randomActivity.title}</div>
                    <div class="text-muted small">${randomActivity.description}</div>
                    <div class="text-muted small">${randomActivity.time}</div>
                </div>
            </div>
        `;
        
        activityContainer.insertAdjacentHTML('afterbegin', newActivityHTML);
        
        // Animate in the new activity
        const newActivity = activityContainer.firstElementChild;
        setTimeout(() => {
            newActivity.style.transition = 'all 0.5s ease';
            newActivity.style.opacity = '1';
            newActivity.style.transform = 'translateY(0)';
        }, 100);
        
        // Remove oldest activity if more than 5
        const allActivities = activityContainer.querySelectorAll('.activity-item');
        if (allActivities.length > 5) {
            const oldestActivity = allActivities[allActivities.length - 1];
            oldestActivity.style.transition = 'all 0.5s ease';
            oldestActivity.style.opacity = '0';
            oldestActivity.style.transform = 'translateY(20px)';
            setTimeout(() => {
                oldestActivity.remove();
            }, 500);
        }
    }
    
    // Add new activity every 45 seconds (simulated)
    setInterval(addNewActivity, 45000);
    
    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined') {
        const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }
    
    // Performance optimization: Debounce resize events
    let resizeTimeout;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            // Recalculate layouts if needed
            console.log('Window resized to:', window.innerWidth, 'x', window.innerHeight);
        }, 250);
    });
    
    // Add smooth scrolling for anchor links
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
    
    console.log('Admin Dashboard initialized successfully!');
});

