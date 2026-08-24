<?php
require_once dirname(__FILE__) . '/functions.php';

class TMDB {
    private $apiKey;
    private $readToken;
    private $baseUrl = 'https://api.themoviedb.org/3';
    private $imageBase = 'https://image.tmdb.org/t/p';
    private $lang = 'zh-CN';
    private $cacheDir;
    private $cacheTime = 3600; // 1小时缓存

    public function __construct() {
        $this->apiKey = TMDB_API_KEY;
        $this->readToken = defined('TMDB_READ_TOKEN') ? TMDB_READ_TOKEN : '';
        $this->cacheDir = dirname(__FILE__) . '/../assets/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
    }

    // 发起请求
    private function request($endpoint, $params = []) {
        $cacheKey = md5($endpoint . json_encode($params));
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';
        
        if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $this->cacheTime) {
            $cached = @file_get_contents($cacheFile);
            if ($cached) return json_decode($cached, true);
        }

        $params['api_key'] = $this->apiKey;
        $params['language'] = $this->lang;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
        $headers = [];
        if ($this->readToken) {
            $headers[] = 'Authorization: Bearer ' . $this->readToken;
        }
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode == 200 && $response) {
            @file_put_contents($cacheFile, $response);
            return json_decode($response, true);
        }
        return null;
    }

    public function getImageUrl($path, $size = 'w500') {
        if (!$path) return '';
        return $this->imageBase . '/' . $size . $path;
    }

    // 获取热门电影
    public function getPopularMovies($page = 1) {
        return $this->request('/movie/popular', ['page' => $page]);
    }

    // 获取热门电视剧
    public function getPopularTV($page = 1) {
        return $this->request('/tv/popular', ['page' => $page]);
    }

    // 获取正在上映
    public function getNowPlaying($page = 1) {
        return $this->request('/movie/now_playing', ['page' => $page]);
    }

    // 趋势
    public function getTrending($type = 'all', $window = 'day', $page = 1) {
        return $this->request("/trending/$type/$window", ['page' => $page]);
    }

    // 搜索
    public function search($query, $page = 1, $type = 'multi') {
        if ($type == 'multi') {
            return $this->request('/search/multi', ['query' => $query, 'page' => $page, 'include_adult' => 'false']);
        }
        return $this->request("/search/$type", ['query' => $query, 'page' => $page, 'include_adult' => 'false']);
    }

    // 电影详情
    public function getMovieDetail($id) {
        return $this->request("/movie/$id", [
            'append_to_response' => 'credits,videos,images,similar,recommendations,release_dates'
        ]);
    }

    // 电视剧详情
    public function getTVDetail($id) {
        return $this->request("/tv/$id", [
            'append_to_response' => 'credits,videos,images,similar,recommendations,content_ratings'
        ]);
    }

    // 获取某季详情
    public function getSeasonDetail($tvId, $seasonNumber) {
        return $this->request("/tv/$tvId/season/$seasonNumber", [
            'append_to_response' => 'videos,images'
        ]);
    }

    // 按分类获取
    public function discover($type, $params = []) {
        $default = ['page' => 1, 'sort_by' => 'popularity.desc'];
        $params = array_merge($default, $params);
        return $this->request("/discover/$type", $params);
    }

    // 获取分类
    public function getGenres($type = 'movie') {
        return $this->request("/genre/$type/list");
    }

    // 获取动漫 (TV分类里的动画类型)
    public function getAnime($page = 1) {
        $genres = $this->getGenres('tv');
        $animeId = 16; // Animation genre id
        if ($genres && isset($genres['genres'])) {
            foreach ($genres['genres'] as $g) {
                if (strpos($g['name'], '动画') !== false || $g['name'] == 'Animation') {
                    $animeId = $g['id'];
                    break;
                }
            }
        }
        return $this->discover('tv', ['page' => $page, 'with_genres' => $animeId]);
    }

    // 获取综艺 (真人秀等类型)
    public function getVariety($page = 1) {
        $genres = $this->getGenres('tv');
        $realityId = 10764; // Reality
        if ($genres && isset($genres['genres'])) {
            foreach ($genres['genres'] as $g) {
                if ($g['id'] == 10764 || strpos($g['name'], '真人') !== false) {
                    $realityId = $g['id'];
                    break;
                }
            }
        }
        return $this->discover('tv', ['page' => $page, 'with_genres' => $realityId]);
    }
}
?>
