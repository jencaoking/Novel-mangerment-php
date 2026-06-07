
&lt;?php
namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\ProductModel;

class PaymentController
{
    protected $orderModel;
    protected $productModel;
    protected $alipaySDK;

    public function __construct()
    {
        require_once __DIR__ . '/../../includes/AlipaySDK.php';
        $this-&gt;orderModel = new OrderModel();
        $this-&gt;productModel = new ProductModel();
        $this-&gt;alipaySDK = new \AlipaySDK();
    }

    /**
     * 发起支付 - 跳转到支付宝
     */
    public function pay($orderId)
    {
        if (!isLoggedIn()) {
            redirect('/login');
        }

        $orderId = (int)$orderId;
        if ($orderId &lt;= 0) {
            $_SESSION['error'] = '无效的订单号';
            redirect('/user/orders');
        }

        // 获取订单信息
        $order = $this-&gt;orderModel-&gt;find($orderId);
        
        if (!$order) {
            $_SESSION['error'] = '订单不存在';
            redirect('/user/orders');
        }

        // 验证订单归属
        if ($order['user_id'] != getCurrentUserId()) {
            $_SESSION['error'] = '无权操作此订单';
            redirect('/user/orders');
        }

        // 检查订单状态
        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = '订单状态异常，无法支付';
            redirect('/user/orders');
        }

        // 获取商品信息
        $product = $this-&gt;productModel-&gt;find($order['product_id']);
        if (!$product) {
            $_SESSION['error'] = '商品不存在';
            redirect('/user/orders');
        }

