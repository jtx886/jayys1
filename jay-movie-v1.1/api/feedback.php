<?php
require_once dirname(__FILE__) . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$db = Database::getInstance();

// ---- action 别名兼容（v1.1） ----
$actionAliases = [
    'create'             => 'submit',
    'publish'            => 'submit',
    'like'               => 'toggle_like',
    'get_list'           => 'list',
    'get_feedback_list'  => 'list',
];
$action = $actionAliases[$action] ?? $action;

// 反馈列表
if ($action == 'list') {
    $page = max(1, intval($_REQUEST['page'] ?? $_GET['page'] ?? 1));
    $perPage = max(1, intval($_REQUEST['page_size'] ?? 10));
    $offset = ($page - 1) * $perPage;
    
    $totalRow = $db->fetchOne("SELECT COUNT(*) c FROM feedbacks");
    $total = intval($totalRow['c'] ?? 0);

    $list = $db->fetchAll("SELECT f.*, u.username, u.avatar, u.is_admin 
        FROM feedbacks f LEFT JOIN users u ON u.id = f.user_id 
        ORDER BY f.id DESC LIMIT $offset, $perPage");
    
    foreach ($list as &$f) {
        $f['likes_count'] = intval($db->fetchOne("SELECT COUNT(*) c FROM feedback_likes WHERE feedback_id = ?", [$f['id']])['c'] ?? 0);
        $f['replies_count'] = intval($db->fetchOne("SELECT COUNT(*) c FROM feedback_replies WHERE feedback_id = ?", [$f['id']])['c'] ?? 0);
        if (is_logged_in()) {
            $liked = $db->fetchOne("SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?", [$f['id'], $_SESSION['user_id']]);
            $f['liked'] = !empty($liked);
        } else {
            $f['liked'] = false;
        }
        // 回复（管理员回复置顶）
        $replies = $db->fetchAll("SELECT r.*, u.username, u.avatar, u.is_admin FROM feedback_replies r 
            LEFT JOIN users u ON u.id = r.user_id WHERE r.feedback_id = ? 
            ORDER BY (r.is_admin=1) DESC, r.id ASC LIMIT 50", [$f['id']]);
        $f['replies'] = $replies;
    }
    json_response(['success' => true, 'list' => $list, 'data' => $list, 'total' => $total, 'page' => $page, 'page_size' => $perPage]);
}

// 提交反馈 (需登录)
if ($action == 'submit') {
    if (!is_logged_in()) json_response(['success' => false, 'message' => '请先登录', 'need_login' => true]);
    $uid = $_SESSION['user_id'];
    $title = trim($_POST['title'] ?? '');
    $content = trim($_POST['content'] ?? '');
    $category = trim($_POST['category'] ?? '建议');
    if (mb_strlen($title) < 2) json_response(['success' => false, 'message' => '标题至少2个字符']);
    if (mb_strlen($content) < 5) json_response(['success' => false, 'message' => '内容至少5个字符']);
    
    $now = date('Y-m-d H:i:s');
    $fid = $db->insert('feedbacks', [
        'user_id'     => $uid,
        'title'       => $title,
        'content'     => $content,
        'category'    => $category,
        'status'      => 'pending',
        'likes_count' => 0,
        'replies_count' => 0,
        'created_at'  => $now,
        'updated_at'  => $now
    ]);
    if ($fid) json_response(['success' => true, 'message' => '反馈提交成功，感谢您的反馈！', 'feedback_id' => intval($fid), 'id' => intval($fid)]);
    json_response(['success' => false, 'message' => '提交失败，请稍后再试']);
}

// 点赞
if ($action == 'toggle_like') {
    if (!is_logged_in()) json_response(['success' => false, 'message' => '请先登录', 'need_login' => true]);
    $uid = $_SESSION['user_id'];
    $fid = intval($_POST['feedback_id'] ?? 0);
    if (!$fid) json_response(['success' => false, 'message' => '参数错误']);
    
    $exist = $db->fetchOne("SELECT id FROM feedback_likes WHERE feedback_id = ? AND user_id = ?", [$fid, $uid]);
    if ($exist) {
        $db->delete('feedback_likes', 'id = ?', [$exist['id']]);
        json_response(['success' => true, 'liked' => false, 'likes_count' => intval($db->fetchOne("SELECT COUNT(*) c FROM feedback_likes WHERE feedback_id=?",[$fid])['c'])]);
    } else {
        @$db->insert('feedback_likes', ['feedback_id' => $fid, 'user_id' => $uid, 'created_at'=>date('Y-m-d H:i:s')]);
        json_response(['success' => true, 'liked' => true,  'likes_count' => intval($db->fetchOne("SELECT COUNT(*) c FROM feedback_likes WHERE feedback_id=?",[$fid])['c'])]);
    }
}

// 回复
if ($action == 'reply') {
    if (!is_logged_in()) json_response(['success' => false, 'message' => '请先登录', 'need_login' => true]);
    $uid = $_SESSION['user_id'];
    $fid = intval($_POST['feedback_id'] ?? 0);
    $content = trim($_POST['content'] ?? '');
    if (!$fid) json_response(['success' => false, 'message' => '参数错误']);
    if (mb_strlen($content) < 1) json_response(['success' => false, 'message' => '回复内容不能为空']);
    
    $u = current_user();
    $now = date('Y-m-d H:i:s');
    $isAdmin = $u && !empty($u['is_admin']) ? 1 : 0;
    $rid = $db->insert('feedback_replies', [
        'feedback_id' => $fid,
        'user_id' => $uid,
        'content' => $content,
        'is_admin' => $isAdmin,
        'created_at' => $now
    ]);
    
    if ($isAdmin) {
        $db->update('feedbacks', ['status' => 'replied', 'updated_at' => $now], 'id = ?', [$fid]);
    } else {
        $db->update('feedbacks', ['updated_at' => $now], 'id = ?', [$fid]);
    }
    
    // 返回新回复
    $reply = $db->fetchOne("SELECT r.*, u.username, u.avatar, u.is_admin FROM feedback_replies r 
        LEFT JOIN users u ON u.id = r.user_id WHERE r.id = ?", [$rid]);
    json_response(['success' => true, 'message' => '回复成功', 'reply' => $reply, 'reply_id' => intval($rid)]);
}

json_response(['success' => false, 'message' => 'Invalid action']);
?>
