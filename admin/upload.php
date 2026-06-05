<?php
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();

$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'add_product') {
        $type = $_POST['type'] ?? '';
        $title = $_POST['title'] ?? '';
        $categoryId = (int)($_POST['category_id'] ?? 0);
        $author = $_POST['author'] ?? '';
        $description = $_POST['description'] ?? '';
        $price = (float)($_POST['price'] ?? 0);
        
        $uploadDir = UPLOAD_PATH;
        
        $coverResult = uploadFile($_FILES['cover'], $uploadDir . 'cover/', ALLOWED_IMAGE_TYPES, MAX_IMAGE_SIZE);
        if (!$coverResult['success']) {
            die('封面上传失败: ' . $coverResult['message']);
        }
        
        $fileDir = $type === 'novel' ? 'novels/' : 'music/';
        $fileTypes = $type === 'novel' ? ALLOWED_NOVEL_TYPES : ALLOWED_MUSIC_TYPES;
        $maxSize = $type === 'novel' ? MAX_NOVEL_SIZE : MAX_MUSIC_SIZE;
        
        $fileResult = uploadFile($_FILES['file'], $uploadDir . $fileDir, $fileTypes, $maxSize);
        if (!$fileResult['success']) {
            die('文件上传失败: ' . $fileResult['message']);
        }
        
        $previewPath = null;
        if ($type === 'music' && isset($_FILES['preview']) && $_FILES['preview']['error'] === UPLOAD_ERR_OK) {
            $previewResult = uploadFile($_FILES['preview'], $uploadDir . 'preview/', ['mp3'], MAX_MUSIC_SIZE);
            if ($previewResult['success']) {
                $previewPath = $previewResult['filename'];
            }
        }
        
        $stmt = $db->prepare("INSERT INTO products (title, type, category_id, author, description, cover, file_path, preview_path, price) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $title,
            $type,
            $categoryId,
            $author,
            $description,
            $coverResult['filename'],
            $fileResult['filename'],
            $previewPath,
            $price
        ]);
        
        redirect('products.php');
    }
}

redirect('products.php');
