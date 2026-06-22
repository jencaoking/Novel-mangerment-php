<?php
/**
 * BookMusic Mall - 速率限制模块
 * 基于文件的简单速率限制器
 */

define('RATE_LIMIT_DIR', __DIR__ . '/../logs/rate_limit/');

// 确保速率限制目录存在
if (!is_dir(RATE_LIMIT_DIR)) {
    mkdir(RATE_LIMIT_DIR, 0755, true);
}

/**
 * 检查是否超过速率限制
 * @param string $key 限制标识（如 IP + 操作类型）
 * @param int $maxAttempts 最大尝试次数
 * @param int $timeWindow 时间窗口（秒）
 * @return array ['allowed' => bool, 'retry_after' => int]
 */
function checkRateLimit($key, $maxAttempts = 5, $timeWindow = 300) {
    $file = RATE_LIMIT_DIR . md5($key) . '.json';
    
    $data = ['attempts' => 0, 'first_attempt' => time()];
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true) ?: $data;
    }
    
    $now = time();
    
    // 如果时间窗口已过期，重置计数器
    if ($now - $data['first_attempt'] > $timeWindow) {
        $data = ['attempts' => 0, 'first_attempt' => $now];
    }
    
    // 检查是否超过限制
    if ($data['attempts'] >= $maxAttempts) {
        $retryAfter = $timeWindow - ($now - $data['first_attempt']);
        return ['allowed' => false, 'retry_after' => max(0, $retryAfter)];
    }
    
    return ['allowed' => true, 'retry_after' => 0];
}

/**
 * 记录一次尝试
 * @param string $key 限制标识
 */
function recordAttempt($key) {
    $file = RATE_LIMIT_DIR . md5($key) . '.json';
    
    $data = ['attempts' => 0, 'first_attempt' => time()];
    
    if (file_exists($file)) {
        $content = file_get_contents($file);
        $data = json_decode($content, true) ?: $data;
    }
    
    $now = time();
    
    // 如果时间窗口已过期，重置计数器
    if ($now - $data['first_attempt'] > 300) {
        $data = ['attempts' => 0, 'first_attempt' => $now];
    }
    
    $data['attempts']++;
    $data['last_attempt'] = $now;
    
    file_put_contents($file, json_encode($data), LOCK_EX);
}

/**
 * 清除速率限制记录
 * @param string $key 限制标识
 */
function clearRateLimit($key) {
    $file = RATE_LIMIT_DIR . md5($key) . '.json';
    if (file_exists($file)) {
        unlink($file);
    }
}

/**
 * 获取客户端 IP
 */
function getRateLimitKey() {
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return $ip;
}
