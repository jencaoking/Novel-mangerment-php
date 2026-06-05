<?php
/**
 * BookMusic Mall - 下载处理
 */

require_once 'includes/auth.php';
require_once 'includes/db.php';

// 要求用户登录
requireLogin();

// 获取商品ID
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($productId <= 0) {
    die('无效的商品ID');
}

// 检查用户是否已购买
if (!hasPurchased(getCurrentUserId(), $productId)) {
    die('您还未购买此商品，无法下载');
}

// 获取商品信息
$stmt = $db->prepare("SELECT * FROM products WHERE id = ? AND status = 1");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    die('商品不存在');
}

// 确定文件路径
$filePath = UPLOAD_PATH . ($product['type'] === 'novel' ? 'novels/' : 'music/') . $product['file_path'];

if (!file_exists($filePath)) {
    die('文件不存在');
}

// 获取订单信息
$stmt = $db->prepare("
    SELECT id FROM orders 
    WHERE user_id = ? AND product_id = ? AND status = 'paid' 
    ORDER BY pay_time DESC 
    LIMIT 1
");
$stmt->execute([getCurrentUserId(), $productId]);
$order = $stmt->fetch();

if (!$order) {
    die('没有找到有效的已支付订单，无法下载。');
}

// 记录下载
$stmt = $db->prepare("
    INSERT INTO downloads (user_id, product_id, order_id, ip, user_agent) 
    VALUES (?, ?, ?, ?, ?)
");
$stmt->execute([
    getCurrentUserId(),
    $productId,
    $order['id'],
    getClientIP(),
    getUserAgent()
]);

// 更新下载次数
$stmt = $db->prepare("UPDATE products SET downloads = downloads + 1 WHERE id = ?");
$stmt->execute([$productId]);

// 设置下载头
$mimeType = $product['type'] === 'novel' ? 'text/plain' : 'audio/mpeg';
$fileName = $product['title'] . ($product['type'] === 'novel' ? '.txt' : '.mp3');

header('Content-Description: File Transfer');
header('Content-Type: ' . $mimeType);
header('Content-Disposition: attachment; filename="' . basename($fileName) . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . filesize($filePath));

// 清除输出缓冲
ob_clean();
flush();

// 读取文件并输出
readfile($filePath);
exit;
