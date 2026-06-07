
&lt;?php
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
        $this-&gt;userModel = new UserModel();
        $this-&gt;productModel = new ProductModel();
        $this-&gt;orderModel = new OrderModel();
        $this-&gt;categoryModel = new CategoryModel();
    }

    public function dashboard()
    {
        $orderStats = $this-&gt;orderModel-&gt;getStats();
        $stats = [
            'totalUsers' =&gt; $this-&gt;userModel-&gt;count(),
            'totalOrders' =&gt; $this-&gt;orderModel-&gt;count(),
            'totalProducts' =&gt; $this-&gt;productModel-&gt;count(),
            'totalRevenue' =&gt; $orderStats['total_revenue'] ?? 0,
            'todayRevenue' =&gt; $orderStats['today_revenue'] ?? 0,
            'pendingOrders' =&gt; $orderStats['pending_count'] ?? 0
        ];

        $topProducts = $this-&gt;productModel-&gt;getTopProducts(10);
        $recentOrders = $this-&gt;orderModel-&gt;getRecentOrders(10);
        $dailyOrderStats = $this-&gt;orderModel-&gt;getDailyOrderStats(7);

        require __DIR__ . '/../../views/admin/dashboard.phtml';
    }

    public function products()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $type = $_GET['type'] ?? '';
        $search = $_GET['search'] ?? '';

        $total = $this-&gt;productModel-&gt;searchAdminProductsCount($type, $search);
        $pagination = paginate($total, $page, 20);
        $products = $this-&gt;productModel-&gt;searchAdminProducts($type, $search, $page, 20);
        $categories = $this-&gt;categoryModel-&gt;getAllCategories();

        require __DIR__ . '/../../views/admin/products.phtml';
    }

    public function users()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $search = $_GET['search'] ?? '';

        $total = $this-&gt;userModel-&gt;searchAdminUsersCount($search);
        $pagination = paginate($total, $page, 20);
        $users = $this-&gt;userModel-&gt;searchAdminUsers($search, $page, 20);

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

            if ($action === 'toggle_status' &amp;&amp; $userId) {
                $user = $this-&gt;userModel-&gt;find($userId);

                if ($user) {
                    if ($user['role'] === 'admin') {
                        $_SESSION['error'] = '无法修改管理员状态';
                        redirect('/admin/users');
                    }
                    
                    $currentUserId = getCurrentUserId();
                    if ($userId == $currentUserId) {
                        $_SESSION['error'] = '无法修改自己的状态';
                        redirect('/admin/users');
                    }
                    
                    $this-&gt;userModel-&gt;toggleStatus($userId);
                }
            }
        }

        redirect('/admin/users');
    }

    public function orders()
    {
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
        $status = $_GET['status'] ?? '';

        $allowedStatus = ['pending', 'paid', 'completed', 'cancelled'];
        $filterStatus = (in_array($status, $allowedStatus)) ? $status : '';

        $total = $this-&gt;orderModel-&gt;searchAdminOrdersCount($filterStatus);
        $pagination = paginate($total, $page, 20);
        $orders = $this-&gt;orderModel-&gt;searchAdminOrders($filterStatus, $page, 20);

        require __DIR__ . '/../../views/admin/orders.phtml';
    }

    public function updateOrderStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            redirect('/admin/orders');
            return;
        }

        if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            $_SESSION['error'] = 'CSRF 验证失败';
            redirect('/admin/orders');
            return;
        }

        $action = $_POST['action'] ?? '';
        $orderId = (int)($_POST['order_id'] ?? 0);

        if ($action !== 'update_status' || $orderId &lt;= 0) {
            redirect('/admin/orders');
            return;
        }

        $newStatus = $_POST['status'] ?? '';
        $allowedStatus = ['pending', 'paid', 'completed', 'cancelled'];

        if (!in_array($newStatus, $allowedStatus)) {
            $_SESSION['error'] = '非法状态值';
            redirect('/admin/orders');
            return;
        }

        $this-&gt;orderModel-&gt;updateStatus($orderId, $newStatus);

        $currentStatus = $_GET['status'] ?? '';
        $redirectUrl = '/admin/orders';
        if ($currentStatus) {
            $redirectUrl .= '?status=' . urlencode($currentStatus);
        }

        redirect($redirectUrl);
    }

    public function stats()
    {
        $monthlyStats = $this-&gt;orderModel-&gt;getMonthlyStats(6);
        
        // 处理数据格式，确保有6个月的数据
        $labels = [];
        $revenueData = [];
        $orderData = [];
        
        for ($i = 5; $i &gt;= 0; $i--) {
            $month = date('Y-m', strtotime("first day of -$i month"));
            $labels[] = date('n月', strtotime($month . '-01'));
            
            $found = false;
            foreach ($monthlyStats as $stat) {
                if ($stat['month'] === $month) {
                    $revenueData[] = (float)$stat['revenue'];
                    $orderData[] = (int)$stat['order_count'];
                    $found = true;
                    break;
                }
            }
            
            if (!$found) {
                $revenueData[] = 0;
                $orderData[] = 0;
            }
        }
        
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
                if ($productId &lt;= 0 &amp;&amp; !in_array($type, ['novel', 'music'])) {
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

                if ($type === 'music' &amp;&amp; isset($_FILES['preview']) &amp;&amp; $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
                    $previewResult = uploadFile($_FILES['preview'], $uploadDir . 'preview/', ['mp3'], MAX_MUSIC_SIZE);
                    if ($previewResult['success']) {
                        $previewPath = $previewResult['filename'];
                    }
                }

                $data = [
                    'title' =&gt; $title,
                    'type' =&gt; $type,
                    'category_id' =&gt; $categoryId,
                    'author' =&gt; $author,
                    'description' =&gt; $description,
                    'cover' =&gt; $coverResult['filename'],
                    'file_path' =&gt; $fileResult['filename'],
                    'preview_path' =&gt; $previewPath,
                    'price' =&gt; $price
                ];
                $this-&gt;productModel-&gt;create($data);

                redirect('/admin/products?success=1');

            } catch (\Exception $e) {
                if ($coverResult &amp;&amp; isset($coverResult['filename'])) {
                    @unlink($uploadDir . 'cover/' . $coverResult['filename']);
                }
                if ($fileResult &amp;&amp; isset($fileResult['filename']) &amp;&amp; $fileDir) {
                    @unlink($uploadDir . $fileDir . $fileResult['filename']);
                }
                if ($previewPath) {
                    @unlink($uploadDir . 'preview/' . $previewPath);
                }

                $_SESSION['error'] = '添加商品失败: ' . $e-&gt;getMessage();
                redirect('/admin/products');
            }
        }
    }

    public function toggleProductStatus()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'CSRF 验证失败';
                redirect('/admin/products');
                return;
            } else {
                $productId = (int)($_POST['product_id'] ?? 0);
                if ($productId &gt; 0) {
                    $this-&gt;productModel-&gt;toggleStatus($productId);
                    $_SESSION['success'] = '商品状态更新成功！';
                }
            }
        }
        redirect('/admin/products');
    }

    public function deleteProduct()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'CSRF 验证失败';
                redirect('/admin/products');
                return;
            } else {
                $productId = (int)($_POST['product_id'] ?? 0);
                $product = $this-&gt;productModel-&gt;find($productId);
                
                if ($product) {
                    try {
                        $this-&gt;productModel-&gt;delete($productId);
                        
                        $uploadDir = UPLOAD_PATH;
                        $fileDir = $product['type'] === 'novel' ? 'novels/' : 'music/';
                        
                        @unlink($uploadDir . 'cover/' . $product['cover']);
                        @unlink($uploadDir . $fileDir . $product['file_path']);
                        if (!empty($product['preview_path'])) {
                            @unlink($uploadDir . 'preview/' . $product['preview_path']);
                        }
                        
                        $_SESSION['success'] = '商品及对应文件已成功删除！';
                    } catch (\PDOException $e) {
                        $_SESSION['error'] = '该商品已有相关订单或下载记录，无法直接删除。建议使用【下架】功能。';
                    }
                }
            }
        }
        redirect('/admin/products');
    }

    public function editProduct($id)
    {
        $product = $this-&gt;productModel-&gt;find((int)$id);
        if (!$product) {
            $_SESSION['error'] = '商品不存在';
            redirect('/admin/products');
            return;
        }
        
        $categories = $this-&gt;categoryModel-&gt;getAllCategories();
        require __DIR__ . '/../../views/admin/product_edit.phtml';
    }

    public function updateProduct($id)
    {
        $productId = (int)$id;
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'CSRF 验证失败';
                redirect("/admin/products/edit/{$productId}");
                return;
            } else {
                $data = [
                    'title' =&gt; trim($_POST['title'] ?? ''),
                    'author' =&gt; trim($_POST['author'] ?? ''),
                    'category_id' =&gt; (int)($_POST['category_id'] ?? 0),
                    'price' =&gt; (float)($_POST['price'] ?? 0),
                    'description' =&gt; trim($_POST['description'] ?? '')
                ];
                
                if ($this-&gt;productModel-&gt;update($productId, $data)) {
                    $_SESSION['success'] = '商品信息修改成功！';
                    redirect('/admin/products');
                    return;
                } else {
                    $_SESSION['error'] = '修改失败，请重试。';
                    redirect("/admin/products/edit/{$productId}");
                    return;
                }
            }
        }
        redirect("/admin/products/edit/{$productId}");
    }
}
