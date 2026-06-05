<?php
$pageTitle = '仪表盘';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$stats = [
    'totalUsers' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'totalOrders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'totalProducts' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'totalRevenue' => $db->query("SELECT COALESCE(SUM(price), 0) FROM orders WHERE status = 'paid'")->fetchColumn(),
    'todayRevenue' => $db->query("SELECT COALESCE(SUM(price), 0) FROM orders WHERE status = 'paid' AND DATE(create_time) = CURDATE()")->fetchColumn(),
    'pendingOrders' => $db->query("SELECT COUNT(*) FROM orders WHERE status = 'pending'")->fetchColumn()
];

$stmt = $db->query("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.sales DESC LIMIT 10");
$topProducts = $stmt->fetchAll();

$stmt = $db->query("SELECT o.*, p.title, u.username FROM orders o LEFT JOIN products p ON o.product_id = p.id LEFT JOIN users u ON o.user_id = u.id ORDER BY o.create_time DESC LIMIT 10");
$recentOrders = $stmt->fetchAll();

$stmt = $db->query("SELECT DATE(create_time) as date, COUNT(*) as count FROM orders WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(create_time) ORDER BY date");
$orderStats = $stmt->fetchAll();
?>
<?php include 'header.php'; ?>

<div class="page-header">
    <h1 class="page-title">仪表盘</h1>
    <p class="page-description">欢迎回来，<?= e($_SESSION['username']) ?></p>
</div>

<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['totalUsers']) ?></div>
            <div class="stat-label">总用户数</div>
        </div>
    </div>

    <div class="stat-card stat-success">
        <div class="stat-icon">
            <i class="bi bi-currency-dollar"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= formatPrice($stats['totalRevenue']) ?></div>
            <div class="stat-label">总销售额</div>
        </div>
    </div>

    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <i class="bi bi-box-seam"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['totalProducts']) ?></div>
            <div class="stat-label">商品总数</div>
        </div>
    </div>

    <div class="stat-card stat-info">
        <div class="stat-icon">
            <i class="bi bi-cart-check"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['pendingOrders']) ?></div>
            <div class="stat-label">待处理订单</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">销售趋势（近7天）</h5>
            </div>
            <div class="card-body">
                <canvas id="salesChart" height="300"></canvas>
            </div>
        </div>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">最新订单</h5>
            </div>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>订单号</th>
                            <th>用户</th>
                            <th>商品</th>
                            <th>金额</th>
                            <th>状态</th>
                            <th>时间</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><?= e($order['order_no']) ?></td>
                                <td><?= e($order['username'] ?? '未知') ?></td>
                                <td><?= e($order['title'] ?? '未知商品') ?></td>
                                <td><?= formatPrice($order['price']) ?></td>
                                <td>
                                    <span class="badge bg-<?= getOrderStatusBadge($order['status']) ?>">
                                        <?= getOrderStatusText($order['status']) ?>
                                    </span>
                                </td>
                                <td><?= timeAgo($order['create_time']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">热销商品 Top10</h5>
            </div>
            <div class="card-body">
                <div class="product-list">
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="product-item">
                            <span class="product-rank"><?= $index + 1 ?></span>
                            <div class="product-info">
                                <div class="product-name"><?= e($product['title']) ?></div>
                                <div class="product-meta">
                                    <span><?= e($product['category_name'] ?? '未分类') ?></span>
                                    <span>销量: <?= $product['sales'] ?></span>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const ctx = document.getElementById('salesChart');
if (ctx) {
    const labels = [];
    const data = [];
    <?php foreach ($orderStats as $stat): ?>
        labels.push('<?= substr($stat['date'], 5) ?>');
        data.push(<?= $stat['count'] ?>);
    <?php endforeach; ?>
    
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: labels,
            datasets: [{
                label: '订单数',
                data: data,
                borderColor: '#0d6efd',
                backgroundColor: 'rgba(13, 110, 253, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });
}
</script>

<?php include 'footer.php'; ?>
