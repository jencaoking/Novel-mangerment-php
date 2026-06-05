<?php
// ================================================
// DEPRECATED - 此文件已废弃
// 请使用 MVC 路由: /user/orders
// ================================================
/**
 * BookMusic Mall - 我的订单
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';

requireLogin();

$userId = getCurrentUserId();

$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 获取订单列表
$stmt = $db->prepare("
    SELECT o.*, p.title, p.cover, p.type, p.author 
    FROM orders o 
    JOIN products p ON o.product_id = p.id 
    WHERE o.user_id = ? 
    ORDER BY o.create_time DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>我的订单 - BookMusic Mall</title>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>
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
                    <a href="/user/" class="btn btn-secondary">用户中心</a>
                    <a href="/logout.php" class="btn btn-outline">退出</a>
                </div>
            </div>
        </div>
    </nav>

    <section class="user-center">
        <div class="container">
            <div class="user-layout">
                <div class="user-sidebar">
                    <div class="user-info">
                        <img src="/uploads/avatar/<?= e($user['avatar'] ?: 'default.jpg') ?>" 
                             alt="<?= e($user['username']) ?>" class="user-avatar">
                        <h3><?= e($user['username']) ?></h3>
                        <p><?= e($user['email']) ?></p>
                    </div>
                    <nav class="user-nav">
                        <a href="/user/">概览</a>
                        <a href="/user/profile.php">个人资料</a>
                        <a href="/user/orders.php" class="active">我的订单</a>
                        <a href="/user/downloads.php">我的下载</a>
                        <a href="/user/password.php">修改密码</a>
                    </nav>
                </div>
                
                <div class="user-content">
                    <h1>我的订单</h1>
                    
                    <?php if (empty($orders)): ?>
                        <p class="empty-message">暂无订单记录</p>
                    <?php else: ?>
                        <div class="orders-list">
                            <?php foreach ($orders as $order): ?>
                                <div class="order-card">
                                    <img src="/uploads/cover/<?= e($order['cover']) ?>" alt="<?= e($order['title']) ?>">
                                    <div class="order-details">
                                        <h3><?= e($order['title']) ?></h3>
                                        <p><?= $order['type'] === 'novel' ? '作者' : '歌手' ?>：<?= e($order['author']) ?></p>
                                        <p>订单号：<?= e($order['order_no']) ?></p>
                                        <p>金额：<strong><?= formatPrice($order['amount']) ?></strong></p>
                                        <p>下单时间：<?= timeAgo($order['create_time']) ?></p>
                                    </div>
                                    <div class="order-status">
                                        <span class="status-badge status-<?= getOrderStatusBadge($order['status']) ?>">
                                            <?= getOrderStatusText($order['status']) ?>
                                        </span>
                                        <?php if ($order['status'] === 'paid'): ?>
                                            <a href="/download.php?id=<?= $order['product_id'] ?>" class="btn btn-primary btn-sm">
                                                下载
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </section>

    <script src="/assets/js/main.js"></script>
    <style>
        .user-center { padding-top: 100px; min-height: calc(100vh - 80px); }
        .user-layout { display: grid; grid-template-columns: 250px 1fr; gap: var(--spacing-2xl); }
        .user-sidebar { position: sticky; top: 100px; height: fit-content; }
        .user-info { text-align: center; padding: var(--spacing-xl); background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); margin-bottom: var(--spacing-lg); }
        .user-avatar { width: 100px; height: 100px; border-radius: 50%; margin-bottom: var(--spacing-md); }
        .user-nav { background: var(--white); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); padding: var(--spacing-md); }
        .user-nav a { display: block; padding: var(--spacing-md); color: var(--gray-700); border-radius: var(--radius-md); }
        .user-nav a:hover, .user-nav a.active { background: var(--accent-light); color: var(--accent-dark); }
        .user-content { background: var(--white); padding: var(--spacing-2xl); border-radius: var(--radius-lg); box-shadow: var(--shadow-md); }
        .empty-message { text-align: center; color: var(--gray-500); padding: var(--spacing-2xl); }
        .orders-list { display: flex; flex-direction: column; gap: var(--spacing-lg); }
        .order-card { display: flex; gap: var(--spacing-lg); padding: var(--spacing-lg); background: var(--gray-50); border-radius: var(--radius-md); }
        .order-card img { width: 100px; height: 100px; object-fit: cover; border-radius: var(--radius-sm); }
        .order-details { flex: 1; }
        .order-details h3 { margin-bottom: var(--spacing-sm); }
        .order-details p { margin: var(--spacing-xs) 0; color: var(--gray-600); }
        .order-status { display: flex; flex-direction: column; align-items: flex-end; gap: var(--spacing-md); }
        .status-badge { padding: var(--spacing-xs) var(--spacing-sm); border-radius: var(--radius-sm); font-size: 0.85rem; font-weight: 600; }
        .status-success { background: #d4edda; color: #155724; }
        .status-warning { background: #fff3cd; color: #856404; }
        .status-secondary { background: #f8f9fa; color: #6c757d; }
        .status-danger { background: #f8d7da; color: #721c24; }
        .btn-sm { padding: 0.5rem 1rem; font-size: 0.9rem; }
        @media (max-width: 768px) { .user-layout { grid-template-columns: 1fr; } .order-card { flex-direction: column; } }
    </style>
</body>
</html>
