<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Branch {
    private $db;
    private $tenantId;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->tenantId = $_ENV['TENANT_ID'] ?? 1;
    }

    /**
     * Get all branches with pagination and filters
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT 
                    id, name, address, city, state, postal_code, 
                    phone, email, manager_name, status, created_at
                FROM branches 
                WHERE tenant_id = ?";
        
        $params = [$this->tenantId];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['city'])) {
            $sql .= " AND city = ?";
            $params[] = $filters['city'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR address LIKE ? OR manager_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " ORDER BY name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get total count of branches with filters
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM branches WHERE tenant_id = ?";
        $params = [$this->tenantId];
        
        // Apply filters
        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }
        
        if (!empty($filters['city'])) {
            $sql .= " AND city = ?";
            $params[] = $filters['city'];
        }
        
        if (!empty($filters['search'])) {
            $sql .= " AND (name LIKE ? OR address LIKE ? OR manager_name LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Get branch by ID
     */
    public function getById($id) {
        $sql = "SELECT id, name, address, city, state, postal_code, 
                       phone, email, manager_name, status, created_at
                FROM branches 
                WHERE id = ? AND tenant_id = ?";
        
        return $this->db->fetch($sql, [$id, $this->tenantId]);
    }

    /**
     * Create new branch
     */
    public function create($data) {
        $sql = "INSERT INTO branches (
                    tenant_id, name, address, city, state, postal_code,
                    phone, email, manager_name, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $this->tenantId,
            $data['name'],
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['postal_code'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['manager_name'] ?? null,
            $data['status'] ?? 'active'
        ];
        
        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Update branch
     */
    public function update($id, $data) {
        $sql = "UPDATE branches SET 
                    name = ?, address = ?, city = ?, state = ?, 
                    postal_code = ?, phone = ?, email = ?, 
                    manager_name = ?, status = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?";
        
        $params = [
            $data['name'],
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['postal_code'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['manager_name'] ?? null,
            $data['status'] ?? 'active',
            $id,
            $this->tenantId
        ];
        
        return $this->db->execute($sql, $params);
    }

    /**
     * Delete branch
     */
    public function delete($id) {
        // Check if branch is used by inventory or sales
        $checkSql = "SELECT 
                        (SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND tenant_id = ?) as inventory_count,
                        (SELECT COUNT(*) FROM sales WHERE branch_id = ? AND tenant_id = ?) as sales_count";
        $result = $this->db->fetch($checkSql, [$id, $this->tenantId, $id, $this->tenantId]);
        
        if ($result['inventory_count'] > 0 || $result['sales_count'] > 0) {
            throw new \Exception('Cannot delete branch: it has associated inventory or sales');
        }
        
        $sql = "DELETE FROM branches WHERE id = ? AND tenant_id = ?";
        return $this->db->execute($sql, [$id, $this->tenantId]);
    }

    /**
     * Get active branches for dropdowns
     */
    public function getActive() {
        $sql = "SELECT id, name, city FROM branches 
                WHERE tenant_id = ? AND status = 'active' 
                ORDER BY name ASC";
        
        return $this->db->fetchAll($sql, [$this->tenantId]);
    }

    /**
     * Get branch statistics
     */
    public function getStats($branchId) {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND tenant_id = ?) as inventory_count,
                    (SELECT COUNT(*) FROM sales WHERE branch_id = ? AND tenant_id = ?) as sales_count,
                    (SELECT SUM(total_amount) FROM sales WHERE branch_id = ? AND tenant_id = ? AND DATE(created_at) = CURDATE()) as today_sales";
        
        return $this->db->fetch($sql, [
            $branchId, $this->tenantId,
            $branchId, $this->tenantId,
            $branchId, $this->tenantId
        ]);
    }
}