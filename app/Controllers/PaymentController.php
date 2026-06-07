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

        try {
            $payUrl = $this->alipaySDK->createPagePayUrl(
                $order['order_no'],
                $order['price'],
                $product['title'],
                'BookMusic Mall - ' . getProductTypeText($product['type'])
            );

            header('Location: ' . $payUrl);
            exit;

        } catch (\Exception $e) {
            error_log('支付宝支付跳转失败: ' . $e->getMessage());
            $_SESSION['error'] = '支付跳转失败，请稍后再试';
            redirect('/user/orders');
        }
    }

    public function notify()
    {
        $data = $_POST;

        error_log('支付宝异步通知收到数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE));

        if (!$this->alipaySDK->verifyNotify($data)) {
            error_log('支付宝异步通知签名验证失败');
            echo 'fail';
            return;
        }

        $outTradeNo = $data['out_trade_no'] ?? '';
        $tradeNo = $data['trade_no'] ?? '';
        $tradeStatus = $data['trade_status'] ?? '';
        $totalAmount = $data['total_amount'] ?? '0';

        if ($tradeStatus !== 'TRADE_SUCCESS' && $tradeStatus !== 'TRADE_FINISHED') {
            echo 'success';
            return;
        }

        $isBatchOrder = strpos($outTradeNo, 'BATCH_') === 0;
        
        if ($isBatchOrder) {
            $this->handleBatchOrderNotify($outTradeNo, $tradeNo, $totalAmount);
        } else {
            $this->handleSingleOrderNotify($outTradeNo, $tradeNo, $totalAmount);
        }
    }

    private function handleSingleOrderNotify($outTradeNo, $tradeNo, $totalAmount)
    {
        $order = $this->orderModel->findByOrderNo($outTradeNo);
        if (!$order) {
            error_log('支付宝异步通知：订单不存在 - ' . $outTradeNo);
            echo 'fail';
            return;
        }

        if ($order['status'] === 'paid' || $order['status'] === 'completed') {
            error_log('支付宝异步通知：订单已支付，跳过处理 - ' . $outTradeNo);
            echo 'success';
            return;
        }

        if (bccomp($order['price'], $totalAmount, 2) !== 0) {
            error_log('支付宝异步通知：金额不匹配 - 订单:' . $order['price'] . ', 通知:' . $totalAmount);
