<?php
require_once dirname(__FILE__) . '/../includes/functions.php';
require_once dirname(__FILE__) . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

$action = isset($_REQUEST['action']) ? $_REQUEST['action'] : '';
$db = Database::getInstance();

if ($action == 'send_code') {
    $email = trim($_POST['email'] ?? '');
    $type = trim($_POST['type'] ?? 'register');
    if (!valid_email($email)) json_response(['success' => false, 'message' => '邮箱格式不正确']);
    if ($type == 'register') {
        $exist = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
        if ($exist) json_response(['success' => false, 'message' => '该邮箱已被注册']);
    }
    // 检查频率：60秒内不重复发送
    $recent = $db->fetchOne("SELECT id FROM email_codes WHERE email = ? AND created_at > DATE_SUB(NOW(), INTERVAL 60 SECOND) ORDER BY id DESC", [$email]);
    if ($recent) json_response(['success' => false, 'message' => '验证码发送过于频繁，请稍后再试']);
    
    $code = generate_code(6);
    $expire = date('Y-m-d H:i:s', time() + 300);
    
    $mailer = new Mailer();
    $sent = $mailer->sendVerificationCode($email, $code);
    
    if ($sent) {
        $db->insert('email_codes', [
            'email' => $email,
            'code' => $code,
            'type' => $type,
            'expire_time' => $expire
        ]);
        json_response(['success' => true, 'message' => '验证码已发送到您的邮箱，5分钟内有效']);
    } else {
        json_response(['success' => false, 'message' => '验证码发送失败，请稍后重试']);
    }
}

if ($action == 'register') {
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';
    $code = trim($_POST['code'] ?? '');
    
    if (!valid_email($email)) json_response(['success' => false, 'message' => '邮箱格式不正确']);
    if (mb_strlen($username) < 2 || mb_strlen($username) > 20) json_response(['success' => false, 'message' => '用户名长度应为2-20个字符']);
    if (strlen($password) < 6) json_response(['success' => false, 'message' => '密码长度不能少于6位']);
    if ($password != $password2) json_response(['success' => false, 'message' => '两次密码输入不一致']);
    
    $emailExist = $db->fetchOne("SELECT id FROM users WHERE email = ?", [$email]);
    if ($emailExist) json_response(['success' => false, 'message' => '该邮箱已被注册']);
    $userExist = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
    if ($userExist) json_response(['success' => false, 'message' => '该用户名已被使用']);
    
    // 验证验证码
    $codeRow = $db->fetchOne("SELECT * FROM email_codes WHERE email = ? AND code = ? AND type = 'register' AND used = 0 ORDER BY id DESC", [$email, $code]);
    if (!$codeRow) json_response(['success' => false, 'message' => '验证码错误']);
    if (strtotime($codeRow['expire_time']) < time()) json_response(['success' => false, 'message' => '验证码已过期']);
    
    $db->update('email_codes', ['used' => 1], 'id = ?', [$codeRow['id']]);
    
    $userId = $db->insert('users', [
        'email' => $email,
        'username' => $username,
        'password' => hash_password($password)
    ]);
    
    if ($userId) {
        $_SESSION['user_id'] = $userId;
        json_response(['success' => true, 'message' => '注册成功']);
    }
    json_response(['success' => false, 'message' => '注册失败，请稍后重试']);
}

if ($action == 'login') {
    $account = trim($_POST['account'] ?? '');
    $password = $_POST['password'] ?? '';
    
    if (empty($account) || empty($password)) json_response(['success' => false, 'message' => '请填写账号和密码']);
    
    $user = null;
    if (valid_email($account)) {
        $user = $db->fetchOne("SELECT * FROM users WHERE email = ?", [$account]);
    } else {
        $user = $db->fetchOne("SELECT * FROM users WHERE username = ?", [$account]);
    }
    
    if (!$user) json_response(['success' => false, 'message' => '账号不存在']);
    if (!verify_password($password, $user['password'])) json_response(['success' => false, 'message' => '密码错误']);
    if ($user['status'] != 1) {
        $banMsg = '账号已被封禁';
        if ($user['unban_time']) $banMsg .= '，解封时间：' . date('Y-m-d H:i:s', strtotime($user['unban_time']));
        json_response(['success' => false, 'message' => $banMsg]);
    }
    
    $db->update('users', ['last_login' => date('Y-m-d H:i:s')], 'id = ?', [$user['id']]);
    $_SESSION['user_id'] = $user['id'];
    json_response(['success' => true, 'message' => '登录成功', 'is_admin' => $user['is_admin'] == 1]);
}

if ($action == 'logout') {
    unset($_SESSION['user_id']);
    session_destroy();
    json_response(['success' => true, 'message' => '已退出登录']);
}

if ($action == 'current_user') {
    $u = current_user();
    if (!$u) {
        json_response(['success' => false, 'message' => '未登录']);
    }
    unset($u['password']);
    $u['theme_color'] = THEME_COLOR;
    json_response(['success' => true, 'user' => $u]);
}

json_response(['success' => false, 'message' => '无效的操作']);
?>
