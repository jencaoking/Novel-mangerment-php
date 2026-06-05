<?php
$pageTitle = '用户管理';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';

$where = '1=1';
$params = [];

if ($search) {
    $where .= ' AND (username LIKE ? OR email LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM users WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $page, 20);

$stmt = $db->prepare("SELECT * FROM users WHERE $where ORDER BY create_time DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$users = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $userId = $_POST['user_id'] ?? 0;

    // CSRF 验证
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('CSRF 验证失败');
    }

    if ($action === 'toggle_status' && $userId) {
        $stmt = $db->prepare("SELECT role, status FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if ($user) {
            // 禁止修改管理员状态
            if ($user['role'] === 'admin') {
                die('无法修改管理员状态');
            }
            $newStatus = $user['status'] == 1 ? 0 : 1;
            $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $userId]);
        }
        redirect('users.php?page=' . $page . ($search ? '&search=' . urlencode($search) : ''));
    }
}
?>
<?php include 'header.php'; ?>

<div class="page-header">
    <h1 class="page-title">用户管理</h1>
    <p class="page-description">管理平台所有用户</p>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="card-title mb-0">用户列表</h5>
            </div>
            <div class="col-auto">
                <form method="GET" class="search-form">
                    <div class="input-group">
                        <input type="text" name="search" class="form-control" placeholder="搜索用户名或邮箱..." value="<?= e($search) ?>">
                        <button class="btn btn-primary" type="submit">
                            <i class="bi bi-search"></i>
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
                    <th>ID</th>
                    <th>用户名</th>
                    <th>邮箱</th>
                    <th>角色</th>
                    <th>状态</th>
                    <th>注册时间</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                    <tr>
                        <td><?= $user['id'] ?></td>
                        <td><?= e($user['username']) ?></td>
                        <td><?= e($user['email']) ?></td>
                        <td>
                            <span class="badge bg-<?= $user['role'] === 'admin' ? 'primary' : 'secondary' ?>">
                                <?= $user['role'] === 'admin' ? '管理员' : '用户' ?>
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-<?= getUserStatusBadge($user['status']) ?>">
                                <?= getUserStatusText($user['status']) ?>
                            </span>
                        </td>
                        <td><?= e($user['create_time']) ?></td>
                        <td>
                            <?php if ($user['role'] !== 'admin'): ?>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                    <input type="hidden" name="action" value="toggle_status">
                                    <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-warning">
                                        <i class="bi bi-toggle-<?= $user['status'] == 1 ? 'off' : 'on' ?>"></i>
                                        <?= $user['status'] == 1 ? '禁用' : '启用' ?>
                                    </button>
                                </form>
                            <?php endif; ?>
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
                            <?php $queryParams = $_GET; $queryParams['page'] = $pagination['current_page'] - 1; ?>
                            <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                <i class="bi bi-chevron-left"></i>
                            </a>
                        </li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                        <li class="page-item <?= $i === $pagination['current_page'] ? 'active' : '' ?>">
                            <?php $queryParams = $_GET; $queryParams['page'] = $i; ?>
                            <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
                                <?= $i ?>
                            </a>
                        </li>
                    <?php endfor; ?>

                    <?php if ($pagination['has_next']): ?>
                        <li class="page-item">
                            <?php $queryParams = $_GET; $queryParams['page'] = $pagination['current_page'] + 1; ?>
                            <a class="page-link" href="?<?= http_build_query($queryParams) ?>">
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
