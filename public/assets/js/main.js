// BookMusic Mall - 主 JavaScript 文件
// 清新现代的用户体验设计

document.addEventListener('DOMContentLoaded', function() {
    initNavbar();
    initAnimations();
    initSmoothScroll();
    lazyLoadImages();
    initCartHelpers();
});

/**
 * 导航栏功能
 */
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    
    if (navbar) {
        // 滚动效果
        window.addEventListener('scroll', function() {
            if (window.scrollY > 50) {
                navbar.classList.add('scrolled');
            } else {
                navbar.classList.remove('scrolled');
            }
        });
    }
}

/**
 * 动画效果 - 滚动时显示
 */
function initAnimations() {
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver(function(entries) {
        entries.forEach(function(entry, index) {
            if (entry.isIntersecting) {
                setTimeout(function() {
                    entry.target.classList.add('animate-in');
                }, index * 100);
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    const animateElements = document.querySelectorAll('.animate-ready');
    animateElements.forEach(function(el) {
        observer.observe(el);
    });
}

/**
 * 平滑滚动
 */
function initSmoothScroll() {
    document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
        anchor.addEventListener('click', function(e) {
            const targetId = this.getAttribute('href');
            if (targetId === '#') return;
            
            const targetElement = document.querySelector(targetId);
            if (targetElement) {
                e.preventDefault();
                targetElement.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
}

/**
 * 图片懒加载
 */
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver(function(entries) {
            entries.forEach(function(entry) {
                if (entry.isIntersecting) {
                    loadImage(entry.target);
                    imageObserver.unobserve(entry.target);
                }
            });
        });
        
        images.forEach(function(img) {
            imageObserver.observe(img);
        });
    } else {
        // 回退方案
        images.forEach(function(img) {
            loadImage(img);
        });
    }
}

function loadImage(img) {
    if (img.dataset.src) {
        img.src = img.dataset.src;
        img.removeAttribute('data-src');
        img.classList.add('loaded');
    }
}

/**
 * 购物车辅助功能
 */
function initCartHelpers() {
    // 为所有"加入购物车"按钮绑定事件
    const addToCartButtons = document.querySelectorAll('[data-add-to-cart]');
    
    addToCartButtons.forEach(function(button) {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            
            const productId = this.dataset.productId || this.dataset.addToCart;
            
            if (!productId) {
                showNotification('商品ID无效', 'error');
                return;
            }
            
            // 防止重复点击
            if (this.disabled) return;
            const originalContent = this.innerHTML;
            this.disabled = true;
            this.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status"></span>加入中...';
            
            try {
                const response = await fetch('/api/cart/add', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: 'product_id=' + encodeURIComponent(productId)
                });
                
                const data = await response.json();
                
                if (data.success) {
                    showNotification(data.message || '已加入购物车', 'success');
                    // 更新导航栏购物车数量
                    updateCartCount(data.cart_count);
                } else {
                    showNotification(data.message || '加入失败', 'error');
                }
            } catch (err) {
                console.error('加入购物车失败:', err);
                showNotification('网络错误，请稍后再试', 'error');
            } finally {
                this.disabled = false;
                this.innerHTML = originalContent;
            }
        });
    });
}

/**
 * 更新购物车数量显示
 */
function updateCartCount(count) {
    const cartBadge = document.querySelector('.cart-badge, [data-cart-count]');
    if (cartBadge) {
        cartBadge.textContent = count;
        if (count > 0) {
            cartBadge.style.display = 'inline-flex';
            // 添加动画效果
            cartBadge.style.transform = 'scale(1.2)';
            setTimeout(function() {
                cartBadge.style.transform = 'scale(1)';
            }, 200);
        } else {
            cartBadge.style.display = 'none';
        }
    }
}

/**
 * 显示通知消息
 */
function showNotification(message, type) {
    if (type === void 0) { type = 'info'; }
    
    // 如果已有通知，先移除
    const existing = document.querySelector('.notification');
    if (existing) {
        existing.remove();
    }
    
    const notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    
    const icons = {
        success: '<i class="bi bi-check-circle-fill"></i>',
        error: '<i class="bi bi-x-circle-fill"></i>',
        info: '<i class="bi bi-info-circle-fill"></i>',
        warning: '<i class="bi bi-exclamation-triangle-fill"></i>'
    };
    
    notification.innerHTML = '<div class="notification-content d-flex align-items-center gap-2"><span class="notification-icon">' + (icons[type] || icons.info) + '</span><span class="notification-message">' + message + '</span></div>';
    
    // 样式已在 CSS 中定义
    document.body.appendChild(notification);
    
    // 自动关闭
    setTimeout(function() {
        notification.style.animation = 'slideOutRight 0.3s ease-in forwards';
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 4000);
}

// 添加需要的动画样式
(function addAnimationStyles() {
    const style = document.createElement('style');
    style.textContent = '\
        @keyframes slideInRight {\
            from {\
                transform: translateX(100%);\
                opacity: 0;\
            }\
            to {\
                transform: translateX(0);\
                opacity: 1;\
            }\
        }\
        \
        @keyframes slideOutRight {\
            from {\
                transform: translateX(0);\
                opacity: 1;\
            }\
            to {\
                transform: translateX(100%);\
                opacity: 0;\
            }\
        }\
    ';
    document.head.appendChild(style);
})();
