<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Supplier {
    private $db;
    private $tenantId;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->tenantId = $_ENV['TENANT_ID'] ?? 1;
    }

    /**
     * Get all suppliers with pagination and filters
     */
    public function getAll($filters = [], $page = 1, $limit = 20) {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT 
                    id, name, contact_person, email, phone, address, 
                    city, state, postal_code, country, tax_id, 
                    payment_terms, credit_limit, status, created_at
                FROM suppliers 
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
            $sql .= " AND (name LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
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
     * Get total count of suppliers with filters
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM suppliers WHERE tenant_id = ?";
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
            $sql .= " AND (name LIKE ? OR contact_person LIKE ? OR email LIKE ?)";
            $searchTerm = '%' . $filters['search'] . '%';
            $params[] = $searchTerm;
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Get supplier by ID
     */
    public function getById($id) {
        $sql = "SELECT id, name, contact_person, email, phone, address, 
                       city, state, postal_code, country, tax_id, 
                       payment_terms, credit_limit, status, created_at
                FROM suppliers 
                WHERE id = ? AND tenant_id = ?";
        
        return $this->db->fetch($sql, [$id, $this->tenantId]);
    }

    /**
     * Create new supplier
     */
    public function create($data) {
        $sql = "INSERT INTO suppliers (
                    tenant_id, name, contact_person, email, phone, address,
                    city, state, postal_code, country, tax_id,
                    payment_terms, credit_limit, status, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $this->tenantId,
            $data['name'],
            $data['contact_person'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
            $data['tax_id'] ?? null,
            $data['payment_terms'] ?? null,
            $data['credit_limit'] ?? null,
            $data['status'] ?? 'active'
        ];
        
        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Update supplier
     */
    public function update($id, $data) {
        $sql = "UPDATE suppliers SET 
                    name = ?, contact_person = ?, email = ?, phone = ?, 
                    address = ?, city = ?, state = ?, postal_code = ?, 
                    country = ?, tax_id = ?, payment_terms = ?, 
                    credit_limit = ?, status = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?";
        
        $params = [
            $data['name'],
            $data['contact_person'] ?? null,
            $data['email'] ?? null,
            $data['phone'] ?? null,
            $data['address'] ?? null,
            $data['city'] ?? null,
            $data['state'] ?? null,
            $data['postal_code'] ?? null,
            $data['country'] ?? null,
            $data['tax_id'] ?? null,
            $data['payment_terms'] ?? null,
            $data['credit_limit'] ?? null,
            $data['status'] ?? 'active',
            $id,
            $this->tenantId
        ];
        
        return $this->db->execute($sql, $params);
    }

    /**
     * Delete supplier
     */
    public function delete($id) {
        // Check if supplier is used by inventory or purchases
        $checkSql = "SELECT 
                        (SELECT COUNT(*) FROM inventory WHERE supplier_id = ? AND tenant_id = ?) as inventory_count,
                        (SELECT COUNT(*) FROM purchases WHERE supplier_id = ? AND tenant_id = ?) as purchase_count";
        $result = $this->db->fetch($checkSql, [$id, $this->tenantId, $id, $this->tenantId]);
        
        if ($result['inventory_count'] > 0 || $result['purchase_count'] > 0) {
            throw new \Exception('Cannot delete supplier: it has associated inventory or purchases');
        }
        
        $sql = "DELETE FROM suppliers WHERE id = ? AND tenant_id = ?";
        return $this->db->execute($sql, [$id, $this->tenantId]);
    }

    /**
     * Get active suppliers for dropdowns
     */
    public function getActive() {
        $sql = "SELECT id, name, city FROM suppliers 
                WHERE tenant_id = ? AND status = 'active' 
                ORDER BY name ASC";
        
        return $this->db->fetchAll($sql, [$this->tenantId]);
    }

    /**
     * Get supplier statistics
     */
    public function getStats($supplierId) {
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM inventory WHERE supplier_id = ? AND tenant_id = ?) as inventory_count,
                    (SELECT COUNT(*) FROM purchases WHERE supplier_id = ? AND tenant_id = ?) as purchase_count,
                    (SELECT SUM(total_amount) FROM purchases WHERE supplier_id = ? AND tenant_id = ? AND DATE(created_at) = CURDATE()) as today_purchases";
        
        return $this->db->fetch($sql, [
            $supplierId, $this->tenantId,
            $supplierId, $this->tenantId,
            $supplierId, $this->tenantId
        ]);
    }

    /**
     * Get supplier performance metrics
     */
    public function getPerformance($supplierId, $period = 'month') {
        $dateFilter = '';
        switch ($period) {
            case 'week':
                $dateFilter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateFilter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'quarter':
                $dateFilter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                break;
            case 'year':
                $dateFilter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }

        $sql = "SELECT 
                    COUNT(*) as total_purchases,
                    COALESCE(SUM(p.total_amount), 0) as total_spent,
                    COALESCE(AVG(p.total_amount), 0) as avg_purchase,
                    COUNT(DISTINCT p.id) as unique_orders
                FROM purchases p
                WHERE p.tenant_id = ? AND p.supplier_id = ? $dateFilter";
        
        return $this->db->fetch($sql, [$this->tenantId, $supplierId]);
    }
}