<?php
require_once dirname(__FILE__) . '/../config.php';

/**
 * Jay影视 v1.1 数据库类 — 已修复全部字段名与SQL兼容问题
 * ✅ MySQL + SQLite 双引擎
 * ✅ 自动初始化，表/列名100%匹配代码引用
 * ✅ SQLite CURDATE() 函数仿真（MySQL兼容）
 * ✅ SQLite LIMIT x,y 语法兼容
 * ✅ 可空字段/索引 双引擎完全兼容
 */
class Database {
    private static $instance = null;
    private $conn;
    private $engine = 'mysql'; // mysql | sqlite
    private $initialized = false;

    private function __construct() {
        $this->connect();
        if ($this->conn && !$this->isDbReady()) {
            $this->initDb();
        }
    }

    private function connect() {
        $engine = defined('DB_ENGINE') ? DB_ENGINE : 'auto';
        $triedMysql = false;
        $mysqlError = '';

        if ($engine === 'auto' || $engine === 'mysql') {
            try {
                $charset = defined('DB_CHARSET') ? DB_CHARSET : 'utf8mb4';
                $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . $charset;
                $opts = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => true,  // true兼容性更高
                ];
                $this->conn = new PDO($dsn, DB_USER, DB_PASS, $opts);
                $this->engine = 'mysql';
                return;
            } catch (PDOException $e) {
                $triedMysql = true;
                $mysqlError = $e->getMessage();
                if ($engine === 'mysql') {
                    $this->failInstall($mysqlError);
                }
            }
        }

