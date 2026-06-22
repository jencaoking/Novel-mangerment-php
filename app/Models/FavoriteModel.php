<?php
namespace App\Models;

class FavoriteModel extends BaseModel {
    protected $table = 'favorites';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'product_id'];

    public function isFavorited($userId, $productId) {
        $sql = "SELECT id FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        return $this->fetch($sql, [$userId, $productId]) !== false;
    }

    public function toggle($userId, $productId) {
        if ($this->isFavorited($userId, $productId)) {
            $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND product_id = ?";
            $this->execute($sql, [$userId, $productId]);
            return false;
        } else {
            $this->create(['user_id' => $userId, 'product_id' => $productId]);
            return true;
        }
    }

    public function getUserFavorites($userId, $page = 1, $perPage = 12) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT f.*, p.title, p.type, p.author, p.cover, p.price, p.sales, p.rating_avg,
                       c.name AS category_name
                FROM {$this->table} f
                LEFT JOIN products p ON f.product_id = p.id
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE f.user_id = ? AND p.id IS NOT NULL
                ORDER BY f.create_time DESC
                LIMIT {$perPage} OFFSET {$offset}";
        return $this->fetchAll($sql, [$userId]);
    }

    public function getUserFavoritesCount($userId) {
        $sql = "SELECT COUNT(*) AS cnt FROM {$this->table} f
                LEFT JOIN products p ON f.product_id = p.id
                WHERE f.user_id = ? AND p.id IS NOT NULL";
        $result = $this->fetch($sql, [$userId]);
        return $result ? (int)$result['cnt'] : 0;
    }

    public function getProductFavoriteCount($productId) {
        $sql = "SELECT COUNT(*) AS cnt FROM {$this->table} WHERE product_id = ?";
        $result = $this->fetch($sql, [$productId]);
        return $result ? (int)$result['cnt'] : 0;
    }
}
