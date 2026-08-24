<?php
require_once dirname(__FILE__) . '/../config.php';

class Database {
    private static $instance = null;
    private $conn;

    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            // 如果还没安装则跳转安装向导，或对API返回JSON
            $current = $_SERVER['REQUEST_URI'] ?? '';
            $isApi = strpos($current, '/api/') !== false ||
                     (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] === 'XMLHttpRequest') ||
                     (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false);
            if (strpos($current, '/install/') === false && PHP_SAPI !== 'cli') {
                if ($isApi) {
                    header('Content-Type: application/json; charset=utf-8');
                    echo json_encode(['success' => false, 'need_install' => true, 'message' => '请先运行安装向导', 'install_url' => '/install/index.php'], JSON_UNESCAPED_UNICODE);
                    exit;
                }
                if (strpos($current, '/admin/') === false) {
                    header('Location: /install/index.php');
                    exit;
                }
            }
            $this->conn = null;
        }
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getConnection() {
        return $this->conn;
    }

    // 便捷查询方法
    public function query($sql, $params = []) {
        if (!$this->conn) return false;
        try {
            $stmt = $this->conn->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log('DB Query Error: ' . $e->getMessage());
            return false;
        }
    }

    public function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        if ($stmt) return $stmt->fetchAll();
        return [];
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
        $fields = array_keys($data);
        $placeholders = array_map(function($f) { return ':' . $f; }, $fields);
        $sql = "INSERT INTO `$table` (`" . implode('`,`', $fields) . "`) VALUES (" . implode(',', $placeholders) . ")";
        $stmt = $this->query($sql, $data);
        return $stmt ? $this->conn->lastInsertId() : false;
    }

    public function update($table, $data, $where, $whereParams = []) {
        if (!$this->conn) return false;
        $set = [];
        foreach (array_keys($data) as $f) {
            $set[] = "`$f` = :set_$f";
        }
        $params = [];
        foreach ($data as $k => $v) {
            $params[':set_' . $k] = $v;
        }
        foreach ($whereParams as $k => $v) {
            $params[$k] = $v;
        }
        $sql = "UPDATE `$table` SET " . implode(', ', $set) . " WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }

    public function delete($table, $where, $params = []) {
        if (!$this->conn) return false;
        $sql = "DELETE FROM `$table` WHERE $where";
        $stmt = $this->query($sql, $params);
        return $stmt ? $stmt->rowCount() : false;
    }
}
?>
