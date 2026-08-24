<?php
require_once dirname(__FILE__) . '/db.php';

// 初始化网站设置并定义常量
function jay_init_settings() {
    $db = Database::getInstance();
    if (!$db->getConnection()) return;
    $settings = $db->fetchAll("SELECT * FROM site_settings");
    foreach ($settings as $s) {
        if (!defined(strtoupper($s['setting_key']))) {
            define(strtoupper($s['setting_key']), $s['setting_value']);
        }
    }
    if (!defined('THEME_COLOR')) define('THEME_COLOR', '#6366f1');
    if (!defined('SITE_NAME')) define('SITE_NAME', 'Jay影视');
    if (!defined('PLAYER_PARSE_URL')) define('PLAYER_PARSE_URL', 'https://svip.ffzyplay.com/?url=');
    if (!defined('TMDB_API_KEY')) define('TMDB_API_KEY', 'cb44223c5dee5676ed3a839f42ed27e3');
}
jay_init_settings();

// JSON响应
function json_response($data, $code = 200) {
    http_response_code($code);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

// 检查用户是否登录
function is_logged_in() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

// 获取当前登录用户
function current_user() {
    if (!is_logged_in()) return null;
    $db = Database::getInstance();
    return $db->fetchOne("SELECT * FROM users WHERE id = ?", [$_SESSION['user_id']]);
}

// 检查是否管理员
function is_admin() {
    $u = current_user();
    return $u && $u['is_admin'] == 1 && $u['status'] == 1;
}

// 生成随机验证码
function generate_code($length = 6) {
    $chars = '0123456789';
    $code = '';
    for ($i = 0; $i < $length; $i++) {
        $code .= $chars[rand(0, strlen($chars) - 1)];
    }
    return $code;
}

// 密码哈希
function hash_password($password) {
    if (function_exists('password_hash')) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    return crypt($password, '$2y$10$' . substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, 22));
}

// 密码验证
function verify_password($password, $hash) {
    if (function_exists('password_verify')) {
        return password_verify($password, $hash);
    }
    return crypt($password, $hash) === $hash;
}

// XSS防护
function e($str) {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// 邮箱格式检查
function valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// 重定向
function redirect($url) {
    header('Location: ' . $url);
    exit;
}

// 网站设置 - 获取单个
function get_setting($key, $default = null) {
    $db = Database::getInstance();
    $row = $db->fetchOne("SELECT setting_value FROM site_settings WHERE setting_key = ?", [$key]);
    return $row ? $row['setting_value'] : $default;
}

// 网站设置 - 保存
function save_setting($key, $value) {
    $db = Database::getInstance();
    $exists = $db->fetchOne("SELECT id FROM site_settings WHERE setting_key = ?", [$key]);
    if ($exists) {
        return $db->update('site_settings', ['setting_value' => $value], 'setting_key = ?', [$key]);
    } else {
        return $db->insert('site_settings', ['setting_key' => $key, 'setting_value' => $value]);
    }
}

// 格式化时间
function time_ago($datetime) {
    $time = is_string($datetime) ? strtotime($datetime) : $datetime;
    $diff = time() - $time;
    if ($diff < 60) return $diff . '秒前';
    if ($diff < 3600) return floor($diff / 60) . '分钟前';
    if ($diff < 86400) return floor($diff / 3600) . '小时前';
    if ($diff < 2592000) return floor($diff / 86400) . '天前';
    return date('Y-m-d', $time);
}
?>
