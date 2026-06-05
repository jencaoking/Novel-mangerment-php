<?php
$pageTitle = '商品管理';
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$type = $_GET['type'] ?? '';
$search = $_GET['search'] ?? '';

$where = '1=1';
$params = [];

if ($type) {
    $where .= ' AND p.type = ?';
    $params[] = $type;
}

if ($search) {
    $where .= ' AND (p.title LIKE ? OR p.author LIKE ?)';
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE $where");
$countStmt->execute($params);
$total = $countStmt->fetchColumn();

$pagination = paginate($total, $page, 20);

$stmt = $db->prepare("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $where ORDER BY p.create_time DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
$stmt->execute($params);
$products = $stmt->fetchAll();

$stmt = $db->query("SELECT * FROM categories ORDER BY type, sort_order");
$categories = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // CSRF 验证
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        die('CSRF 验证失败');
    }

    if ($action === 'delete' && isset($_POST['product_id'])) {
        $productId = (int)$_POST['product_id'];

        // 1. 查询文件路径
        $stmt = $db->prepare("SELECT cover, file_path, preview_path, type FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            // 2. 删除物理文件
            $baseDir = UPLOAD_PATH;
            if ($product['cover']) @unlink($baseDir . 'cover/' . $product['cover']);
            if ($product['file_path']) @unlink($baseDir . ($product['type'] === 'novel' ? 'novels/' : 'music/') . $product['file_path']);
            if ($product['preview_path']) @unlink($baseDir . 'preview/' . $product['preview_path']);

            // 3. 删除数据库记录
            $stmt = $db->prepare("DELETE FROM products WHERE id = ?");
            $stmt->execute([$productId]);
        }
        redirect('products.php');
    }

    if ($action === 'toggle_status' && isset($_POST['product_id'])) {
        $productId = (int)$_POST['product_id'];
        $stmt = $db->prepare("SELECT status FROM products WHERE id = ?");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if ($product) {
            $newStatus = $product['status'] == 1 ? 0 : 1;
            $stmt = $db->prepare("UPDATE products SET status = ? WHERE id = ?");
            $stmt->execute([$newStatus, $productId]);
        }
        redirect('products.php?page=' . $page);
    }
}
?>
<?php include 'header.php'; ?>

<div class="page-header">
    <h1 class="page-title">商品管理</h1>
    <p class="page-description">管理小说和音乐商品</p>
</div>

<div class="card">
    <div class="card-header">
        <div class="row align-items-center">
            <div class="col">
                <h5 class="card-title mb-0">商品列表</h5>
            </div>
            <div class="col-auto">
                <div class="d-flex gap-2">
                    <form method="GET" class="search-form">
                        <div class="input-group">
                            <select name="type" class="form-select" style="width: auto;">
                                <option value="">全部类型</option>
                                <option value="novel" <?= $type === 'novel' ? 'selected' : '' ?>>小说</option>
                                <option value="music" <?= $type === 'music' ? 'selected' : '' ?>>音乐</option>
                            </select>
                            <input type="text" name="search" class="form-control" placeholder="搜索商品..." value="<?= e($search) ?>" style="width: 200px;">
                            <button class="btn btn-primary" type="submit">
                                <i class="bi bi-search"></i>
                            </button>
                        </div>
                    </form>
                    <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#addProductModal">
                        <i class="bi bi-plus-circle"></i> 添加商品
                    </button>
                </div>
            </div>
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>封面</th>
                    <th>标题</th>
                    <th>类型</th>
                    <th>分类</th>
                    <th>作者</th>
                    <th>价格</th>
                    <th>销量</th>
                    <th>状态</th>
                    <th>操作</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($products as $product): ?>
                    <tr>
                        <td><?= $product['id'] ?></td>
                        <td>
                            <img src="/uploads/cover/<?= e($product['cover']) ?>" class="product-thumb" alt="<?= e($product['title']) ?>">
                        </td>
                        <td><?= e($product['title']) ?></td>
                        <td>
                            <span class="badge bg-<?= $product['type'] === 'novel' ? 'info' : 'primary' ?>">
                                <?= getProductTypeText($product['type']) ?>
                            </span>
                        </td>
                        <td><?= e($product['category_name'] ?? '未分类') ?></td>
                        <td><?= e($product['author']) ?></td>
                        <td><?= formatPrice($product['price']) ?></td>
                        <td><?= $product['sales'] ?></td>
                        <td>
                            <span class="badge bg-<?= $product['status'] == 1 ? 'success' : 'secondary' ?>">
                                <?= $product['status'] == 1 ? '上架' : '下架' ?>
                            </span>
                        </td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="toggle_status">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-warning">
                                    <i class="bi bi-toggle-<?= $product['status'] == 1 ? 'off' : 'on' ?>"></i>
                                </button>
                            </form>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('确定要删除吗？');">
                                <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="product_id" value="<?= $product['id'] ?>">
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
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

<div class="modal fade" id="addProductModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">添加商品</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form action="upload.php" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?= generateCSRFToken() ?>">
                    <input type="hidden" name="action" value="add_product">
                    <div class="mb-3">
                        <label class="form-label">商品类型</label>
                        <select name="type" class="form-select" required>
                            <option value="novel">小说</option>
                            <option value="music">音乐</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">商品标题</label>
                        <input type="text" name="title" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">分类</label>
                        <select name="category_id" class="form-select" required>
                            <?php 
                            $groupedCategories = [];
                            foreach ($categories as $cat) {
                                $groupedCategories[$cat['type']][] = $cat;
                            }
                            foreach ($groupedCategories as $type => $cats): 
                            ?>
                                <optgroup label="<?= getProductTypeText($type) ?>">
                                    <?php foreach ($cats as $cat): ?>
                                        <option value="<?= $cat['id'] ?>"><?= e($cat['name']) ?></option>
                                    <?php endforeach; ?>
                                </optgroup>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">作者/歌手</label>
                        <input type="text" name="author" class="form-control" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">描述</label>
                        <textarea name="description" class="form-control" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">价格</label>
                        <input type="number" name="price" class="form-control" step="0.01" min="0" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">封面图片</label>
                        <input type="file" name="cover" class="form-control" accept="image/*" required>
                    </div>
                    <div class="mb-3" id="fileField">
                        <label class="form-label">文件（TXT/MP3）</label>
                        <input type="file" name="file" class="form-control" required>
                    </div>
                    <div class="mb-3" id="previewField" style="display: none;">
                        <label class="form-label">试听片段（MP3）</label>
                        <input type="file" name="preview" class="form-control" accept=".mp3">
                    </div>
                    <button type="submit" class="btn btn-primary">添加</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
document.querySelector('[name="type"]').addEventListener('change', function() {
    const previewField = document.getElementById('previewField');
    const fileInput = document.querySelector('[name="file"]');
    if (this.value === 'music') {
        previewField.style.display = 'block';
        fileInput.accept = '.mp3,.wav,.ogg';
    } else {
        previewField.style.display = 'none';
        fileInput.accept = '.txt';
    }
});
</script>

<?php include 'footer.php'; ?>
