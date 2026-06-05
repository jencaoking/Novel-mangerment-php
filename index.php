<?php
/**
 * BookMusic Mall - 首页
 * 小说与音乐数字内容销售平台
 */

require_once 'includes/auth.php';
require_once 'includes/db.php';

// 获取热门小说
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.type = 'novel' AND p.status = 1 
    ORDER BY p.sales DESC, p.downloads DESC 
    LIMIT 6
");
$stmt->execute();
$hotNovels = $stmt->fetchAll();

// 获取热门音乐
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.type = 'music' AND p.status = 1 
    ORDER BY p.sales DESC, p.downloads DESC 
    LIMIT 6
");
$stmt->execute();
$hotMusic = $stmt->fetchAll();

// 获取最新上架
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.status = 1 
    ORDER BY p.create_time DESC 
    LIMIT 8
");
$stmt->execute();
$latestProducts = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="BookMusic Mall - 小说与音乐数字内容销售平台，提供优质的小说和音乐资源下载服务">
    <title>BookMusic Mall - 小说与音乐数字内容销售平台</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="icon" type="image/svg+xml" href="/assets/images/favicon.svg">
</head>
<body>
    <!-- 导航栏 -->
    <nav class="navbar">
        <div class="container">
            <div class="navbar-content">
                <a href="/" class="navbar-brand">
                    <svg viewBox="0 0 40 40" xmlns="http://www.w3.org/2000/svg">
                        <path d="M20 2C10.06 2 2 10.06 2 20s8.06 18 18 18 18-8.06 18-18S29.94 2 20 2zm0 4c7.73 0 14 6.27 14 14s-6.27 14-14 14S6 25.73 6 20 12.27 6 20 6zm-4 6v16l12-8-12-8z"/>
                    </svg>
                    <span>BookMusic Mall</span>
                </a>
                
                <ul class="navbar-nav">
                    <li><a href="/">首页</a></li>
                    <li><a href="/novels.php">小说</a></li>
                    <li><a href="/music.php">音乐</a></li>
                    <li><a href="#features">特色</a></li>
                    <li><a href="#about">关于</a></li>
                </ul>
                
                <div class="navbar-actions">
                    <?php if (isLoggedIn()): ?>
                        <a href="/user/" class="btn btn-secondary">用户中心</a>
                        <a href="/logout.php" class="btn btn-outline">退出</a>
                    <?php else: ?>
                        <a href="/login.php" class="btn btn-secondary">登录</a>
                        <a href="/register.php" class="btn btn-primary">注册</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- 英雄区域 -->
    <section class="hero">
        <div class="hero-content">
            <h1 class="hero-title">
                发现<span>数字内容</span>的无限可能
            </h1>
            <p class="hero-subtitle">
                精选优质小说与音乐资源，打造您的私人数字图书馆
            </p>
            <div class="hero-actions">
                <a href="/novels.php" class="btn btn-primary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    浏览小说
                </a>
                <a href="/music.php" class="btn btn-secondary">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 3v10.55c-.59-.34-1.27-.55-2-.55-2.21 0-4 1.79-4 4s1.79 4 4 4 4-1.79 4-4V7h4V3h-6z"/>
                    </svg>
                    探索音乐
                </a>
            </div>
        </div>
        
        <!-- 装饰性元素 -->
        <div class="hero-decoration">
            <div class="floating-element" style="top: 20%; left: 10%; animation-delay: 0s;"></div>
            <div class="floating-element" style="top: 60%; right: 15%; animation-delay: 2s;"></div>
            <div class="floating-element" style="bottom: 20%; left: 20%; animation-delay: 4s;"></div>
        </div>
    </section>

    <!-- 特色功能 -->
    <section id="features" class="features">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">为什么选择我们</h2>
                <p class="section-subtitle">我们致力于为您提供最优质的数字内容服务和极致的用户体验</p>
            </div>
            
            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 2l3.09 6.26L22 9.27l-5 4.87 1.18 6.88L12 17.77l-6.18 3.25L7 14.14 2 9.27l6.91-1.01L12 2z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">精选内容</h3>
                    <p class="feature-description">
                        严格筛选每一部小说和音乐作品，确保内容质量，为您提供最优质的阅读和聆听体验
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.35 10.04C18.67 6.59 15.64 4 12 4 9.11 4 6.6 5.64 5.35 8.04 2.34 8.36 0 10.91 0 14c0 3.31 2.69 6 6 6h13c2.76 0 5-2.24 5-5 0-2.64-2.05-4.78-4.65-4.96zM17 13l-5 5-5-5h3V9h4v4h3z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">极速下载</h3>
                    <p class="feature-description">
                        高速稳定的下载服务，支持断点续传，让您快速获取心仪的内容，随时随地享受
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M12 1L3 5v6c0 5.55 3.84 10.74 9 12 5.16-1.26 9-6.45 9-12V5l-9-4zm0 10.99h7c-.53 4.12-3.28 7.79-7 8.94V12H5V6.3l7-3.11v8.8z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">安全保障</h3>
                    <p class="feature-description">
                        采用银行级加密技术，保护您的支付信息和个人隐私，交易安全无忧
                    </p>
                </div>
                
                <div class="feature-card">
                    <div class="feature-icon">
                        <svg width="30" height="30" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M11.99 2C6.47 2 2 6.48 2 12s4.47 10 9.99 10C17.52 22 22 17.52 22 12S17.52 2 11.99 2zM12 20c-4.42 0-8-3.58-8-8s3.58-8 8-8 8 3.58 8 8-3.58 8-8 8zm3.5-9c.83 0 1.5-.67 1.5-1.5S16.33 8 15.5 8 14 8.67 14 9.5s.67 1.5 1.5 1.5zm-7 0c.83 0 1.5-.67 1.5-1.5S9.33 8 8.5 8 7 8.67 7 9.5 7.67 11 8.5 11zm3.5 6.5c2.33 0 4.31-1.46 5.11-3.5H6.89c.8 2.04 2.78 3.5 5.11 3.5z"/>
                        </svg>
                    </div>
                    <h3 class="feature-title">贴心服务</h3>
                    <p class="feature-description">
                        7×24小时在线客服支持，解答您的任何疑问，让您的购物体验更加顺畅
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- 热门小说 -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">热门小说</h2>
                <p class="section-subtitle">精心挑选的热门小说，深受读者喜爱</p>
            </div>
            
            <div class="products-grid">
                <?php foreach ($hotNovels as $novel): ?>
                    <div class="product-card" onclick="window.location.href='/product.php?id=<?= $novel['id'] ?>'">
                        <img src="/uploads/cover/<?= e($novel['cover']) ?>" alt="<?= e($novel['title']) ?>" class="product-image">
                        <div class="product-info">
                            <span class="product-category"><?= e($novel['category_name'] ?? '小说') ?></span>
                            <h3 class="product-title"><?= e($novel['title']) ?></h3>
                            <p class="product-author">作者：<?= e($novel['author']) ?></p>
                            <div class="product-footer">
                                <span class="product-price"><?= formatPrice($novel['price']) ?></span>
                                <div class="product-stats">
                                    <span>销量 <?= $novel['sales'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="/novels.php" class="btn btn-outline">查看更多小说</a>
            </div>
        </div>
    </section>

    <!-- 热门音乐 -->
    <section class="products-section" style="background: var(--white);">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">热门音乐</h2>
                <p class="section-subtitle">动听旋律，触动心灵</p>
            </div>
            
            <div class="products-grid">
                <?php foreach ($hotMusic as $music): ?>
                    <div class="product-card" onclick="window.location.href='/product.php?id=<?= $music['id'] ?>'">
                        <img src="/uploads/cover/<?= e($music['cover']) ?>" alt="<?= e($music['title']) ?>" class="product-image">
                        <div class="product-info">
                            <span class="product-category"><?= e($music['category_name'] ?? '音乐') ?></span>
                            <h3 class="product-title"><?= e($music['title']) ?></h3>
                            <p class="product-author">歌手：<?= e($music['author']) ?></p>
                            <div class="product-footer">
                                <span class="product-price"><?= formatPrice($music['price']) ?></span>
                                <div class="product-stats">
                                    <span>销量 <?= $music['sales'] ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="text-center mt-4">
                <a href="/music.php" class="btn btn-outline">查看更多音乐</a>
            </div>
        </div>
    </section>

    <!-- 最新上架 -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title">最新上架</h2>
                <p class="section-subtitle">新鲜出炉，抢先体验</p>
            </div>
            
            <div class="products-grid">
                <?php foreach ($latestProducts as $product): ?>
                    <div class="product-card" onclick="window.location.href='/product.php?id=<?= $product['id'] ?>'">
                        <img src="/uploads/cover/<?= e($product['cover']) ?>" alt="<?= e($product['title']) ?>" class="product-image">
                        <div class="product-info">
                            <span class="product-category"><?= e($product['category_name'] ?? ($product['type'] === 'novel' ? '小说' : '音乐')) ?></span>
                            <h3 class="product-title"><?= e($product['title']) ?></h3>
                            <p class="product-author">
                                <?= $product['type'] === 'novel' ? '作者' : '歌手' ?>：<?= e($product['author']) ?>
                            </p>
                            <div class="product-footer">
                                <span class="product-price"><?= formatPrice($product['price']) ?></span>
                                <div class="product-stats">
                                    <span><?= timeAgo($product['create_time']) ?></span>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-content">
                <div class="footer-section">
                    <h4>关于我们</h4>
                    <p>BookMusic Mall 致力于为用户提供优质的小说和音乐数字内容服务，打造您的私人数字图书馆。</p>
                </div>
                
                <div class="footer-section">
                    <h4>快速链接</h4>
                    <ul class="footer-links">
                        <li><a href="/novels.php">小说商城</a></li>
                        <li><a href="/music.php">音乐商城</a></li>
                        <li><a href="/user/">用户中心</a></li>
                        <li><a href="/admin/">管理后台</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>帮助中心</h4>
                    <ul class="footer-links">
                        <li><a href="#">购买流程</a></li>
                        <li><a href="#">下载说明</a></li>
                        <li><a href="#">支付方式</a></li>
                        <li><a href="#">常见问题</a></li>
                    </ul>
                </div>
                
                <div class="footer-section">
                    <h4>联系我们</h4>
                    <ul class="footer-links">
                        <li>客服邮箱：support@bookmusic.com</li>
                        <li>客服电话：400-123-4567</li>
                        <li>工作时间：9:00-21:00</li>
                    </ul>
                </div>
            </div>
            
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> BookMusic Mall. All rights reserved. | 小说与音乐数字内容销售平台</p>
            </div>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>
    
    <style>
        /* 英雄区域装饰元素 */
        .hero-decoration {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            pointer-events: none;
            overflow: hidden;
        }
        
        .floating-element {
            position: absolute;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(212, 175, 55, 0.2), transparent);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% {
                transform: translateY(0) rotate(0deg);
            }
            50% {
                transform: translateY(-20px) rotate(180deg);
            }
        }
    </style>
</body>
</html>
