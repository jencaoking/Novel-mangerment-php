<?php
namespace App\Controllers;

use App\Models\ProductModel;
use App\Models\CategoryModel;
use App\Models\OrderModel;
use App\Models\DownloadModel;
use App\Models\ReviewModel;

class ProductController {
    
    protected $productModel;
    protected $categoryModel;
    protected $orderModel;
    protected $downloadModel;
    protected $reviewModel;

    public function __construct() {
        $this->productModel = new ProductModel();
        $this->categoryModel = new CategoryModel();
        $this->orderModel = new OrderModel();
        $this->downloadModel = new DownloadModel();
        $this->reviewModel = new ReviewModel();
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
        $paidOrder = null;
        $hasReviewed = false;
        
        if (isLoggedIn()) {
            $userId = getCurrentUserId();
            $hasPurchased = $this->orderModel->hasPurchased($userId, $productId);
            
            // 如果购买过，获取订单信息，检查是否已评价
            if ($hasPurchased) {
                $paidOrder = $this->orderModel->getPaidOrder($userId, $productId);
                if ($paidOrder) {
                    $hasReviewed = $this->reviewModel->hasReviewed($paidOrder['id']);
                }
            }
        }

        // 获取当前商品的评价列表
        $reviews = $this->reviewModel->getReviewsByProduct($productId, 1, 10);
        $reviewCount = $this->reviewModel->getProductReviewCount($productId);
        $ratingDistribution = $this->reviewModel->getRatingDistribution($productId);

        $message = '';
        require __DIR__ . '/../../views/product.phtml';
    }

    /**
     * 提交商品评价 (AJAX 模式)
     */
    public function submitReview($id) {
        header('Content-Type: application/json');
        $productId = (int)$id;
        
        if (!isLoggedIn()) {
            echo json_encode(['success' => false, 'message' => '请先登录']);
            return;
        }

        $userId = getCurrentUserId();
        $rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 5;
        $content = isset($_POST['content']) ? trim($_POST['content']) : '';

        // 1. 验证评分合法性
        if ($rating < 1 || $rating > 5) {
            echo json_encode(['success' => false, 'message' => '评分参数错误']);
            return;
        }

        // 2. 验证购买权限
        $order = $this->orderModel->getPaidOrder($userId, $productId);
        if (!$order) {
            echo json_encode(['success' => false, 'message' => '只有购买过的用户才能评价']);
            return;
        }

        // 3. 验证是否重复评价
        if ($this->reviewModel->hasReviewed($order['id'])) {
            echo json_encode(['success' => false, 'message' => '您已经评价过该商品了']);
            return;
        }

        // 4. 插入评价
        try {
            $this->reviewModel->addReview($userId, $productId, $order['id'], $rating, $content);
            
            // 更新商品的平均分和评价总数
            $stats = $this->reviewModel->calculateProductStats($productId);
            $this->productModel->updateRatingStats($productId, $stats['avg_rating'], $stats['review_count']);
            
            echo json_encode(['success' => true, 'message' => '评价成功！']);
        } catch (\Exception $e) {
            error_log('评价异常: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => '评价失败，请稍后重试']);
        }
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

        // ==========================================
        // 3.5 限制同一订单每日下载次数（新增防刷逻辑）
        // ==========================================
        $maxDailyDownloads = 5;
        $dailyCount = $this->downloadModel->getDailyDownloadCountByOrder($order['id']);
        
        if ($dailyCount + 1 > $maxDailyDownloads) {
            http_response_code(429);
            die("为了保护资源安全，同一订单每日最多可下载 {$maxDailyDownloads} 次。您今日的下载次数已用完，请明天再来。");
        }
        // ==========================================

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