        // ---- SQLite Fallback ----
        try {
            $sqlitePath = defined('SQLITE_PATH') ? SQLITE_PATH : __DIR__ . '/../assets/uploads/jay_movie.sqlite';
            $dir = dirname($sqlitePath);
            if (!is_dir($dir)) {
                @mkdir($dir, 0755, true);
            }
            @chmod($dir, 0755);
            if (!is_file($sqlitePath)) {
                @touch($sqlitePath);
                @chmod($sqlitePath, 0666);
            }
            $this->conn = new PDO('sqlite:' . $sqlitePath);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $this->conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
            $this->engine = 'sqlite';
            // ---- 注册 MySQL 兼容函数 ----
            $fnList = [
                'CURDATE'     => function() { return date('Y-m-d'); },
                'NOW'         => function() { return date('Y-m-d H:i:s'); },
                'DATE'        => function($d) { if (empty($d)) return ''; $t = is_numeric($d) ? intval($d) : strtotime($d); return $t ? date('Y-m-d', $t) : substr($d, 0, 10); },
                'YEAR'        => function($d) { $t = is_numeric($d)?$d:@strtotime($d); return $t?date('Y',$t):''; },
                'MONTH'       => function($d) { $t = is_numeric($d)?$d:@strtotime($d); return $t?date('m',$t):''; },
                'IFNULL'      => function($a,$b) { return $a===null||$a==='' ? $b : $a; },
                'PASSWORD'    => function($p) { return password_hash($p, PASSWORD_DEFAULT); },
                'MD5'         => function($s) { return md5($s); },
                'DATEDIFF'    => function($a,$b) { $ta=strtotime($a);$tb=strtotime($b); return $ta===false||$tb===false?0:intval(($ta-$tb)/86400); },
            ];
            foreach ($fnList as $name => $fn) {
                try {
                    if (PHP_VERSION_ID >= 80500 && class_exists('Pdo\Sqlite', false)) {
                        \Pdo\Sqlite::createFunction($this->conn, $name, $fn);
                    } else {
                        $this->conn->sqliteCreateFunction($name, $fn);
                    }
                } catch (Throwable $e) { /* ignore sqlite function registration failures */ }
            }
            if ($triedMysql) {
                error_log('[JayMovie v1.1] MySQL 连接失败，已自动切换 SQLite：' . $mysqlError);
            }
        } catch (PDOException $e) {
            $this->failInstall('SQLite连接失败: ' . $e->getMessage() . '; 请确认目录可写: ' . dirname($sqlitePath));
        }
    }

    private function failInstall($msg) {
        $current = $_SERVER['REQUEST_URI'] ?? '';
        $isApi = strpos($current, '/api/') !== false ||
                 (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
                 (isset($_POST['action']) || isset($_GET['action']));
        if (PHP_SAPI === 'cli') { $this->conn = null; return; }
        if ($isApi) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => false, 'need_install' => true, 'message' => '数据库不可用', 'detail' => $msg, 'install_url' => '/install/index.php'], JSON_UNESCAPED_UNICODE);
        } else {
            header('Location: /install/index.php');
        }
        exit;
    }

    private function isDbReady() {
        try {
            if ($this->engine == 'mysql') {
                $stmt = $this->conn->query("SHOW TABLES LIKE 'users'");
            } else {
                $stmt = $this->conn->query("SELECT name FROM sqlite_master WHERE type='table' AND name='users'");
            }
            $row = $stmt->fetch();
            return !empty($row);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * 创建表结构 — 字段名与 admin/api.php / api/*.php 完全一致
     * 代码统一使用的列名：
     *   users:       status, ban_time, unban_time, ban_reason, last_login_at, last_login_ip
     *   play_sources: name, url, type, sort, status
     *   announcements: title, content, show_popup, admin_id
     *   feedbacks:    单数表名->改为复数 feedbacks 对齐代码
     */
    private function initDb() {
        $isMySQL = $this->engine == 'mysql';
        $QT = $isMySQL ? '`' : '"';
        $INT_PK   = $isMySQL ? 'INT(11) PRIMARY KEY AUTO_INCREMENT' : 'INTEGER PRIMARY KEY AUTOINCREMENT';
        $TINYINT  = $isMySQL ? 'TINYINT(1)' : 'INTEGER';
        $DATETIME = $isMySQL ? 'DATETIME' : 'TEXT';
        $INT      = $isMySQL ? 'INT(11)' : 'INTEGER';
        $BIGINT   = $isMySQL ? 'BIGINT' : 'INTEGER';

        $Q = function($sql) use ($isMySQL) {
            if (!$isMySQL) {
                $sql = preg_replace('/`([^`]+)`/', '"$1"', $sql);
            }
            $this->conn->exec($sql);
        };

        $schema = [];
        // ========== users ==========
        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}users{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}username{$QT} VARCHAR(64) NOT NULL UNIQUE,
            {$QT}email{$QT} VARCHAR(128) NOT NULL UNIQUE,
            {$QT}password{$QT} VARCHAR(255) NOT NULL,
            {$QT}avatar{$QT} TEXT,
            {$QT}is_admin{$QT} $TINYINT DEFAULT 0,
            {$QT}status{$QT} $TINYINT DEFAULT 1,
            {$QT}ban_time{$QT} $DATETIME,
            {$QT}unban_time{$QT} $DATETIME,
            {$QT}ban_reason{$QT} TEXT,
            {$QT}last_login_at{$QT} $DATETIME,
            {$QT}last_login_ip{$QT} VARCHAR(45),
            {$QT}created_at{$QT} $DATETIME DEFAULT " . ($isMySQL ? "CURRENT_TIMESTAMP" : "'".date('Y-m-d H:i:s')."'") . "
        )";

        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}email_verifications{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}email{$QT} VARCHAR(128) NOT NULL,
            {$QT}code{$QT} VARCHAR(10) NOT NULL,
            {$QT}used{$QT} $TINYINT DEFAULT 0,
            {$QT}type{$QT} VARCHAR(20) DEFAULT 'register',
            {$QT}expires_at{$QT} $DATETIME NOT NULL,
            {$QT}expire_time{$QT} $DATETIME NOT NULL,
            {$QT}created_at{$QT} $DATETIME
        )";
        // 兼容别名表 email_codes
        if ($isMySQL) {
            $schema[] = "DROP VIEW IF EXISTS {$QT}email_codes{$QT}";
            $schema[] = "CREATE VIEW {$QT}email_codes{$QT} AS SELECT id, email, code, used, type, expires_at AS expire_time, created_at FROM {$QT}email_verifications{$QT}";
        } else {
            // SQLite: 直接创建同名的表避免兼容性问题
            $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}email_codes{$QT} (
                {$QT}id{$QT} $INT_PK,
                {$QT}email{$QT} VARCHAR(128) NOT NULL,
                {$QT}code{$QT} VARCHAR(10) NOT NULL,
                {$QT}used{$QT} $TINYINT DEFAULT 0,
                {$QT}type{$QT} VARCHAR(20) DEFAULT 'register',
                {$QT}expire_time{$QT} $DATETIME NOT NULL,
                {$QT}created_at{$QT} $DATETIME
            )";
        }

        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}favorites{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}user_id{$QT} $INT NOT NULL,
            {$QT}media_type{$QT} VARCHAR(10) NOT NULL,
            {$QT}media_id{$QT} $BIGINT NOT NULL,
            {$QT}title{$QT} VARCHAR(255) NOT NULL,
            {$QT}poster{$QT} TEXT,
            {$QT}created_at{$QT} $DATETIME,
            UNIQUE(user_id, media_type, media_id)
        )";

        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}watch_history{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}user_id{$QT} $INT NOT NULL,
            {$QT}media_type{$QT} VARCHAR(10) NOT NULL,
            {$QT}media_id{$QT} $BIGINT NOT NULL,
            {$QT}season{$QT} $INT DEFAULT 0,
            {$QT}episode{$QT} $INT DEFAULT 0,
            {$QT}title{$QT} VARCHAR(255) NOT NULL,
            {$QT}poster{$QT} TEXT,
            {$QT}position_sec{$QT} $INT DEFAULT 0,
            {$QT}duration_sec{$QT} $INT DEFAULT 0,
            {$QT}created_at{$QT} $DATETIME,
            {$QT}updated_at{$QT} $DATETIME,
            UNIQUE(user_id, media_type, media_id, season, episode)
        )";

        // 播放源：字段名完全对齐 admin/api.php:118-123
        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}play_sources{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}name{$QT} VARCHAR(64) NOT NULL,
            {$QT}url{$QT} VARCHAR(512) NOT NULL,
            {$QT}type{$QT} VARCHAR(16) DEFAULT 'movie',
            {$QT}sort{$QT} $INT DEFAULT 0,
            {$QT}status{$QT} $TINYINT DEFAULT 1
        )";

        // 公告：show_popup, admin_id（对齐 admin/api.php:141-157）
        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}announcements{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}title{$QT} VARCHAR(255) NOT NULL,
            {$QT}content{$QT} TEXT NOT NULL,
            {$QT}show_popup{$QT} $TINYINT DEFAULT 1,
            {$QT}admin_id{$QT} $INT DEFAULT 0,
            {$QT}created_at{$QT} $DATETIME,
            {$QT}updated_at{$QT} $DATETIME
        )";

        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}announcement_dismissals{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}user_id{$QT} $INT,
            {$QT}session_id{$QT} VARCHAR(128),
            {$QT}announcement_id{$QT} $INT NOT NULL,
            {$QT}dismissed_at{$QT} $DATETIME
        )";

        // ---- feedbacks（复数，对齐 admin/api.php:24 / api/feedback.php ）----
        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}feedbacks{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}user_id{$QT} $INT NOT NULL,
            {$QT}title{$QT} VARCHAR(255) NOT NULL,
            {$QT}content{$QT} TEXT NOT NULL,
            {$QT}category{$QT} VARCHAR(32) DEFAULT '建议',
            {$QT}status{$QT} VARCHAR(20) DEFAULT 'pending',
            {$QT}likes_count{$QT} $INT DEFAULT 0,
            {$QT}replies_count{$QT} $INT DEFAULT 0,
            {$QT}created_at{$QT} $DATETIME,
            {$QT}updated_at{$QT} $DATETIME
        )";
        // v1.1 迁移：补齐 feedbacks.category（老库没有该字段时自动加入）
        try {
            if ($isMySQL) {
                $Q("ALTER TABLE {$QT}feedbacks{$QT} ADD COLUMN {$QT}category{$QT} VARCHAR(32) DEFAULT '建议'");
            } else {
                // SQLite：先查 PRAGMA，不存在则重建（太复杂），改用直接加列
                $cols = $this->conn->query("PRAGMA table_info({$QT}feedbacks{$QT})")->fetchAll();
                $has = false;
                foreach ($cols as $c) if (strcasecmp($c['name'] ?? '', 'category') === 0) { $has = true; break; }
                if (!$has) $Q("ALTER TABLE {$QT}feedbacks{$QT} ADD COLUMN {$QT}category{$QT} VARCHAR(32) DEFAULT '建议'");
            }
        } catch (Throwable $e) { /* ignore */ }

        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}feedback_replies{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}feedback_id{$QT} $INT NOT NULL,
            {$QT}user_id{$QT} $INT NOT NULL,
            {$QT}content{$QT} TEXT NOT NULL,
            {$QT}is_admin{$QT} $TINYINT DEFAULT 0,
            {$QT}created_at{$QT} $DATETIME
        )";

        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}feedback_likes{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}feedback_id{$QT} $INT NOT NULL,
            {$QT}user_id{$QT} $INT NOT NULL,
            {$QT}created_at{$QT} $DATETIME,
            UNIQUE(feedback_id, user_id)
        )";

        $schema[] = "CREATE TABLE IF NOT EXISTS {$QT}site_settings{$QT} (
            {$QT}id{$QT} $INT_PK,
            {$QT}setting_key{$QT} VARCHAR(128) NOT NULL UNIQUE,
            {$QT}setting_value{$QT} TEXT,
            {$QT}updated_at{$QT} $DATETIME
        )";

        foreach ($schema as $sql) {
            try { $Q($sql); } catch (Exception $e) { error_log('INIT DB SQL error: ' . $e->getMessage()); }
        }

        // ====== 初始数据 ======
        try {
            $pwd = password_hash('101113', PASSWORD_DEFAULT);
            $now = date('Y-m-d H:i:s');
            if ($isMySQL) {
                $this->conn->exec("INSERT IGNORE INTO `users` (id,username,email,password,is_admin,status,created_at) VALUES (1,'杰同学','admin@jaymovie.com','$pwd',1,1,'$now')");
            } else {
                $stmt = $this->conn->prepare("INSERT OR IGNORE INTO \"users\" (id,username,email,password,is_admin,status,created_at) VALUES (1,?,?,?,1,1,?)");
                $stmt->execute(['杰同学', 'admin@jaymovie.com', $pwd, $now]);
            }
            $this->ensureInitialSources($isMySQL);
            $this->ensureInitialSettings($isMySQL);
            $this->ensureInitialAnnouncement($isMySQL, $now);
        } catch (Exception $e) {
            error_log('INIT DATA: ' . $e->getMessage());
        }

        $this->initialized = true;
        if (!defined('SITE_INSTALLED')) {
            define('SITE_INSTALLED', true);
        }
    }

    private function ensureInitialSources($isMySQL) {
        $QT = $isMySQL ? '`' : '"';
        $check = $this->conn->query("SELECT COUNT(*) c FROM {$QT}play_sources{$QT}")->fetch();
        if (!empty($check['c'])) return;
        // admin/api.php 第118-123行定义的字段: name, url, type, sort, status
        $rows = [
            ['name' => '云影资源',   'url'  => 'https://api.yyzy-tv.vip/inc/apijson.php',     'type' => 'json',  'sort' => 1, 'status' => 1],
            ['name' => '备用资源1',  'url'  => 'https://json.emsbd.com/api.php/provide/vod',   'type' => 'json',  'sort' => 2, 'status' => 1],
            ['name' => '高清线路',   'url'  => 'https://www.xmly.app/inc/apijson.php',        'type' => 'json',  'sort' => 3, 'status' => 0],
        ];
        foreach ($rows as $r) {
            $stmt = $this->conn->prepare("INSERT INTO {$QT}play_sources{$QT} ({$QT}name{$QT},{$QT}url{$QT},{$QT}type{$QT},{$QT}sort{$QT},{$QT}status{$QT}) VALUES (?,?,?,?,?)");
            $stmt->execute(array_values($r));
        }
    }

    private function ensureInitialSettings($isMySQL) {
        $QT = $isMySQL ? '`' : '"';
        $check = $this->conn->query("SELECT COUNT(*) c FROM {$QT}site_settings{$QT}")->fetch();
        if (!empty($check['c'])) return;
        $rows = [
            ['site_name',       'Jay影视'],
            ['theme_color',     '#6366f1'],
            ['player_parse_url','https://svip.ffzyplay.com/?url='],
            ['tmdb_api_key',    'cb44223c5dee5676ed3a839f42ed27e3'],
            ['tmdb_read_token', 'eyJhbGciOiJIUzI1NiJ9.eyJhdWQiOiJjYjQ0MjIzYzVlZWU1Njc2ZWQzYTM5ZjQyZWQyN2UzIiwiZXhwIjoxOTU0Mjk1MjM2LCJzY29wZXMiOlsiYXBpX3JlYWQiXSwidmVyc2lvbiI6MX0.G5UWM3wql05P9SnJf0Py9NNjMikjsXSNGX7a6i6t4qs'],
        ];
        foreach ($rows as $r) {
            $stmt = $this->conn->prepare("INSERT INTO {$QT}site_settings{$QT} ({$QT}setting_key{$QT},{$QT}setting_value{$QT}) VALUES (?,?)");
            $stmt->execute($r);
        }
    }

    private function ensureInitialAnnouncement($isMySQL, $now) {
        $QT = $isMySQL ? '`' : '"';
        $check = $this->conn->query("SELECT COUNT(*) c FROM {$QT}announcements{$QT}")->fetch();
        if (!empty($check['c'])) return;
        $title = '🎉 欢迎来到 Jay影视';
        $content = '<p>欢迎使用 <b>Jay影视</b>！在这里可以观看海量高清电影、电视剧、动漫和综艺。</p>
                    <p>👋 新用户请先注册账号再播放。如有问题，请进入「反馈」页面提交您的建议或 bug~</p>
                    <p style="color:#6366f1;">— 杰同学</p>';
        if ($isMySQL) {
            $stmt = $this->conn->prepare("INSERT INTO `announcements` (`title`,`content`,`show_popup`,`admin_id`,`created_at`,`updated_at`) VALUES (?,?,1,1,?,?)");
        } else {
            $stmt = $this->conn->prepare('INSERT INTO "announcements" ("title","content","show_popup","admin_id","created_at","updated_at") VALUES (?,?,1,1,?,?)');
        }
        $stmt->execute([$title, $content, $now, $now]);
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() { return $this->conn; }
    public function getEngine()     { return $this->engine; }
    public function isInitialized() { return $this->initialized; }

    /**
     * 执行一条无返回结果的 SQL（如 DELETE/CREATE/ALTER），兼容 MySQL/SQLite 语法预处理
     * @param string $sql
     * @param array  $params
     * @return int|false 受影响行数或false失败
     */
    public function exec($sql, $params = []) {
        if (!$this->conn) return false;
        $this->normalizeSql($sql, $params);
        try {
            if (empty($params)) {
                return $this->conn->exec($sql);
            }
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->execute($params);
            return $stmt->rowCount();
        } catch (PDOException $e) {
            error_log('[DB Exec Error] ' . $e->getMessage() . ' SQL: ' . $sql);
            return false;
        }
    }

    public function quoteName($name) {
        return $this->engine == 'mysql' ? "`$name`" : '"$name"';
    }

    /** SQL 兼容性预处理：
     * - SQLite LIMIT offset,count -> LIMIT count OFFSET offset
     * - `name` -> "name" (SQLite)
     * - INSERT IGNORE (MySQL) -> INSERT OR IGNORE (SQLite)
     * - UPDATE IGNORE / ON DUPLICATE KEY 不处理
     */
    private function normalizeSql(&$sql, &$params) {
        if ($this->engine === 'sqlite') {
            $sql = preg_replace('/`([^`]+)`/', '"$1"', $sql);
            // LIMIT offset, rowCount -> LIMIT rowCount OFFSET offset
            if (preg_match('/\bLIMIT\s+(\d+)\s*,\s*(\d+)\s*;?\s*$/i', $sql, $mm)) {
                $offset = $mm[1]; $count = $mm[2];
                $sql = preg_replace('/\bLIMIT\s+(\d+)\s*,\s*(\d+)\s*;?\s*$/i', " LIMIT $count OFFSET $offset ", $sql);
            }
            // INSERT IGNORE 替换
            if (preg_match('/^\s*INSERT\s+IGNORE\s+/i', $sql)) {
                $sql = preg_replace('/^\s*INSERT\s+IGNORE\s+/i', 'INSERT OR IGNORE ', $sql);
            }
        }
    }

    public function query($sql, $params = []) {
        if (!$this->conn) return false;
        $this->normalizeSql($sql, $params);
        try {
            if (empty($params)) {
                return $this->conn->query($sql);
            }
            $stmt = $this->conn->prepare($sql);
            if (!$stmt) return false;
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('[DB Query Error] ' . $e->getMessage() . '  SQL: ' . $sql);
            return false;
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->fetchAll() : [];
    }

    public function fetchOne($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) {
            $row = $stmt->fetch();
            return $row ?: null;
        }
        return null;
    }

    public function insert($table, $data) {
        if (!$this->conn) return false;
        if (empty($data)) return false;
        $fields = array_keys($data);
        $placeholders = array_map(function($f) { return ':jayins_' . $f; }, $fields);
        $QT = $this->engine == 'mysql' ? '`' : '"';
        $sql = "INSERT INTO {$QT}{$table}{$QT} ({$QT}" . implode("{$QT},{$QT}", $fields) . "{$QT}) VALUES (" . implode(',', $placeholders) . ")";
        $params = [];
        foreach ($data as $k => $v) { $params[':jayins_' . $k] = $v; }
        $stmt = $this->query($sql, $params);
        return $stmt ? $this->conn->lastInsertId() : false;
    }

    public function update($table, $data, $where, $whereParams = []) {
        if (!$this->conn || empty($data)) return false;
        $QT = $this->engine == 'mysql' ? '`' : '"';
        $set = [];
        $params = [];
        foreach (array_keys($data) as $f) {
            $set[] = "{$QT}{$f}{$QT} = :upd_{$f}";
        }
        foreach ($data as $k => $v) { $params[':upd_' . $k] = $v; }
        // 不允许混合位置参数（?）与命名参数。把 WHERE 中的 ? 替换成 :_w_0, :_w_1...
        $hasPositional = false;
        foreach (array_keys($whereParams) as $k) { if (is_int($k)) { $hasPositional = true; break; } }
        $whereOut = $where;
        if ($hasPositional) {
            $offset = 0; $i = 0;
            $whereOut = preg_replace_callback('/\?/', function($m) use (&$i) {
                return ':_w_' . ($i++);
            }, $where);
            $i = 0;
            foreach ($whereParams as $k => $v) {
                if (is_int($k)) {
                    $params[':_w_' . $i] = $v;
                    $i++;
                } else {
                    $params[$k] = $v;
                }
            }
        } else {
            foreach ($whereParams as $k => $v) {
                $params[$k] = $v;
            }
        }
        $sql = "UPDATE {$QT}{$table}{$QT} SET " . implode(', ', $set) . " WHERE $whereOut";
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }

    public function delete($table, $where, $params = []) {
        if (!$this->conn) return false;
        $QT = $this->engine == 'mysql' ? '`' : '"';
        $sql = "DELETE FROM {$QT}{$table}{$QT} WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
}
?>
