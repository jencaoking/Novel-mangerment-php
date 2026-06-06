<?php
namespace App\Models;

class DownloadModel extends BaseModel {
    protected $table = 'downloads';
    protected $primaryKey = 'id';
    protected $fillable = ['user_id', 'product_id', 'order_id', 'ip', 'user_agent'];

    /**
     * 记录下载日志
     */
    public function logDownload($userId, $productId, $orderId) {
        $data = [
            'user_id' => $userId,
            'product_id' => $productId,
            'order_id' => $orderId,
            'ip' => getClientIP(),
            'user_agent' => getUserAgent()
        ];
        return $this->create($data);
    }

    /**
     * 获取用户的下载记录
     */
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

    /**
     * 获取指定订单今日的下载次数
     * @param  int $orderId 订单ID
     * @return  int 今日下载次数
     */
    public function getDailyDownloadCountByOrder($orderId) {
        $today = date('Y-m-d');
        
        $sql = "SELECT COUNT(*) as count 
                FROM {$this->table}  
                WHERE order_id = ? 
                AND DATE(create_time) = ?";
                
        $result = $this->fetch($sql, [$orderId, $today]);
        
        return $result ? (int)$result['count'] : 0;
    }
}
