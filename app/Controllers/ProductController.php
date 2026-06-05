<?php
namespace App\Controllers;

class ProductController {
    
    public function novels() {
        global $db;
        
        $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $sort = $_GET['sort'] ?? 'latest';
        $search = trim($_GET['search'] ?? '');
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        $where = "p.type = 'novel' AND p.status = 1";
        $params = [];

        if ($category > 0) {
            $where .= " AND p.category_id = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $where .= " AND (p.title LIKE ? OR p.author LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $orderBy = match($sort) {
            'hot' => 'p.sales DESC, p.downloads DESC',
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            default => 'p.create_time DESC'
        };

        $countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE {$where}");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $pagination = paginate($total, $page);

        $stmt = $db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE {$where}
            ORDER BY {$orderBy}
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
        ");
        $stmt->execute($params);
        $novels = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM categories WHERE type = 'novel' AND status = 1 ORDER BY sort_order");
        $stmt->execute();
        $categories = $stmt->fetchAll();

        require __DIR__ . '/../../views/novels.phtml';
    }
    
    public function music() {
        global $db;
        
        $category = isset($_GET['category']) ? (int)$_GET['category'] : 0;
        $sort = $_GET['sort'] ?? 'latest';
        $search = trim($_GET['search'] ?? '');
        $page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

        $where = "p.type = 'music' AND p.status = 1";
        $params = [];

        if ($category > 0) {
            $where .= " AND p.category_id = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $where .= " AND (p.title LIKE ? OR p.author LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $orderBy = match($sort) {
            'hot' => 'p.sales DESC, p.downloads DESC',
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            default => 'p.create_time DESC'
        };

        $countStmt = $db->prepare("SELECT COUNT(*) FROM products p WHERE {$where}");
        $countStmt->execute($params);
        $total = $countStmt->fetchColumn();

        $pagination = paginate($total, $page);

        $stmt = $db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE {$where}
            ORDER BY {$orderBy}
            LIMIT {$pagination['per_page']} OFFSET {$pagination['offset']}
        ");
        $stmt->execute($params);
        $music = $stmt->fetchAll();

        $stmt = $db->prepare("SELECT * FROM categories WHERE type = 'music' AND status = 1 ORDER BY sort_order");
        $stmt->execute();
        $categories = $stmt->fetchAll();

        require __DIR__ . '/../../views/music.phtml';
    }
    
    public function show($id) {
        global $db;
        
        $productId = (int)$id;
        
        if ($productId <= 0) {
            redirect('/');
        }

        $stmt = $db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ? AND p.status = 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            redirect('/');
        }

        $hasPurchased = false;
        if (isLoggedIn()) {
            $hasPurchased = hasPurchased(getCurrentUserId(), $productId);
        }

        $message = '';
        require __DIR__ . '/../../views/product.phtml';
    }
    
    public function buy($id) {
        global $db;
        
        $productId = (int)$id;
        
        if ($productId <= 0) {
            redirect('/');
        }

        $stmt = $db->prepare("
            SELECT p.*, c.name as category_name 
            FROM products p 
            LEFT JOIN categories c ON p.category_id = c.id 
            WHERE p.id = ? AND p.status = 1
        ");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();

        if (!$product) {
            redirect('/');
        }

        $hasPurchased = false;
        if (isLoggedIn()) {
            $hasPurchased = hasPurchased(getCurrentUserId(), $productId);
        }

        $message = '';
        if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buy'])) {
            if (!isLoggedIn()) {
                $_SESSION['redirect_url'] = "/product/{$productId}";
                redirect('/login.php');
            }
            
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $message = '安全验证失败';
            } else {
                $orderNo = generateOrderNo();
                $stmt = $db->prepare("
                    INSERT INTO orders (order_no, user_id, product_id, price, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                
                try {
                    $stmt->execute([$orderNo, getCurrentUserId(), $productId, $product['price']]);
                    $orderId = $db->lastInsertId();
                    
                    $stmt = $db->prepare("UPDATE orders SET status = 'paid', pay_time = NOW() WHERE id = ?");
                    $stmt->execute([$orderId]);
                    
                    $stmt = $db->prepare("UPDATE products SET sales = sales + 1 WHERE id = ?");
                    $stmt->execute([$productId]);
                    
                    $message = '购买成功！';
                    $hasPurchased = true;
                } catch (PDOException $e) {
                    $message = '购买失败，请稍后再试';
                }
            }
        }

        require __DIR__ . '/../../views/product.phtml';
    }
}
