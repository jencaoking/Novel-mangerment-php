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
        $row = $stmt->fetch();
        return $row ? $this->hideFields($row) : false;
    }

    public function findBy($column, $value) {
        if (!$this->validateColumn($column)) {
            return false;
        }
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->table} WHERE {$column} = ?");
        $stmt->execute([$value]);
        $row = $stmt->fetch();
        return $row ? $this->hideFields($row) : false;
    }

    public function findAll($conditions = [], $orderBy = null, $offset = 0, $limit = null) {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $conditionParts = [];
            foreach ($conditions as $column => $value) {
                if (!$this->validateColumn($column)) {
                    return [];
                }
                $conditionParts[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $conditionParts);
        }

        if ($orderBy) {
            if (!$this->validateOrderBy($orderBy)) {
                return [];
            }
            $sql .= " ORDER BY {$orderBy}";
        }

        if ($limit !== null) {
            $sql .= " LIMIT {$offset}, {$limit}";
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetchAll();
        return array_map([$this, 'hideFields'], $result);
    }

    public function count($conditions = []) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $params = [];

        if (!empty($conditions)) {
            $conditionParts = [];
            foreach ($conditions as $column => $value) {
                if (!$this->validateColumn($column)) {
                    return 0;
                }
                $conditionParts[] = "{$column} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(" AND ", $conditionParts);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ? (int)$result['total'] : 0;
    }

    public function create(array $data) {
        $data = $this->filterFillable($data);
        
        // ✅ 新增：如果过滤后无数据，直接返回 false，避免生成错误 SQL
        if (empty($data)) {
            return false; 
        }

        // 对所有列名进行注入校验
        foreach (array_keys($data) as $column) {
            if (!$this->validateColumn($column)) {
                return false;
            }
        }

        $columns = implode(", ", array_keys($data));
        $placeholders = ":" . implode(", :", array_keys($data));
        
        // 统一具名占位符格式，确保键名带冒号
        $namedData = [];
        foreach ($data as $k => $v) {
            $namedData[':' . $k] = $v;
        }
        
        $sql = "INSERT INTO {$this->table} ({$columns}) VALUES ({$placeholders})";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($namedData);
        
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

        // 对所有列名进行注入校验
        foreach (array_keys($data) as $column) {
            if (!$this->validateColumn($column)) {
                return false;
            }
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
        foreach ($params as $key => $value) {
            $type = is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR;
            $stmt->bindValue(is_int($key) ? $key + 1 : $key, $value, $type);
        }
        $stmt->execute();
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

    protected function validateColumn($column) {
        if (!is_string($column) || empty($column)) {
            return false;
        }
        return preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*$/', $column) === 1;
    }

    protected function validateOrderBy($orderBy) {
        if (!is_string($orderBy) || empty($orderBy)) {
            return false;
        }
        $parts = explode(',', $orderBy);
        foreach ($parts as $part) {
            $part = trim($part);
            // 使用正则从末尾精确剥离方向词，避免误替换列名中的子串
            $part = trim(preg_replace('/\s+(ASC|DESC)$/i', '', $part));
            if (!preg_match('/^[a-zA-Z_][a-zA-Z0-9_]*(\.[a-zA-Z_][a-zA-Z0-9_]*)?$/', $part)) {
                return false;
            }
        }
        return true;
    }
}