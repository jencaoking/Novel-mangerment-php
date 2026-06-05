<?php
// ================================================
// DEPRECATED - 此文件已废弃
// 请使用 MVC 路由: /admin/stats
// ================================================
$pageTitle = '数据统计';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$stats = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'total_products' => $db->query("SELECT COUNT(*) FROM products")->fetchColumn(),
    'total_orders' => $db->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'total_revenue' => $db->query("SELECT COALESCE(SUM(price), 0) FROM orders WHERE status IN ('paid', 'completed')")->fetchColumn(),
    'today_orders' => $db->query("SELECT COUNT(*) FROM orders WHERE DATE(create_time) = CURDATE()")->fetchColumn(),
    'today_revenue' => $db->query("SELECT COALESCE(SUM(price), 0) FROM orders WHERE status IN ('paid', 'completed') AND DATE(create_time) = CURDATE()")->fetchColumn(),
];

$stmt = $db->query("SELECT DATE(create_time) as date, COUNT(*) as count FROM orders WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) GROUP BY DATE(create_time) ORDER BY date");
$orderTrend = $stmt->fetchAll();

$stmt = $db->query("SELECT p.title, p.sales, p.downloads FROM products p ORDER BY p.sales DESC LIMIT 10");
$topProducts = $stmt->fetchAll();

$chartLabels = array_column($orderTrend, 'date');
$chartData = array_column($orderTrend, 'count');
?>
<?php include 'header.php'; ?>

<div class="page-header">
    <h1 class="page-title">数据统计</h1>
    <p class="page-description">查看平台运营数据</p>
</div>

<div class="stats-grid">
    <div class="stat-card stat-primary">
        <div class="stat-icon">
            <i class="bi bi-people-fill"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['total_users']) ?></div>
            <div class="stat-label">总用户数</div>
        </div>
    </div>
    <div class="stat-card stat-success">
        <div class="stat-icon">
            <i class="bi bi-cash-stack"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= formatPrice($stats['total_revenue']) ?></div>
            <div class="stat-label">总销售额</div>
        </div>
    </div>
    <div class="stat-card stat-info">
        <div class="stat-icon">
            <i class="bi bi-cart3"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['total_orders']) ?></div>
            <div class="stat-label">总订单数</div>
        </div>
    </div>
    <div class="stat-card stat-warning">
        <div class="stat-icon">
            <i class="bi bi-box-seam"></i>
        </div>
        <div class="stat-content">
            <div class="stat-value"><?= number_format($stats['total_products']) ?></div>
            <div class="stat-label">商品总数</div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">订单趋势（近30天）</h5>
            </div>
            <div class="card-body">
                <canvas id="orderTrendChart" height="300"></canvas>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">今日数据</h5>
            </div>
            <div class="card-body">
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        今日订单
                        <span class="badge bg-primary rounded-pill"><?= number_format($stats['today_orders']) ?></span>
                    </div>
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        今日收入
                        <span class="badge bg-success rounded-pill"><?= formatPrice($stats['today_revenue']) ?></span>
                    </div>
                </div>
            </div>
        </div>
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="card-title">热销商品</h5>
            </div>
            <div class="card-body">
                <div class="product-list">
                    <?php foreach ($topProducts as $index => $product): ?>
                        <div class="product-item">
                            <span class="product-rank"><?= $index + 1 ?></span>
                            <div class="product-info">
                                <div class="product-name"><?= e($product['title']) ?></div>
                                <div class="product-meta">
                                    <span>销量: <?= $product['sales'] ?></span>
                                    <span>下载: <?= $product['downloads'] ?></span>
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
const orderCtx = document.getElementById('orderTrendChart');
if (orderCtx) {
    const labels = <?= json_encode(array_map(function($d) { return substr($d, 5); }, $chartLabels)) ?>;
    const data = <?= json_encode($chartData) ?>;

    new Chart(orderCtx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: '订单数',
                data: data,
                backgroundColor: 'rgba(13, 110, 253, 0.6)',
                borderColor: 'rgba(13, 110, 253, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
}
</script>

<?php include 'footer.php'; ?>