<?php
/**
 * BookMusic Mall - 数据库连接文件
 * 使用PDO进行数据库连接
 */

require_once __DIR__ . '/config.php';

class Database {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET
            ];
            
            $this->pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            error_log("数据库连接失败: " . $e->getMessage());
            die("数据库连接失败，请稍后再试。");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    // 防止克隆
    private function __clone() {}
    
    public function __wakeup() {
        throw new \Exception("Cannot unserialize singleton");
    }
    
    public function __serialize(): array {
        throw new \Exception("Cannot serialize singleton");
    }
    
    public function __unserialize(array $data): void {
        throw new \Exception("Cannot unserialize singleton");
    }
}

/**
 * 获取数据库连接
 * @return PDO
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
