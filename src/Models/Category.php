<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Category {
    private $db;
    private $tenantId;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->tenantId = $_ENV['TENANT_ID'] ?? 1;
    }

    /**
     * Get all categories with pagination and filters
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT 
                    id, name, description, status, created_at
                FROM categories 
                WHERE tenant_id = ?";
        
        $params = [$this->tenantId];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get total count of categories with filters
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM categories WHERE tenant_id = ?";
        $params = [$this->tenantId];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR description LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Get category by ID
     */
    public function getById($id) {
        $sql = "SELECT id, name, description, status, created_at 
                FROM categories 
                WHERE id = ? AND tenant_id = ?";
        
        return $this->db->fetch($sql, [$id, $this->tenantId]);
    }

    /**
     * Create new category
     */
    public function create($data) {
        $sql = "INSERT INTO categories (
                    tenant_id, name, description, status, created_at
                ) VALUES (?, ?, ?, ?, NOW())";
        
        $params = [
            $this->tenantId,
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'active'
        ];
        
        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Update category
     */
    public function update($id, $data) {
        $sql = "UPDATE categories SET 
                    name = ?, description = ?, status = ?, 
                    updated_at = NOW()
                WHERE id = ? AND tenant_id = ?";
        
        $params = [
            $data['name'],
            $data['description'] ?? null,
            $data['status'] ?? 'active',
            $id,
            $this->tenantId
        ];
        
        return $this->db->execute($sql, $params);
    }

    /**
     * Delete category
     */
    public function delete($id) {
        // Check if category is used by products
        $checkSql = "SELECT COUNT(*) as count FROM products WHERE category_id = ? AND tenant_id = ?";
        $result = $this->db->fetch($checkSql, [$id, $this->tenantId]);
        
        if ($result['count'] > 0) {
            throw new \Exception('Cannot delete category: it is used by products');
        }
        
        $sql = "DELETE FROM categories WHERE id = ? AND tenant_id = ?";
        return $this->db->execute($sql, [$id, $this->tenantId]);
    }

    /**
     * Get active categories for dropdowns
     */
    public function getActive() {
        $sql = "SELECT id, name FROM categories 
                WHERE tenant_id = ? AND status = 'active' 
                ORDER BY name ASC";
        
        return $this->db->fetchAll($sql, [$this->tenantId]);
    }
}