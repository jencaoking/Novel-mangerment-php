<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Models\OrderModel;
use App\Models\DownloadModel;

class ProductController {
    
    protected $productModel;
    protected $categoryModel;
    protected $orderModel;
    protected $downloadModel;

    public function __construct() {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->orderModel = new OrderModel();
        $this->downloadModel = new DownloadModel();
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

    /**
     * 安全下载处理
     */
    public function download($id) {
        $productId = (int)$id;
        
        // 1. 检查用户登录状态
        if (!isLoggedIn()) {
            $_SESSION['redirect_url'] = "/download/{$productId}";
            redirect('/login');
        }

        $userId = getCurrentUserId();
        $product = $this->productModel->getProductDetail($productId);

        // 2. 验证商品是否存在
        if (!$product) {
            http_response_code(404);
            die('商品不存在');
        }

        // 3. 验证购买权限
        $order = $this->orderModel->getPaidOrder($userId, $productId);
        if (!$order) {
            http_response_code(403);
            die('您尚未购买此商品，无权下载');
        }

        // 4. 确定文件路径
        $fileDir = $product['type'] === 'novel' ? 'novels/' : 'music/';
        $filePath = UPLOAD_PATH . $fileDir . $product['file_path'];

        // 5. 检查物理文件是否存在
        if (!file_exists($filePath)) {
            http_response_code(404);
            die('文件已丢失，请联系管理员');
        }

        // 6. 记录下载日志
        $this->downloadModel->logDownload($userId, $productId, $order['id']);

        // 7. 发送文件（流式输出）
        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . basename($product['title']) . '.' . pathinfo($filePath, PATHINFO_EXTENSION) . '"');
        header('Content-Transfer-Encoding: binary');
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($filePath));
        
        // 清空缓冲区并输出文件
        ob_clean();
        flush();
        readfile($filePath);
        exit;
    }
}