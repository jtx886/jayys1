<?php
// 数据库配置
define('DB_HOST', 'localhost');
define('DB_NAME', 'jay_movie');
define('DB_USER', 'root');
define('DB_PASS', '');

// SMTP邮件配置
define('SMTP_HOST', 'smtp.163.com');
define('SMTP_PORT', 465);
define('SMTP_USER', 'jtxnb886@163.com');
define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');
define('SMTP_FROM', 'jtxnb886@163.com');
define('SMTP_FROM_NAME', 'Jay影视');

// 网站基础配置
define('SITE_URL', (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']);
define('SITE_PATH', dirname(__FILE__));

// 错误报告
error_reporting(E_ALL);
ini_set('display_errors', 0);

// 时区设置
date_default_timezone_set('Asia/Shanghai');

// session设置
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
?>
