<?php
/**
 * BookMusic Mall - 微信支付SDK封装类
 * 基于官方 wechatpay/wechatpay V3 SDK
 */

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Util\PemUtil;

class WechatPaySDK
{
    private $instance;
    private $mchId;
    private $appId;
    private $notifyUrl;

    public function __construct()
    {
        // 检查必要的配置常量是否已定义
        if (!defined('WECHAT_MCH_ID') || !defined('WECHAT_APP_ID') || 
            !defined('WECHAT_API_V3_KEY') || !defined('WECHAT_CERT_SERIAL') ||
            !defined('WECHAT_PRIVATE_KEY_PATH') || !defined('WECHAT_CERT_PATH') ||
            !defined('WECHAT_PLATFORM_CERT_SERIAL') || !defined('WECHAT_NOTIFY_URL')) {
            throw new \Exception('微信支付配置不完整，请检查 includes/config.php 中的 WECHAT_* 配置项');
        }

        $this->mchId = WECHAT_MCH_ID;
        $this->appId = WECHAT_APP_ID;
        $this->notifyUrl = WECHAT_NOTIFY_URL;

        // 构造商户私钥
        if (!file_exists(WECHAT_PRIVATE_KEY_PATH)) {
            throw new \Exception('微信支付商户私钥文件不存在: ' . WECHAT_PRIVATE_KEY_PATH);
        }
        if (!file_exists(WECHAT_CERT_PATH)) {
            throw new \Exception('微信支付平台证书文件不存在: ' . WECHAT_CERT_PATH);
        }

        $merchantPrivateKeyInstance = Rsa::from(
            file_get_contents(WECHAT_PRIVATE_KEY_PATH),
            Rsa::KEY_TYPE_PRIVATE
        );

        // 构建 API V3 客户端
        $this->instance = Builder::factory([
            'mchid'      => $this->mchId,
            'serial'     => WECHAT_CERT_SERIAL,
            'privateKey' => $merchantPrivateKeyInstance,
            'certs'      => [
                WECHAT_PLATFORM_CERT_SERIAL => PemUtil::loadCertificate(WECHAT_CERT_PATH)
            ],
        ]);
    }

    /**
     * Native支付（适用于PC端扫码）
     * @param string $orderNo 商户订单号
     * @param float $amount 订单金额（元）
     * @param string $description 订单描述
     * @return array 包含 code_url 的响应数据
     */
    public function nativePay($orderNo, $amount, $description)
    {
        $resp = $this->instance->chain('v3/pay/transactions/native')->post([
            'json' => [
                'mchid'        => $this->mchId,
                'out_trade_no' => $orderNo,
                'appid'        => $this->appId,
                'description'  => $description,
                'notify_url'   => $this->notifyUrl,
                'amount'       => [
                    'total'    => intval($amount * 100), // 单位为分
                    'currency' => 'CNY'
                ],
            ],
        ]);

        return json_decode($resp->getBody(), true);
    }

    /**
     * JSAPI支付（适用于小程序和公众号）
     * @param string $orderNo 商户订单号
     * @param float $amount 订单金额（元）
     * @param string $description 订单描述
     * @param string $openId 用户openid
     * @return array 调起支付的参数
     */
    public function jsapiPay($orderNo, $amount, $description, $openId)
    {
        $resp = $this->instance->chain('v3/pay/transactions/jsapi')->post([
            'json' => [
                'mchid'        => $this->mchId,
                'out_trade_no' => $orderNo,
                'appid'        => $this->appId,
                'description'  => $description,
                'notify_url'   => $this->notifyUrl,
                'amount'       => [
                    'total'    => intval($amount * 100),
                    'currency' => 'CNY'
                ],
                'payer' => [
                    'openid' => $openId
                ],
            ],
        ]);

        $result = json_decode($resp->getBody(), true);

        // 构建调起支付的参数
        $prepayId = $result['prepay_id'] ?? '';
        if ($prepayId) {
            $timeStamp = (string)time();
            $nonceStr = $this->generateNonceStr();
            $package = 'prepay_id=' . $prepayId;

            $signParams = [
                'appId'     => $this->appId,
                'timeStamp' => $timeStamp,
                'nonceStr'  => $nonceStr,
                'package'   => $package
            ];

            $paySign = $this->signParams($signParams);

            return [
                'appId'     => $this->appId,
                'timeStamp' => $timeStamp,
                'nonceStr'  => $nonceStr,
                'package'   => $package,
                'paySign'   => $paySign,
                'signType'  => 'RSA'
            ];
        }

        return $result;
    }

