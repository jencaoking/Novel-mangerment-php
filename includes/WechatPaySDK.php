<?php
namespace App\Includes;

use WeChatPay\Builder;
use WeChatPay\Crypto\Rsa;
use WeChatPay\Crypto\AesGcm;
use WeChatPay\Util\PemUtil;

/**
 * BookMusic Mall - 微信支付SDK封装类
 * 基于官方 wechatpay/wechatpay V3 SDK
 */
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

        // 检查证书文件是否存在
        if (!file_exists(WECHAT_PRIVATE_KEY_PATH)) {
            throw new \Exception('微信支付商户私钥文件不存在: ' . WECHAT_PRIVATE_KEY_PATH);
        }
        if (!file_exists(WECHAT_CERT_PATH)) {
            throw new \Exception('微信支付平台证书文件不存在: ' . WECHAT_CERT_PATH);
        }

        // 构造商户私钥
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
     * 验证并解密微信支付异步回调数据
     * @return array|false 解密后的订单数据，失败返回 false
     */
    public function verifyAndDecryptNotify()
    {
        try {
            // 1. 获取微信回调请求头和请求体
            $signature = $_SERVER['HTTP_WECHATPAY_SIGNATURE'] ?? '';
            $timestamp = $_SERVER['HTTP_WECHATPAY_TIMESTAMP'] ?? '';
            $nonce     = $_SERVER['HTTP_WECHATPAY_NONCE'] ?? '';
            $serial    = $_SERVER['HTTP_WECHATPAY_SERIAL'] ?? '';
            $body      = file_get_contents('php://input');

            if (empty($signature) || empty($timestamp) || empty($nonce) || empty($body) || empty($serial)) {
                throw new \Exception('缺失微信回调验证参数');
            }

            // 检查时间戳是否在 5 分钟内，防止重放攻击
            if (abs(time() - intval($timestamp)) > 300) {
                throw new \Exception('微信回调时间戳异常');
            }

            // 2. 构造验签字符串
            $message = "{$timestamp}\n{$nonce}\n{$body}\n";

            // 3. 加载微信支付平台公钥（证书）用于验签
            $platformPublicKeyInstance = PemUtil::loadCertificate(WECHAT_CERT_PATH);

            // 4. 验证签名
            $isVerified = Rsa::verify($message, $signature, $platformPublicKeyInstance);
            if (!$isVerified) {
                throw new \Exception('微信回调签名验证失败');
            }

            // 5. 解析请求体，准备解密 resource 数据
            $data = json_decode($body, true);
            if (!isset($data['resource'])) {
                throw new \Exception('回调数据中缺失 resource 字段');
            }

            $resource = $data['resource'];
            
            // 6. 使用 API v3 密钥进行 AES-256-GCM 解密
            $decryptedStr = AesGcm::decrypt(
                $resource['ciphertext'],
                WECHAT_API_V3_KEY,
                $resource['nonce'],
                $resource['associated_data'] ?? ''
            );

            return json_decode($decryptedStr, true);

        } catch (\Exception $e) {
            error_log('微信支付回调处理异常: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * 查询订单
     * @param string $orderNo 商户订单号
     * @return array 订单信息
     */
    public function queryOrder($orderNo)
    {
        $resp = $this->instance->chain('v3/pay/transactions/out-trade-no/' . $orderNo)->get([
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
            $resp = $this->instance->chain('v3/pay/transactions/out-trade-no/' . $orderNo . '/close')->post([
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
     * 对参数进行签名（用于生成支付参数）
     * @param array $params 待签名的参数
     * @return string 签名字符串
     */
    private function signParams($params)
    {
        ksort($params);
        $signStr = '';
        foreach ($params as $key => $value) {
            if ($value !== '' && $value !== null) {
                $signStr .= $key . '=' . $value . "\n";
            }
        }

        $merchantPrivateKeyInstance = Rsa::from(
            file_get_contents(WECHAT_PRIVATE_KEY_PATH),
            Rsa::KEY_TYPE_PRIVATE
        );

        return Rsa::sign($signStr, $merchantPrivateKeyInstance);
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
