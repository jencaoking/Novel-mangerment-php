<?php
namespace App\Controllers;

use App\Models\CartModel;
use App\Models\ProductModel;

class CartController {
    protected $cartModel;
    protected $productModel;

    public function __construct(
        CartModel $cartModel,
        ProductModel $productModel
    ) {
        $this->cartModel = $cartModel;
        $this->productModel = $productModel;
    }

    public function index() {
        if (!isLoggedIn()) {
            redirect('/login');
        }

        $userId = getCurrentUserId();
        
        // 清理已删除/下架商品的购物车记录
        $this->cartModel->cleanOrphanedItems($userId);
        
        $cartItems = $this->cartModel->getUserCart($userId);
        
        $totalPrice = array_sum(array_column($cartItems, 'price'));
        
        // 默认全选
        $allSelected = !empty($cartItems);
        $selectedIds = array_column($cartItems, 'product_id');
        
        $user = getCurrentUser();
        
        require __DIR__ . '/../../views/user/cart.phtml';
    }

    public function add() {
        if (!isLoggedIn()) {
            json_response(['success' => false, 'message' => '请先登录'], 401);
        }

        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            json_response(['success' => false, 'message' => 'CSRF 验证失败'], 403);
        }

        $userId = getCurrentUserId();
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($productId <= 0) {
            json_response(['success' => false, 'message' => '无效的商品ID']);
        }
        
        $product = $this->productModel->find($productId);
        if (!$product) {
            json_response(['success' => false, 'message' => '商品不存在']);
        }
        
        if ($product['status'] != 1) {
            json_response(['success' => false, 'message' => '商品已下架']);
        }
        
        if ($this->cartModel->isInCart($userId, $productId)) {
            json_response(['success' => false, 'message' => '商品已在购物车中']);
        }
        
        try {
            $result = $this->cartModel->addToCart($userId, $productId);
            
            if ($result) {
                $cartCount = $this->cartModel->getUserCartCount($userId);
                json_response([
                    'success' => true, 
                    'message' => '已加入购物车',
                    'cart_count' => $cartCount
                ]);
            } else {
                json_response(['success' => false, 'message' => '加入失败，请稍后再试']);
            }
        } catch (\Exception $e) {
            error_log('加入购物车失败: ' . $e->getMessage());
            json_response(['success' => false, 'message' => '系统错误，请稍后再试']);
        }
    }

    public function remove() {
        if (!isLoggedIn()) {
            json_response(['success' => false, 'message' => '请先登录'], 401);
        }

        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            json_response(['success' => false, 'message' => 'CSRF 验证失败'], 403);
        }

        $userId = getCurrentUserId();
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($productId <= 0) {
            json_response(['success' => false, 'message' => '无效的商品ID']);
        }
        
        try {
            $result = $this->cartModel->removeFromCart($userId, $productId);
            
            if ($result) {
                $cartCount = $this->cartModel->getUserCartCount($userId);
                json_response([
                    'success' => true, 
                    'message' => '已移除',
                    'cart_count' => $cartCount
                ]);
            } else {
                json_response(['success' => false, 'message' => '移除失败']);
            }
        } catch (\Exception $e) {
            error_log('移除购物车商品失败: ' . $e->getMessage());
            json_response(['success' => false, 'message' => '系统错误，请稍后再试']);
        }
    }

    public function clear() {
        if (!isLoggedIn()) {
            json_response(['success' => false, 'message' => '请先登录'], 401);
        }

        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            json_response(['success' => false, 'message' => 'CSRF 验证失败'], 403);
        }

        $userId = getCurrentUserId();
        
        try {
            $result = $this->cartModel->clearCart($userId);
            
            json_response([
                'success' => true, 
                'message' => '购物车已清空',
                'cart_count' => 0
            ]);
        } catch (\Exception $e) {
            error_log('清空购物车失败: ' . $e->getMessage());
            json_response(['success' => false, 'message' => '系统错误，请稍后再试']);
        }
    }
}