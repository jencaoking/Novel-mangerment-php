<?php
namespace App\Models;

class CategoryModel extends BaseModel {
    protected $table = 'categories';
    protected $primaryKey = 'id';
    protected $fillable = ['name', 'type', 'sort_order', 'status'];

    public function getCategoriesByType($type) {
        $sql = "SELECT * FROM {$this->table} WHERE type = ? AND status = 1 ORDER BY sort_order ASC";
        return $this->fetchAll($sql, [$type]);
    }

    public function getAllCategories() {
        $sql = "SELECT * FROM {$this->table} ORDER BY type ASC, sort_order ASC";
        return $this->fetchAll($sql);
    }

    public function getCategoryById($id) {
        return $this->find($id);
    }

    public function createCategory($name, $type, $sortOrder = 0) {
        $data = [
            'name' => $name,
            'type' => $type,
            'sort_order' => $sortOrder,
            'status' => 1
        ];
        return $this->create($data);
    }

    public function updateCategory($id, $name, $sortOrder) {
        $data = [
            'name' => $name,
            'sort_order' => $sortOrder
        ];
        return $this->update($id, $data);
    }

    public function toggleStatus($id) {
        $sql = "UPDATE {$this->table} SET status = CASE WHEN status = 1 THEN 0 ELSE 1 END WHERE id = ?";
        return $this->execute($sql, [$id]);
    }
}