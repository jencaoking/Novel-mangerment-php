<?php
namespace App\Controllers;

class AuthController
{
    public function showLogin()
    {
        if (isLoggedIn()) {
            redirect('/');
        }
        $error = '';
        require __DIR__ . '/../../views/auth/login.phtml';
    }

    public function processLogin()
    {
        if (isLoggedIn()) {
            redirect('/');
        }

        $error = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $error = '安全验证失败，请刷新页面重试';
            } else {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $remember = isset($_POST['remember']);

                if (empty($username) || empty($password)) {
                    $error = '请填写用户名和密码';
                } else {
                    $result = login($username, $password, $remember);

                    if ($result['success']) {
                        $redirectUrl = $_SESSION['redirect_url'] ?? null;
                        unset($_SESSION['redirect_url']);

                        if (!$redirectUrl && $result['user']['role'] === 'admin') {
                            redirect('/admin/dashboard');
                        }

                        redirect($redirectUrl ?? '/');
                    } else {
                        $error = $result['message'];
                    }
                }
            }
        }

        require __DIR__ . '/../../views/auth/login.phtml';
    }

    public function showRegister()
    {
        if (isLoggedIn()) {
            redirect('/');
        }
        $error = '';
        $success = '';
        require __DIR__ . '/../../views/auth/register.phtml';
    }

    public function processRegister()
    {
        if (isLoggedIn()) {
            redirect('/');
        }

        $error = '';
        $success = '';

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $error = '安全验证失败，请刷新页面重试';
            } else {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';

                if (empty($username) || empty($email) || empty($password)) {
                    $error = '请填写所有必填字段';
                } elseif (!isValidUsername($username)) {
                    $error = '用户名格式不正确（3-20个字符，支持中文、英文、数字和下划线）';
                } elseif (!isValidEmail($email)) {
                    $error = '请输入有效的邮箱地址';
                } elseif (strlen($password) < 6) {
                    $error = '密码至少需要6个字符';
                } elseif ($password !== $confirmPassword) {
                    $error = '两次输入的密码不一致';
                } else {
                    $result = register($username, $email, $password);

                    if ($result['success']) {
                        $success = '注册成功！即将跳转到登录页面...';
                    } else {
                        $error = $result['message'];
                    }
                }
            }
        }

        require __DIR__ . '/../../views/auth/register.phtml';
    }

    public function logout()
    {
        logout();
        redirect('/');
    }
}
