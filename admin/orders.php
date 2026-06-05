<?php
$pageTitle = '订单管理';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$status = $_GET['status'] ?? '';

$where = '1=1';
$params = [];

if ($status) {
    $where .= ' AND o.status = ?';
    $params[] = $status;
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM orders o WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $page, 20);

$stmt = $db->prepare("SELECT o.*, p.title as product_title, u.username, u.email FROM orders o LEFT JOIN products p ON o.product_id = p.id LEFT JOIN users u ON o.user_id = u.id WHERE $where ORDER BY o.create_time DESC LIMIT ? OFFSET ?");
$params[] = $pagination['per_page'];
$params[] = $pagination['offset'];
$stmt->execute($params);
$orders = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $orderId = $_POST['order_id'] ?? 0;
    
    if ($action === 'update_status' && $orderId) {
        $newStatus = $_POST['status'] ?? '';
        $stmt = $db->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->execute([$newStatus, $orderId]);
        redirect('orders.php?page=' . $page . ($status ? '&status=' . $status : ''));
    }
}
?>
<?php include 'header.php'; ?>

<div class="page-header">
    <h1 class="page-title">订单管理</h1>
    <p class="page-description">管理所有订单</p>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="card-title mb-0">订单列表</h5>
            </div>
            <div class="col-auto">
                <form method="GET" class="search-form">
                    <div class="input-group">
                        <select name="status" class="form-select">
                            <option value="">全部状态</option>
                            <option value="pending" <?= $status === 'pending' ? 'selected' : '' ?>>待支付</option>
                            <option value="paid" <?= $status === 'paid' ? 'selected' : '' ?>>已支付</option>
                            <option value="completed" <?= $status === 'completed' ? 'selected' : '' ?>>已完成</option>
                            <option value="cancelled" <?= $status === 'cancelled' ? 'selected' : '' ?>>已取消</option>
                        </select>
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-filter"></i> 筛选
                        </button>
                    </div>
                </form>
            </div>
        </div>
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
                    <th>创建时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?= e($order['order_no']) ?></td>
                        <td>
                            <div><?= e($order['username'] ?? '未知') ?></div>
                            <small class="text-muted"><?= e($order['email'] ?? '') ?></small>
                        </td>
                        <td><?= e($order['product_title'] ?? '未知商品') ?></td>
                        <td><?= formatPrice($order['price']) ?></td>
                        <td>
                            <span class="badge bg-<?= getOrderStatusBadge($order['status']) ?>">
                                <?= getOrderStatusText($order['status']) ?>
                            </span>
                        </td>
                        <td><?= e($order['create_time']) ?></td>
                        <td>
                            <form method="POST" class="d-inline">
                                <input type="hidden" name="action" value="update_status">
                                <input type="hidden" name="order_id" value="<?= $order['id'] ?>">
                                <select name="status" class="form-select form-select-sm d-inline" style="width: auto;" onchange="this.form.submit()">
                                    <option value="pending" <?= $order['status'] === 'pending' ? 'selected' : '' ?>>待支付</option>
                                    <option value="paid" <?= $order['status'] === 'paid' ? 'selected' : '' ?>>已支付</option>
                                    <option value="completed" <?= $order['status'] === 'completed' ? 'selected' : '' ?>>已完成</option>
                                    <option value="cancelled" <?= $order['status'] === 'cancelled' ? 'selected' : '' ?>>已取消</option>
                                </select>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php if ($pagination['total_pages'] > 1): ?>
        <div class="card-footer">
            <nav>
                <ul class="pagination justify-content-center mb-0">
                    <?php if ($pagination['has_previous']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $pagination['current_page'] - 1 ?><?= $status ? '&status=' . $status : '' ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                    
                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <a class="page-link" href="?page=<?= $i ?><?= $status ? '&status=' . $status : '' ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>
                    
                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?= $pagination['current_page'] + 1 ?><?= $status ? '&status=' . $status : '' ?>">
                                <i class="bi bi-chevron-right"></i>
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>
            </nav>
        </div>
    <?php endif; ?>
</div>

<?php include 'footer.php'; ?>
