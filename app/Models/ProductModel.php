<?php
namespace App\Models;

class ProductModel extends BaseModel {
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = ['title', 'type', 'category_id', 'author', 'description', 'cover', 'file_path', 'preview_path', 'price', 'downloads', 'sales', 'status'];

    public function getProductsByType($type, $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.type = ? AND p.status = 1 
                ORDER BY p.create_time DESC 
                LIMIT ?, ?";
        return $this->fetchAll($sql, [$type, $offset, $perPage]);
    }

    public function getTotalByType($type) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE type = ? AND status = 1";
        $result = $this->fetch($sql, [$type]);
        return $result['total'];
    }

    public function getProductDetail($id) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = ?";
        return $this->fetch($sql, [$id]);
    }

    public function searchProducts($keyword, $type = null, $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.status = 1";
        $params = [];

        if ($type) {
            $sql .= " AND p.type = ?";
            $params[] = $type;
        }

        $sql .= " AND (p.title LIKE ? OR p.author LIKE ? OR p.description LIKE ?)";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";

        $sql .= " ORDER BY p.create_time DESC LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $perPage;

        return $this->fetchAll($sql, $params);
    }

    public function searchCount($keyword, $type = null) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE status = 1";
        $params = [];

        if ($type) {
            $sql .= " AND type = ?";
            $params[] = $type;
        }

        $sql .= " AND (title LIKE ? OR author LIKE ? OR description LIKE ?)";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";

        $result = $this->fetch($sql, $params);
        return $result['total'];
    }

    public function incrementDownloads($id) {
        $sql = "UPDATE {$this->table} SET downloads = downloads + 1 WHERE id = ?";
        return $this->execute($sql, [$id]);
    }

    public function incrementSales($id) {
        $sql = "UPDATE {$this->table} SET sales = sales + 1 WHERE id = ?";
        return $this->execute($sql, [$id]);
    }

    public function getAllProducts($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.create_time DESC 
                LIMIT ?, ?";
        return $this->fetchAll($sql, [$offset, $perPage]);
    }

    public function getTotalProducts() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->fetch($sql);
        return $result['total'];
    }

    public function getTopProducts($limit = 10) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                ORDER BY p.sales DESC 
                LIMIT ?";
        return $this->fetchAll($sql, [$limit]);
    }

    public function searchAdminProducts($type = '', $search = '', $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE 1=1";
        $params = [];

        if ($type) {
            $sql .= " AND p.type = ?";
            $params[] = $type;
        }

        if ($search) {
            $sql .= " AND (p.title LIKE ? OR p.author LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY p.create_time DESC LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $perPage;

        return $this->fetchAll($sql, $params);
    }

    public function searchAdminProductsCount($type = '', $search = '') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} p WHERE 1=1";
        $params = [];

        if ($type) {
            $sql .= " AND p.type = ?";
            $params[] = $type;
        }

        if ($search) {
            $sql .= " AND (p.title LIKE ? OR p.author LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $result = $this->fetch($sql, $params);
        return $result['total'];
    }

    public function getProductsWithFilter($type, $category = 0, $search = '', $sort = 'latest', $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT p.*, c.name as category_name 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.type = ? AND p.status = 1";
        $params = [$type];

        if ($category > 0) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            $sql .= " AND (p.title LIKE ? OR p.author LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $orderBy = match($sort) {
            'hot' => 'p.sales DESC, p.downloads DESC',
            'price_asc' => 'p.price ASC',
            'price_desc' => 'p.price DESC',
            default => 'p.create_time DESC'
        };

        $sql .= " ORDER BY {$orderBy} LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $perPage;

        return $this->fetchAll($sql, $params);
    }

    public function getProductsWithFilterCount($type, $category = 0, $search = '') {
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} p 
                WHERE p.type = ? AND p.status = 1";
        $params = [$type];

        if ($category > 0) {
            $sql .= " AND p.category_id = ?";
            $params[] = $category;
        }

        if (!empty($search)) {
            $search = str_replace(['%', '_'], ['\%', '\_'], $search);
            $sql .= " AND (p.title LIKE ? OR p.author LIKE ?)";
            $params[] = "%{$search}%";
            $params[] = "%{$search}%";
        }

        $result = $this->fetch($sql, $params);
        return $result['total'];
    }
}