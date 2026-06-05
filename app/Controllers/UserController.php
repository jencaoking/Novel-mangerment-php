<?php
namespace App\Controllers;

class UserController
{
    public function __construct()
    {
        requireLogin();
    }

    private function getUser()
    {
        global $db;
        $userId = getCurrentUserId();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    public function profile()
    {
        $error = '';
        $success = false;
        $user = $this->getUser();

        require __DIR__ . '/../../views/user/profile.phtml';
    }

    public function updateProfile()
    {
        global $db;
        $userId = getCurrentUserId();
        $error = '';
        $success = false;
        $user = $this->getUser();

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $error = '安全验证失败';
            } else {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');

                if (empty($username) || empty($email)) {
                    $error = '请填写所有必填字段';
                } elseif (!isValidUsername($username)) {
                    $error = '用户名格式不正确';
                } elseif (!isValidEmail($email)) {
                    $error = '邮箱格式不正确';
                } else {
                    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
                    $stmt->execute([$username, $userId]);
                    if ($stmt->fetch()) {
                        $error = '用户名已被使用';
                    } else {
                        $stmt = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
                        $stmt->execute([$email, $userId]);
                        if ($stmt->fetch()) {
                            $error = '邮箱已被使用';
                        } else {
                            $stmt = $db->prepare("UPDATE users SET username = ?, email = ? WHERE id = ?");
                            if ($stmt->execute([$username, $email, $userId])) {
                                $_SESSION['username'] = $username;
                                $_SESSION['email'] = $email;
                                $success = true;
                                $user = $this->getUser();
                            } else {
                                $error = '更新失败，请稍后再试';
                            }
                        }
                    }
                }
            }
        }

        require __DIR__ . '/../../views/user/profile.phtml';
    }

    public function orders()
    {
        global $db;
        $userId = getCurrentUserId();
        $user = $this->getUser();

        $stmt = $db->prepare("
            SELECT o.*, p.title, p.cover_image 
            FROM orders o 
            LEFT JOIN products p ON o.product_id = p.id 
            WHERE o.user_id = ? 
            ORDER BY o.create_time DESC
        ");
        $stmt->execute([$userId]);
        $orders = $stmt->fetchAll();

        require __DIR__ . '/../../views/user/orders.phtml';
    }

    public function downloads()
    {
        global $db;
        $userId = getCurrentUserId();
        $user = $this->getUser();

        $purchasedProducts = getUserPurchasedProducts($userId);

        require __DIR__ . '/../../views/user/downloads.phtml';
    }
}
