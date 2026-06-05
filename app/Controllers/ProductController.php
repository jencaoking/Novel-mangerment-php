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

        $total = $this->productModel->getProductsWithFilterCount('novel', $category, $search);
        $pagination = paginate($total, $page, 12);
        $novels = $this->productModel->getProductsWithFilter('novel', $category, $search, $sort, $page, 12);
        $categories = $this->categoryModel->getCategoriesByType('novel');

        require __DIR__ . '/../../views/novels.phtml';
    }
    
    public function music() {
        $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $sort = $_GET['sort'] ?? 'latest';
        $search = trim($_GET['search'] ?? '');
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        $total = $this->productModel->getProductsWithFilterCount('music', $category, $search);
        $pagination = paginate($total, $page, 12);
        $music = $this->productModel->getProductsWithFilter('music', $category, $search, $sort, $page, 12);
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
                redirect('/login');
            }
            
            if ($hasPurchased) {
                $message = '您已购买此商品';
            } elseif (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $message = '安全验证失败';
            } else {
                try {
                    // 创建订单
                    $orderId = $this->orderModel->createOrder(getCurrentUserId(), $productId, $product['price']);
                    
                    if ($orderId) {
                        // 直接跳转到支付宝支付页面
                        redirect('/payment/pay/' . $orderId);
                    } else {
                        $message = '创建订单失败，请稍后再试';
                    }
                } catch (\Exception $e) {
                    error_log('购买异常: ' . $e->getMessage());
                    $message = '创建订单失败，请稍后再试';
                }
            }
        }

        require __DIR__ . '/../../views/product.phtml';
    }
}