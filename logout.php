<?php
/**
 * BookMusic Mall - 用户登出
 */

require_once 'includes/auth.php';

// 执行登出
logout();

// 重定向到首页
redirect('/');
