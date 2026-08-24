<?php
require_once dirname(__FILE__) . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

if (!is_logged_in()) json_response(['success' => false, 'message' => '请先登录', 'need_login' => true]);

$action = $_REQUEST['action'] ?? '';
$db = Database::getInstance();
$uid = $_SESSION['user_id'];

// ---------- 工具：字段兼容：收藏 / 历史 ----------
function favAlias($row) {
    if (!$row) return $row;
    $row['tmdb_id'] = $row['media_id'] ?? ($row['tmdb_id'] ?? null);
    $row['type']    = $row['media_type'] ?? ($row['type'] ?? null);
    if (!isset($row['favorited_at']) && isset($row['created_at'])) $row['favorited_at'] = $row['created_at'];
    return $row;
}
function histAlias($row) {
    if (!$row) return $row;
    $row['tmdb_id']         = $row['media_id']       ?? ($row['tmdb_id'] ?? null);
    $row['type']            = $row['media_type']     ?? ($row['type'] ?? null);
    $row['progress_seconds']= $row['position_sec']   ?? ($row['progress_seconds'] ?? 0);
    $row['watch_seconds']   = $row['duration_sec']   ?? ($row['watch_seconds']   ?? 0);
    $row['season_number']   = $row['season']         ?? ($row['season_number']   ?? 0);
    $row['episode_number']  = $row['episode']        ?? ($row['episode_number']  ?? 0);
    $row['last_position']   = $row['position_sec']   ?? ($row['last_position']   ?? 0);
    return $row;
}

// ---------- 收藏操作 ----------
if ($action == 'toggle_favorite') {
    $tmdbId = intval($_POST['tmdb_id'] ?? ($_POST['media_id'] ?? 0));
    $type   = trim($_POST['type']   ?? ($_POST['media_type'] ?? 'movie'));
    $title  = trim($_POST['title']  ?? '');
    $poster = trim($_POST['poster'] ?? '');
    if (!$tmdbId) json_response(['success' => false, 'message' => '参数错误']);

    $exist = $db->fetchOne("SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?", [$uid, $tmdbId, $type]);
    if ($exist) {
        $db->delete('favorites', 'id = ? AND user_id = ?', [$exist['id'], $uid]);
        json_response(['success' => true, 'favorited' => false, 'message' => '已取消收藏']);
    } else {
        @$db->insert('favorites', [
            'user_id'    => $uid,
            'media_type' => $type,
            'media_id'   => $tmdbId,
            'title'      => $title,
            'poster'     => $poster,
            'created_at' => date('Y-m-d H:i:s')
        ]);
        json_response(['success' => true, 'favorited' => true, 'message' => '收藏成功']);
    }
}

if ($action == 'check_favorite') {
    $tmdbId = intval($_GET['tmdb_id'] ?? ($_GET['media_id'] ?? 0));
    $type   = trim($_GET['type']   ?? ($_GET['media_type'] ?? 'movie'));
    $exist = $db->fetchOne("SELECT id FROM favorites WHERE user_id = ? AND media_id = ? AND media_type = ?", [$uid, $tmdbId, $type]);
    json_response(['success' => true, 'favorited' => !empty($exist)]);
}

if ($action == 'get_favorites' || $action == 'list_favorites') {
    $rows = $db->fetchAll("SELECT * FROM favorites WHERE user_id = ? ORDER BY id DESC", [$uid]);
    $list = array_map('favAlias', $rows);
    json_response(['success' => true, 'list' => $list, 'data' => $list, 'total' => count($list)]);
}

if ($action == 'remove_favorite' || $action == 'delete_favorite') {
    $id = intval($_POST['id'] ?? 0);
    $tmdbId = intval($_POST['tmdb_id'] ?? ($_POST['media_id'] ?? 0));
    $type   = trim($_POST['type']   ?? ($_POST['media_type'] ?? ''));
    if ($id > 0) {
        $db->delete('favorites', 'id = ? AND user_id = ?', [$id, $uid]);
    } elseif ($tmdbId && $type) {
        $db->delete('favorites', 'user_id = ? AND media_id = ? AND media_type = ?', [$uid, $tmdbId, $type]);
    } else {
        json_response(['success' => false, 'message' => '参数错误']);
    }
    json_response(['success' => true, 'message' => '已取消收藏']);
}

