<?php
/**
 * BookMusic Mall - 支付宝支付SDK封装类
 * 轻量级实现,不依赖第三方库
 */

class AlipaySDK {
    
    private $appId;
    private $privateKey;
    private $alipayPublicKey;
    private $gatewayUrl;
    private $returnUrl;
    private $notifyUrl;
    private $signType = 'RSA2';
    private $charset = 'UTF-8';
    private $format = 'json';
    private $version = '1.0';
    
    public function __construct() {
        $this->appId = ALIPAY_APP_ID;
        $this->privateKey = ALIPAY_PRIVATE_KEY;
        $this->alipayPublicKey = ALIPAY_PUBLIC_KEY;
        $this->gatewayUrl = ALIPAY_GATEWAY_URL;
        $this->returnUrl = ALIPAY_RETURN_URL;
        $this->notifyUrl = ALIPAY_NOTIFY_URL;
    }
    
    /**
     * 生成PC网站支付链接
     * @param string $outTradeNo 商户订单号
     * @param float $totalAmount 订单金额
     * @param string $subject 订单标题
     * @param string $body 订单描述
     * @return string 支付URL
     */
    public function createPagePayUrl($outTradeNo, $totalAmount, $subject, $body = '') {
        $bizContent = [
            'out_trade_no' => $outTradeNo,
            'total_amount' => sprintf('%.2f', $totalAmount),
            'subject' => $subject,
            'body' => $body,
            'product_code' => 'FAST_INSTANT_TRADE_PAY'
        ];
        
        $params = $this->buildRequestParams('alipay.trade.page.pay', $bizContent);
        
        return $this->gatewayUrl . '?' . http_build_query($params);
    }
    
    /**
     * 生成手机网站支付链接(H5)
     * @param string $outTradeNo 商户订单号
     * @param float $totalAmount 订单金额
     * @param string $subject 订单标题
     * @param string $body 订单描述
     * @return string 支付URL
     */
    public function createWapPayUrl($outTradeNo, $totalAmount, $subject, $body = '') {
        $bizContent = [
            'out_trade_no' => $outTradeNo,
            'total_amount' => sprintf('%.2f', $totalAmount),
            'subject' => $subject,
            'body' => $body,
            'product_code' => 'QUICK_WAP_WAY'
        ];
        
        $params = $this->buildRequestParams('alipay.trade.wap.pay', $bizContent);
        
        return $this->gatewayUrl . '?' . http_build_query($params);
    }
    
    /**
     * 验证异步通知签名
     * @param array $data POST数据
     * @return bool 验证结果
     */
    public function verifyNotify($data) {
        if (empty($data['sign']) || empty($data['sign_type'])) {
            return false;
        }
        
        $sign = $data['sign'];
        $signType = $data['sign_type'];
        
        // 移除sign和sign_type字段
        unset($data['sign'], $data['sign_type']);
        
        // 过滤空值并排序
        $data = array_filter($data, function($value) {
            return $value !== '' && $value !== null;
        });
        ksort($data);
        
        // 构建待签名字符串
        $signStr = '';
        foreach ($data as $key => $value) {
            $signStr .= $key . '=' . $value . '&';
        }
        $signStr = rtrim($signStr, '&');
        
        // 验证签名
        $publicKey = $this->formatPublicKey($this->alipayPublicKey);
        $signature = base64_decode($sign);
        
        if ($signType === 'RSA2') {
            $result = openssl_verify($signStr, $signature, $publicKey, OPENSSL_ALGO_SHA256);
        } else {
            $result = openssl_verify($signStr, $signature, $publicKey, OPENSSL_ALGO_SHA1);
        }
        
        return $result === 1;
    }
    
    /**
     * 验证同步返回签名
     * @param array $data GET数据
     * @return bool 验证结果
     */
    public function verifyReturn($data) {
        return $this->verifyNotify($data);
    }
    
    /**
     * 构建请求参数
     * @param string $method API方法名
     * @param array $bizContent 业务参数
     * @return array 完整请求参数
     */
    private function buildRequestParams($method, $bizContent) {
        $params = [
            'app_id' => $this->appId,
            'method' => $method,
            'charset' => $this->charset,
            'sign_type' => $this->signType,
            'timestamp' => date('Y-m-d H:i:s'),
            'version' => $this->version,
            'biz_content' => json_encode($bizContent, JSON_UNESCAPED_UNICODE),
            'notify_url' => $this->notifyUrl,
            'return_url' => $this->returnUrl
        ];
        
        // 生成签名
        $params['sign'] = $this->generateSign($params);
        
        return $params;
    }
    
    /**
     * 生成签名
     * @param array $params 请求参数
     * @return string 签名字符串
     */
    private function generateSign($params) {
        // 移除sign字段
        unset($params['sign']);
        
        // 过滤空值并排序
        $params = array_filter($params, function($value) {
            return $value !== '' && $value !== null;
        });
        ksort($params);
        
        // 构建待签名字符串
        $signStr = '';
        foreach ($params as $key => $value) {
            $signStr .= $key . '=' . $value . '&';
        }
        $signStr = rtrim($signStr, '&');
        
        // 使用私钥签名
        $privateKey = $this->formatPrivateKey($this->privateKey);
        $signature = '';
        
        if ($this->signType === 'RSA2') {
            openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA256);
        } else {
            openssl_sign($signStr, $signature, $privateKey, OPENSSL_ALGO_SHA1);
        }
        
        return base64_encode($signature);
    }
    
    /**
     * 格式化私钥
     * @param string $privateKey 私钥内容
     * @return string 格式化后的私钥
     */
    private function formatPrivateKey($privateKey) {
        $privateKey = str_replace(["\r", "\n"], '', $privateKey);
        $privateKey = str_replace('-----BEGIN RSA PRIVATE KEY-----', '', $privateKey);
        $privateKey = str_replace('-----END RSA PRIVATE KEY-----', '', $privateKey);
        $privateKey = str_replace('-----BEGIN PRIVATE KEY-----', '', $privateKey);
        $privateKey = str_replace('-----END PRIVATE KEY-----', '', $privateKey);
        $privateKey = wordwrap($privateKey, 64, "\n", true);
        
        return "-----BEGIN RSA PRIVATE KEY-----\n" . $privateKey . "\n-----END RSA PRIVATE KEY-----";
    }
    
    /**
     * 格式化公钥
     * @param string $publicKey 公钥内容
     * @return string 格式化后的公钥
     */
    private function formatPublicKey($publicKey) {
        $publicKey = str_replace(["\r", "\n"], '', $publicKey);
        $publicKey = str_replace('-----BEGIN PUBLIC KEY-----', '', $publicKey);
        $publicKey = str_replace('-----END PUBLIC KEY-----', '', $publicKey);
        $publicKey = wordwrap($publicKey, 64, "\n", true);
        
        return "-----BEGIN PUBLIC KEY-----\n" . $publicKey . "\n-----END PUBLIC KEY-----";
    }
}
