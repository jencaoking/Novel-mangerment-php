<?php
/**
 * BookMusic Mall - 用户中心首页
 */

require_once '../includes/auth.php';
require_once '../includes/db.php';

// 要求用户登录
requireLogin();

// 获取用户信息
$userId = getCurrentUserId();
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$user = $stmt->fetch();

// 获取订单统计
$stmt = $db->prepare("SELECT COUNT(*) FROM orders WHERE user_id = ? AND status = 'paid'");
$stmt->execute([$userId]);
$totalOrders = $stmt->fetchColumn();

// 获取下载统计
$stmt = $db->prepare("SELECT COUNT(*) FROM downloads WHERE user_id = ?");
$stmt->execute([$userId]);
$totalDownloads = $stmt->fetchColumn();

// 获取消费总额
$stmt = $db->prepare("SELECT COALESCE(SUM(amount), 0) FROM orders WHERE user_id = ? AND status = 'paid'");
$stmt->execute([$userId]);
$totalSpent = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>用户中心 - BookMusic Mall</title>
    <link rel="stylesheet" href="/assets/css/style.css">
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
                    <a href="/user/" class="btn btn-secondary">用户中心</a>
                    <a href="/logout.php" class="btn btn-outline">退出</a>
                </div>
            </div>
        </div>
    </nav>

    <!-- 用户中心 -->
    <section class="user-center">
        <div class="container">
            <div class="user-layout">
                <!-- 侧边栏 -->
                <div class="user-sidebar">
                    <div class="user-info">
                        <img src="/uploads/avatar/<?= $user['avatar'] ?? 'default.jpg' ?>" 
                             alt="<?= e($user['username']) ?>" 
                             class="user-avatar">
                        <h3><?= e($user['username']) ?></h3>
                        <p><?= e($user['email']) ?></p>
                    </div>
                    
                    <nav class="user-nav">
                        <a href="/user/" class="active">概览</a>
                        <a href="/user/profile.php">个人资料</a>
                        <a href="/user/orders.php">我的订单</a>
                        <a href="/user/downloads.php">我的下载</a>
                        <a href="/user/password.php">修改密码</a>
                    </nav>
                </div>
                
                <!-- 主内容区 -->
                <div class="user-content">
                    <h1>用户中心</h1>
                    
                    <!-- 统计卡片 -->
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?= $totalOrders ?></span>
                                <span class="stat-label">我的订单</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M19 9h-4V3H9v6H5l7 7 7-7zM5 18v2h14v-2H5z"/>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?= $totalDownloads ?></span>
                                <span class="stat-label">下载次数</span>
                            </div>
                        </div>
                        
                        <div class="stat-card">
                            <div class="stat-icon">
                                <svg width="40" height="40" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M11.8 10.8c0-1.6-1.3-2.8-2.8-2.8s-2.8 1.3-2.8 2.8c0 1.6 1.3 2.8 2.8 2.8s2.8-1.2 2.8-2.8zm4.4 0c0-1.6-1.3-2.8-2.8-2.8s-2.8 1.3-2.8 2.8c0 1.6 1.3 2.8 2.8 2.8s2.8-1.2 2.8-2.8zM12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8z"/>
                                </svg>
                            </div>
                            <div class="stat-info">
                                <span class="stat-number"><?= formatPrice($totalSpent) ?></span>
                                <span class="stat-label">消费总额</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- 最近订单 -->
                    <div class="recent-section">
                        <h2>最近订单</h2>
                        <?php
                        $stmt = $db->prepare("
                            SELECT o.*, p.title, p.cover, p.type 
                            FROM orders o 
                            JOIN products p ON o.product_id = p.id 
                            WHERE o.user_id = ? 
                            ORDER BY o.create_time DESC 
                            LIMIT 5
                        ");
                        $stmt->execute([$userId]);
                        $recentOrders = $stmt->fetchAll();
                        ?>
                        
                        <?php if (empty($recentOrders)): ?>
                            <p class="empty-message">暂无订单记录</p>
                        <?php else: ?>
                            <div class="orders-list">
                                <?php foreach ($recentOrders as $order): ?>
                                    <div class="order-item">
                                        <img src="/uploads/cover/<?= e($order['cover']) ?>" 
                                             alt="<?= e($order['title']) ?>">
                                        <div class="order-info">
                                            <h4><?= e($order['title']) ?></h4>
                                            <p>订单号：<?= e($order['order_no']) ?></p>
                                            <p>金额：<?= formatPrice($order['amount']) ?></p>
                                        </div>
                                        <div class="order-status">
                                            <span class="status-badge status-<?= $order['status'] ?>">
                                                <?= match($order['status']) {
                                                    'paid' => '已支付',
                                                    'unpaid' => '待支付',
                                                    'cancelled' => '已取消',
                                                    'refunded' => '已退款'
                                                } ?>
                                            </span>
                                            <span class="order-time"><?= timeAgo($order['create_time']) ?></span>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <script src="/assets/js/main.js"></script>
    
    <style>
        .user-center {
            padding-top: 100px;
            min-height: calc(100vh - 80px);
        }
        
        .user-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: var(--spacing-2xl);
        }
        
        .user-sidebar {
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        
        .user-info {
            text-align: center;
            padding: var(--spacing-xl);
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            margin-bottom: var(--spacing-lg);
        }
        
        .user-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            margin-bottom: var(--spacing-md);
        }
        
        .user-nav {
            background: var(--white);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
            padding: var(--spacing-md);
        }
        
        .user-nav a {
            display: block;
            padding: var(--spacing-md);
            color: var(--gray-700);
            border-radius: var(--radius-md);
            transition: all var(--transition-fast);
        }
        
        .user-nav a:hover,
        .user-nav a.active {
            background: var(--accent-light);
            color: var(--accent-dark);
        }
        
        .user-content {
            background: var(--white);
            padding: var(--spacing-2xl);
            border-radius: var(--radius-lg);
            box-shadow: var(--shadow-md);
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: var(--spacing-lg);
            margin-bottom: var(--spacing-2xl);
        }
        
        .stat-card {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
            padding: var(--spacing-lg);
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }
        
        .stat-icon {
            color: var(--accent-color);
        }
        
        .stat-number {
            display: block;
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--primary-color);
        }
        
        .stat-label {
            color: var(--gray-600);
        }
        
        .recent-section h2 {
            margin-bottom: var(--spacing-lg);
        }
        
        .empty-message {
            color: var(--gray-500);
            text-align: center;
            padding: var(--spacing-xl);
        }
        
        .orders-list {
            display: flex;
            flex-direction: column;
            gap: var(--spacing-md);
        }
        
        .order-item {
            display: flex;
            align-items: center;
            gap: var(--spacing-lg);
            padding: var(--spacing-md);
            background: var(--gray-50);
            border-radius: var(--radius-md);
        }
        
        .order-item img {
            width: 60px;
            height: 60px;
            object-fit: cover;
            border-radius: var(--radius-sm);
        }
        
        .order-info {
            flex: 1;
        }
        
        .order-info h4 {
            margin-bottom: var(--spacing-xs);
        }
        
        .order-info p {
            margin: 0;
            font-size: 0.9rem;
            color: var(--gray-600);
        }
        
        .status-badge {
            display: inline-block;
            padding: var(--spacing-xs) var(--spacing-sm);
            border-radius: var(--radius-sm);
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .status-paid {
            background: #d4edda;
            color: #155724;
        }
        
        .status-unpaid {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-cancelled {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-refunded {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        @media (max-width: 768px) {
            .user-layout {
                grid-template-columns: 1fr;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>
