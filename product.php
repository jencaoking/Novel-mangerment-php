<?php
/**
 * BookMusic Mall - 商品详情页面
 */

require_once 'includes/auth.php';
require_once 'includes/db.php';

// 获取商品ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    redirect('/');
}

// 获取商品信息
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE p.id = ? AND p.status = 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('/');
}

// 检查用户是否已购买
$hasPurchased = false;
if (isLoggedIn()) {
    $hasPurchased = hasPurchased(getCurrentUserId(), $productId);
}

// 处理购买
$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
    if (!isLoggedIn()) {
        $_SESSION['redirect_url'] = "/product.php?id={$productId}";
        redirect('/login.php');
    }
    
    // 验证CSRF Token
    if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
        $message = '安全验证失败';
    } else {
        // 创建订单
        $orderNo = generateOrderNo();
        $stmt = $db->prepare("
            INSERT INTO orders (order_no, user_id, product_id, price, status) 
            VALUES (?, ?, ?, ?, 'pending')
        ");
        
        try {
            $stmt->execute([$orderNo, getCurrentUserId(), $productId, $product['price']]);
            $orderId = $db->lastInsertId();
            
            // 这里应该跳转到支付页面
            // 为了演示，我们直接标记为已支付
            $stmt = $db->prepare("UPDATE orders SET status = 'paid', pay_time = NOW() WHERE id = ?");
            $stmt->execute([$orderId]);
            
            // 更新商品销量
            $stmt = $db->prepare("UPDATE products SET sales = sales + 1 WHERE id = ?");
            $stmt->execute([$productId]);
            
            $message = '购买成功！';
            $hasPurchased = true;
        } catch (PDOException $e) {
            $message = '购买失败，请稍后再试';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($product['title']) ?> - BookMusic Mall</title>
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

    <!-- 商品详情 -->
    <section class="product-detail">
        <div class="container">
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    <?= e($message) ?>
                </div>
            <?php endif; ?>
            
            <div class="product-detail-grid">
                <!-- 商品图片 -->
                <div class="product-image-section">
                    <img src="/uploads/cover/<?= e($product['cover']) ?>" 
                         alt="<?= e($product['title']) ?>" 
                         class="product-cover">
                    
                    <?php if ($product['type'] === 'music' && $product['preview_path']): ?>
                        <div class="music-player">
                            <h4>试听</h4>
                            <audio controls>
                                <source src="/uploads/preview/<?= e($product['preview_path']) ?>" type="audio/mpeg">
                                您的浏览器不支持音频播放。
                            </audio>
                        </div>
                    <?php endif; ?>
                </div>
                
                <!-- 商品信息 -->
                <div class="product-info-section">
                    <span class="product-category-badge">
                        <?= e($product['category_name'] ?? ($product['type'] === 'novel' ? '小说' : '音乐')) ?>
                    </span>
                    
                    <h1 class="product-title"><?= e($product['title']) ?></h1>
                    
                    <p class="product-author">
                        <?= $product['type'] === 'novel' ? '作者' : '歌手' ?>：
                        <strong><?= e($product['author']) ?></strong>
                    </p>
                    
                    <div class="product-stats">
                        <div class="stat-item">
                            <span class="stat-label">价格</span>
                            <span class="stat-value price"><?= formatPrice($product['price']) ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">销量</span>
                            <span class="stat-value"><?= $product['sales'] ?></span>
                        </div>
                        <div class="stat-item">
                            <span class="stat-label">下载量</span>
                            <span class="stat-value"><?= $product['downloads'] ?></span>
                        </div>
                    </div>
                    
                    <div class="product-description">
                        <h3>简介</h3>
                        <p><?= nl2br(e($product['description'])) ?></p>
                    </div>
                    
                    <!-- 操作按钮 -->
                    <div class="product-actions">
                        <?php if ($hasPurchased): ?>
                            <a href="/download.php?id=<?= $productId ?>" class="btn btn-primary btn-lg">
                                <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                                </svg>
                                立即下载
                            </a>
                        <?php else: ?>
                            <form method="POST" action="">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <button type="submit" name="buy" class="btn btn-primary btn-lg">
                                    <svg width="20" height="20" viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M11 9h2V6h-2v3zm-2 7c0 1.1.9 2 2 2s2-.9 2-2-.9-2-2-2-2 .9-2 2zm9-13h-6v2h6v13H6V8H4v9c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2z"/>
                                    </svg>
                                    立即购买
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- 页脚 -->
    <footer class="footer">
        <div class="container">
            <div class="footer-bottom">
                <p>&copy; <?= date('Y') ?> BookMusic Mall. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <script src="/assets/js/main.js"></script>
    
    <style>
        .product-detail {
            padding-top: 120px;
            min-height: calc(100vh - 80px);
        }
        
        .alert {
            display: flex;
            align-items: center;
            gap: var(--spacing-sm);
            padding: var(--spacing-md);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-xl);
        }
        
        .alert-success {
            background: #efe;
            color: var(--success-color);
            border: 1px solid #cfc;
        }
        
        .product-detail-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-3xl);
            margin-bottom: var(--spacing-3xl);
        }
        
        .product-image-section {
            position: sticky;
            top: 100px;
        }
        
        .product-cover {
            width: 100%;
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-xl);
        }
        
        .music-player {
            margin-top: var(--spacing-xl);
            padding: var(--spacing-lg);
            background: var(--white);
            border-radius: var(--radius-md);
            box-shadow: var(--shadow-md);
        }
        
        .music-player h4 {
            margin-bottom: var(--spacing-md);
        }
        
        .music-player audio {
            width: 100%;
        }
        
        .product-info-section {
            padding: var(--spacing-xl);
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .product-category-badge {
            display: inline-block;
            padding: var(--spacing-xs) var(--spacing-md);
            background: var(--accent-light);
            color: var(--accent-dark);
            font-size: 0.85rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            margin-bottom: var(--spacing-md);
        }
        
        .product-title {
            font-size: 2.5rem;
            margin-bottom: var(--spacing-md);
        }
        
        .product-author {
            font-size: 1.1rem;
            color: var(--gray-600);
            margin-bottom: var(--spacing-xl);
        }
        
        .product-stats {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-lg);
            padding: var(--spacing-lg);
            background: var(--gray-50);
            border-radius: var(--radius-md);
            margin-bottom: var(--spacing-xl);
        }
        
        .stat-item {
            text-align: center;
        }
        
        .stat-label {
            display: block;
            font-size: 0.85rem;
            color: var(--gray-600);
            margin-bottom: var(--spacing-xs);
        }
        
        .stat-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-value.price {
            color: var(--accent-color);
        }
        
        .product-description {
            margin-bottom: var(--spacing-xl);
        }
        
        .product-description h3 {
            margin-bottom: var(--spacing-md);
        }
        
        .product-description p {
            line-height: 1.8;
            color: var(--gray-700);
        }
        
        .product-actions {
            display: flex;
            gap: var(--spacing-md);
        }
        
        .btn-lg {
            padding: 1rem 2rem;
            font-size: 1.1rem;
        }
        
        @media (max-width: 768px) {
            .product-detail-grid {
                grid-template-columns: 1fr;
            }
            
            .product-image-section {
                position: static;
            }
            
            .product-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
