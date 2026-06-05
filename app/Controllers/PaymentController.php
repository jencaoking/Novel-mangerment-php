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

        // 查找订单
        $order = $this->orderModel->findByOrderNo($outTradeNo);
        if (!$order) {
            error_log('支付宝异步通知：订单不存在 - ' . $outTradeNo);
            echo 'fail';
            return;
        }

        // 幂等性检查：如果订单已支付，直接返回成功
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
