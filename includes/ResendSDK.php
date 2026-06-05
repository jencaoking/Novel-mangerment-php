<?php
/**
 * BookMusic Mall - Resend 邮件服务 SDK
 */

class ResendSDK {
    private $apiKey;
    private $baseUrl = 'https://api.resend.com';
    
    public function __construct($apiKey) {
        $this->apiKey = $apiKey;
    }
    
    /**
     * 发送邮件
     * @param array $params 邮件参数
     * @return array 响应结果
     */
    public function sendEmail($params) {
        $url = $this->baseUrl . '/emails';
        
        $headers = [
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            return [
                'success' => false,
                'message' => 'CURL Error: ' . $error
            ];
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return [
                'success' => true,
                'data' => $result,
                'http_code' => $httpCode
            ];
        } else {
            return [
                'success' => false,
                'message' => $result['message'] ?? 'Unknown error',
                'http_code' => $httpCode,
                'data' => $result
            ];
        }
    }
}
