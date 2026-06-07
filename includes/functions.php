<?php
/**
 * BookMusic Mall - 公共函数库
 */

require_once __DIR__ . '/config.php';

/**
 * 安全输出HTML内容
 */
function e($string) {
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * 转义 LIKE 查询的特殊字符
 * 防止 LIKE 通配符攻击
 * 
 * @param string $keyword 搜索关键词
 * @return string 转义后的关键词（不含百分号，可直接用于 LIKE 查询）
 */
function escapeLike($keyword) {
    // 先转义反斜杠，再转义通配符
    // 这样可以确保用户输入的 \、% 和 _ 都被正确转义
    $keyword = addcslashes($keyword, '\\');
    $keyword = str_replace(['%', '_'], ['\%', '\_'], $keyword);
    return $keyword;
}

/**
 * 生成CSRF Token
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * 验证CSRF Token
 */
function verifyCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * 密码加密
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
}

/**
 * 密码验证
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * 生成唯一订单号
 * 使用更高熵的随机数防止碰撞
 */
function generateOrderNo() {
    return date('YmdHis') . bin2hex(random_bytes(4));
}

/**
 * 格式化价格
 */
function formatPrice($price) {
    return '¥' . number_format($price, 2, '.', ',');
}

/**
 * 格式化文件大小
 */
function formatFileSize($bytes) {
    if ($bytes >= 1073741824) {
        return number_format($bytes / 1073741824, 2) . ' GB';
    } elseif ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    } else {
        return $bytes . ' bytes';
    }
}

/**
 * 验证邮箱格式
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * 验证用户名格式
 */
function isValidUsername($username) {
    return preg_match('/^[a-zA-Z0-9_\x{4e00}-\x{9fa5}]{3,20}$/u', $username);
}

/**
 * 获取客户端IP
 * 注意：不在反向代理后面时，直接使用 REMOTE_ADDR
 * 如果在代理后面，应配置信任的代理白名单
 */
function getClientIP() {
    // 如果需要在反向代理后获取真实IP，应该只信任配置的代理
    // 这里采用安全策略：直接返回 REMOTE_ADDR
    if (!empty($_SERVER['REMOTE_ADDR'])) {
        return $_SERVER['REMOTE_ADDR'];
    }
    return '0.0.0.0';
}

/**
 * 获取User Agent
 */
function getUserAgent() {
    return $_SERVER['HTTP_USER_AGENT'] ?? '';
}

/**
 * 重定向
 * 防止 HTTP 头注入攻击
 */
function redirect($url) {
    // 移除可能的换行符，防止头注入
    $url = preg_replace('/[\r\n]/', '', $url);
    header("Location: $url");
    exit;
}

/**
 * JSON响应
 */
function jsonResponse($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 上传文件
 */
function uploadFile($file, $targetDir, $allowedTypes, $maxSize) {
    if (!isset($file['tmp_name']) || empty($file['tmp_name'])) {
        return ['success' => false, 'message' => '没有上传文件'];
    }
    
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => '文件上传出错'];
    }
    
    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => '文件大小超出限制'];
    }
    
    $fileExt = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($fileExt, $allowedTypes)) {
        return ['success' => false, 'message' => '不支持的文件类型'];
    }
    
    $allowedMimeTypes = [
        'jpg' => ['image/jpeg', 'image/pjpeg'],
        'jpeg' => ['image/jpeg', 'image/pjpeg'],
        'png' => ['image/png', 'image/x-png'],
        'gif' => ['image/gif'],
        'mp3' => ['audio/mpeg', 'audio/mp3', 'audio/x-mpeg'],
        'wav' => ['audio/wav', 'audio/x-wav'],
        'ogg' => ['audio/ogg', 'application/ogg'],
        'txt' => ['text/plain']
    ];
    
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $detectedMimeType = $finfo->file($file['tmp_name']);
    
    // 对所有文件类型进行 MIME 验证
    if (isset($allowedMimeTypes[$fileExt]) && !in_array($detectedMimeType, $allowedMimeTypes[$fileExt])) {
        return ['success' => false, 'message' => '文件类型与扩展名不匹配'];
    }
    
    $fileName = uniqid() . '.' . $fileExt;
    $targetPath = $targetDir . $fileName;
    
    if (!is_dir($targetDir)) {
        mkdir($targetDir, 0755, true);
    }
    
    if (move_uploaded_file($file['tmp_name'], $targetPath)) {
        return ['success' => true, 'filename' => $fileName, 'path' => $targetPath];
    }
    
    return ['success' => false, 'message' => '文件保存失败'];
}

/**
 * 分页函数
 */
function paginate($total, $page, $perPage = ITEMS_PER_PAGE) {
    $totalPages = ceil($total / $perPage);
    $page = max(1, min($page, $totalPages));
    $offset = ($page - 1) * $perPage;
    
    return [
        'total' => $total,
        'per_page' => $perPage,
        'current_page' => $page,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_previous' => $page > 1,
        'has_next' => $page < $totalPages
    ];
}

/**
 * 生成随机字符串
 */
function generateRandomString($length = 16) {
    $bytes = random_bytes((int)ceil($length / 2));
    return substr(bin2hex($bytes), 0, $length);
}

/**
 * 时间格式化
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) {
        return '刚刚';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . '分钟前';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . '小时前';
    } elseif ($diff < 2592000) {
        return floor($diff / 86400) . '天前';
    } else {
        return date('Y-m-d', $timestamp);
    }
}

/**
 * 截取字符串
 */
function truncate($string, $length = 100, $suffix = '...') {
    if (mb_strlen($string, 'UTF-8') <= $length) {
        return $string;
    }
    return mb_substr($string, 0, $length, 'UTF-8') . $suffix;
}

/**
 * 获取订单状态文本
 */
function getOrderStatusText($status) {
    $statusMap = [
        'pending' => '待支付',
        'paid' => '已支付',
        'completed' => '已完成',
        'cancelled' => '已取消'
    ];
    return $statusMap[$status] ?? '未知';
}

/**
 * 获取订单状态徽章颜色
 */
function getOrderStatusBadge($status) {
    $badgeMap = [
        'pending' => 'warning',
        'paid' => 'success',
        'completed' => 'info',
        'cancelled' => 'secondary'
    ];
    return $badgeMap[$status] ?? 'light';
}

/**
 * 获取商品类型文本
 */
function getProductTypeText($type) {
    return $type === 'novel' ? '小说' : '音乐';
}

/**
 * 获取用户状态文本
 */
function getUserStatusText($status) {
    return $status == 1 ? '正常' : '禁用';
}

/**
 * 获取用户状态徽章颜色
 */
function getUserStatusBadge($status) {
    return $status == 1 ? 'success' : 'danger';
}

/**
 * 使用 Resend API 发送邮件
 * @param string $to 收件人邮箱
 * @param string $subject 邮件主题
 * @param string $htmlContent HTML 内容
 * @param string $from 发件人邮箱（可选，默认使用 onboarding@resend.dev）
 * @return array 发送结果
 */
function sendEmailWithResend($to, $subject, $htmlContent, $from = 'onboarding@resend.dev') {
    require_once __DIR__ . '/ResendSDK.php';
    
    if (RESEND_API_KEY === 're_xxxxxxxxx') {
        return [
            'success' => false,
            'message' => '请先配置 RESEND_API_KEY'
        ];
    }
    
    $resend = new ResendSDK(RESEND_API_KEY);
    
    $params = [
        'from' => $from,
        'to' => [$to], // Resend API 需要数组格式
        'subject' => $subject,
        'html' => $htmlContent
    ];
    
    return $resend->sendEmail($params);
}
