<?php
namespace App\Controllers;

use App\Models\OrderModel;
use App\Models\ProductModel;

class PaymentController
{
    protected $orderModel;
    protected $productModel;
    protected $alipaySDK;
    protected $wechatPaySDK;

    public function __construct(
        OrderModel $orderModel,
        ProductModel $productModel,
        \AlipaySDK $alipaySDK,
        \App\Includes\WechatPaySDK $wechatPaySDK
    ) {
        $this->orderModel = $orderModel;
        $this->productModel = $productModel;
        $this->alipaySDK = $alipaySDK;
        $this->wechatPaySDK = $wechatPaySDK;
    }

    /**
     * 支付页面 - 根据支付渠道重定向到对应支付页面
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

        $order = $this->orderModel->find($orderId);

        if (!$order) {
            $_SESSION['error'] = '订单不存在';
            redirect('/user/orders');
        }

        if ($order['user_id'] != getCurrentUserId()) {
            $_SESSION['error'] = '无权操作此订单';
            redirect('/user/orders');
        }

        if ($order['status'] !== 'pending') {
            $_SESSION['error'] = '订单状态异常，无法支付';
            redirect('/user/orders');
        }

        $product = $this->productModel->find($order['product_id']);
        if (!$product) {
            $_SESSION['error'] = '商品不存在';
            redirect('/user/orders');
        }

        // 根据支付渠道选择支付方式
        $paymentChannel = $order['payment_channel'] ?? 'alipay';

        try {
            if ($paymentChannel === 'wechat') {
                // 微信支付
                $result = $this->wechatPaySDK->nativePay(
                    $order['order_no'],
                    $order['price'],
                    $product['title']
                );

                if (isset($result['code_url'])) {
                    // 将 code_url 存入 session，用于前端生成二维码
                    $_SESSION['wechat_pay_qrcode'] = $result['code_url'];
                    $_SESSION['wechat_pay_order_no'] = $order['order_no'];
                    $_SESSION['wechat_pay_amount'] = $order['price'];
                    redirect('/payment/wechat/qr/' . $orderId);
                } else {
                    throw new \Exception('微信支付下单失败');
                }
            } else {
                // 支付宝支付
                $payUrl = $this->alipaySDK->createPagePayUrl(
                    $order['order_no'],
                    $order['price'],
                    $product['title'],
                    'BookMusic Mall - ' . getProductTypeText($product['type'])
                );

                header('Location: ' . $payUrl);
                exit;
            }

        } catch (\Exception $e) {
            error_log('支付跳转失败: ' . $e->getMessage());
            $_SESSION['error'] = '支付跳转失败，请稍后再试';
            redirect('/user/orders');
        }
    }

    /**
     * 微信支付二维码展示页面
     */
    public function wxpay($orderId)
    {
        if (!isLoggedIn()) {
            redirect('/login');
        }

        $orderId = (int)$orderId;

        if ($orderId <= 0) {
            $_SESSION['error'] = '无效的订单号';
            redirect('/user/orders');
        }

        $order = $this->orderModel->find($orderId);

        if (!$order || $order['user_id'] != getCurrentUserId()) {
            $_SESSION['error'] = '无权操作此订单';
            redirect('/user/orders');
        }

        $codeUrl = $_SESSION['wechat_pay_qrcode'] ?? '';

        if (empty($codeUrl)) {
            $_SESSION['error'] = '支付信息已过期，请重新发起支付';
            redirect('/user/orders');
        }

        // 获取商品信息
        $product = $this->productModel->find($order['product_id']);
        $productTitle = $product ? $product['title'] : '';

        include __DIR__ . '/../../views/user/wxpay.phtml';
        exit;
    }

