<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Models\OrderModel;

class ProductController {
    
    protected $productModel;
    protected $categoryModel;
    protected $orderModel;

    public function __construct() {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->orderModel = new OrderModel();
    }
    
    public function novels() {
        $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $sort = $_GET['sort'] ?? 'latest';
        $search = trim($_GET['search'] ?? '');
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        $where = "p.type = 'novel' AND p.status = 1";
        $params = [];

        if ($category > 0) {
            $where .= " AND p.category_id = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            $where .= " AND (p.title LIKE ? OR p.author LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $orderBy = match($sort) {
            'hot' => 'p.sales DESC, p.downloads DESC',
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            default => 'p.create_time DESC'
        };

        $countSql = "SELECT COUNT(*) FROM products p WHERE {$where}";
        $total = $this->productModel->fetch($countSql, $params)['COUNT(*)'];

        $pagination = paginate($total, $page);

        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE {$where} ORDER BY {$orderBy} LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
        $novels = $this->productModel->fetchAll($sql, $params);

        $categories = $this->categoryModel->getCategoriesByType('novel');

        require __DIR__ . '/../../views/novels.phtml';
    }
    
    public function music() {
        $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $sort = $_GET['sort'] ?? 'latest';
        $search = trim($_GET['search'] ?? '');
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        $where = "p.type = 'music' AND p.status = 1";
        $params = [];

        if ($category > 0) {
            $where .= " AND p.category_id = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            $where .= " AND (p.title LIKE ? OR p.author LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $orderBy = match($sort) {
            'hot' => 'p.sales DESC, p.downloads DESC',
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            default => 'p.create_time DESC'
        };

        $countSql = "SELECT COUNT(*) FROM products p WHERE {$where}";
        $total = $this->productModel->fetch($countSql, $params)['COUNT(*)'];

        $pagination = paginate($total, $page);

        $sql = "SELECT p.*, c.name as category_name FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE {$where} ORDER BY {$orderBy} LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}";
        $music = $this->productModel->fetchAll($sql, $params);

        $categories = $this->categoryModel->getCategoriesByType('music');

        require __DIR__ . '/../../views/music.phtml';
    }
    
    public function show($id) {
        $productId = (int)$id;
        
        if ($productId <= 0) {
            redirect('/');
        }

        $product = $this->productModel->getProductDetail($productId);

        if (!$product) {
            redirect('/');
        }

        $hasPurchased = false;
        if (isLoggedIn()) {
            $hasPurchased = $this->orderModel->hasPurchased(getCurrentUserId(), $productId);
        }

        $message = '';
        require __DIR__ . '/../../views/product.phtml';
    }
    
    public function buy($id) {
        $productId = (int)$id;
        
        if ($productId <= 0) {
            redirect('/');
        }

        $product = $this->productModel->getProductDetail($productId);

        if (!$product) {
            redirect('/');
        }

        $hasPurchased = false;
        if (isLoggedIn()) {
            $hasPurchased = $this->orderModel->hasPurchased(getCurrentUserId(), $productId);
        }

        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
            if (!isLoggedIn()) {
                $_SESSION['redirect_url'] = "/product/{$productId}";
                redirect('/login.php');
            }
            
            if ($hasPurchased) {
                $message = '您已购买此商品';
            } elseif (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $message = '安全验证失败';
            } else {
                try {
                    $this->orderModel->createOrder(getCurrentUserId(), $productId, $product['price']);
                    $message = '订单创建成功，请完成支付';
                } catch (\Exception $e) {
                    $message = '创建订单失败，请稍后再试';
                }
            }
        }

        require __DIR__ . '/../../views/product.phtml';
    }
}