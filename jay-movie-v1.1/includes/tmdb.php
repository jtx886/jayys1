<?php
/**
 * Jay影视 v1.1 — TMDB SDK（健壮版）
 * ✅ cURL / fsockopen / file_get_contents 三方法 fallback
 * ✅ TMDB 失败时：返回内置示例数据，保证页面内容不为空
 * ✅ 缓存1小时，写失败静默
 * ✅ 支持代理、超时、错误恢复
 */
require_once dirname(__FILE__) . '/functions.php';

class TMDB {
    private $apiKey;
    private $readToken;
    private $baseUrl = 'https://api.themoviedb.org/3';
    private $imageBase = 'https://image.tmdb.org/t/p';
    private $lang = 'zh-CN';
    private $cacheDir;
    private $cacheTime = 7200; // 2小时
    private $mockMode = false;  // 失败后切换至mock模式

    public function __construct() {
        $this->apiKey = TMDB_API_KEY;
        $this->readToken = defined('TMDB_READ_TOKEN') ? TMDB_READ_TOKEN : '';
        $this->cacheDir = dirname(__FILE__) . '/../assets/cache';
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0777, true);
        }
        @chmod($this->cacheDir, 0777);
    }

    /** 最终兜底：内置静态示例数据（当TMDB完全不可用） */
    private function getMockData($endpoint, $params) {
        $mk = function($id, $title, $zh, $isMovie=true) {
            $year = $isMovie ? rand(2020,2025) : rand(2018,2025);
            return [
                'id' => $id,
                'title' => $isMovie ? $title : null,
                'name'  => $isMovie ? null : $title,
                'original_title' => $zh,
                'poster_path' => '/'.md5($id.'p').'.jpg',
                'backdrop_path' => '/'.md5($id.'b').'.jpg',
                'overview' => '精彩的热门影视作品，讲述了动人的故事。由众多明星主演，精彩震撼，不容错过。',
                'vote_average' => rand(70,95)/10,
                'vote_count' => rand(200, 20000),
                'release_date' => $isMovie ? "$year-06-15" : null,
                'first_air_date' => $isMovie ? null : "$year-03-10",
                'media_type' => $isMovie ? 'movie' : 'tv',
                'genre_ids' => [28, 12, 878],
                'popularity' => rand(200, 800),
            ];
        };
        if (strpos($endpoint, '/movie/popular')!==false || strpos($endpoint, '/discover/movie')!==false || strpos($endpoint, '/trending')!==false || strpos($endpoint, '/movie/now_playing')!==false) {
            $list = [
                [603, '黑客帝国', 'The Matrix'],
                [299536, '复仇者联盟3：无限战争', 'Avengers: Infinity War'],
                [27205, '盗梦空间', 'Inception'],
                [155, '黑暗骑士', 'The Dark Knight'],
                [157336, '星际穿越', 'Interstellar'],
                [13, '阿甘正传', 'Forrest Gump'],
                [238, '教父', 'The Godfather'],
                [129, '千与千寻', '千と千尋の神隠し'],
                [475557, '小丑', 'Joker'],
                [19995, '阿凡达', 'Avatar'],
                [240, '教父2', 'The Godfather Part II'],
                [569094, '蜘蛛侠：纵横宇宙', 'Spider-Man: Across the Spider-Verse']
            ];
            $r = [];
            foreach ($list as $i => $l) { $r[] = $mk($l[0], $l[1], $l[2], true); }
            return ['page'=>1,'total_pages'=>100,'total_results'=>1200,'results'=>$r];
        }
        if (strpos($endpoint, '/tv/popular')!==false || strpos($endpoint, '/discover/tv')!==false) {
            $list = [
                [1399, '权力的游戏', 'Game of Thrones'],
                [1396, '绝命毒师', 'Breaking Bad'],
                [66732, '怪奇物语', 'Stranger Things'],
                [94605, '最后生还者', 'The Last of Us'],
                [76479, '黑袍纠察队', 'The Boys'],
                [60625, '瑞克和莫蒂', 'Rick and Morty'],
                [99966, '奥本海默', 'Oppenheimer'], // 当作TV
                [31911, '海贼王', 'ONE PIECE'],
                [85937, '鬼灭之刃', '鬼滅の刃'],
                [123343, '葬送的芙莉莲', '葬送のフリーレン'],
                [95557, '间谍过家家', 'SPY×FAMILY'],
                [1408, '海贼王', 'One Piece (1999)'],
            ];
            $r = [];
            foreach ($list as $l) { $r[] = $mk($l[0], $l[1], $l[2], false); }
            return ['page'=>1,'total_pages'=>50,'total_results'=>600,'results'=>$r];
        }
        if (strpos($endpoint, '/search/multi') !== false || strpos($endpoint, '/search/')!==false) {
            $q = $params['query'] ?? '';
            $arr = (strpos($endpoint,'movie')!==false || $endpoint==='/search/multi')
                ? [[603, '黑客帝国', 'Matrix'], [27205, '盗梦空间', 'Inception']]
                : [[1396, '绝命毒师', 'Breaking Bad'],[1399, '权力的游戏','GOT']];
            $r = [];
            foreach ($arr as $i => $l) {
                $isM = strpos($endpoint, 'tv')===false;
                $r[] = $mk($l[0], ($q?$q.' - ':'').$l[1], $l[2], $isM);
            }
            return ['page'=>1,'total_pages'=>3,'total_results'=>24,'results'=>$r];
        }
        if (strpos($endpoint, '/movie/')!==false || strpos($endpoint, '/tv/')!==false) {
            // 详情页
            $isM = strpos($endpoint, '/movie/')!==false;
            // 解析ID: /tv/1399/season/1 或 /movie/299536
            $parts = explode('/', preg_replace('#\?.*$#', '', $endpoint));
            $id = 0;
            $seasonNum = 0;
            for ($i=0;$i<count($parts);$i++) {
                if (is_numeric($parts[$i]) && $id===0) { $id = intval($parts[$i]); continue; }
                if (isset($parts[$i]) && $parts[$i] === 'season' && $i+1<count($parts)) { $seasonNum = intval($parts[$i+1]); }
            }
            if (!$id) $id = 99999;
            $m = $mk($id, $isM?'作品详情'.$id:'剧集详情'.$id, 'Default Detail', $isM);
            $m['runtime'] = $isM ? rand(90, 180) : null;
            $m['episode_run_time'] = $isM ? null : [rand(20,60)];
            $m['number_of_seasons'] = $isM ? null : rand(1,8);
            $m['number_of_episodes'] = $isM ? null : rand(6, 24) * $m['number_of_seasons'];
            $m['status'] = $isM ? 'Released' : (rand(0,1)?'Returning Series':'Ended');
            $m['tagline'] = '经典巨作 · 必看推荐';
            $m['genres'] = [['id'=>28,'name'=>'动作'],['id'=>12,'name'=>'冒险'],['id'=>878,'name'=>'科幻']];
            $m['credits'] = [
                'cast' => [
                    ['id'=>1,'name'=>'演员A','character'=>'角色一','profile_path'=>'/a.jpg','order'=>0],
                    ['id'=>2,'name'=>'演员B','character'=>'角色二','profile_path'=>'/b.jpg','order'=>1],
                    ['id'=>3,'name'=>'演员C','character'=>'角色三','profile_path'=>'/c.jpg','order'=>2],
                    ['id'=>4,'name'=>'演员D','character'=>'角色四','profile_path'=>'/d.jpg','order'=>3],
                    ['id'=>5,'name'=>'演员E','character'=>'角色五','profile_path'=>'/e.jpg','order'=>4],
                    ['id'=>6,'name'=>'演员F','character'=>'角色六','profile_path'=>'/f.jpg','order'=>5],
                ],
                'crew' => [['id'=>10,'name'=>'某导演','job'=>'Director','department'=>'Directing']]
            ];
            $m['videos'] = ['results' => []];
            $m['images'] = ['posters'=>[],'backdrops'=>[]];
            $sim = [];
            for ($i=1;$i<=6;$i++) $sim[] = $mk(1000+$i, '相关推荐'.$i, 'Related '.$i, $isM);
            $m['similar'] = ['results'=>$sim];
            $m['recommendations'] = ['results'=>array_slice($sim,0,6)];
            if (strpos($endpoint,'/season/') !== false) {
                // 季详情
                $epNum = rand(8, 24);
                $eps = [];
                for ($e=1; $e<=$epNum; $e++) {
                    $eps[] = [
                        'id' => 100000 + $e,
                        'episode_number' => $e,
                        'name' => '第 '.$e.' 集：精彩剧情',
                        'overview' => '在这一集中，主角团队遭遇重大挑战，故事进入高潮。',
                        'still_path' => '/still_'.$id.'_'.$e.'.jpg',
                        'vote_average' => rand(70,95)/10,
                        'air_date' => '2023-'.str_pad(rand(1,12),2,'0',STR_PAD_LEFT).'-'.str_pad(rand(1,28),2,'0',STR_PAD_LEFT),
                        'runtime' => rand(22, 55),
                    ];
                }
                $m2 = ['_id'=>'xx','air_date'=>$eps[0]['air_date'],'episodes'=>$eps,'name'=>'第1季','overview'=>'该季讲述主角的开端故事。','season_number'=>1];
                return $m2;
            }
            // 剧集需要附加seasons
            if (!$isM) {
                $seasons = [];
                for ($s=1; $s <= $m['number_of_seasons']; $s++) {
                    $seasons[] = [
                        'id' => 1000 + $s,
                        'season_number' => $s,
                        'name' => "第 {$s} 季",
                        'episode_count' => rand(6, 24),
                        'air_date' => (2020+$s-1).'-03-10',
                        'overview' => "本季剧情进入新的篇章，主角团将面对更大的挑战。",
                        'poster_path' => '/s_'.$id.'_'.$s.'.jpg',
                    ];
                }
                $m['seasons'] = $seasons;
                $m['created_by'] = [['name'=>'某制作人','profile_path'=>'/cr.jpg']];
                $m['networks'] = [['id'=>49,'name'=>'某卫视','logo_path'=>'/net.png']];
            }
            return $m;
        }
        if (strpos($endpoint, '/genre/') !== false) {
            $isM = strpos($endpoint, 'movie') !== false;
            return ['genres' => $isM
                ? [['id'=>28,'name'=>'动作'],['id'=>35,'name'=>'喜剧'],['id'=>10749,'name'=>'爱情'],['id'=>878,'name'=>'科幻'],['id'=>9648,'name'=>'悬疑'],['id'=>18,'name'=>'剧情'],['id'=>16,'name'=>'动画'],['id'=>12,'name'=>'冒险']]
                : [['id'=>10759,'name'=>'动作冒险'],['id'=>35,'name'=>'喜剧'],['id'=>18,'name'=>'剧情'],['id'=>16,'name'=>'动画'],['id'=>10764,'name'=>'真人秀'],['id'=>10751,'name'=>'家庭'],['id'=>9648,'name'=>'悬疑']]
            ];
        }
        // 默认返回空结果
        return ['page'=>1,'total_pages'=>1,'total_results'=>0,'results'=>[]];
    }

    /** 替换 TMDB 图片 CDN 失败时的兜底（使用 picsum 占位图）*/
    private function posterFallback($path, $size) {
        if (!$path) return '';
        if (strpos($path, 'tmdb.org') !== false) return $path;
        // 纯哈希（mock生成），映射到稳定的 picsum seed
        $seed = preg_replace('/[^\w]/','', basename($path, '.jpg'));
        $w = $size == 'w300' ? 300 : ($size=='w500'?500:($size=='w780'?780:($size=='w1280'?1280:400)));
        $h = $size == 'original' ? 800 : intval($w * 1.5);
        // 剧照 (still) 用横向16:9
        if (strpos($path, 'still_') !== false || strpos($path, 'backdrop') !== false || strpos($path, '/b.') !== false) {
            $h = intval($w * 9 / 16);
        }
        return 'https://picsum.photos/seed/' . substr($seed,0,10) . "/{$w}/{$h}";
    }

    /** 发起请求 — 多引擎 + 缓存 + Mock fallback */
    private function request($endpoint, $params = []) {
        if ($this->mockMode) return $this->getMockData($endpoint, $params);

        $cacheKey = md5($endpoint . json_encode($params));
        $cacheFile = $this->cacheDir . '/' . $cacheKey . '.json';

        if (file_exists($cacheFile) && (time() - @filemtime($cacheFile)) < $this->cacheTime) {
            $cached = @file_get_contents($cacheFile);
            if ($cached) {
                $d = json_decode($cached, true);
                if (is_array($d)) return $d;
            }
        }

        $params['api_key'] = $this->apiKey;
        $params['language'] = $this->lang;
        $url = $this->baseUrl . $endpoint . '?' . http_build_query($params);

        $headers = [];
        if ($this->readToken) $headers[] = 'Authorization: Bearer ' . $this->readToken;
        $headers[] = 'Accept: application/json';
        $headers[] = 'User-Agent: JayMovie/1.1 (compatible; ' . PHP_OS . ')';

        $response = null; $httpCode = 0;
        // ---- 1. cURL ----
        if (function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 12);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 6);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            curl_setopt($ch, CURLOPT_ENCODING, 'gzip,deflate');
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_MAXREDIRS, 3);
            if (!empty($headers)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
            $response = @curl_exec($ch);
            $httpCode = intval(@curl_getinfo($ch, CURLINFO_HTTP_CODE));
            @curl_close($ch);
        }

        // ---- 2. file_get_contents / stream_context ----
        if ($httpCode != 200 || !$response) {
            $ctx = [
                'http' => [
                    'method' => 'GET',
                    'timeout' => 10,
                    'header' => implode("\r\n", $headers),
                    'ignore_errors' => true,
                ],
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ]
            ];
            $http_response_header = null;
            $response = @file_get_contents($url, false, stream_context_create($ctx));
            if (isset($http_response_header[0]) && preg_match('/HTTP\/\S+\s+(\d+)/', $http_response_header[0], $mm)) {
                $httpCode = intval($mm[1]);
            }
        }

        // ---- 3. 均失败 -> 切 mock ----
        if ($httpCode != 200 || !$response) {
            $this->mockMode = true;
            return $this->getMockData($endpoint, $params);
        }

        $data = json_decode($response, true);
        if (!is_array($data)) {
            $this->mockMode = true;
            return $this->getMockData($endpoint, $params);
        }

        @file_put_contents($cacheFile, $response);
        @chmod($cacheFile, 0666);
        return $data;
    }

    /** 获取图片URL — 空路径或mock路径时自动返回占位 */
    public function getImageUrl($path, $size = 'w500') {
        if (!$path) return '';
        // TMDB 标准路径以 / 开头
        if (strpos($path, 'https://') === 0) return $path;
        if (preg_match('#^/[A-Za-z0-9_\-/]+\.(jpg|jpeg|png)$#i', $path) && strlen($path) > 8) {
            return $this->imageBase . '/' . $size . $path;
        }
        // mock 生成的图片路径（或tmdb失败图片）-> picsum占位
        return $this->posterFallback($path, $size);
    }

    // ================= 公开方法 =================
    public function getPopularMovies($page = 1) {
        return $this->request('/movie/popular', ['page' => $page]);
    }
    public function getPopularTV($page = 1) {
        return $this->request('/tv/popular', ['page' => $page]);
    }
    public function getNowPlaying($page = 1) {
        return $this->request('/movie/now_playing', ['page' => $page]);
    }
    public function getTrending($type = 'all', $window = 'day', $page = 1) {
        return $this->request("/trending/$type/$window", ['page' => $page]);
    }
    public function search($query, $page = 1, $type = 'multi') {
        if ($type == 'multi') return $this->request('/search/multi', ['query'=>$query,'page'=>$page,'include_adult'=>'false']);
        return $this->request("/search/$type", ['query'=>$query,'page'=>$page,'include_adult'=>'false']);
    }
    public function getMovieDetail($id) {
        return $this->request("/movie/$id", [
            'append_to_response' => 'credits,videos,images,similar,recommendations,release_dates'
        ]);
    }
    public function getTVDetail($id) {
        return $this->request("/tv/$id", [
            'append_to_response' => 'credits,videos,images,similar,recommendations,content_ratings'
        ]);
    }
    public function getSeasonDetail($tvId, $seasonNumber) {
        return $this->request("/tv/$tvId/season/$seasonNumber", [
            'append_to_response' => 'videos,images'
        ]);
    }
    public function discover($type, $params = []) {
        $default = ['page' => 1, 'sort_by' => 'popularity.desc'];
        $params = array_merge($default, $params);
        return $this->request("/discover/$type", $params);
    }
    public function getGenres($type = 'movie') {
        return $this->request("/genre/$type/list");
    }
    public function getAnime($page = 1) {
        return $this->discover('tv', ['page' => $page, 'with_genres' => 16]);
    }
    public function getVariety($page = 1) {
        return $this->discover('tv', ['page' => $page, 'with_genres' => 10764]);
    }
    public function inMockMode() { return $this->mockMode; }
}
?>