    /**
     * 支付宝异步通知
     */
    public function notify()
    {
        $data = $_POST;

        error_log('支付宝异步通知收到数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        if (!$this->alipaySDK->verifyNotify($data)) {
            error_log('支付宝异步通知签名验证失败');
            echo 'fail';
            return;
        }

        // 验证 app_id 是否匹配
        $notifyAppId = $data['app_id'] ?? '';
        if ($notifyAppId !== ALIPAY_APP_ID) {
            error_log('支付宝异步通知：app_id 不匹配 - 收到:' . $notifyAppId . ', 期望:' . ALIPAY_APP_ID);
            echo 'fail';
            return;
        }

        $outTradeNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['trade_no'] ?? '';
        $tradeStatus = $data['trade_status'] ?? '';
        $totalAmount = $data['total_amount'] ?? '0';

        // 只有 TRADE_SUCCESS 和 TRADE_FINISHED 才表示支付成功
        if ($tradeStatus !== 'TRADE_SUCCESS' && $tradeStatus !== 'TRADE_FINISHED') {
            error_log('支付宝异步通知：交易未成功 status=' . $tradeStatus);
            echo 'fail';
            return;
        }

        $isBatchOrder = strpos($outTradeNo, 'BATCH_') === 0;

        if ($isBatchOrder) {
            $this->handleBatchOrderNotify($outTradeNo, $tradeNo, $totalAmount, 'alipay');
        } else {
            $this->handleSingleOrderNotify($outTradeNo, $tradeNo, $totalAmount, 'alipay');
        }
    }

    /**
     * 微信支付异步通知
     */
    public function wechatNotify()
    {
        $headers = getallheaders();
        $body = file_get_contents('php://input');

        error_log('微信支付异步通知收到数据: ' . $body);

        // 验证签名
        if (!$this->wechatPaySDK->verifyNotify($headers, $body)) {
            error_log('微信支付异步通知签名验证失败');
            echo 'FAIL';
            return;
        }

        $notifyData = json_decode($body, true);

        if (!$notifyData || !isset($notifyData['event_type'])) {
            echo 'FAIL';
            return;
        }

        // 处理支付成功通知
        if ($notifyData['event_type'] === 'TRANSACTION.SUCCESS') {
            $resource = $notifyData['resource'] ?? [];

            $ciphertext = $resource['ciphertext'] ?? '';
            $associatedData = $resource['associated_data'] ?? '';
            $nonce = $resource['nonce'] ?? '';

            if ($ciphertext && $nonce) {
                $orderData = $this->wechatPaySDK->decryptNotifyData($ciphertext, $associatedData, $nonce);

                $outTradeNo = $orderData['out_trade_no'] ?? '';
                $transactionId = $orderData['transaction_id'] ?? '';
                $tradeStatus = $orderData['trade_state'] ?? '';
                $totalAmount = isset($orderData['amount']['payer_total']) ? $orderData['amount']['payer_total'] / 100 : 0;

                if ($tradeStatus === 'SUCCESS') {
                    $isBatchOrder = strpos($outTradeNo, 'BATCH_') === 0;

                    if ($isBatchOrder) {
                        $this->handleBatchOrderNotify($outTradeNo, $transactionId, $totalAmount, 'wechat');
                    } else {
                        $this->handleSingleOrderNotify($outTradeNo, $transactionId, $totalAmount, 'wechat');
                    }
                }
            }
        }

        echo 'SUCCESS';
    }

    private function handleSingleOrderNotify($outTradeNo, $tradeNo, $totalAmount, $channel)
    {
        $order = $this->orderModel->findByOrderNo($outTradeNo);
        if (!$order) {
            error_log('支付异步通知：订单不存在 - ' . $outTradeNo);
            echo $channel === 'alipay' ? 'fail' : 'FAIL';
            return;
        }

        if ($order['status'] === 'paid' || $order['status'] === 'completed') {
            error_log('支付异步通知：订单已支付，跳过处理 - ' . $outTradeNo);
            echo $channel === 'alipay' ? 'success' : 'SUCCESS';
            return;
        }

        // 使用高精度比较（4位小数）防止金额被篡改
        if (bccomp($order['price'], (string)$totalAmount, 4) !== 0) {
            error_log('支付异步通知：金额不匹配 - 订单:' . $order['price'] . ', 通知:' . $totalAmount);
            echo $channel === 'alipay' ? 'fail' : 'FAIL';
            return;
        }

        try {
            $payTime = date('Y-m-d H:i:s');
            $this->orderModel->updateOrderPaid($order['id'], $tradeNo, $payTime);

            $this->productModel->increaseSales($order['product_id']);

            error_log('支付异步通知：订单支付成功 - ' . $outTradeNo . ', 渠道:' . $channel);
            echo $channel === 'alipay' ? 'success' : 'SUCCESS';

        } catch (\Exception $e) {
            error_log('支付异步通知：更新订单失败 - ' . $e->getMessage());
            echo $channel === 'alipay' ? 'fail' : 'FAIL';
        }
    }

