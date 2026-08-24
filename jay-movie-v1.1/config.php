<?php
// ==== Jay影视 v1.1 配置文件 ====
// 自动检测：支持 MySQL / SQLite 双引擎，无配置即可使用（默认SQLite）

// ---- 数据库引擎：'auto' 自动检测MySQL失败就用SQLite；'mysql' 强制MySQL；'sqlite' 强制SQLite ----
if (!defined('DB_ENGINE')) define('DB_ENGINE', 'auto');

// ---- MySQL 配置（如使用MySQL请填写，不填则自动使用SQLite）----
if (!defined('DB_HOST'))   define('DB_HOST', 'localhost');
if (!defined('DB_NAME'))   define('DB_NAME', 'jay_movie');
if (!defined('DB_USER'))   define('DB_USER', 'root');
if (!defined('DB_PASS'))   define('DB_PASS', '');
if (!defined('DB_CHARSET')) define('DB_CHARSET', 'utf8mb4');

// ---- SQLite 路径（留空使用默认 assets/uploads/jay_movie.sqlite）----
if (!defined('SQLITE_PATH')) define('SQLITE_PATH', __DIR__ . '/assets/uploads/jay_movie.sqlite');

// ---- SMTP邮件配置（使用163 SMTP）----
if (!defined('SMTP_HOST'))    define('SMTP_HOST', 'smtp.163.com');
if (!defined('SMTP_PORT'))    define('SMTP_PORT', 465);
if (!defined('SMTP_USER'))    define('SMTP_USER', 'jtxnb886@163.com');
if (!defined('SMTP_PASS'))    define('SMTP_PASS', 'FLLRDtadYAfGXp9Y');
if (!defined('SMTP_FROM'))    define('SMTP_FROM', 'jtxnb886@163.com');
if (!defined('SMTP_FROM_NAME')) define('SMTP_FROM_NAME', 'Jay影视');

// ---- 网站基础配置 ----
if (!defined('SITE_URL')) {
    $scheme = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') || (isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443) ? 'https' : 'http';
    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
    define('SITE_URL', $scheme . '://' . $host);
}
if (!defined('SITE_PATH')) define('SITE_PATH', dirname(__FILE__));

// ---- 错误报告（生产环境关闭显示）----
// 跨PHP版本兼容：E_STRICT 在PHP 7后基本合并，8.4+已移除常量
$errMask = E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED;
if (PHP_VERSION_ID < 80400) {
    $errMask = $errMask & ~E_STRICT;
}
if (!defined('SITE_INSTALLED')) {
    error_reporting($errMask);
    ini_set('display_errors', 0);
} else {
    error_reporting($errMask);
    ini_set('display_errors', 0);
}
ini_set('log_errors', 1);
if (!defined('SITE_LOG_FILE')) {
    define('SITE_LOG_FILE', __DIR__ . '/assets/uploads/error.log');
}
ini_set('error_log', SITE_LOG_FILE);

// ---- 时区设置 ----
date_default_timezone_set('Asia/Shanghai');

// ---- session设置（兼容性处理：PHP 5.x 使用 session_id 判断）----
if (version_compare(PHP_VERSION, '5.4.0', '>=')) {
    if (session_status() == PHP_SESSION_NONE) {
        @session_start();
    }
} else {
    if (session_id() == '') {
        @session_start();
    }
}
?>
