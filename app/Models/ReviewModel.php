<?php
namespace App\Models;

class ReviewModel extends BaseModel {
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'product_id', 'order_id', 'rating', 'content', 'status'];

    /**
     * 添加评价
     */
    public function addReview($userId, $productId, $orderId, $rating, $content) {
        // 确保评分在 1-5 范围内
        $rating = max(1, min(5, $rating));
        $sql = "INSERT INTO {$this->table} (user_id, product_id, order_id, rating, content) VALUES (?, ?, ?, ?, ?)";
        return $this->execute($sql, [$userId, $productId, $orderId, $rating, $content]);
    }
    
    /**
     * 获取商品的评价列表（包含用户头像和用户名）
     */
    public function getReviewsByProduct($productId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT r.*, u.username, u.avatar 
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id 
                WHERE r.product_id = ? AND r.status = 1 
                ORDER BY r.create_time DESC 
                LIMIT ?, ?";
        return $this->fetchAll($sql, [$productId, $offset, $perPage]);
    }
    
    /**
     * 检查用户是否已经对该订单做过评价
     */
    public function hasReviewed($orderId) {
        $sql = "SELECT id FROM {$this->table} WHERE order_id = ?";
        return !empty($this->fetch($sql, [$orderId]));
    }

    /**
     * 获取商品评价总数
     */
    public function getProductReviewCount($productId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE product_id = ? AND status = 1";
        $result = $this->fetch($sql, [$productId]);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * 获取用户的评价列表
     */
    public function getUserReviews($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT r.*, p.title, p.cover 
                FROM {$this->table} r 
                LEFT JOIN products p ON r.product_id = p.id 
                WHERE r.user_id = ? 
                ORDER BY r.create_time DESC 
                LIMIT ?, ?";
        return $this->fetchAll($sql, [$userId, $offset, $perPage]);
    }

    /**
     * 计算商品的平均评分和评价数
     */
    public function calculateProductStats($productId) {
        $sql = "SELECT 
                    AVG(rating) as avg_rating,
                    COUNT(*) as review_count
                FROM {$this->table} 
                WHERE product_id = ? AND status = 1";
        $result = $this->fetch($sql, [$productId]);
        return [
            'avg_rating' => $result['avg_rating'] ? round($result['avg_rating'], 2) : 0,
            'review_count' => $result['review_count']
        ];
    }

    /**
     * 获取评分分布
     */
    public function getRatingDistribution($productId) {
        $sql = "SELECT 
                    rating,
                    COUNT(*) as count
                FROM {$this->table} 
                WHERE product_id = ? AND status = 1
                GROUP BY rating
                ORDER BY rating DESC";
        return $this->fetchAll($sql, [$productId]);
    }

    /**
     * 获取后台评价列表（包含用户名和商品名）
     */
    public function getAdminReviews($page = 1, $perPage = 20, $search = '') {
        $offset = ($page - 1) * $perPage;
        $params = [];
        
        $sql = "SELECT r.*, u.username, p.title as product_title 
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id 
                LEFT JOIN products p ON r.product_id = p.id 
                WHERE 1=1";
        
        if (!empty($search)) {
            // LIKE 查询特殊字符转义，防止通配符攻击
            $searchEscaped = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $sql .= " AND (u.username LIKE ? OR p.title LIKE ? OR r.content LIKE ?)";
            $params = array_fill(0, 3, $searchEscaped);
        }
        
        $sql .= " ORDER BY r.create_time DESC LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $perPage;
        
        return $this->fetchAll($sql, $params);
    }

    /**
     * 获取后台评价总数（用于分页）
     */
    public function getAdminReviewsCount($search = '') {
        $params = [];
        $sql = "SELECT COUNT(*) as total 
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id 
                LEFT JOIN products p ON r.product_id = p.id 
                WHERE 1=1";
                
        if (!empty($search)) {
            // LIKE 查询特殊字符转义，防止通配符攻击
            $searchEscaped = '%' . str_replace(['%', '_'], ['\%', '\_'], $search) . '%';
            $sql .= " AND (u.username LIKE ? OR p.title LIKE ? OR r.content LIKE ?)";
            $params = array_fill(0, 3, $searchEscaped);
        }
        
        $result = $this->fetch($sql, $params);
        return $result ? (int)$result['total'] : 0;
    }

    /**
     * 切换评价状态（1-正常，0-隐藏）
     */
    public function toggleStatus($id) {
        $sql = "UPDATE {$this->table} SET status = 1 - status WHERE id = ?";
        return $this->execute($sql, [$id]);
    }

    /**
     * 根据ID获取评价详情
     */
    public function find($id) {
        $sql = "SELECT r.*, u.username, p.title as product_title 
                FROM {$this->table} r 
                LEFT JOIN users u ON r.user_id = u.id 
                LEFT JOIN products p ON r.product_id = p.id 
                WHERE r.id = ?";
        return $this->fetch($sql, [$id]);
    }
}
