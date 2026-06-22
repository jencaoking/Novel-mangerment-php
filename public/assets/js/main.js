// BookMusic Mall - Main JavaScript

document.addEventListener('DOMContentLoaded', function() {
    initNavbar();
    initAnimations();
    initSmoothScroll();
    lazyLoadImages();
    initCartHelpers();
});

/**
 * Navbar scroll effect
 */
function initNavbar() {
    var navbar = document.querySelector('.navbar');
    if (!navbar) return;

    var scrolled = false;
    var ticking = false;

    window.addEventListener('scroll', function() {
        if (!ticking) {
            window.requestAnimationFrame(function() {
                var isScrolled = window.scrollY > 50;
                if (isScrolled !== scrolled) {
                    scrolled = isScrolled;
                    navbar.classList.toggle('scrolled', scrolled);
                }
                ticking = false;
            });
            ticking = true;
        }
    }, { passive: true });
}

/**
 * Scroll-reveal animations (respects prefers-reduced-motion)
 */
function initAnimations() {
    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    var elements = document.querySelectorAll('.animate-ready');

    if (prefersReduced) {
        elements.forEach(function(el) {
            el.classList.add('animate-in');
        });
        return;
    }

    if (!('IntersectionObserver' in window)) {
        elements.forEach(function(el) {
            el.classList.add('animate-in');
        });
        return;
    }

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, {
        threshold: 0.1,
        rootMargin: '0px 0px -40px 0px'
    });

    elements.forEach(function(el) {
        observer.observe(el);
    });
}

/**
 * Smooth scroll for anchor links
 */
function initSmoothScroll() {
    var prefersReduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            var targetId = this.getAttribute('href');
            if (targetId === '#') return;

            var targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: prefersReduced ? 'auto' : 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * Lazy load images with data-src
 */
function lazyLoadImages() {
    var images = document.querySelectorAll('img[data-src]');

    if (!('IntersectionObserver' in window)) {
        images.forEach(function(img) {
            loadImage(img);
        });
        return;
    }

    var observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                loadImage(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });

    images.forEach(function(img) {
        observer.observe(img);
    });
}

function loadImage(img) {
    if (img.dataset.src) {
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
    }
}

/**
 * Cart helpers
 */
function initCartHelpers() {
    var buttons = document.querySelectorAll('[data-add-to-cart]');

    buttons.forEach(function(button) {
        button.addEventListener('click', async function(e) {
            e.preventDefault();

            var productId = this.dataset.productId || this.dataset.addToCart;
            if (!productId) {
                showNotification('商品ID无效', 'error');
                return;
            }

            if (this.disabled) return;
            var originalContent = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>加入中...';

            try {
                var response = await fetch('/api/cart/add', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'product_id=' + encodeURIComponent(productId)
                });

                var data = await response.json();

                if (data.success) {
                    showNotification(data.message || '已加入购物车', 'success');
                    updateCartCount(data.cart_count);
                } else {
                    showNotification(data.message || '加入失败', 'error');
                }
            } catch (err) {
                showNotification('网络错误，请稍后再试', 'error');
            } finally {
                this.disabled = false;
                this.innerHTML = originalContent;
            }
        });
    });
}

/**
 * Update cart badge count
 */
function updateCartCount(count) {
    var badge = document.querySelector('[data-cart-count]');
    if (badge) {
        badge.textContent = count;
        if (count > 0) {
            badge.style.display = 'inline-flex';
        } else {
            badge.style.display = 'none';
        }
    }
}

/**
 * Show notification toast
 */
function showNotification(message, type) {
    if (type === undefined) type = 'info';

    var existing = document.querySelector('.notification');
    if (existing) existing.remove();

    var notification = document.createElement('div');
    notification.className = 'notification notification-' + type;

    var icons = {
        success: '<i class="bi bi-check-circle-fill"></i>',
        error: '<i class="bi bi-x-circle-fill"></i>',
        info: '<i class="bi bi-info-circle-fill"></i>',
        warning: '<i class="bi bi-exclamation-triangle-fill"></i>'
    };

    notification.innerHTML = '<span>' + (icons[type] || icons.info) + '</span><span>' + message + '</span>';
    document.body.appendChild(notification);

    setTimeout(function() {
        notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 4000);
}