        // 生成支付宝支付链接(PC端)
        try {
            $payUrl = $this-&gt;alipaySDK-&gt;createPagePayUrl(
                $order['order_no'],
                $order['price'],
                $product['title'],
                'BookMusic Mall - ' . getProductTypeText($product['type'])
            );

            // 重定向到支付宝
            header('Location: ' . $payUrl);
            exit;

        } catch (\Exception $e) {
            error_log('支付宝支付跳转失败: ' . $e-&gt;getMessage());
            $_SESSION['error'] = '支付跳转失败，请稍后再试';
            redirect('/user/orders');
        }
    }

    /**
     * 支付宝异步通知回调
     */
    public function notify()
    {
        // 获取POST数据
        $data = $_POST;

        // 记录日志
        error_log('支付宝异步通知收到数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        // 验证签名
        if (!$this-&gt;alipaySDK-&gt;verifyNotify($data)) {
            error_log('支付宝异步通知签名验证失败');
            echo 'fail';
            return;
        }

        // 获取关键参数
        $outTradeNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['trade_no'] ?? '';
        $tradeStatus = $data['trade_status'] ?? '';
        $totalAmount = $data['total_amount'] ?? '0';

        // 只处理支付成功的通知
        if ($tradeStatus !== 'TRADE_SUCCESS' &amp;&amp; $tradeStatus !== 'TRADE_FINISHED') {
            echo 'success';
            return;
        }

        // 判断是否为批次订单（合并支付）
        $isBatchOrder = strpos($outTradeNo, 'BATCH_') === 0;
        
        if ($isBatchOrder) {
            // 批次订单处理逻辑
            $this-&gt;handleBatchOrderNotify($outTradeNo, $tradeNo, $totalAmount);
        } else {
            // 单个订单处理逻辑
            $this-&gt;handleSingleOrderNotify($outTradeNo, $tradeNo, $totalAmount);
        }
    }

    /**
     * 处理单个订单的异步通知
     */
    private function handleSingleOrderNotify($outTradeNo, $tradeNo, $totalAmount)
    {
        // 查找订单
        $order = $this-&gt;orderModel-&gt;findByOrderNo($outTradeNo);
        if (!$order) {
            error_log('支付宝异步通知：订单不存在 - ' . $outTradeNo);
            echo 'fail';
            return;
        }

        // 幂等性检查
        if ($order['status'] === 'paid' || $order['status'] === 'completed') {
            error_log('支付宝异步通知：订单已支付，跳过处理 - ' . $outTradeNo);
            echo 'success';
            return;
        }

        // 验证金额
        if (bccomp($order['price'], $totalAmount, 2) !== 0) {
            error_log('支付宝异步通知：金额不匹配 - 订单:' . $order['price'] . ', 通知:' . $totalAmount);
            echo 'fail';
            return;
        }

        // 更新订单状态
        try {
            $payTime = date('Y-m-d H:i:s');
            $this-&gt;orderModel-&gt;updateOrderPaid($order['id'], $tradeNo, $payTime);
            
            // 更新商品销量
            $this-&gt;productModel-&gt;increaseSales($order['product_id']);
            
            error_log('支付宝异步通知：订单支付成功 - ' . $outTradeNo);
            echo 'success';
            
        } catch (\Exception $e) {
            error_log('支付宝异步通知：更新订单失败 - ' . $e-&gt;getMessage());
            echo 'fail';
        }
    }

    /**
     * 处理批次订单的异步通知（合并支付）
     */
    private function handleBatchOrderNotify($batchTradeNo, $tradeNo, $totalAmount)
    {
        $db = getDB();
        
        try {
            // 获取批次下的所有订单
            $sql = "SELECT id, product_id, price FROM orders WHERE trade_no = ? AND status = 'pending'";
            $stmt = $db-&gt;prepare($sql);
            $stmt-&gt;execute([$batchTradeNo]);
            $orders = $stmt-&gt;fetchAll();
            
            if (empty($orders)) {
                error_log('支付宝异步通知：批次订单不存在或已全部处理 - ' . $batchTradeNo);
                echo 'success'; // 幂等性返回
                return;
            }
            
            // 验证总金额（可选，防止中间人攻击）
            $expectedTotal = array_sum(array_column($orders, 'price'));
            if (bccomp($expectedTotal, $totalAmount, 2) !== 0) {
                error_log('支付宝异步通知：批次总金额不匹配 - 预期:' . $expectedTotal . ', 实际:' . $totalAmount);
                echo 'fail';
                return;
            }
            
            // 事务更新所有订单状态
            $db-&gt;beginTransaction();
            
            $updateStmt = $db-&gt;prepare("UPDATE orders SET status = 'paid', pay_time = ?, trade_no = ? WHERE id = ?");
            $payTime = date('Y-m-d H:i:s');
            
            foreach ($orders as $order) {
                $updateStmt-&gt;execute([$payTime, $tradeNo, $order['id']]);
                
                // 更新商品销量
                $this-&gt;productModel-&gt;increaseSales($order['product_id']);
            }
            
            $db-&gt;commit();
            
            error_log('支付宝异步通知：批次订单支付成功 - ' . $batchTradeNo . '，共' . count($orders) . '件商品');
            echo 'success';
            
        } catch (\Exception $e) {
            if ($db-&gt;inTransaction()) {
                $db-&gt;rollBack();
            }
            
            error_log('支付宝异步通知：批次订单更新失败 - ' . $e-&gt;getMessage());
            echo 'fail';
        }
    }

    /**
     * 支付宝同步返回
     */
    public function returnUrl()
    {
        // 获取GET参数
        $data = $_GET;

        // 验证签名
        if (!$this-&gt;alipaySDK-&gt;verifyReturn($data)) {
            $_SESSION['error'] = '支付验证失败';
            redirect('/user/orders');
        }

        $outTradeNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['trade_no'] ?? '';

        // 查找订单
        $order = $this-&gt;orderModel-&gt;findByOrderNo($outTradeNo);
        
        if (!$order) {
            $_SESSION['error'] = '订单不存在';
            redirect('/user/orders');
        }

        // 检查订单状态
        if ($order['status'] === 'paid' || $order['status'] === 'completed') {
            $_SESSION['success'] = '支付成功！';
        } else {
            $_SESSION['info'] = '支付处理中，请稍后查看订单状态';
        }

        redirect('/user/orders');
    }

    /**
     * 查询订单支付状态
     */
    public function query($orderId)
    {
        if (!isLoggedIn()) {
            json_response(['success' =&gt; false, 'message' =&gt; '请先登录']);
        }

        $orderId = (int)$orderId;
        $order = $this-&gt;orderModel-&gt;find($orderId);

        if (!$order || $order['user_id'] != getCurrentUserId()) {
            json_response(['success' =&gt; false, 'message' =&gt; '订单不存在']);
        }

        json_response([
            'success' =&gt; true,
            'order_no' =&gt; $order['order_no'],
            'status' =&gt; $order['status'],
            'price' =&gt; $order['price'],
            'pay_time' =&gt; $order['pay_time']
        ]);
    }

    /**
     * 查询批次订单支付状态（用于前端轮询）
     */
    public function queryBatchStatus()
    {
        if (!isLoggedIn()) {
            json_response(['success' =&gt; false, 'message' =&gt; '请先登录'], 401);
        }

        $batchTradeNo = isset($_GET['batch_trade_no']) ? $_GET['batch_trade_no'] : '';
        
        if (empty($batchTradeNo) || strpos($batchTradeNo, 'BATCH_') !== 0) {
            json_response(['success' =&gt; false, 'message' =&gt; '无效的批次号']);
        }

        try {
            $db = getDB();

            // 查询该批次下所有订单的状态
            $sql = "SELECT id, status, pay_time FROM orders WHERE trade_no = ? AND user_id = ?";
            $stmt = $db-&gt;prepare($sql);
            $stmt-&gt;execute([$batchTradeNo, getCurrentUserId()]);
            $orders = $stmt-&gt;fetchAll();
            
            if (empty($orders)) {
                json_response(['success' =&gt; false, 'message' =&gt; '批次订单不存在']);
            }
            
            // 检查是否所有订单都已支付
            $allPaid = true;
            $anyPaid = false;
            
            foreach ($orders as $order) {
                if ($order['status'] !== 'paid') {
                    $allPaid = false;
                } else {
                    $anyPaid = true;
                }
            }
            
            // 返回批次支付状态
            if ($allPaid) {
                json_response([
                    'success' =&gt; true,
                    'status' =&gt; 'paid',
                    'batch_trade_no' =&gt; $batchTradeNo,
                    'order_count' =&gt; count($orders),
                    'message' =&gt; '支付成功'
                ]);
            } elseif ($anyPaid) {
                json_response([
                    'success' =&gt; true,
                    'status' =&gt; 'partial_paid',
                    'batch_trade_no' =&gt; $batchTradeNo,
                    'order_count' =&gt; count($orders),
                    'message' =&gt; '部分订单已支付'
                ]);
            } else {
                json_response([
                    'success' =&gt; true,
                    'status' =&gt; 'pending',
                    'batch_trade_no' =&gt; $batchTradeNo,
                    'order_count' =&gt; count($orders),
                    'message' =&gt; '等待支付'
                ]);
            }
            
        } catch (\Exception $e) {
            error_log('查询批次订单状态失败: ' . $e-&gt;getMessage());
            json_response(['success' =&gt; false, 'message' =&gt; '查询失败，请稍后再试']);
        }
    }

    /**
     * 购物车合并结算 - 批次流水号模式
     */
    public function checkoutCart()
    {
        if (!isLoggedIn()) {
            json_response(['success' =&gt; false, 'message' =&gt; '请先登录'], 401);
        }

        $userId = getCurrentUserId();
        
        // 1. 从 POST 获取选中的商品 ID 数组
        $productIds = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
        
        if (!is_array($productIds) || empty($productIds)) {
            json_response(['success' =&gt; false, 'message' =&gt; '请选择要结算的商品']);
        }
        
        // 验证并过滤商品ID
        $productIds = array_map('intval', $productIds);
        $productIds = array_filter($productIds, function($id) {
            return $id &gt; 0;
        });
        
        if (empty($productIds)) {
            json_response(['success' =&gt; false, 'message' =&gt; '无效的商品ID']);
        }
        
        try {
            // 2. 获取购物车中选中的商品信息
            $db = getDB();

            // 构建 IN 查询的参数占位符
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT p.id, p.price, p.title, p.type 
                    FROM cart c 
                    LEFT JOIN products p ON c.product_id = p.id 
                    WHERE c.user_id = ? AND c.product_id IN (" . $placeholders . ")";
            
            $params = array_merge([$userId], $productIds);
            $stmt = $db-&gt;prepare($sql);
            $stmt-&gt;execute($params);
            $cartItems = $stmt-&gt;fetchAll();
            
            if (empty($cartItems)) {
                json_response(['success' =&gt; false, 'message' =&gt; '购物车中没有选中的商品']);
            }
            
            // 3. 计算总价并生成批次流水号
            $totalAmount = array_sum(array_column($cartItems, 'price'));
            $batchTradeNo = 'BATCH_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
            $orderTitles = '合并订单等' . count($cartItems) . '件商品';
            
            // 4. 事务处理：插入多条订单记录 + 清空选中购物车商品
            $db-&gt;beginTransaction();
            
            // 修复：order_no 在 orders 表中为 UNIQUE NOT NULL，合并支付时必须为每条订单单独生成
            $orderStmt = $db-&gt;prepare(
                "INSERT INTO orders (order_no, user_id, product_id, price, trade_no, status) 
                 VALUES (?, ?, ?, ?, ?, 'pending')"
            );
            
            foreach ($cartItems as $item) {
                $orderStmt-&gt;execute([
                    generateOrderNo(),
                    $userId, 
                    $item['id'], 
                    $item['price'], 
                    $batchTradeNo
                ]);
            }
            
            // 清空选中的购物车商品
            $deletePlaceholders = implode(',', array_fill(0, count($productIds), '?'));
            $deleteSql = "DELETE FROM cart WHERE user_id = ? AND product_id IN (" . $deletePlaceholders . ")";
            $deleteParams = array_merge([$userId], $productIds);
            $db-&gt;prepare($deleteSql)-&gt;execute($deleteParams);
            
            $db-&gt;commit();
            
            // 5. 生成支付宝支付链接
            // 使用批次号作为商户订单号，支付宝会原样返回
            $payUrl = $this-&gt;alipaySDK-&gt;createPagePayUrl(
                $batchTradeNo,
                $totalAmount,
                $orderTitles,
                'BookMusic Mall - 合并支付'
            );
            
            // 6. 返回 JSON 响应
            json_response([
                'success' =&gt; true,
                'batch_trade_no' =&gt; $batchTradeNo,
                'total_amount' =&gt; sprintf('%.2f', $totalAmount),
                'pay_url' =&gt; $payUrl,
                'item_count' =&gt; count($cartItems)
            ]);
            
        } catch (\Exception $e) {
            if ($db-&gt;inTransaction()) {
                $db-&gt;rollBack();
            }
            
            error_log('购物车结算失败: ' . $e-&gt;getMessage());
            json_response(['success' =&gt; false, 'message' =&gt; '结算失败，请稍后再试']);
        }
    }
}

/**
 * JSON响应辅助函数
 */
if (!function_exists('json_response')) {
    function json_response($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
