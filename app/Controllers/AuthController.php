<?php
namespace App\Controllers;

require_once __DIR__ . '/../../includes/rate_limit.php';

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
            // 速率限制：每 IP 每5分钟最多5次登录尝试
            $rateKey = getRateLimitKey() . ':login';
            $rateCheck = checkRateLimit($rateKey, 5, 300);
            
            if (!$rateCheck['allowed']) {
                $error = '登录尝试过于频繁，请稍后再试（' . $rateCheck['retry_after'] . '秒）';
                require __DIR__ . '/../../views/auth/login.phtml';
                return;
            }

            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $error = '安全验证失败，请刷新页面重试';
            } else {
                $username = trim($_POST['username'] ?? '');
                $password = $_POST['password'] ?? '';
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));
                $remember = isset($_POST['remember']);

                if (empty($username) || empty($password) || empty($captcha)) {
                    $error = '请填写用户名、密码和验证码';
                } elseif (!isset($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
                    $error = '验证码错误，请重新输入';
                    unset($_SESSION['captcha']);
                } else {
                    unset($_SESSION['captcha']);
                    recordAttempt($rateKey);
                    $result = login($username, $password, $remember);

                    if ($result['success']) {
                        clearRateLimit($rateKey);
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
            // 速率限制：每 IP 每5分钟最多3次注册尝试
            $rateKey = getRateLimitKey() . ':register';
            $rateCheck = checkRateLimit($rateKey, 3, 300);
            
            if (!$rateCheck['allowed']) {
                $error = '注册尝试过于频繁，请稍后再试（' . $rateCheck['retry_after'] . '秒）';
                require __DIR__ . '/../../views/auth/register.phtml';
                return;
            }

            if (!isset($_POST['csrf_token']) || !verifyCSRFToken($_POST['csrf_token'])) {
                $error = '安全验证失败，请刷新页面重试';
            } else {
                $username = trim($_POST['username'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                $confirmPassword = $_POST['confirm_password'] ?? '';
                $captcha = strtoupper(trim($_POST['captcha'] ?? ''));

                if (empty($username) || empty($email) || empty($password) || empty($captcha)) {
                    $error = '请填写所有必填字段和验证码';
                } elseif (!isset($_SESSION['captcha']) || $captcha !== $_SESSION['captcha']) {
                    $error = '验证码错误，请重新输入';
                    unset($_SESSION['captcha']);
                } elseif (!isValidUsername($username)) {
                    $error = '用户名格式不正确（3-20个字符，支持中文、英文、数字和下划线）';
                } elseif (!isValidEmail($email)) {
                    $error = '请输入有效的邮箱地址';
                } elseif (strlen($password) < 6) {
                    $error = '密码至少需要6个字符';
                } elseif ($password !== $confirmPassword) {
                    $error = '两次输入的密码不一致';
                } else {
                    recordAttempt($rateKey);
                    $result = register($username, $email, $password);

                    if ($result['success']) {
                        clearRateLimit($rateKey);
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

    public function captcha()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $characters = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $captchaCode = substr(str_shuffle($characters), 0, 4);
        
        $_SESSION['captcha'] = strtoupper($captchaCode);

        $image = imagecreatetruecolor(100, 38);
        
        $bgColor = imagecolorallocate($image, 243, 244, 246);
        $textColor = imagecolorallocate($image, 59, 130, 246);
        
        imagefill($image, 0, 0, $bgColor);
        
        for ($i = 0; $i < 6; $i++) {
            $lineColor = imagecolorallocate($image, rand(150, 220), rand(150, 220), rand(150, 220));
            imageline($image, rand(0, 100), rand(0, 38), rand(0, 100), rand(0, 38), $lineColor);
        }
        
        imagestring($image, 5, 30, 12, $captchaCode, $textColor);
        
        ob_clean();
        header('Content-Type: image/png');
        header('Cache-Control: no-cache, must-revalidate');
        
        imagepng($image);
        imagedestroy($image);
        exit;
    }
}
