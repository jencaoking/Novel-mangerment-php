<?php
namespace App\Controllers;

use App\Models\CartModel;
use App\Models\ProductModel;

class CartController {
    protected $cartModel;
    protected $productModel;

    public function __construct() {
        $this->cartModel = new CartModel();
        $this->productModel = new ProductModel();
    }

    /**
     * 渲染购物车页面
     */
    public function index() {
        if (!isLoggedIn()) {
            redirect('/login');
        }

        $userId = getCurrentUserId();
        
        // 获取购物车商品列表（含商品信息）
        $cartItems = $this->cartModel->getUserCart($userId);
        
        // 计算总价
        $totalPrice = array_sum(array_column($cartItems, 'price'));
        
        // 获取用户信息用于侧边栏
        $user = getCurrentUser();
        
        require __DIR__ . '/../../views/user/cart.phtml';
    }

    /**
     * API: 添加商品到购物车
     */
    public function add() {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = getCurrentUserId();
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($productId <= 0) {
            jsonResponse(['success' => false, 'message' => '无效的商品ID']);
        }
        
        // 检查商品是否存在
        $product = $this->productModel->find($productId);
        if (!$product) {
            jsonResponse(['success' => false, 'message' => '商品不存在']);
        }
        
        // 检查商品是否上架
        if ($product['status'] != 1) {
            jsonResponse(['success' => false, 'message' => '商品已下架']);
        }
        
        // 检查是否已购买
        if ($this->cartModel->isInCart($userId, $productId)) {
            jsonResponse(['success' => false, 'message' => '商品已在购物车中']);
        }
        
        // 添加到购物车
        try {
            $result = $this->cartModel->addToCart($userId, $productId);
            
            if ($result) {
                $cartCount = $this->cartModel->getUserCartCount($userId);
                jsonResponse([
                    'success' => true, 
                    'message' => '已加入购物车',
                    'cart_count' => $cartCount
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => '加入失败，请稍后再试']);
            }
        } catch (\Exception $e) {
            error_log('加入购物车失败: ' . $e->getMessage());
            jsonResponse(['success' => false, 'message' => '系统错误，请稍后再试']);
        }
    }

    /**
     * API: 移除购物车商品
     */
    public function remove() {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = getCurrentUserId();
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($productId <= 0) {
            jsonResponse(['success' => false, 'message' => '无效的商品ID']);
        }
        
        try {
            $result = $this->cartModel->removeFromCart($userId, $productId);
            
            if ($result) {
                $cartCount = $this->cartModel->getUserCartCount($userId);
                jsonResponse([
                    'success' => true, 
                    'message' => '已移除',
                    'cart_count' => $cartCount
                ]);
            } else {
                jsonResponse(['success' => false, 'message' => '移除失败']);
            }
        } catch (\Exception $e) {
            error_log('移除购物车商品失败: ' . $e->getMessage());
            jsonResponse(['success' => false, 'message' => '系统错误，请稍后再试']);
        }
    }

    /**
     * API: 清空购物车
     */
    public function clear() {
        if (!isLoggedIn()) {
            jsonResponse(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = getCurrentUserId();
        
        try {
            $result = $this->cartModel->clearCart($userId);
            
            jsonResponse([
                'success' => true, 
                'message' => '购物车已清空',
                'cart_count' => 0
            ]);
        } catch (\Exception $e) {
            error_log('清空购物车失败: ' . $e->getMessage());
            jsonResponse(['success' => false, 'message' => '系统错误，请稍后再试']);
        }
    }
}
