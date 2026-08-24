<?php
require_once dirname(__FILE__) . '/../includes/functions.php';
require_once dirname(__FILE__) . '/../includes/mailer.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_admin()) json_response(['success' => false, 'message' => '权限不足', 'need_login' => true]);

$action = $_REQUEST['action'] ?? '';
$db = Database::getInstance();

// 仪表盘数据
if ($action == 'dashboard') {
    $stats = [
        'total_users' => $db->fetchOne("SELECT COUNT(*) c FROM users")['c'],
        'today_register' => $db->fetchOne("SELECT COUNT(*) c FROM users WHERE DATE(created_at) = CURDATE()")['c'],
        'total_feedbacks' => $db->fetchOne("SELECT COUNT(*) c FROM feedbacks")['c'],
        'pending_feedbacks' => $db->fetchOne("SELECT COUNT(*) c FROM feedbacks WHERE status = 'pending'")['c'],
        'total_watch' => $db->fetchOne("SELECT COUNT(*) c FROM watch_history")['c'],
        'total_favorites' => $db->fetchOne("SELECT COUNT(*) c FROM favorites")['c'],
        'banned_users' => $db->fetchOne("SELECT COUNT(*) c FROM users WHERE status = 0")['c']
    ];
    $latestUsers = $db->fetchAll("SELECT id, username, email, avatar, status, created_at FROM users ORDER BY id DESC LIMIT 8");
    $latestFeedbacks = $db->fetchAll("SELECT f.id, f.title, f.created_at, f.status, u.username FROM feedbacks f 
        LEFT JOIN users u ON u.id = f.user_id ORDER BY f.id DESC LIMIT 8");
    json_response(['success' => true, 'stats' => $stats, 'latest_users' => $latestUsers, 'latest_feedbacks' => $latestFeedbacks]);
}

// 用户管理
if ($action == 'user_list') {
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;
    $keyword = trim($_GET['keyword'] ?? '');
    $where = '';
    $params = [];
    if ($keyword) {
        $where = "WHERE username LIKE ? OR email LIKE ?";
        $params[] = "%$keyword%";
        $params[] = "%$keyword%";
    }
    $total = $db->fetchOne("SELECT COUNT(*) c FROM users $where", $params)['c'];
    $list = $db->fetchAll("SELECT * FROM users $where ORDER BY id DESC LIMIT $offset, $perPage", $params);
    json_response(['success' => true, 'list' => $list, 'total' => $total, 'per_page' => $perPage]);
}

if ($action == 'ban_user') {
    $uid = intval($_POST['user_id'] ?? 0);
    $days = intval($_POST['days'] ?? 0);
    $reason = trim($_POST['reason'] ?? '违反社区规定');
    if (!$uid) json_response(['success' => false, 'message' => '参数错误']);
    
    $banTime = date('Y-m-d H:i:s');
    if ($days > 0) {
        $unbanTime = date('Y-m-d H:i:s', strtotime("+$days days"));
    } else {
        $unbanTime = null;
    }
    $db->update('users', [
        'status' => 0,
        'ban_time' => $banTime,
        'unban_time' => $unbanTime,
        'ban_reason' => $reason
    ], 'id = ?', [$uid]);
    
    // 发送邮件通知
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$uid]);
    if ($user && $user['email']) {
        try {
            $mailer = new Mailer();
            $mailer->sendBanNotification($user['email'], $user['username'], $banTime, $unbanTime, $reason);
        } catch (Exception $e) {}
    }
    json_response(['success' => true, 'message' => '已封禁用户并发送通知邮件']);
}

if ($action == 'unban_user') {
    $uid = intval($_POST['user_id'] ?? 0);
    $db->update('users', ['status' => 1, 'ban_time' => null, 'unban_time' => null, 'ban_reason' => null], 'id = ?', [$uid]);
    json_response(['success' => true, 'message' => '已解封用户']);
}

if ($action == 'user_history') {
    $uid = intval($_GET['user_id'] ?? 0);
    if (!$uid) json_response(['success' => false, 'message' => '参数错误']);
    $list = $db->fetchAll("SELECT * FROM watch_history WHERE user_id = ? ORDER BY updated_at DESC LIMIT 100", [$uid]);
    json_response(['success' => true, 'list' => $list]);
}

if ($action == 'user_favorites') {
    $uid = intval($_GET['user_id'] ?? 0);
    if (!$uid) json_response(['success' => false, 'message' => '参数错误']);
    $list = $db->fetchAll("SELECT * FROM favorites WHERE user_id = ? ORDER BY id DESC LIMIT 100", [$uid]);
    json_response(['success' => true, 'list' => $list]);
}

if ($action == 'send_email_to_user') {
    $uid = intval($_POST['user_id'] ?? 0);
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    if (!$uid || !$title || !$content) json_response(['success' => false, 'message' => '参数不完整']);
    $user = $db->fetchOne("SELECT * FROM users WHERE id = ?", [$uid]);
    if (!$user) json_response(['success' => false, 'message' => '用户不存在']);
    $mailer = new Mailer();
    $sent = $mailer->sendCustomNotification($user['email'], $user['username'], $title, $content);
    json_response(['success' => $sent, 'message' => $sent ? '邮件发送成功' : '邮件发送失败']);
}