    /**
     * 验证微信支付异步通知
     * @param array $headers 通知头信息
     * @param string $body 通知体
     * @return bool 验证结果
     */
    public function verifyNotify($headers, $body)
    {
        // 验证签名
        $signature = $headers['Wechatpay-Signature'] ?? '';
        $timestamp = $headers['Wechatpay-Timestamp'] ?? '';
        $nonce = $headers['Wechatpay-Nonce'] ?? '';
        $serial = $headers['Wechatpay-Serial'] ?? '';

        if (!$signature || !$timestamp || !$nonce) {
            return false;
        }

        $message = $timestamp . "\n" . $nonce . "\n" . $body . "\n";
        $cert = PemUtil::loadCertificate(WECHAT_CERT_PATH);

        $result = openssl_verify(
            $message,
            base64_decode($signature),
            $cert,
            OPENSSL_ALGO_SHA256
        );

        return $result === 1;
    }

    /**
     * 解密通知数据
     * @param string $ciphertext 密文
     * @param string $associatedData 附加数据
     * @param string $nonce 随机数
     * @return array 解密后的数据
     */
    public function decryptNotifyData($ciphertext, $associatedData, $nonce)
    {
        $ciphertextBinary = base64_decode($ciphertext);
        $key = hash('sha256', WECHAT_API_V3_KEY, true);

        $authTag = substr($ciphertextBinary, -16);
        $ciphertextBody = substr($ciphertextBinary, 0, -16);

        $decrypted = openssl_decrypt(
            $ciphertextBody,
            'aes-256-gcm',
            $key,
            OPENSSL_RAW_DATA,
            $nonce,
            $authTag,
            $associatedData
        );

        return json_decode($decrypted, true);
    }

    /**
     * 查询订单
     * @param string $orderNo 商户订单号
     * @return array 订单信息
     */
    public function queryOrder($orderNo)
    {
        $resp = $this->instance->chain('v3/pay/transactions/out-trade-no/' . $orderNo)
            ->get([
                'query' => [
                    'mchid' => $this->mchId
                ]
            ]);

        return json_decode($resp->getBody(), true);
    }

    /**
     * 关闭订单
     * @param string $orderNo 商户订单号
     * @return bool 关闭结果
     */
    public function closeOrder($orderNo)
    {
        try {
            $resp = $this->instance->chain('v3/pay/transactions/out-trade-no/' . $orderNo . '/close')
                ->post([
                    'json' => [
                        'mchid' => $this->mchId
                    ]
                ]);

            return $resp->getStatusCode() === 204;
        } catch (\Exception $e) {
            error_log('微信支付关闭订单失败: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 验证回调签名参数
     * @param array $params 待签名的参数
     * @return string 签名字符串
     */
    private function signParams($params)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $signStr .= $key . '=' . $value . '&';
            }
        }
        $signStr = rtrim($signStr, '&');

        $privateKey = file_get_contents(WECHAT_PRIVATE_KEY_PATH);
        $keyInstance = Rsa::from($privateKey, Rsa::KEY_TYPE_PRIVATE);

        $signature = '';
        openssl_sign($signStr, $signature, $keyInstance, OPENSSL_ALGO_SHA256);

        return base64_encode($signature);
    }

    /**
     * 生成随机字符串
     * @param int $length 长度
     * @return string 随机字符串
     */
    private function generateNonceStr($length = 32)
    {
        $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $str = '';
        for ($i = 0; $i < $length; $i++) {
            $str .= $chars[mt_rand(0, strlen($chars) - 1)];
        }
        return $str;
    }
}
