<?php
// ================================================
// DEPRECATED - 此文件已废弃
// 请使用 MVC 路由: /admin/dashboard
// ================================================
require_once __DIR__ . '/../includes/auth.php';
requireAdmin();
redirect('dashboard.php');
