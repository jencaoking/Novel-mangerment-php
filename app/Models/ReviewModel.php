<?php
namespace App\Models;

class ReviewModel extends BaseModel {
    protected $table = 'reviews';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'product_id', 'order_id', 'rating', 'content', 'status'];

    /**
     * 获取商品的评价列表
     */
    public function getProductReviews($productId, $page = 1, $perPage = 10) {
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
     * 获取商品评价总数
     */
    public function getProductReviewCount($productId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE product_id = ? AND status = 1";
        $result = $this->fetch($sql, [$productId]);
        return $result['total'];
    }

    /**
     * 创建评价
     */
    public function createReview($userId, $productId, $orderId, $rating, $content) {
        $data = [
            'user_id' => $userId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'rating' => max(1, min(5, $rating)),
            'content' => $content,
            'status' => 1
        ];
        return $this->create($data);
    }

    /**
     * 检查订单是否已评价
     */
    public function hasReviewedOrder($orderId) {
        $sql = "SELECT id FROM {$this->table} WHERE order_id = ?";
        return $this->fetch($sql, [$orderId]) !== false;
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
}