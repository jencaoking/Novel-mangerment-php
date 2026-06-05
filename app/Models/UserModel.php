<?php
namespace App\Models;

class UserModel extends BaseModel {
    protected $table = 'users';
    protected $primaryKey = 'id';
    protected $fillable = ['username', 'email', 'password', 'avatar', 'role', 'status', 'last_login', 'remember_token', 'remember_token_expire'];
    protected $hidden = ['password', 'remember_token', 'remember_token_expire'];

    public function findByUsernameOrEmail($username) {
        $sql = "SELECT * FROM {$this->table} WHERE (username = ? OR email = ?) AND status = 1";
        return $this->fetch($sql, [$username, $username]);
    }

    public function findByUsername($username) {
        return $this->findBy('username', $username);
    }

    public function findByEmail($email) {
        return $this->findBy('email', $email);
    }

    public function updateLastLogin($userId) {
        $sql = "UPDATE {$this->table} SET last_login = NOW() WHERE id = ?";
        return $this->execute($sql, [$userId]);
    }

    public function updateRememberToken($userId, $token, $expire) {
        $sql = "UPDATE {$this->table} SET remember_token = ?, remember_token_expire = ? WHERE id = ?";
        return $this->execute($sql, [$token, $expire, $userId]);
    }

    public function clearRememberToken($userId) {
        $sql = "UPDATE {$this->table} SET remember_token = NULL, remember_token_expire = NULL WHERE id = ?";
        return $this->execute($sql, [$userId]);
    }

    public function findByRememberToken($token) {
        $sql = "SELECT * FROM {$this->table} WHERE remember_token = ? AND remember_token_expire > NOW() AND status = 1";
        return $this->fetch($sql, [$token]);
    }

    public function getUsers($page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT id, username, email, avatar, role, status, create_time FROM {$this->table} ORDER BY create_time DESC LIMIT ?, ?";
        return $this->fetchAll($sql, [$offset, $perPage]);
    }

    public function getTotalUsers() {
        $sql = "SELECT COUNT(*) as total FROM {$this->table}";
        $result = $this->fetch($sql);
        return $result['total'];
    }

    public function toggleStatus($userId) {
        $sql = "UPDATE {$this->table} SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = ?";
        return $this->execute($sql, [$userId]);
    }

    public function isUsernameExists($username) {
        $sql = "SELECT id FROM {$this->table} WHERE username = ?";
        return $this->fetch($sql, [$username]) !== false;
    }

    public function isEmailExists($email) {
        $sql = "SELECT id FROM {$this->table} WHERE email = ?";
        return $this->fetch($sql, [$email]) !== false;
    }

    public function getTotalSpent($userId) {
        $sql = "SELECT COALESCE(SUM(price), 0) as total FROM orders WHERE user_id = ? AND status = 'paid'";
        $result = $this->fetch($sql, [$userId]);
        return $result['total'];
    }

    public function searchAdminUsers($search = '', $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        $sql = "SELECT * FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $sql .= " ORDER BY create_time DESC LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $perPage;

        return $this->fetchAll($sql, $params);
    }

    public function searchAdminUsersCount($search = '') {
        $sql = "SELECT COUNT(*) as total FROM {$this->table} WHERE 1=1";
        $params = [];

        if ($search) {
            $sql .= " AND (username LIKE ? OR email LIKE ?)";
            $params[] = "%$search%";
            $params[] = "%$search%";
        }

        $result = $this->fetch($sql, $params);
        return $result['total'];
    }
}