    private function handleBatchOrderNotify($batchTradeNo, $tradeNo, $totalAmount, $channel)
    {
        $db = getDB();

        try {
            $sql = "SELECT id, product_id, price FROM orders WHERE trade_no = ? AND status = 'pending'";
            $stmt = $db->prepare($sql);
            $stmt->execute([$batchTradeNo]);
            $orders = $stmt->fetchAll();

            if (empty($orders)) {
                error_log('支付异步通知：批次订单不存在或已全部处理 - ' . $batchTradeNo);
                echo $channel === 'alipay' ? 'success' : 'SUCCESS';
                return;
            }

            // 计算预期总金额（使用 BCMath 确保精度）
            $expectedTotal = '0';
            foreach ($orders as $order) {
                $expectedTotal = bcadd($expectedTotal, $order['price'], 4);
            }

            // 使用高精度比较（4位小数）防止金额被篡改
            if (bccomp($expectedTotal, (string)$totalAmount, 4) !== 0) {
                error_log('支付异步通知：批次总金额不匹配 - 预期:' . $expectedTotal . ', 实际:' . $totalAmount);
                echo $channel === 'alipay' ? 'fail' : 'FAIL';
                return;
            }

            $db->beginTransaction();

            $updateStmt = $db->prepare("UPDATE orders SET status = 'paid', pay_time = ?, trade_no = ?, payment_channel = ? WHERE id = ?");
            $payTime = date('Y-m-d H:i:s');

            foreach ($orders as $order) {
                $updateStmt->execute([$payTime, $tradeNo, $channel, $order['id']]);

                $this->productModel->increaseSales($order['product_id']);
            }

            $db->commit();

            error_log('支付异步通知：批次订单支付成功 - ' . $batchTradeNo . '，共' . count($orders) . '件商品，渠道:' . $channel);
            echo $channel === 'alipay' ? 'success' : 'SUCCESS';

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('支付异步通知：批次订单更新失败 - ' . $e->getMessage());
            echo $channel === 'alipay' ? 'fail' : 'FAIL';
        }
    }

    public function returnUrl()
    {
        $data = $_GET;

        if (!$this->alipaySDK->verifyReturn($data)) {
            $_SESSION['error'] = '支付验证失败';
            redirect('/user/orders');
        }

        // 验证 app_id 是否匹配
        $returnAppId = $data['app_id'] ?? '';
        if ($returnAppId !== ALIPAY_APP_ID) {
            error_log('支付宝同步返回：app_id 不匹配 - 收到:' . $returnAppId . ', 期望:' . ALIPAY_APP_ID);
            $_SESSION['error'] = '支付验证失败';
            redirect('/user/orders');
        }

        $outTradeNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['trade_no'] ?? '';
        $tradeStatus = $data['trade_status'] ?? '';

        $order = $this->orderModel->findByOrderNo($outTradeNo);

        if (!$order) {
            $_SESSION['error'] = '订单不存在';
            redirect('/user/orders');
        }

        // 同步返回结果不可靠，根据本地订单状态判断
        if ($order['status'] === 'paid' || $order['status'] === 'completed') {
            $_SESSION['success'] = '支付成功！';
        } elseif ($order['status'] === 'pending') {
            $_SESSION['info'] = '支付处理中，请稍后查看订单状态';
        } else {
            $_SESSION['error'] = '订单状态异常';
        }

        redirect('/user/orders');
    }

