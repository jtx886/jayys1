<?php
require_once dirname(__FILE__) . '/../includes/functions.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$db = Database::getInstance();

if ($action == 'get_latest_announcement') {
    $row = $db->fetchOne("SELECT * FROM announcements WHERE show_popup = 1 ORDER BY id DESC LIMIT 1");
    if (!$row) { json_response(['success' => true, 'announcement' => null]); }
    
    $dismissed = false;
    if (is_logged_in()) {
        $check = $db->fetchOne("SELECT id FROM announcement_dismissals WHERE user_id = ? AND announcement_id = ?", [$_SESSION['user_id'], $row['id']]);
        if ($check) $dismissed = true;
    }
    if ($dismissed) {
        json_response(['success' => true, 'announcement' => null]);
    }
    json_response(['success' => true, 'announcement' => [
        'id' => $row['id'],
        'title' => $row['title'],
        'content' => $row['content'],
        'created_at' => $row['created_at']
    ]]);
}

if ($action == 'dismiss_announcement' && is_logged_in()) {
    $aid = intval($_POST['announcement_id'] ?? 0);
    if ($aid > 0) {
        $db->query("INSERT IGNORE INTO announcement_dismissals (user_id, announcement_id) VALUES (?, ?)", [$_SESSION['user_id'], $aid]);
    }
    json_response(['success' => true]);
}

if ($action == 'play_sources') {
    $list = $db->fetchAll("SELECT * FROM play_sources WHERE status = 1 ORDER BY sort ASC, id ASC");
    json_response(['success' => true, 'sources' => $list]);
}

if ($action == 'get_settings') {
    $list = $db->fetchAll("SELECT setting_key, setting_value FROM site_settings");
    $settings = [];
    foreach ($list as $s) $settings[$s['setting_key']] = $s['setting_value'];
    json_response(['success' => true, 'settings' => $settings]);
}

json_response(['success' => false, 'message' => 'Invalid action']);
?>
