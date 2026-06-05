/**
 * BookMusic Mall - 主JavaScript文件
 * 小说与音乐数字内容销售平台
 */

// DOM加载完成后执行
document.addEventListener('DOMContentLoaded', function() {
    // 初始化所有功能
    initNavbar();
    initAnimations();
    initProductCards();
    initFormValidation();
    // initSearch(); // 已禁用：使用表单搜索代替 AJAX 搜索
    lazyLoadImages();
});

/**
 * 导航栏功能
 */
function initNavbar() {
    const navbar = document.querySelector('.navbar');
    if (!navbar) return;
    let lastScroll = 0;
    
    // 滚动效果
    window.addEventListener('scroll', () => {
        const currentScroll = window.pageYOffset;
        
        if (currentScroll > 100) {
            navbar.style.background = 'rgba(26, 35, 50, 0.98)';
            navbar.style.boxShadow = '0 4px 20px rgba(0, 0, 0, 0.3)';
        } else {
            navbar.style.background = 'rgba(26, 35, 50, 0.95)';
            navbar.style.boxShadow = 'none';
        }
        
        lastScroll = currentScroll;
    });
    
    // 移动端菜单
    const menuToggle = document.querySelector('.menu-toggle');
    const navMenu = document.querySelector('.navbar-nav');
    
    if (menuToggle && navMenu) {
        menuToggle.addEventListener('click', () => {
            navMenu.classList.toggle('active');
            menuToggle.classList.toggle('active');
        });
    }
}

/**
 * 动画效果
 */
function initAnimations() {
    // 观察器配置
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    // 创建观察器
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animate-in');
                observer.unobserve(entry.target);
            }
        });
    }, observerOptions);
    
    // 观察所有需要动画的元素
    const animateElements = document.querySelectorAll('.feature-card, .product-card');
    animateElements.forEach(el => {
        el.classList.add('animate-ready');
        observer.observe(el);
    });
    
    // 添加动画样式
    const style = document.createElement('style');
    style.textContent = `
        .animate-ready {
            opacity: 0;
            transform: translateY(30px);
            transition: opacity 0.6s ease-out, transform 0.6s ease-out;
        }
        .animate-in {
            opacity: 1;
            transform: translateY(0);
        }
    `;
    document.head.appendChild(style);
}

/**
 * 商品卡片交互
 */
function initProductCards() {
    const productCards = document.querySelectorAll('.product-card');
    
    productCards.forEach(card => {
        // 鼠标移入效果
        card.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-8px) scale(1.02)';
        });
        
        // 鼠标移出效果
        card.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0) scale(1)';
        });
        
        // 点击效果
        card.addEventListener('click', function(e) {
            // 创建涟漪效果
            const ripple = document.createElement('div');
            ripple.style.cssText = `
                position: absolute;
                border-radius: 50%;
                background: rgba(212, 175, 55, 0.3);
                transform: scale(0);
                animation: ripple 0.6s linear;
                pointer-events: none;
            `;
            
            const rect = this.getBoundingClientRect();
            const size = Math.max(rect.width, rect.height);
            ripple.style.width = ripple.style.height = size + 'px';
            ripple.style.left = (e.clientX - rect.left - size / 2) + 'px';
            ripple.style.top = (e.clientY - rect.top - size / 2) + 'px';
            
            this.style.position = 'relative';
            this.style.overflow = 'hidden';
            this.appendChild(ripple);
            
            setTimeout(() => ripple.remove(), 600);
        });
    });
    
    // 添加涟漪动画
    const style = document.createElement('style');
    style.textContent = `
        @keyframes ripple {
            to {
                transform: scale(4);
                opacity: 0;
            }
        }
    `;
    document.head.appendChild(style);
}

/**
 * 表单验证
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form[data-validate]');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            let isValid = true;
            const formData = new FormData(this);
            
            // 清除之前的错误
            this.querySelectorAll('.error-message').forEach(el => el.remove());
            this.querySelectorAll('.error').forEach(el => el.classList.remove('error'));
            
            // 验证必填字段
            this.querySelectorAll('[required]').forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    showFieldError(field, '此字段为必填项');
                }
            });
            
            // 验证邮箱
            this.querySelectorAll('[type="email"]').forEach(field => {
                if (field.value && !isValidEmail(field.value)) {
                    isValid = false;
                    showFieldError(field, '请输入有效的邮箱地址');
                }
            });
            
            // 验证密码
            this.querySelectorAll('[data-min-length]').forEach(field => {
                const minLength = parseInt(field.dataset.minLength);
                if (field.value && field.value.length < minLength) {
                    isValid = false;
                    showFieldError(field, `密码至少需要${minLength}个字符`);
                }
            });
            
            // 验证确认密码
            const password = this.querySelector('[name="password"]');
            const confirmPassword = this.querySelector('[name="confirm_password"]');
            if (password && confirmPassword && password.value !== confirmPassword.value) {
                isValid = false;
                showFieldError(confirmPassword, '两次输入的密码不一致');
            }
            
            if (isValid) {
                // 显示加载状态
                const submitBtn = this.querySelector('[type="submit"]');
                const originalText = submitBtn.textContent;
                submitBtn.textContent = '处理中...';
                submitBtn.disabled = true;
                
                // 提交表单并在finally中重置状态
                submitForm(this, formData).finally(() => {
                    submitBtn.textContent = originalText;
                    submitBtn.disabled = false;
                });
            }
        });
    });
}

/**
 * 显示字段错误
 */
