<?php
namespace App\Models;

use PDO;

class BaseModel {
    protected $pdo;
    protected $table;
    protected $primaryKey = 'id';
    protected $fillable = [];
    protected $hidden = [];

    public function __construct() {
        $this->pdo = $this->getConnection();
    }

    protected function getConnection() {
        require_once __DIR__ . '/../../includes/db.php';
        return getDB();
    }

    public function find($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$this->primaryKey} = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function findBy($column, $value) {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        return $stmt->fetch();
    }

    public function findAll($conditions = [], $orderBy = null, $offset = 0, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $conditionParts = [];
            foreach ($conditions as $column => $value) {
                $conditionParts[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $conditionParts);
        }

        if ($orderBy) {
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $conditionParts = [];
            foreach ($conditions as $column => $value) {
                $conditionParts[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $conditionParts);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result['total'];
    }

    public function create(array $data) {
        $data = $this->filterFillable($data);
        
        // ✅ 新增：如果过滤后无数据，直接返回 false，避免生成错误 SQL
        if (empty($data)) {
            return false; 
        }

        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($data);
        
        return $this->pdo->lastInsertId();
    }

    public function update($id, array $data) {
        $data = $this->filterFillable($data);
        
        // ✅ 新增：剔除 $data 中可能包含的主键，防止更新主键或造成参数冲突
        if (isset($data[$this->primaryKey])) {
            unset($data[$this->primaryKey]);
        }
        
        // ✅ 新增：空数据校验
        if (empty($data)) {
            return false;
        }

        $updateParts = [];
        $params = [];
        foreach ($data as $column => $value) {
            $updateParts[] = "{$column} = :{$column}";
            // 规范化绑定参数的 key 带有冒号
            $params[":{$column}"] = $value;
        }
        
        // ✅ 修复：使用特殊占位符 :_pk_id 替代原来的 :id
        $sql = "UPDATE {$this->table} SET " . implode(", ", $updateParts) . " WHERE {$this->primaryKey} = :_pk_id";
        $params[':_pk_id'] = $id;
        
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute($params);
    }

    public function delete($id) {
        $stmt = $this->pdo->prepare("DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?");
        return $stmt->execute([$id]);
    }

    protected function filterFillable(array $data) {
        if (empty($this->fillable)) {
            return $data;
        }
        return array_intersect_key($data, array_flip($this->fillable));
    }

    protected function hideFields(array $data) {
        if (empty($this->hidden)) {
            return $data;
        }
        return array_diff_key($data, array_flip($this->hidden));
    }

    protected function query($sql, $params = []) {
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt;
    }

    protected function fetch($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetch();
    }

    protected function fetchAll($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->fetchAll();
    }

    protected function execute($sql, $params = []) {
        $stmt = $this->query($sql, $params);
        return $stmt->rowCount();
    }
}