    /**
     * 查询订单状态（轮询）
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
            'pay_time' => $order['pay_time'],
            'payment_channel' => $order['payment_channel'] ?? 'alipay'
        ]);
    }

    public function queryBatchStatus()
    {
        if (!isLoggedIn()) {
            json_response(['success' => false, 'message' => '请先登录'], 401);
        }

        $batchTradeNo = isset($_GET['batch_trade_no']) ? $_GET['batch_trade_no'] : '';

        if (empty($batchTradeNo) || strpos($batchTradeNo, 'BATCH_') !== 0) {
                jsonResponse(['success' => false, 'message' => '无效的批次号']);
            }

        try {
            $db = getDB();

            $sql = "SELECT id, status, pay_time FROM orders WHERE trade_no = ? AND user_id = ?";
            $stmt = $db->prepare($sql);
            $stmt->execute([$batchTradeNo, getCurrentUserId()]);
            $orders = $stmt->fetchAll();

            if (empty($orders)) {
                jsonResponse(['success' => false, 'message' => '批次订单不存在']);
            }

            $allPaid = true;
            $anyPaid = false;

            foreach ($orders as $order) {
                if ($order['status'] !== 'paid') {
                    $allPaid = false;
                } else {
                    $anyPaid = true;
                }
            }

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
     * 购物车结算 - 支持选择支付渠道
     */
    public function checkoutCart()
    {
        if (!isLoggedIn()) {
            json_response(['success' => false, 'message' => '请先登录'], 401);
        }

        $userId = getCurrentUserId();

        $productIds = isset($_POST['product_ids']) ? $_POST['product_ids'] : [];
        $paymentChannel = isset($_POST['payment_channel']) ? $_POST['payment_channel'] : 'alipay';

        // 验证支付渠道
        if (!in_array($paymentChannel, ['alipay', 'wechat'])) {
            $paymentChannel = 'alipay';
        }

        if (!is_array($productIds) || empty($productIds)) {
            json_response(['success' => false, 'message' => '请选择要结算的商品']);
            exit();
        }

        $productIds = array_map('intval', $productIds);
        $productIds = array_filter($productIds, function($id) {
            return $id > 0;
        });

        if (empty($productIds)) {
            json_response(['success' => false, 'message' => '无效的商品ID']);
            exit();
        }

        try {
            $db = getDB();

            $placeholders = implode(',', array_fill(0, count($productIds), '?'));
            $sql = "SELECT p.id, p.price, p.title, p.type
                    FROM cart c
                    LEFT JOIN products p ON c.product_id = p.id
                    WHERE c.user_id = ? AND c.product_id IN (" . $placeholders . ")";

            $params = array_merge([$userId], $productIds);
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            $cartItems = $stmt->fetchAll();

            if (empty($cartItems)) {
                json_response(['success' => false, 'message' => '购物车中没有选中的商品']);
            }

            // 计算总金额（使用 BCMath 确保精度）
            $totalAmount = '0';
            foreach ($cartItems as $item) {
                $totalAmount = bcadd($totalAmount, $item['price'], 4);
            }

            $batchTradeNo = 'BATCH_' . date('YmdHis') . '_' . mt_rand(1000, 9999);
            $orderTitles = '合并订单等' . count($cartItems) . '件商品';

            $db->beginTransaction();

            // 创建订单时记录支付渠道
            $orderStmt = $db->prepare(
                "INSERT INTO orders (order_no, user_id, product_id, price, trade_no, payment_channel, status)
                 VALUES (?, ?, ?, ?, ?, ?, 'pending')"
            );

            foreach ($cartItems as $item) {
                $orderStmt->execute([
                    generateOrderNo(),
                    $userId,
                    $item['id'],
                    $item['price'],
                    $batchTradeNo,
                    $paymentChannel
                ]);
            }

            $deletePlaceholders = implode(',', array_fill(0, count($productIds), '?'));
            $deleteSql = "DELETE FROM cart WHERE user_id = ? AND product_id IN (" . $deletePlaceholders . ")";
            $deleteParams = array_merge([$userId], $productIds);
            $db->prepare($deleteSql)->execute($deleteParams);

            $db->commit();

            // 根据支付渠道调用不同的支付方式
            if ($paymentChannel === 'wechat') {
                // 微信支付
                $result = $this->wechatPaySDK->nativePay(
                    $batchTradeNo,
                    (float)$totalAmount,
                    $orderTitles
                );

                if (isset($result['code_url'])) {
                    json_response([
                        'success' => true,
                        'channel' => 'wechat',
                        'batch_trade_no' => $batchTradeNo,
                        'total_amount' => sprintf('%.2f', (float)$totalAmount),
                        'code_url' => $result['code_url'],
                        'item_count' => count($cartItems)
                    ]);
                } else {
                    throw new \Exception('微信支付下单失败');
                }
            } else {
                // 支付宝支付
                $payUrl = $this->alipaySDK->createPagePayUrl(
                    $batchTradeNo,
                    (float)$totalAmount,
                    $orderTitles,
                    'BookMusic Mall - 合并支付'
                );

                json_response([
                    'success' => true,
                    'channel' => 'alipay',
                    'batch_trade_no' => $batchTradeNo,
                    'total_amount' => sprintf('%.2f', (float)$totalAmount),
                    'pay_url' => $payUrl,
                    'item_count' => count($cartItems)
                ]);
            }

        } catch (\Exception $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            error_log('购物车结算失败: ' . $e->getMessage());
            json_response(['success' => false, 'message' => '结算失败，请稍后再试']);
        }
    }
}

if (!function_exists('json_response')) {
    function json_response($data) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }
}
