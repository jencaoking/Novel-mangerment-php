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
        $recentOrders = $this->orderModel->getUserRecentOrders($userId, 5);

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

    /**
     * 处理修改密码请求
     */
    public function changePassword()
    {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            // 1. CSRF 安全验证
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $_SESSION['error'] = '安全验证失败';
            } else {
                $oldPassword = $_POST['old_password'] ?? '';
                $newPassword = $_POST['new_password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                $user = $this->getUser();

                // 2. 表单基础验证
                if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
                    $_SESSION['error'] = '请填写所有密码字段';
                } elseif (!password_verify($oldPassword, $user['password'])) {
                    // 3. 验证原密码是否正确
                    $_SESSION['error'] = '原密码不正确';
                } elseif (strlen($newPassword) < 6) {
                    $_SESSION['error'] = '新密码至少需要6个字符';
                } elseif ($newPassword !== $confirmPassword) {
                    $_SESSION['error'] = '两次输入的新密码不一致';
                } else {
                    // 4. 加密新密码并更新到数据库
                    $hashedPassword = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                    
                    if ($this->userModel->update($user['id'], ['password' => $hashedPassword])) {
                        $_SESSION['success'] = '密码修改成功！下次登录请使用新密码。';
                    } else {
                        $_SESSION['error'] = '密码修改失败，请稍后再试';
                    }
                }
            }
        }
        
        // 完成后重定向回个人资料页，提示信息会在页面顶部显示
        redirect('/user/profile');
    }
}