function showFieldError(field, message) {
    field.classList.add('error');
    const errorDiv = document.createElement('div');
    errorDiv.className = 'error-message';
    errorDiv.textContent = message;
    errorDiv.style.cssText = `
        color: #d64f4f;
        font-size: 0.85rem;
        margin-top: 0.25rem;
        animation: shake 0.3s ease-in-out;
    `;
    field.parentNode.appendChild(errorDiv);
}

/**
 * 验证邮箱格式
 */
function isValidEmail(email) {
    return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
}

/**
 * 提交表单
 */
async function submitForm(form, formData) {
    try {
        const response = await fetch(form.action, {
            method: form.method || 'POST',
            body: formData
        });
        
        if (!response.ok) {
            throw new Error('网络请求异常');
        }
        
        const result = await response.json();
        
        if (result.success) {
            showNotification('操作成功！', 'success');
            if (result.redirect) {
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 1000);
            }
        } else {
            showNotification(result.message || '操作失败，请重试', 'error');
        }
    } catch (error) {
        showNotification('网络错误，请稍后再试', 'error');
    }
}

/**
 * 显示通知
 */
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.textContent = message;
    notification.style.cssText = `
        position: fixed;
        top: 100px;
        right: 20px;
        padding: 1rem 2rem;
        background: ${type === 'success' ? '#4a9d7c' : type === 'error' ? '#d64f4f' : '#5b8dbd'};
        color: white;
        border-radius: 0.5rem;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.2);
        z-index: 10000;
        animation: slideInRight 0.3s ease-out;
    `;
    
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.style.animation = 'slideOutRight 0.3s ease-in';
        setTimeout(() => notification.remove(), 300);
    }, 3000);
}

/**
 * 搜索功能
 */
function initSearch() {
    const searchInput = document.querySelector('.search-input');
    const searchResults = document.querySelector('.search-results');
    
    if (searchInput && searchResults) {
        let debounceTimer;
        
        searchInput.addEventListener('input', function() {
            clearTimeout(debounceTimer);
            const query = this.value.trim();
            
            if (query.length < 2) {
                searchResults.style.display = 'none';
                return;
            }
            
            debounceTimer = setTimeout(() => {
                performSearch(query, searchResults);
            }, 300);
        });
        
        // 点击外部关闭搜索结果
        document.addEventListener('click', (e) => {
            if (!searchInput.contains(e.target) && !searchResults.contains(e.target)) {
                searchResults.style.display = 'none';
            }
        });
    }
}

/**
 * 执行搜索
 */
async function performSearch(query, resultsContainer) {
    try {
        const response = await fetch(`/api/search.php?q=${encodeURIComponent(query)}`);
        const results = await response.json();
        
        if (results.length > 0) {
            resultsContainer.innerHTML = results.map(item => `
                <div class="search-result-item">
                    <img src="${item.cover}" alt="${item.title}">
                    <div>
                        <h4>${item.title}</h4>
                        <p>${item.author}</p>
                        <span class="price">${item.price}</span>
                    </div>
                </div>
            `).join('');
            resultsContainer.style.display = 'block';
        } else {
            resultsContainer.innerHTML = '<p class="no-results">未找到相关结果</p>';
            resultsContainer.style.display = 'block';
        }
    } catch (error) {
        console.error('搜索失败:', error);
    }
}

/**
 * 平滑滚动
 */
function smoothScroll(target) {
    const element = document.querySelector(target);
    if (element) {
        element.scrollIntoView({
            behavior: 'smooth',
            block: 'start'
        });
    }
}

/**
 * 图片懒加载
 */
function lazyLoadImages() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                imageObserver.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// 添加动画样式
const animationStyles = document.createElement('style');
animationStyles.textContent = `
    @keyframes slideInRight {
        from {
            transform: translateX(100%);
            opacity: 0;
        }
        to {
            transform: translateX(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutRight {
        from {
            transform: translateX(0);
            opacity: 1;
        }
        to {
            transform: translateX(100%);
            opacity: 0;
        }
    }
    
    @keyframes shake {
        0%, 100% { transform: translateX(0); }
        25% { transform: translateX(-5px); }
        75% { transform: translateX(5px); }
    }
`;
document.head.appendChild(animationStyles);
