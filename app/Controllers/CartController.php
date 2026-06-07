
&lt;?php
namespace App\Controllers;

use App\Models\CartModel;
use App\Models\ProductModel;

class CartController {
    protected $cartModel;
    protected $productModel;

    public function __construct() {
        $this-&gt;cartModel = new CartModel();
        $this-&gt;productModel = new ProductModel();
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
        $cartItems = $this-&gt;cartModel-&gt;getUserCart($userId);
        
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
            json_response(['success' =&gt; false, 'message' =&gt; '请先登录'], 401);
        }

        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            json_response(['success' =&gt; false, 'message' =&gt; 'CSRF 验证失败'], 403);
        }

        $userId = getCurrentUserId();
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($productId &lt;= 0) {
            json_response(['success' =&gt; false, 'message' =&gt; '无效的商品ID']);
        }
        
        // 检查商品是否存在
        $product = $this-&gt;productModel-&gt;find($productId);
        if (!$product) {
            json_response(['success' =&gt; false, 'message' =&gt; '商品不存在']);
        }
        
        // 检查商品是否上架
        if ($product['status'] != 1) {
            json_response(['success' =&gt; false, 'message' =&gt; '商品已下架']);
        }
        
        // 检查是否已购买
        if ($this-&gt;cartModel-&gt;isInCart($userId, $productId)) {
            json_response(['success' =&gt; false, 'message' =&gt; '商品已在购物车中']);
        }
        
        // 添加到购物车
        try {
            $result = $this-&gt;cartModel-&gt;addToCart($userId, $productId);
            
            if ($result) {
                $cartCount = $this-&gt;cartModel-&gt;getUserCartCount($userId);
                json_response([
                    'success' =&gt; true, 
                    'message' =&gt; '已加入购物车',
                    'cart_count' =&gt; $cartCount
                ]);
            } else {
                json_response(['success' =&gt; false, 'message' =&gt; '加入失败，请稍后再试']);
            }
        } catch (\Exception $e) {
            error_log('加入购物车失败: ' . $e-&gt;getMessage());
            json_response(['success' =&gt; false, 'message' =&gt; '系统错误，请稍后再试']);
        }
    }

    /**
     * API: 移除购物车商品
     */
    public function remove() {
        if (!isLoggedIn()) {
            json_response(['success' =&gt; false, 'message' =&gt; '请先登录'], 401);
        }

        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            json_response(['success' =&gt; false, 'message' =&gt; 'CSRF 验证失败'], 403);
        }

        $userId = getCurrentUserId();
        $productId = isset($_POST['product_id']) ? (int)$_POST['product_id'] : 0;
        
        if ($productId &lt;= 0) {
            json_response(['success' =&gt; false, 'message' =&gt; '无效的商品ID']);
        }
        
        try {
            $result = $this-&gt;cartModel-&gt;removeFromCart($userId, $productId);
            
            if ($result) {
                $cartCount = $this-&gt;cartModel-&gt;getUserCartCount($userId);
                json_response([
                    'success' =&gt; true, 
                    'message' =&gt; '已移除',
                    'cart_count' =&gt; $cartCount
                ]);
            } else {
                json_response(['success' =&gt; false, 'message' =&gt; '移除失败']);
            }
        } catch (\Exception $e) {
            error_log('移除购物车商品失败: ' . $e-&gt;getMessage());
            json_response(['success' =&gt; false, 'message' =&gt; '系统错误，请稍后再试']);
        }
    }

    /**
     * API: 清空购物车
     */
    public function clear() {
        if (!isLoggedIn()) {
            json_response(['success' =&gt; false, 'message' =&gt; '请先登录'], 401);
        }

        if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
            json_response(['success' =&gt; false, 'message' =&gt; 'CSRF 验证失败'], 403);
        }

        $userId = getCurrentUserId();
        
        try {
            $result = $this-&gt;cartModel-&gt;clearCart($userId);
            
            json_response([
                'success' =&gt; true, 
                'message' =&gt; '购物车已清空',
                'cart_count' =&gt; 0
            ]);
        } catch (\Exception $e) {
            error_log('清空购物车失败: ' . $e-&gt;getMessage());
            json_response(['success' =&gt; false, 'message' =&gt; '系统错误，请稍后再试']);
        }
    }
}
