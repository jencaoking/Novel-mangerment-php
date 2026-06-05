<?php
namespace App\Models;

class ProductModel extends BaseModel {
    protected $table = 'products';
    protected $primaryKey = 'id';
    protected $fillable = ['title', 'type', 'category_id', 'author', 'description', 'cover', 'file_path', 'preview_path', 'price', 'downloads', 'sales', 'status'];

    private function buildBaseQuery(string $selectFields = 'p.*, c.name as category_name'): string {
        return "SELECT {$selectFields} 
                FROM {$this->table} p 
                LEFT JOIN categories c ON p.category_id = c.id";
    }

    private function buildActiveCondition(): string {
        return "p.status = 1";
    }

    private function applyPagination(array &$params, int $page, int $perPage): string {
        $offset = ($page - 1) * $perPage;
        $params[] = $offset;
        $params[] = $perPage;
        return " LIMIT ?, ?";
    }

    private function buildCountQuery(string $tableAlias = null): string {
        $tableName = $tableAlias ? "{$this->table} {$tableAlias}" : $this->table;
        return "SELECT COUNT(*) as total FROM {$tableName}";
    }

    public function getProductsByType($type, $page = 1, $perPage = 12) {
        $sql = $this->buildBaseQuery() . " 
                WHERE p.type = ? AND " . $this->buildActiveCondition() . " 
                ORDER BY p.create_time DESC";
        $params = [$type];
        $sql .= $this->applyPagination($params, $page, $perPage);
        return $this->fetchAll($sql, $params);
    }

    public function getTotalByType($type) {
        $sql = $this->buildCountQuery() . " WHERE type = ? AND status = 1";
        $result = $this->fetch($sql, [$type]);
        return $result['total'];
    }

    public function getProductDetail($id) {
        $sql = $this->buildBaseQuery() . " WHERE p.id = ?";
        return $this->fetch($sql, [$id]);
    }

    public function searchProducts($keyword, $type = null, $page = 1, $perPage = 12) {
        $sql = $this->buildBaseQuery() . " WHERE " . $this->buildActiveCondition();
        $params = [];

        if ($type) {
            $sql .= " AND p.type = ?";
            $params[] = $type;
        }

        $sql .= " AND (p.title LIKE ? OR p.author LIKE ? OR p.description LIKE ?)";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";
        $params[] = "%{$keyword}%";

        $sql .= " ORDER BY p.create_time DESC";
        $sql .= $this->applyPagination($params, $page, $perPage);

        return $this->fetchAll($sql, $params);
    }

    public function searchCount($keyword, $type = null) {
        $sql = $this->buildCountQuery() . " WHERE status = 1";
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
        $sql = $this->buildBaseQuery() . " ORDER BY p.create_time DESC";
        $params = [];
        $sql .= $this->applyPagination($params, $page, $perPage);
        return $this->fetchAll($sql, $params);
    }

    public function getTotalProducts() {
        $sql = $this->buildCountQuery();
        $result = $this->fetch($sql);
        return $result['total'];
    }

    public function getTopProducts($limit = 10) {
        $sql = $this->buildBaseQuery() . " ORDER BY p.sales DESC LIMIT ?";
        return $this->fetchAll($sql, [$limit]);
    }

    public function searchAdminProducts($type = '', $search = '', $page = 1, $perPage = 20) {
        $sql = $this->buildBaseQuery() . " WHERE 1=1";
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

        $sql .= " ORDER BY p.create_time DESC";
        $sql .= $this->applyPagination($params, $page, $perPage);

        return $this->fetchAll($sql, $params);
    }

    public function searchAdminProductsCount($type = '', $search = '') {
        $sql = $this->buildCountQuery('p') . " WHERE 1=1";
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
        $sql = $this->buildBaseQuery() . " WHERE p.type = ? AND " . $this->buildActiveCondition();
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

        $sql .= " ORDER BY {$orderBy}";
        $sql .= $this->applyPagination($params, $page, $perPage);

        return $this->fetchAll($sql, $params);
    }

    public function getProductsWithFilterCount($type, $category = 0, $search = '') {
        $sql = $this->buildCountQuery('p') . " WHERE p.type = ? AND p.status = 1";
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