// ---------- 观看历史 ----------
if ($action == 'update_watch_history' || $action == 'save_watch_progress') {
    $tmdbId   = intval($_POST['tmdb_id']   ?? ($_POST['media_id']   ?? 0));
    $type     = trim($_POST['type']      ?? ($_POST['media_type'] ?? 'movie'));
    $season   = intval($_POST['season']   ?? ($_POST['season_number'] ?? 0));
    $episode  = intval($_POST['episode']  ?? ($_POST['episode_number'] ?? 0));
    $title    = trim($_POST['title'] ?? '');
    $poster   = trim($_POST['poster'] ?? '');
    // 两种入参格式：progress 或 累加
    $progSec  = intval($_POST['progress'] ?? $_POST['progress_seconds'] ?? $_POST['position_sec'] ?? $_POST['last_position'] ?? 0);
    $durSec   = intval($_POST['duration_seconds'] ?? $_POST['watch_seconds'] ?? $_POST['duration_sec'] ?? 0);
    if (!$durSec) $durSec = $progSec; // 无累加模式则 = progress
    if (!$tmdbId || !$title) json_response(['success' => false, 'message' => '参数错误']);

    $exist = $db->fetchOne("SELECT * FROM watch_history WHERE user_id = ? AND media_id = ? AND media_type = ? AND season = ? AND episode = ?",
        [$uid, $tmdbId, $type, $season, $episode]);
    $now = date('Y-m-d H:i:s');
    if ($exist) {
        $newDur = $exist['duration_sec'] + $durSec;
        $newProg = max($exist['position_sec'], $progSec);
        $db->update('watch_history', [
            'duration_sec' => $newDur,
            'position_sec' => $newProg,
            'title'        => $title,
            'poster'       => $poster,
            'updated_at'   => $now
        ], 'id = ?', [$exist['id']]);
    } else {
        $db->insert('watch_history', [
            'user_id'      => $uid,
            'media_type'   => $type,
            'media_id'     => $tmdbId,
            'season'       => $season,
            'episode'      => $episode,
            'title'        => $title,
            'poster'       => $poster,
            'position_sec' => $progSec,
            'duration_sec' => $durSec,
            'created_at'   => $now,
            'updated_at'   => $now
        ]);
    }
    json_response(['success' => true, 'message' => '已保存观看进度']);
}

if ($action == 'get_watch_history' || $action == 'list_watch_history') {
    $rows = $db->fetchAll("SELECT * FROM watch_history WHERE user_id = ? ORDER BY updated_at DESC", [$uid]);
    $list = array_map('histAlias', $rows);
    json_response(['success' => true, 'list' => $list, 'data' => $list, 'total' => count($list)]);
}

if ($action == 'remove_watch_history' || $action == 'delete_watch_history') {
    $id = intval($_POST['id'] ?? 0);
    $tmdbId = intval($_POST['tmdb_id'] ?? ($_POST['media_id'] ?? 0));
    $type   = trim($_POST['type']   ?? ($_POST['media_type'] ?? ''));
    $season = intval($_POST['season']  ?? ($_POST['season_number'] ?? 0));
    $ep     = intval($_POST['episode'] ?? ($_POST['episode_number'] ?? 0));
    if ($id > 0) {
        $db->delete('watch_history', 'id = ? AND user_id = ?', [$id, $uid]);
    } elseif ($tmdbId && $type) {
        $params = [$uid, $tmdbId, $type];
        $where = "WHERE user_id = ? AND media_id = ? AND media_type = ? AND season = ? AND episode = ?";
        array_push($params, $season, $ep);
        $db->delete('watch_history', trim(preg_replace('/^WHERE\s*/i','',$where)), $params);
    } else {
        json_response(['success' => false, 'message' => '参数错误']);
    }
    json_response(['success' => true, 'message' => '已删除观看记录']);
}

if ($action == 'clear_watch_history') {
    $db->delete('watch_history', 'user_id = ?', [$uid]);
    json_response(['success' => true, 'message' => '已清空观看历史']);
}

// ---------- 头像设置 ----------
if ($action == 'update_avatar') {
    $avatar = trim($_POST['avatar'] ?? '');
    if (!$avatar) json_response(['success' => false, 'message' => '头像不能为空']);
    // 处理base64
    if (strpos($avatar, 'data:') === 0) {
        // 1. 检查大小（> 2MB 拒绝）
        $raw = $avatar;
        if (preg_match('/^data:image\/(png|jpeg|jpg|gif|webp);base64,(.+)$/i', $raw, $m)) {
            $bin = base64_decode($m[2], true);
            if ($bin === false || strlen($bin) > 2 * 1024 * 1024) {
                json_response(['success' => false, 'message' => '头像图片过大（最大2MB）或格式错误']);
            }
            // 2. 存文件
            $upDir = __DIR__ . '/../assets/uploads/avatars';
            if (!is_dir($upDir)) { @mkdir($upDir, 0755, true); }
            $ext = strtolower($m[1]); if ($ext === 'jpg') $ext = 'jpeg';
            $fn = 'u_' . $uid . '_' . substr(md5($uid.mt_rand()), 0, 8) . '.' . $ext;
            $target = $upDir . '/' . $fn;
            @file_put_contents($target, $bin);
            $avatarUrl = 'assets/uploads/avatars/' . $fn;
        } else {
            json_response(['success' => false, 'message' => '头像格式必须为png/jpg/gif/webp']);
        }
    } else {
        // URL 直接使用
        $avatarUrl = $avatar;
    }
    $db->update('users', ['avatar' => $avatarUrl], 'id = ?', [$uid]);
    json_response(['success' => true, 'message' => '头像更新成功', 'avatar' => $avatarUrl]);
}

json_response(['success' => false, 'message' => 'Invalid action']);
?>
