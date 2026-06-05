<?php
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
        $this->orderModel = new OrderModel();
        $this->productModel = new ProductModel();
        $this->alipaySDK = new \AlipaySDK();
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
        if ($orderId <= 0) {
            $_SESSION['error'] = '无效的订单号';
            redirect('/user/orders');
        }

        // 获取订单信息
        $order = $this->orderModel->find($orderId);
        
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
        $product = $this->productModel->find($order['product_id']);
        if (!$product) {
            $_SESSION['error'] = '商品不存在';
            redirect('/user/orders');
        }

        // 生成支付宝支付链接(PC端)
        try {
            $payUrl = $this->alipaySDK->createPagePayUrl(
                $order['order_no'],
                $order['price'],
                $product['title'],
                'BookMusic Mall - ' . getProductTypeText($product['type'])
            );

            // 重定向到支付宝
            header('Location: ' . $payUrl);
            exit;

        } catch (\Exception $e) {
            error_log('支付宝支付跳转失败: ' . $e->getMessage());
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
        if (!$this->alipaySDK->verifyNotify($data)) {
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
        if ($tradeStatus !== 'TRADE_SUCCESS' && $tradeStatus !== 'TRADE_FINISHED') {
            echo 'success';
            return;
        }

        // 判断是否为批次订单（合并支付）
        $isBatchOrder = strpos($outTradeNo, 'BATCH_') === 0;
        
        if ($isBatchOrder) {
            // 批次订单处理逻辑
            $this->handleBatchOrderNotify($outTradeNo, $tradeNo, $totalAmount);
        } else {
            // 单个订单处理逻辑
            $this->handleSingleOrderNotify($outTradeNo, $tradeNo, $totalAmount);
        }
    }

    /**
     * 处理单个订单的异步通知
     */
    private function handleSingleOrderNotify($outTradeNo, $tradeNo, $totalAmount)
    {
        // 查找订单
        $order = $this->orderModel->findByOrderNo($outTradeNo);
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
            $this->orderModel->updateOrderPaid($order['id'], $tradeNo, $payTime);
            
            // 更新商品销量
            $this->productModel->increaseSales($order['product_id']);
            
            error_log('支付宝异步通知：订单支付成功 - ' . $outTradeNo);
            echo 'success';
            
        } catch (\Exception $e) {
            error_log('支付宝异步通知：更新订单失败 - ' . $e->getMessage());
            echo 'fail';
        }
    }

    /**
     * 处理批次订单的异步通知（合并支付）
     */
    private function handleBatchOrderNotify($batchTradeNo, $tradeNo, $totalAmount)
    {
        global $db;
        
        try {
            // 获取批次下的所有订单
            $sql = "SELECT id, product_id, price FROM orders WHERE trade_no = ? AND status = 'pending'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$batchTradeNo]);
            $orders = $stmt->fetchAll();
            
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
            $db->beginTransaction();
            
            $updateStmt = $db->prepare("UPDATE orders SET status = 'paid', pay_time = ?, trade_no = CONCAT(trade_no, '_PAID') WHERE id = ?");
            $payTime = date('Y-m-d H:i:s');
            
            foreach ($orders as $order) {
                $updateStmt->execute([$payTime, $order['id']]);
                
                // 更新商品销量
                $this->productModel->increaseSales($order['product_id']);
            }
            
            $db->commit();
            
            error_log('支付宝异步通知：批次订单支付成功 - ' . $batchTradeNo . '，共' . count($orders) . '件商品');
            echo 'success';
            
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('支付宝异步通知：批次订单更新失败 - ' . $e->getMessage());
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
        if (!$this->alipaySDK->verifyReturn($data)) {
            $_SESSION['error'] = '支付验证失败';
            redirect('/user/orders');
        }

        $outTradeNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['trade_no'] ?? '';

        // 查找订单
        $order = $this->orderModel->findByOrderNo($outTradeNo);
        
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
            json_response(['success' => false, 'message' => '请先登录']);
        }

        $orderId = (int)$orderId;
        $order = $this->orderModel->find($orderId);

        if (!$order || $order['user_id'] != getCurrentUserId()) {
            json_response(['success' => false, 'message' => '订单不存在']);
        }

        json_response([
            'success' => true,
            'order_no' => $order['order_no'],
            'status' => $order['status'],
            'price' => $order['price'],
            'pay_time' => $order['pay_time']
        ]);
    }

    /**
     * 查询批次订单支付状态（用于前端轮询）
     */
    public function queryBatchStatus()
    {
        if (!isLoggedIn()) {
            json_response(['success' => false, 'message' => '请先登录'], 401);
        }

        $batchTradeNo = isset($_GET['batch_trade_no']) ? $_GET['batch_trade_no'] : '';
        
        if (empty($batchTradeNo) || strpos($batchTradeNo, 'BATCH_') !== 0) {
            json_response(['success' => false, 'message' => '无效的批次号']);
        }

        try {
            global $db;
            
            // 查询该批次下所有订单的状态
            $sql = "SELECT id, status, pay_time FROM orders WHERE trade_no = ? AND user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$batchTradeNo, getCurrentUserId()]);
            $orders = $stmt->fetchAll();
            
            if (empty($orders)) {
                json_response(['success' => false, 'message' => '批次订单不存在']);
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
                    'success' => true,
                    'status' => 'paid',
                    'batch_trade_no' => $batchTradeNo,
                    'order_count' => count($orders),
                    'message' => '支付成功'
                ]);
            } elseif ($anyPaid) {
                json_response([
                    'success' => true,
                    'status' => 'partial_paid',
                    'batch_trade_no' => $batchTradeNo,
                    'order_count' => count($orders),
                    'message' => '部分订单已支付'
                ]);
            } else {
                json_response([
                    'success' => true,
                    'status' => 'pending',
                    'batch_trade_no' => $batchTradeNo,
                    'order_count' => count($orders),
                    'message' => '等待支付'
                ]);
            }
            
        } catch (\Exception $e) {
            error_log('查询批次订单状态失败: ' . $e->getMessage());
            json_response(['success' => false, 'message' => '查询失败，请稍后再试']);
        }
    }

    /**
     * 购物车合并结算 - 批次流水号模式
     */
    public function checkoutCart()
    {
        if (!isLoggedIn()) {
            json_response(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = getCurrentUserId();
        
        // 1. 从 POST 获取选中的商品 ID 数组
        $productIds = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
        
        if (empty($productIds)) {
            json_response(['success' => false, 'message' => '请选择要结算的商品']);
        }
        
        // 验证并过滤商品ID
        $productIds = array_map('intval', $productIds);
        $productIds = array_filter($productIds, function($id) {
            return $id > 0;
        });
        
        if (empty($productIds)) {
            json_response(['success' => false, 'message' => '无效的商品ID']);
        }
        
        try {
            // 2. 获取购物车中选中的商品信息
            global $db;
            
            // 构建 IN 查询的参数占位符
            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT p.id, p.price, p.title, p.type 
                    FROM cart c 
                    LEFT JOIN products p ON c.product_id = p.id 
                    WHERE c.user_id = ? AND c.product_id IN ($placeholders)";
            
            $params = array_merge([$userId], $productIds);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $cartItems = $stmt->fetchAll();
            
            if (empty($cartItems)) {
                json_response(['success' => false, 'message' => '购物车中没有选中的商品']);
            }
            
            // 3. 计算总价并生成批次流水号
            $totalAmount = array_sum(array_column($cartItems, 'price'));
            $batchTradeNo = 'BATCH_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
            $orderTitles = '合并订单等' . count($cartItems) . '件商品';
            
            // 4. 事务处理：插入多条订单记录 + 清空选中购物车商品
            $db->beginTransaction();
            
            $orderStmt = $db->prepare(
                "INSERT INTO orders (user_id, product_id, price, trade_no, status) 
                 VALUES (?, ?, ?, ?, 'pending')"
            );
            
            foreach ($cartItems as $item) {
                $orderStmt->execute([
                    $userId, 
                    $item['id'], 
                    $item['price'], 
                    $batchTradeNo
                ]);
            }
            
            // 清空选中的购物车商品
            $deletePlaceholders = implode(',', array_fill(0, count($productIds), '?'));
            $deleteSql = "DELETE FROM cart WHERE user_id = ? AND product_id IN ($deletePlaceholders)";
            $deleteParams = array_merge([$userId], $productIds);
            $db->prepare($deleteSql)->execute($deleteParams);
            
            $db->commit();
            
            // 5. 生成支付宝支付链接
            // 使用批次号作为商户订单号，支付宝会原样返回
            $payUrl = $this->alipaySDK->createPagePayUrl(
                $batchTradeNo,
                $totalAmount,
                $orderTitles,
                'BookMusic Mall - 合并支付'
            );
            
            // 6. 返回 JSON 响应
            json_response([
                'success' => true,
                'batch_trade_no' => $batchTradeNo,
                'total_amount' => sprintf('%.2f', $totalAmount),
                'pay_url' => $payUrl,
                'item_count' => count($cartItems)
            ]);
            
        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            
            error_log('购物车结算失败: ' . $e->getMessage());
            json_response(['success' => false, 'message' => '结算失败，请稍后再试']);
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
