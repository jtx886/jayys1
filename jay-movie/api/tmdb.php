<?php
require_once dirname(__FILE__) . '/../includes/functions.php';
require_once dirname(__FILE__) . '/../includes/tmdb.php';

header('Content-Type: application/json; charset=utf-8');

$action = $_REQUEST['action'] ?? '';
$tmdb = new TMDB();

if ($action == 'home_data') {
    $data = [
        'trending' => $tmdb->getTrending('all', 'day', 1),
        'popular_movies' => $tmdb->getPopularMovies(1),
        'popular_tv' => $tmdb->getPopularTV(1),
        'now_playing' => $tmdb->getNowPlaying(1),
        'anime' => $tmdb->getAnime(1),
        'variety' => $tmdb->getVariety(1)
    ];
    // 处理图片URL
    foreach ($data as $key => $list) {
        if (isset($list['results'])) {
            foreach ($list['results'] as &$item) {
                if (isset($item['poster_path'])) $item['poster_url'] = $tmdb->getImageUrl($item['poster_path'], 'w500');
                if (isset($item['backdrop_path'])) $item['backdrop_url'] = $tmdb->getImageUrl($item['backdrop_path'], 'original');
                if (isset($item['profile_path'])) $item['profile_url'] = $tmdb->getImageUrl($item['profile_path'], 'w185');
            }
        }
    }
    json_response(['success' => true, 'data' => $data]);
}

if ($action == 'search') {
    $q = trim($_GET['q'] ?? '');
    $page = intval($_GET['page'] ?? 1);
    if (!$q) json_response(['success' => false, 'message' => '请输入搜索关键词']);
    $res = $tmdb->search($q, $page, 'multi');
    if (isset($res['results'])) {
        foreach ($res['results'] as &$item) {
            if (isset($item['poster_path'])) $item['poster_url'] = $tmdb->getImageUrl($item['poster_path'], 'w500');
            if (isset($item['backdrop_path'])) $item['backdrop_url'] = $tmdb->getImageUrl($item['backdrop_path'], 'w1280');
        }
    }
    json_response(['success' => true, 'results' => $res['results'] ?? [], 'total' => $res['total_results'] ?? 0]);
}

if ($action == 'detail') {
    $type = $_GET['type'] ?? 'movie';
    $id = intval($_GET['id'] ?? 0);
    if (!$id) json_response(['success' => false, 'message' => 'ID错误']);
    $detail = $type == 'tv' ? $tmdb->getTVDetail($id) : $tmdb->getMovieDetail($id);
    if (!$detail) json_response(['success' => false, 'message' => '获取详情失败']);
    $detail['poster_url'] = isset($detail['poster_path']) ? $tmdb->getImageUrl($detail['poster_path'], 'w500') : '';
    $detail['backdrop_url'] = isset($detail['backdrop_path']) ? $tmdb->getImageUrl($detail['backdrop_path'], 'original') : '';
    if (isset($detail['credits']['cast'])) {
        foreach ($detail['credits']['cast'] as &$c) {
            if (isset($c['profile_path'])) $c['profile_url'] = $tmdb->getImageUrl($c['profile_path'], 'w185');
        }
    }
    if (isset($detail['seasons'])) {
        foreach ($detail['seasons'] as &$s) {
            if (isset($s['poster_path'])) $s['poster_url'] = $tmdb->getImageUrl($s['poster_path'], 'w300');
        }
    }
    if (isset($detail['similar']['results'])) {
        foreach ($detail['similar']['results'] as &$s) {
            if (isset($s['poster_path'])) $s['poster_url'] = $tmdb->getImageUrl($s['poster_path'], 'w500');
        }
    }
    json_response(['success' => true, 'detail' => $detail]);
}

if ($action == 'season') {
    $tvId = intval($_GET['tv_id'] ?? 0);
    $sn = intval($_GET['season'] ?? 1);
    $season = $tmdb->getSeasonDetail($tvId, $sn);
    if (!$season) json_response(['success' => false, 'message' => '获取季数据失败']);
    if (isset($season['poster_path'])) $season['poster_url'] = $tmdb->getImageUrl($season['poster_path'], 'w500');
    if (isset($season['episodes'])) {
        foreach ($season['episodes'] as &$ep) {
            if (isset($ep['still_path'])) $ep['still_url'] = $tmdb->getImageUrl($ep['still_path'], 'w300');
        }
    }
    json_response(['success' => true, 'season' => $season]);
}

if ($action == 'category') {
    $type = $_GET['type'] ?? 'movie';
    $page = intval($_GET['page'] ?? 1);
    $genre = intval($_GET['genre'] ?? 0);
    $sort = $_GET['sort'] ?? 'popularity.desc';
    $params = ['page' => $page, 'sort_by' => $sort];
    if ($genre > 0) $params['with_genres'] = $genre;
    $res = $tmdb->discover($type, $params);
    if (isset($res['results'])) {
        foreach ($res['results'] as &$item) {
            if (isset($item['poster_path'])) $item['poster_url'] = $tmdb->getImageUrl($item['poster_path'], 'w500');
        }
    }
    json_response(['success' => true, 'results' => $res['results'] ?? [], 'total' => $res['total_pages'] ?? 1]);
}

json_response(['success' => false, 'message' => 'Invalid action']);
?>
