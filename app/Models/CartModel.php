<?php
namespace App\Models;

class CartModel extends BaseModel {
    protected $table = 'cart';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'product_id'];

    public function addToCart($userId, $productId) {
        if ($this->isInCart($userId, $productId)) {
            return false;
        }
        $data = [
            'user_id' => $userId,
            'product_id' => $productId
        ];
        try {
            return $this->create($data);
        } catch (\PDOException $e) {
            return false;
        }
    }

    public function removeFromCart($userId, $productId) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        return $this->execute($sql, [$userId, $productId]);
    }

    public function clearCart($userId) {
        $sql = "DELETE FROM {$this->table} WHERE user_id = ?";
        return $this->execute($sql, [$userId]);
    }

    public function getUserCart($userId) {
        $sql = "SELECT c.*, p.title, p.type, p.price, p.cover, p.status AS product_status
                FROM {$this->table} c 
                LEFT JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ? AND p.id IS NOT NULL AND p.status = 1
                ORDER BY c.create_time DESC";
        return $this->fetchAll($sql, [$userId]);
    }

    public function getUserCartCount($userId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = ?";
        $result = $this->fetch($sql, [$userId]);
        return $result ? (int)$result['total'] : 0;
    }

    public function isInCart($userId, $productId) {
        $sql = "SELECT id FROM {$this->table} WHERE user_id = ? AND product_id = ?";
        return $this->fetch($sql, [$userId, $productId]) !== false;
    }

    public function cleanOrphanedItems($userId) {
        $sql = "DELETE c FROM {$this->table} c 
                LEFT JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ? AND (p.id IS NULL OR p.status = 0)";
        return $this->execute($sql, [$userId]);
    }
}