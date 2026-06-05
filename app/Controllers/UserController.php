<?php
namespace App\Controllers;

use App\Models\UserModel;
use App\Models\OrderModel;

class UserController
{
    protected $userModel;
    protected $orderModel;

    public function __construct()
    {
        requireLogin();
        $this->userModel = new UserModel();
        $this->orderModel = new OrderModel();
    }

    private function getUser()
    {
        $userId = getCurrentUserId();
        return $this->userModel->find($userId);
    }

    public function index()
    {
        $userId = getCurrentUserId();
        $user = $this->getUser();
        
        $totalOrders = $this->orderModel->getUserOrderCount($userId);
        $totalSpent = $this->userModel->getTotalSpent($userId);
        
        $recentOrders = $this->orderModel->fetchAll("
            SELECT o.*, p.title, p.cover as cover_image, p.type 
            FROM orders o 
            JOIN products p ON o.product_id = p.id 
            WHERE o.user_id = ? 
            ORDER BY o.create_time DESC 
            LIMIT 5
        ", [$userId]);

        require __DIR__ . '/../../views/user/index.phtml';
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
                    if ($this->userModel->isUsernameExists($username) && $user['username'] !== $username) {
                        $error = '用户名已被使用';
                    } elseif ($this->userModel->isEmailExists($email) && $user['email'] !== $email) {
                        $error = '邮箱已被使用';
                    } else {
                        if ($this->userModel->update($userId, ['username' => $username, 'email' => $email])) {
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

        require __DIR__ . '/../../views/user/profile.phtml';
    }

    public function orders()
    {
        $userId = getCurrentUserId();
        $user = $this->getUser();
        $orders = $this->orderModel->getUserOrders($userId);

        require __DIR__ . '/../../views/user/orders.phtml';
    }

    public function downloads()
    {
        $userId = getCurrentUserId();
        $user = $this->getUser();
        $purchasedProducts = $this->orderModel->getUserPurchasedProducts($userId);

        require __DIR__ . '/../../views/user/downloads.phtml';
    }
}