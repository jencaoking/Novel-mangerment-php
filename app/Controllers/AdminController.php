<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\ProductModel;
use App\Models\OrderModel;
use App\Models\CategoryModel;

class AdminController
{
    const PAID_STATUSES = ['paid', 'completed'];

    protected $userModel;
    protected $productModel;
    protected $orderModel;
    protected $categoryModel;

    public function __construct()
    {
        requireAdmin();
        $this->userModel = new UserModel();
        $this->productModel = new ProductModel();
        $this->orderModel = new OrderModel();
        $this->categoryModel = new CategoryModel();
    }

    public function dashboard()
    {
        $orderStats = $this->orderModel->getStats();
        $stats = [
            'totalUsers' => $this->userModel->count(),
            'totalOrders' => $this->orderModel->count(),
            'totalProducts' => $this->productModel->count(),
            'totalRevenue' => $orderStats['total_revenue'] ?? 0,
            'todayRevenue' => $orderStats['today_revenue'] ?? 0,
            'pendingOrders' => $orderStats['pending_count'] ?? 0
        ];

        $topProducts = $this->productModel->fetchAll("SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id ORDER BY p.sales DESC LIMIT 10");
        $recentOrders = $this->orderModel->fetchAll("SELECT o.*, p.title, u.username FROM orders o LEFT JOIN products p ON o.product_id = p.id LEFT JOIN users u ON o.user_id = u.id ORDER BY o.create_time DESC LIMIT 10");
        $dailyOrderStats = $this->orderModel->fetchAll("SELECT DATE(create_time) as date, COUNT(*) as count FROM orders WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) GROUP BY DATE(create_time) ORDER BY date");

        require __DIR__ . '/../../views/admin/dashboard.phtml';
    }

    public function products()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $type = $_GET['type'] ?? '';
        $search = $_GET['search'] ?? '';

        $where = 'p.status = 1';
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

        $countSql = "SELECT COUNT(*) FROM products p WHERE $where";
        $total = $this->productModel->fetch($countSql, $params)['COUNT(*)'];

        $pagination = paginate($total, $page, 20);

        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE $where ORDER BY p.create_time DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
        $products = $this->productModel->fetchAll($sql, $params);
        $categories = $this->categoryModel->getAllCategories();

        require __DIR__ . '/../../views/admin/products.phtml';
    }

    public function users()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $search = $_GET['search'] ?? '';

        $where = '1=1';
        $params = [];

        if ($search) {
            $where .= ' AND (username LIKE ? OR email LIKE ?)';
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $countSql = "SELECT COUNT(*) FROM users WHERE $where";
        $total = $this->userModel->fetch($countSql, $params)['COUNT(*)'];

        $pagination = paginate($total, $page, 20);

        $sql = "SELECT * FROM users WHERE $where ORDER BY create_time DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
        $users = $this->userModel->fetchAll($sql, $params);

        require __DIR__ . '/../../views/admin/users.phtml';
    }

    public function toggleUserStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $action = $_POST['action'] ?? '';
            $userId = $_POST['user_id'] ?? 0;

            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'CSRF 验证失败';
                redirect('/admin/users');
            }

            if ($action === 'toggle_status' && $userId) {
                $user = $this->userModel->find($userId);

                if ($user) {
                    if ($user['role'] === 'admin') {
                        $_SESSION['error'] = '无法修改管理员状态';
                        redirect('/admin/users');
                    }
                    $this->userModel->toggleStatus($userId);
                }
            }
        }

        redirect('/admin/users');
    }

    public function orders()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $status = $_GET['status'] ?? '';

        $where = '1=1';
        $params = [];

        $allowedStatus = ['pending', 'paid', 'completed', 'cancelled'];
        if ($status && in_array($status, $allowedStatus)) {
            $where .= ' AND o.status = ?';
            $params[] = $status;
        }

        $countSql = "SELECT COUNT(*) FROM orders o WHERE $where";
        $total = $this->orderModel->fetch($countSql, $params)['COUNT(*)'];

        $pagination = paginate($total, $page, 20);

        $sql = "SELECT o.*, p.title as product_title, u.username, u.email FROM orders o LEFT JOIN products p ON o.product_id = p.id LEFT JOIN users u ON o.user_id = u.id WHERE $where ORDER BY o.create_time DESC LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
        $orders = $this->orderModel->fetchAll($sql, $params);

        require __DIR__ . '/../../views/admin/orders.phtml';
    }

    public function stats()
    {
        require __DIR__ . '/../../views/admin/stats.phtml';
    }

    public function upload()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/products');
            return;
        }

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            die('非法请求：CSRF Token 无效');
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add_product') {
            $productId = (int)($_POST['product_id'] ?? 0);
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
            $fileDir = '';

            try {
                if ($productId <= 0 && !in_array($type, ['novel', 'music'])) {
                    throw new \Exception('非法类型');
                }

                if (empty($_FILES['cover']['tmp_name']) || $_FILES['cover']['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception('缺少封面图片');
                }

                if (empty($_FILES['file']['tmp_name']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
                    throw new \Exception('缺少商品文件');
                }

                $coverResult = uploadFile($_FILES['cover'], $uploadDir . 'cover/', ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
                if (!$coverResult['success']) {
                    throw new \Exception('封面上传失败: ' . $coverResult['message']);
                }

                $fileDir = $type === 'novel' ? 'novels/' : 'music/';
                $fileTypes = $type === 'novel' ? ALLOWED_NOVEL_TYPES : ALLOWED_MUSIC_TYPES;
                $maxSize = $type === 'novel' ? MAX_NOVEL_SIZE : MAX_MUSIC_SIZE;

                $fileResult = uploadFile($_FILES['file'], $uploadDir . $fileDir, $fileTypes, $maxSize);
                if (!$fileResult['success']) {
                    throw new \Exception('文件上传失败: ' . $fileResult['message']);
                }

                if ($type === 'music' && isset($_FILES['preview']) && $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
                    $previewResult = uploadFile($_FILES['preview'], $uploadDir . 'preview/', ['mp3'], MAX_MUSIC_SIZE);
                    if ($previewResult['success']) {
                        $previewPath = $previewResult['filename'];
                    }
                }

                $data = [
                    'title' => $title,
                    'type' => $type,
                    'category_id' => $categoryId,
                    'author' => $author,
                    'description' => $description,
                    'cover' => $coverResult['filename'],
                    'file_path' => $fileResult['filename'],
                    'preview_path' => $previewPath,
                    'price' => $price
                ];
                $this->productModel->create($data);

                redirect('/admin/products?success=1');

            } catch (\Exception $e) {
                if ($coverResult && isset($coverResult['filename'])) {
                    @unlink($uploadDir . 'cover/' . $coverResult['filename']);
                }
                if ($fileResult && isset($fileResult['filename']) && $fileDir) {
                    @unlink($uploadDir . $fileDir . $fileResult['filename']);
                }
                if ($previewPath) {
                    @unlink($uploadDir . 'preview/' . $previewPath);
                }

                $_SESSION['error'] = '添加商品失败: ' . $e->getMessage();
                redirect('/admin/products');
            }
        }

        redirect('/admin/products');
    }
}