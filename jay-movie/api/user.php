<?php
require_once dirname(__FILE__) . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) json_response(['success' => false, 'message' => '请先登录', 'need_login' => true]);

$action = $_REQUEST['action'] ?? '';
$db = Database::getInstance();
$uid = $_SESSION['user_id'];

// 收藏操作
if ($action == 'toggle_favorite') {
    $tmdbId = intval($_POST['tmdb_id'] ?? 0);
    $type = trim($_POST['type'] ?? 'movie');
    $title = trim($_POST['title'] ?? '');
    $poster = trim($_POST['poster'] ?? '');
    if (!$tmdbId) json_response(['success' => false, 'message' => '参数错误']);
    
    $exist = $db->fetchOne("SELECT id FROM favorites WHERE user_id = ? AND tmdb_id = ? AND type = ?", [$uid, $tmdbId, $type]);
    if ($exist) {
        $db->delete('favorites', 'id = ?', [$exist['id']]);
        json_response(['success' => true, 'favorited' => false, 'message' => '已取消收藏']);
    } else {
        $db->insert('favorites', [
            'user_id' => $uid, 'tmdb_id' => $tmdbId, 'type' => $type, 'title' => $title, 'poster' => $poster
        ]);
        json_response(['success' => true, 'favorited' => true, 'message' => '收藏成功']);
    }
}

if ($action == 'check_favorite') {
    $tmdbId = intval($_GET['tmdb_id'] ?? 0);
    $type = trim($_GET['type'] ?? 'movie');
    $exist = $db->fetchOne("SELECT id FROM favorites WHERE user_id = ? AND tmdb_id = ? AND type = ?", [$uid, $tmdbId, $type]);
    json_response(['success' => true, 'favorited' => !empty($exist)]);
}

if ($action == 'get_favorites') {
    $list = $db->fetchAll("SELECT * FROM favorites WHERE user_id = ? ORDER BY id DESC", [$uid]);
    json_response(['success' => true, 'list' => $list]);
}

if ($action == 'remove_favorite') {
    $id = intval($_POST['id'] ?? 0);
    $db->delete('favorites', 'id = ? AND user_id = ?', [$id, $uid]);
    json_response(['success' => true, 'message' => '已取消收藏']);
}

// 观看历史
if ($action == 'update_watch_history') {
    $tmdbId = intval($_POST['tmdb_id'] ?? 0);
    $type = trim($_POST['type'] ?? 'movie');
    $season = intval($_POST['season_number'] ?? 0) ?: null;
    $episode = intval($_POST['episode_number'] ?? 0) ?: null;
    $title = trim($_POST['title'] ?? '');
    $poster = trim($_POST['poster'] ?? '');
    $seconds = intval($_POST['watch_seconds'] ?? 0);
    $position = intval($_POST['last_position'] ?? 0);
    if (!$tmdbId || !$title) json_response(['success' => false, 'message' => '参数错误']);
    
    $exist = $db->fetchOne("SELECT id, watch_seconds FROM watch_history WHERE user_id = ? AND tmdb_id = ? AND type = ? AND " . 
        ($season ? "season_number = $season" : 'season_number IS NULL') . " AND " . 
        ($episode ? "episode_number = $episode" : 'episode_number IS NULL'),
        [$uid, $tmdbId, $type]);
    
    if ($exist) {
        $newSeconds = $exist['watch_seconds'] + $seconds;
        $db->update('watch_history', [
            'watch_seconds' => $newSeconds,
            'last_position' => $position,
            'title' => $title,
            'poster' => $poster
        ], 'id = ?', [$exist['id']]);
    } else {
        $db->insert('watch_history', [
            'user_id' => $uid,
            'tmdb_id' => $tmdbId,
            'type' => $type,
            'season_number' => $season,
            'episode_number' => $episode,
            'title' => $title,
            'poster' => $poster,
            'watch_seconds' => $seconds,
            'last_position' => $position
        ]);
    }
    json_response(['success' => true]);
}

if ($action == 'get_watch_history') {
    $list = $db->fetchAll("SELECT * FROM watch_history WHERE user_id = ? ORDER BY updated_at DESC", [$uid]);
    json_response(['success' => true, 'list' => $list]);
}

if ($action == 'remove_watch_history') {
    $id = intval($_POST['id'] ?? 0);
    $db->delete('watch_history', 'id = ? AND user_id = ?', [$id, $uid]);
    json_response(['success' => true, 'message' => '已删除']);
}

if ($action == 'clear_watch_history') {
    $db->delete('watch_history', 'user_id = ?', [$uid]);
    json_response(['success' => true, 'message' => '已清空观看历史']);
}

// 头像设置
if ($action == 'update_avatar') {
    $avatar = trim($_POST['avatar'] ?? '');
    if (!$avatar) json_response(['success' => false, 'message' => '头像不能为空']);
    $db->update('users', ['avatar' => $avatar], 'id = ?', [$uid]);
    json_response(['success' => true, 'message' => '头像更新成功']);
}

json_response(['success' => false, 'message' => 'Invalid action']);
?>
