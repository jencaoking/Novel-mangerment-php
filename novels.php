<?php
/**
 * BookMusic Mall - 小说商城
 */

require_once 'includes/auth.php';
require_once 'includes/db.php';

// 获取参数
$category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
$sort = $_GET['sort'] ?? 'latest';
$search = trim($_GET['search'] ?? '');
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// 构建查询
$where = "p.type = 'novel' AND p.status = 1";
$params = [];

if ($category > 0) {
    $where .= " AND p.category_id = ?";
    $params[] = $category;
}

if (!empty($search)) {
    $where .= " AND (p.title LIKE ? OR p.author LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

// 排序
$orderBy = match($sort) {
    'hot' => 'p.sales DESC, p.downloads DESC',
    'price_asc' => 'p.price ASC',
    'price_desc' => 'p.price DESC',
    default => 'p.create_time DESC'
};

// 获取总数
$countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE {$where}");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

// 分页
$pagination = paginate($total, $page);

// 获取商品列表
$stmt = $db->prepare("
    SELECT p.*, c.name as category_name 
    FROM products p 
    LEFT JOIN categories c ON p.category_id = c.id 
    WHERE {$where}
    ORDER BY {$orderBy}
    LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
");
$stmt->execute($params);
$novels = $stmt->fetchAll();

// 获取分类列表
$stmt = $db->prepare("SELECT * FROM categories WHERE type = 'novel' AND status = 1 ORDER BY sort_order");
$stmt->execute();
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>小说商城 - BookMusic Mall</title>
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
                    <li><a href="/novels.php" class="active">小说</a></li>
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

    <!-- 页面内容 -->
    <section class="page-content">
        <div class="container">
            <!-- 页面标题 -->
            <div class="page-header">
                <h1>小说商城</h1>
                <p>精选优质小说，开启您的阅读之旅</p>
            </div>
            
            <!-- 搜索和筛选 -->
            <div class="filter-bar">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="搜索小说名称或作者..." 
                           value="<?= e($search) ?>" class="search-input">
                    <button type="submit" class="btn btn-primary">搜索</button>
                </form>
                
                <div class="filter-options">
                    <!-- 分类筛选 -->
                    <select name="category" onchange="this.form.submit()" class="filter-select">
                        <option value="0">全部分类</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?= $cat['id'] ?>" <?= $category == $cat['id'] ? 'selected' : '' ?>>
                                <?= e($cat['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    
                    <!-- 排序 -->
                    <select name="sort" onchange="this.form.submit()" class="filter-select">
                        <option value="latest" <?= $sort === 'latest' ? 'selected' : '' ?>>最新上架</option>
                        <option value="hot" <?= $sort === 'hot' ? 'selected' : '' ?>>最热门</option>
                        <option value="price_asc" <?= $sort === 'price_asc' ? 'selected' : '' ?>>价格从低到高</option>
                        <option value="price_desc" <?= $sort === 'price_desc' ? 'selected' : '' ?>>价格从高到低</option>
                    </select>
                </div>
            </div>
            
            <!-- 商品列表 -->
            <?php if (empty($novels)): ?>
                <div class="empty-state">
                    <svg width="80" height="80" viewBox="0 0 24 24" fill="var(--gray-400)">
                        <path d="M18 2H6c-1.1 0-2 .9-2 2v16c0 1.1.9 2 2 2h12c1.1 0 2-.9 2-2V4c0-1.1-.9-2-2-2zM6 4h5v8l-2.5-1.5L6 12V4z"/>
                    </svg>
                    <h3>暂无小说</h3>
                    <p>没有找到符合条件的小说，请尝试其他搜索条件</p>
                </div>
            <?php else: ?>
                <div class="products-grid">
                    <?php foreach ($novels as $novel): ?>
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
                
                <!-- 分页 -->
                <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_previous']): ?>
                            <a href="?page=<?= $page - 1 ?>&category=<?= $category ?>&sort=<?= $sort ?>&search=<?= urlencode($search) ?>" 
                               class="btn btn-outline">上一页</a>
                        <?php endif; ?>
                        
                        <span class="page-info">
                            第 <?= $page ?> / <?= $pagination['total_pages'] ?> 页
                        </span>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?page=<?= $page + 1 ?>&category=<?= $category ?>&sort=<?= $sort ?>&search=<?= urlencode($search) ?>" 
                               class="btn btn-outline">下一页</a>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
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
        .page-content {
            padding-top: 120px;
            min-height: calc(100vh - 80px);
        }
        
        .page-header {
            text-align: center;
            margin-bottom: var(--spacing-2xl);
        }
        
        .page-header h1 {
            font-size: 3rem;
            margin-bottom: var(--spacing-sm);
        }
        
        .page-header p {
            color: var(--gray-600);
            font-size: 1.2rem;
        }
        
        .filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
            padding: var(--spacing-lg);
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .search-form {
            display: flex;
            gap: var(--spacing-md);
            flex: 1;
            max-width: 500px;
        }
        
        .search-input {
            flex: 1;
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 1rem;
        }
        
        .search-input:focus {
            outline: none;
            border-color: var(--accent-color);
        }
        
        .filter-options {
            display: flex;
            gap: var(--spacing-md);
        }
        
        .filter-select {
            padding: 0.75rem 1rem;
            border: 2px solid var(--gray-200);
            border-radius: var(--radius-md);
            font-size: 0.95rem;
            cursor: pointer;
        }
        
        .empty-state {
            text-align: center;
            padding: var(--spacing-3xl);
            color: var(--gray-500);
        }
        
        .empty-state svg {
            margin-bottom: var(--spacing-lg);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            gap: var(--spacing-lg);
            margin-top: var(--spacing-2xl);
        }
        
        .page-info {
            color: var(--gray-600);
        }
        
        @media (max-width: 768px) {
            .filter-bar {
                flex-direction: column;
            }
            
            .search-form {
                max-width: 100%;
            }
            
            .filter-options {
                width: 100%;
            }
        }
    </style>
</body>
</html>
