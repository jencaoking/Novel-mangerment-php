<?php
namespace App\Models;

class OrderModel extends BaseModel {
    protected $table = 'orders';
    protected $primaryKey = 'id';
    protected $fillable = ['order_no', 'user_id', 'product_id', 'price', 'status', 'pay_time', 'cancel_time', 'refund_time', 'refund_reason', 'payment_channel', 'trade_no'];

    public function createOrder($userId, $productId, $price) {
        $orderNo = date('YmdHis') . sprintf('%03d', microtime(true) * 1000 % 1000) . random_int(1000, 9999);
        $data = [
            'order_no' => $orderNo,
            'user_id' => $userId,
            'product_id' => $productId,
            'price' => $price,
            'status' => 'pending'
        ];
        return $this->create($data);
    }

    public function findByOrderNo($orderNo) {
        return $this->findBy('order_no', $orderNo);
    }

    public function getUserOrders($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT o.*, p.title, p.type, p.cover 
                FROM {$this->table} o 
                LEFT JOIN products p ON o.product_id = p.id 
                WHERE o.user_id = ? 
                ORDER BY o.create_time DESC 
                LIMIT ?, ?";
        return $this->fetchAll($sql, [$userId, $offset, $perPage]);
    }

    public function getUserOrderCount($userId) {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE user_id = ?";
        $result = $this->fetch($sql, [$userId]);
        return $result ? (int)$result['total'] : 0;
    }

    public function getUserPurchasedProducts($userId) {
        $sql = "SELECT p.*, o.order_no, o.pay_time 
                FROM products p 
                INNER JOIN {$this->table} o ON p.id = o.product_id 
                WHERE o.user_id = ? AND o.status = 'paid' 
                ORDER BY o.pay_time DESC";
        return $this->fetchAll($sql, [$userId]);
    }

    public function hasPurchased($userId, $productId) {
        $sql = "SELECT id FROM {$this->table} WHERE user_id = ? AND product_id = ? AND status = 'paid'";
        return $this->fetch($sql, [$userId, $productId]) !== false;
    }

    public function updateStatus($orderId, $status) {
        $data = ['status' => $status];
        if ($status === 'paid') {
            $data['pay_time'] = date('Y-m-d H:i:s');
        } elseif ($status === 'cancelled') {
            $data['cancel_time'] = date('Y-m-d H:i:s');
        } elseif ($status === 'completed') {
            $data['complete_time'] = date('Y-m-d H:i:s');
        }
        return $this->update($orderId, $data);
    }

    public function getAllOrders($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT o.*, u.username, p.title, p.type 
                FROM {$this->table} o 
                LEFT JOIN users u ON o.user_id = u.id 
                LEFT JOIN products p ON o.product_id = p.id 
                ORDER BY o.create_time DESC 
                LIMIT ?, ?";
        return $this->fetchAll($sql, [$offset, $perPage]);
    }

    public function getTotalOrders() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->fetch($sql);
        return $result ? (int)$result['total'] : 0;
    }

    public function getStats() {
        $sql = "SELECT 
                    COUNT(*) as total_orders,
                    SUM(CASE WHEN status = 'paid' THEN price ELSE 0 END) as total_revenue,
                    SUM(CASE WHEN status = 'paid' AND DATE(create_time) = CURDATE() THEN price ELSE 0 END) as today_revenue,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_count,
                    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) as paid_count
                FROM {$this->table}";
        return $this->fetch($sql);
    }

    public function getMonthlyStats($months = 6) {
        $sql = "SELECT 
                    DATE_FORMAT(create_time, '%Y-%m') as month,
                    COUNT(*) as order_count,
                    SUM(CASE WHEN status = 'paid' THEN price ELSE 0 END) as revenue
                FROM {$this->table}
                WHERE create_time >= DATE_SUB(NOW(), INTERVAL ? MONTH)
                GROUP BY DATE_FORMAT(create_time, '%Y-%m')
                ORDER BY month DESC";
        return $this->fetchAll($sql, [$months]);
    }

    public function getRecentOrders($limit = 10) {
        $sql = "SELECT o.*, p.title, u.username 
                FROM {$this->table} o 
                LEFT JOIN products p ON o.product_id = p.id 
                LEFT JOIN users u ON o.user_id = u.id 
                ORDER BY o.create_time DESC 
                LIMIT ?";
        return $this->fetchAll($sql, [$limit]);
    }

    public function getDailyOrderStats($days = 7) {
        $sql = "SELECT DATE(create_time) as date, COUNT(*) as count 
                FROM {$this->table} 
                WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL ? DAY) 
                GROUP BY DATE(create_time) 
                ORDER BY date";
        return $this->fetchAll($sql, [$days]);
    }

    public function searchAdminOrders($status = '', $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT o.*, p.title as product_title, u.username, u.email 
                FROM {$this->table} o 
                LEFT JOIN products p ON o.product_id = p.id 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY o.create_time DESC LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $perPage;

        return $this->fetchAll($sql, $params);
    }

    public function searchAdminOrdersCount($status = '') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} o WHERE 1=1";
        $params = [];

        if ($status) {
            $sql .= " AND o.status = ?";
            $params[] = $status;
        }

        $result = $this->fetch($sql, $params);
        return $result ? (int)$result['total'] : 0;
    }

    public function getUserRecentOrders($userId, $limit = 5) {
        $sql = "SELECT o.*, p.title, p.cover as cover_image, p.type 
                FROM {$this->table} o 
                JOIN products p ON o.product_id = p.id 
                WHERE o.user_id = ? 
                ORDER BY o.create_time DESC 
                LIMIT ?";
        return $this->fetchAll($sql, [$userId, $limit]);
    }

    /**
     * 更新订单为已支付状态
     * @param int $orderId 订单ID
     * @param string $tradeNo 支付宝交易号
     * @param string $payTime 支付时间
     * @return bool
     */
    public function updateOrderPaid($orderId, $tradeNo, $payTime) {
        $sql = "UPDATE {$this->table} SET status = 'paid', pay_time = ?, trade_no = ? WHERE id = ?";
        return $this->execute($sql, [$payTime, $tradeNo, $orderId]);
    }

    /**
     * 获取用户针对某商品的已支付订单
     * @param int $userId
     * @param int $productId
     * @return array|false
     */
    public function getPaidOrder($userId, $productId) {
        $sql = "SELECT id, order_no FROM {$this->table} 
                WHERE user_id = ? AND product_id = ? AND status = 'paid' 
                ORDER BY pay_time DESC LIMIT 1";
        return $this->fetch($sql, [$userId, $productId]);
    }
}