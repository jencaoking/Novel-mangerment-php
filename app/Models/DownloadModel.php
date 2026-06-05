<?php
namespace App\Models;

class DownloadModel extends BaseModel {
    protected $table = 'downloads';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'product_id', 'order_id', 'ip', 'user_agent'];

    public function recordDownload($userId, $productId, $orderId) {
        $data = [
            'user_id' => $userId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? ''
        ];
        return $this->create($data);
    }

    public function getUserDownloads($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT d.*, p.title, p.type, p.cover 
                FROM {$this->table} d 
                LEFT JOIN products p ON d.product_id = p.id 
                WHERE d.user_id = ? 
                ORDER BY d.create_time DESC 
                LIMIT ?, ?";
        return $this->fetchAll($sql, [$userId, $offset, $perPage]);
    }

    public function getUserDownloadCount($userId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = ?";
        $result = $this->fetch($sql, [$userId]);
        return $result['total'];
    }

    public function getDownloadsByProduct($productId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE product_id = ?";
        $result = $this->fetch($sql, [$productId]);
        return $result['total'];
    }

    public function getDownloadStats() {
        $sql = "SELECT 
                    COUNT(*) as total_downloads,
                    COUNT(DISTINCT user_id) as unique_users
                FROM {$this->table}";
        return $this->fetch($sql);
    }

    public function getMonthlyDownloadStats($months = 6) {
        $sql = "SELECT 
                    DATE_FORMAT(create_time, '%Y-%m') as month,
                    COUNT(*) as download_count
                FROM {$this->table}
                WHERE create_time >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(create_time, '%Y-%m')
                ORDER BY month DESC";
        return $this->fetchAll($sql, [$months]);
    }
}