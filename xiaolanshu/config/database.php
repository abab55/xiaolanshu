<?php
class Database {
    private static $instance = null;
    private $db;

    private function __construct() {
        $dbPath = __DIR__ . '/../data/xiaolanshu.db';
        $dir = dirname($dbPath);
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $this->db = new SQLite3($dbPath);
        $this->db->enableExceptions(true);
        $this->db->exec('PRAGMA journal_mode=WAL');
        $this->db->exec('PRAGMA foreign_keys=ON');
    }

    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function getDb() {
        return $this->db;
    }

    public function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $type = is_int($value) ? SQLITE3_INTEGER : (is_float($value) ? SQLITE3_FLOAT : (is_null($value) ? SQLITE3_NULL : SQLITE3_TEXT));
            $stmt->bindValue($key, $value, $type);
        }
        return $stmt->execute();
    }

    public function fetch($sql, $params = []) {
        $result = $this->query($sql, $params);
        return $result ? $result->fetchArray(SQLITE3_ASSOC) : false;
    }

    public function fetchAll($sql, $params = []) {
        $result = $this->query($sql, $params);
        $rows = [];
        while ($row = $result->fetchArray(SQLITE3_ASSOC)) {
            $rows[] = $row;
        }
        return $rows;
    }

    public function insert($sql, $params = []) {
        $this->query($sql, $params);
        return $this->db->lastInsertRowID();
    }

    public function exec($sql) {
        return $this->db->exec($sql);
    }

    public function lastInsertId() {
        return $this->db->lastInsertRowID();
    }
}
