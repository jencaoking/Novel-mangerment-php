<?php
namespace App\Controllers;

class AdminController
{
    public function __construct()
    {
        requireAdmin();
    }

    public function dashboard()
    {
        global $db;

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

        require __DIR__ . '/../../views/admin/dashboard.phtml';
    }

    public function products()
    {
        global $db;

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

        require __DIR__ . '/../../views/admin/products.phtml';
    }

    public function users()
    {
        global $db;

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

        require __DIR__ . '/../../views/admin/users.phtml';
    }

    public function toggleUserStatus()
    {
        global $db;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $userId = $_POST['user_id'] ?? 0;

            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                die('CSRF 验证失败');
            }

            if ($action === 'toggle_status' && $userId) {
                $stmt = $db->prepare("SELECT role, status FROM users WHERE id = ?");
                $stmt->execute([$userId]);
                $user = $stmt->fetch();

                if ($user) {
                    if ($user['role'] === 'admin') {
                        die('无法修改管理员状态');
                    }
                    $newStatus = $user['status'] == 1 ? 0 : 1;
                    $stmt = $db->prepare("UPDATE users SET status = ? WHERE id = ?");
                    $stmt->execute([$newStatus, $userId]);
                }
            }
        }

        redirect('/admin/users');
    }

    public function orders()
    {
        global $db;

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

        $stmt = $db->prepare("SELECT o.*, p.title as product_title, u.username, u.email FROM orders o LEFT JOIN products p ON o.product_id = p.id LEFT JOIN users u ON o.user_id = u.id WHERE $where ORDER BY o.create_time DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}");
        $stmt->execute($params);
        $orders = $stmt->fetchAll();

        require __DIR__ . '/../../views/admin/orders.phtml';
    }

    public function stats()
    {
        global $db;

        require __DIR__ . '/../../views/admin/stats.phtml';
    }

    public function upload()
    {
        // 只接受 POST 请求
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/products');
            return;
        }

        // 1. CSRF 校验
        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            die('非法请求：CSRF Token 无效');
        }

        global $db;
        $action = $_POST['action'] ?? '';

        // 2. 处理添加商品
        if ($action === 'add_product') {
            $type = $_POST['type'] ?? '';
            $title = $_POST['title'] ?? '';
            $categoryId = (int)($_POST['category_id'] ?? 0);
            $author = $_POST['author'] ?? '';
            $description = $_POST['description'] ?? '';
            $price = (float)($_POST['price'] ?? 0);

            $uploadDir = UPLOAD_PATH;
            $coverResult = null;
            $fileResult = null;
            $previewPath = null;

            try {
                // 3. 上传封面图片
                $coverResult = uploadFile($_FILES['cover'], $uploadDir . 'cover/', ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
                if (!$coverResult['success']) {
                    throw new \Exception('封面上传失败: ' . $coverResult['message']);
                }

                // 4. 上传商品文件（小说或音乐）
                $fileDir = $type === 'novel' ? 'novels/' : 'music/';
                $fileTypes = $type === 'novel' ? ALLOWED_NOVEL_TYPES : ALLOWED_MUSIC_TYPES;
                $maxSize = $type === 'novel' ? MAX_NOVEL_SIZE : MAX_MUSIC_SIZE;

                $fileResult = uploadFile($_FILES['file'], $uploadDir . $fileDir, $fileTypes, $maxSize);
                if (!$fileResult['success']) {
                    throw new \Exception('文件上传失败: ' . $fileResult['message']);
                }

                // 5. 上传预览文件（仅音乐类型）
                if ($type === 'music' && isset($_FILES['preview']) && $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
                    $previewResult = uploadFile($_FILES['preview'], $uploadDir . 'preview/', ['mp3'], MAX_MUSIC_SIZE);
                    if ($previewResult['success']) {
                        $previewPath = $previewResult['filename'];
                    }
                }

                // 6. 插入数据库
                $stmt = $db->prepare("
                    INSERT INTO products (title, type, category_id, author, description, cover, file_path, preview_path, price) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $title,
                    $type,
                    $categoryId,
                    $author,
                    $description,
                    $coverResult['filename'],
                    $fileResult['filename'],
                    $previewPath,
                    $price
                ]);

                // 7. 成功重定向
                redirect('/admin/products?success=1');

            } catch (\Exception $e) {
                // 8. 发生错误时，清理已上传的文件（防止垃圾文件残留）
                if ($coverResult && isset($coverResult['filename'])) {
                    @unlink($uploadDir . 'cover/' . $coverResult['filename']);
                }
                if ($fileResult && isset($fileResult['filename'])) {
                    @unlink($uploadDir . $fileDir . $fileResult['filename']);
                }
                if ($previewPath) {
                    @unlink($uploadDir . 'preview/' . $previewPath);
                }

                // 输出错误信息（建议后续将错误存入 Session 后重定向）
                die('添加商品失败: ' . $e->getMessage());
            }
        }

        // 其他 action 或默认重定向
        redirect('/admin/products');
    }
}