// 播放源管理
if ($action == 'source_list') {
    $list = $db->fetchAll("SELECT * FROM play_sources ORDER BY sort ASC, id ASC");
    json_response(['success' => true, 'list' => $list]);
}

if ($action == 'save_source') {
    $id = intval($_POST['id'] ?? 0);
    $data = [
        'name' => trim($_POST['name'] ?? ''),
        'url' => trim($_POST['url'] ?? ''),
        'type' => trim($_POST['type'] ?? 'movie'),
        'sort' => intval($_POST['sort'] ?? 0),
        'status' => intval($_POST['status'] ?? 1)
    ];
    if (!$data['name'] || !$data['url']) json_response(['success' => false, 'message' => '名称和URL不能为空']);
    if ($id > 0) {
        $db->update('play_sources', $data, 'id = ?', [$id]);
    } else {
        $id = $db->insert('play_sources', $data);
    }
    json_response(['success' => true, 'message' => '保存成功', 'id' => $id]);
}

if ($action == 'delete_source') {
    $id = intval($_POST['id'] ?? 0);
    $db->delete('play_sources', 'id = ?', [$id]);
    json_response(['success' => true, 'message' => '已删除']);
}

// 公告管理
if ($action == 'announcement_list') {
    $list = $db->fetchAll("SELECT a.*, u.username FROM announcements a LEFT JOIN users u ON u.id = a.admin_id ORDER BY a.id DESC LIMIT 50");
    json_response(['success' => true, 'list' => $list]);
}

if ($action == 'save_announcement') {
    $id = intval($_POST['id'] ?? 0);
    $data = [
        'title' => trim($_POST['title'] ?? ''),
        'content' => trim($_POST['content'] ?? ''),
        'show_popup' => intval($_POST['show_popup'] ?? 1)
    ];
    if (!$data['title'] || !$data['content']) json_response(['success' => false, 'message' => '标题和内容不能为空']);
    if ($id > 0) {
        $db->update('announcements', $data, 'id = ?', [$id]);
    } else {
        $data['admin_id'] = $_SESSION['user_id'];
        $id = $db->insert('announcements', $data);
    }
    json_response(['success' => true, 'message' => '保存成功', 'id' => $id]);
}

if ($action == 'delete_announcement') {
    $id = intval($_POST['id'] ?? 0);
    $db->delete('announcements', 'id = ?', [$id]);
    json_response(['success' => true, 'message' => '已删除']);
}

// 反馈管理
if ($action == 'feedback_list') {
    $list = $db->fetchAll("SELECT f.*, u.username FROM feedbacks f LEFT JOIN users u ON u.id = f.user_id ORDER BY f.id DESC LIMIT 100");
    json_response(['success' => true, 'list' => $list]);
}

if ($action == 'update_feedback_status') {
    $fid = intval($_POST['feedback_id'] ?? 0);
    $status = trim($_POST['status'] ?? 'pending');
    $db->update('feedbacks', ['status' => $status], 'id = ?', [$fid]);
    json_response(['success' => true, 'message' => '状态已更新']);
}

if ($action == 'admin_reply_feedback') {
    $fid = intval($_POST['feedback_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if (!$fid || !$content) json_response(['success' => false, 'message' => '参数错误']);
    $rid = $db->insert('feedback_replies', [
        'feedback_id' => $fid,
        'user_id' => $_SESSION['user_id'],
        'content' => $content,
        'is_admin' => 1
    ]);
    $db->update('feedbacks', ['status' => 'replied'], 'id = ?', [$fid]);
    $reply = $db->fetchOne("SELECT r.*, u.username, u.is_admin FROM feedback_replies r 
        LEFT JOIN users u ON u.id = r.user_id WHERE r.id = ?", [$rid]);
    json_response(['success' => true, 'message' => '回复成功', 'reply' => $reply]);
}

if ($action == 'delete_feedback') {
    $fid = intval($_POST['id'] ?? 0);
    $db->delete('feedback_replies', 'feedback_id = ?', [$fid]);
    $db->delete('feedback_likes', 'feedback_id = ?', [$fid]);
    $db->delete('feedbacks', 'id = ?', [$fid]);
    json_response(['success' => true, 'message' => '已删除']);
}

// 网站设置
if ($action == 'save_theme_color') {
    $color = trim($_POST['color'] ?? '');
    if (!preg_match('/^#[0-9a-fA-F]{6}$/', $color)) json_response(['success' => false, 'message' => '颜色格式错误']);
    save_setting('theme_color', $color);
    json_response(['success' => true, 'message' => '主题色已更新']);
}

if ($action == 'save_player_parse_url') {
    $url = trim($_POST['url'] ?? '');
    save_setting('player_parse_url', $url);
    json_response(['success' => true, 'message' => '解析器地址已更新']);
}

if ($action == 'save_tmdb_key') {
    $key = trim($_POST['api_key'] ?? '');
    $token = trim($_POST['read_token'] ?? '');
    if ($key) save_setting('tmdb_api_key', $key);
    if ($token) save_setting('tmdb_read_token', $token);
    json_response(['success' => true, 'message' => 'TMDB配置已更新']);
}

json_response(['success' => false, 'message' => 'Invalid action']);
?>
