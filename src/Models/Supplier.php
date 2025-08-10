<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Supplier
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getTenantId();
    }

    /**
     * Create a new supplier
     */
    public function create($data)
    {
        $supplierData = [
            'tenant_id' => $this->tenantId,
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_ps' => $data['name_ps'] ?? null,
            'name_dr' => $data['name_dr'] ?? null,
            'contact_person' => $data['contact_person'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'tax_number' => $data['tax_number'] ?? null,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'payment_terms' => $data['payment_terms'] ?? '30 days',
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('suppliers', $supplierData);
    }

    /**
     * Update supplier information
     */
    public function update($supplierId, $data)
    {
        $updateData = array_intersect_key($data, array_flip([
            'name', 'name_ar', 'name_ps', 'name_dr', 'contact_person', 'phone', 'email',
            'address', 'city', 'country', 'tax_number', 'credit_limit', 'payment_terms',
            'status', 'notes'
        ]));

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('suppliers', $updateData, 'id = ? AND tenant_id = ?', [$supplierId, $this->tenantId]);
    }

    /**
     * Get supplier by ID
     */
    public function getById($supplierId)
    {
        $sql = "SELECT * FROM suppliers WHERE id = ? AND tenant_id = ?";
        return $this->db->fetch($sql, [$supplierId, $this->tenantId]);
    }

    /**
     * Get supplier by name or contact
     */
    public function getByContact($contact, $type = 'phone')
    {
        $sql = "SELECT * FROM suppliers WHERE $type = ? AND tenant_id = ?";
        return $this->db->fetch($sql, [$contact, $this->tenantId]);
    }

    /**
     * Get all suppliers with filters and pagination
     */
    public function getAll($filters = [], $page = 1, $limit = 20)
    {
        $sql = "SELECT * FROM suppliers WHERE tenant_id = ?";
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['name'])) {
            $sql .= " AND (name LIKE ? OR name_ar LIKE ? OR name_ps LIKE ? OR name_dr LIKE ?)";
            $searchTerm = '%' . $filters['name'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['phone'])) {
            $sql .= " AND phone LIKE ?";
            $params[] = '%' . $filters['phone'] . '%';
        }

        if (!empty($filters['email'])) {
            $sql .= " AND email LIKE ?";
            $params[] = '%' . $filters['email'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['city'])) {
            $sql .= " AND city = ?";
            $params[] = $filters['city'];
        }

        $sql .= " ORDER BY name ASC";
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get supplier count for pagination
     */
    public function getCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM suppliers WHERE tenant_id = ?";
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['name'])) {
            $sql .= " AND (name LIKE ? OR name_ar LIKE ? OR name_ps LIKE ? OR name_dr LIKE ?)";
            $searchTerm = '%' . $filters['name'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['phone'])) {
            $sql .= " AND phone LIKE ?";
            $params[] = '%' . $filters['phone'] . '%';
        }

        if (!empty($filters['email'])) {
            $sql .= " AND email LIKE ?";
            $params[] = '%' . $filters['email'] . '%';
        }

        if (!empty($filters['status'])) {
            $sql .= " AND status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['city'])) {
            $sql .= " AND city = ?";
            $params[] = $filters['city'];
        }

        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Get supplier purchase history
     */
    public function getPurchaseHistory($supplierId, $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, u.username as created_by_name
                FROM purchases p 
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ? AND p.supplier_id = ?
                ORDER BY p.created_at DESC";
        
        $params = [$this->tenantId, $supplierId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get supplier purchase history count
     */
    public function getPurchaseHistoryCount($supplierId)
    {
        $sql = "SELECT COUNT(*) as total FROM purchases WHERE tenant_id = ? AND supplier_id = ?";
        $result = $this->db->fetch($sql, [$this->tenantId, $supplierId]);
        return $result['total'] ?? 0;
    }

    /**
     * Get supplier credit summary
     */
    public function getCreditSummary($supplierId)
    {
        $sql = "SELECT 
                    credit_limit,
                    (SELECT COALESCE(SUM(total_amount - paid_amount), 0) 
                     FROM purchases 
                     WHERE supplier_id = s.id AND tenant_id = s.tenant_id AND status = 'received') as outstanding_amount,
                    (credit_limit - (SELECT COALESCE(SUM(total_amount - paid_amount), 0) 
                                    FROM purchases 
                                    WHERE supplier_id = s.id AND tenant_id = s.tenant_id AND status = 'received')) as available_credit,
                    CASE 
                        WHEN (SELECT COALESCE(SUM(total_amount - paid_amount), 0) 
                              FROM purchases 
                              WHERE supplier_id = s.id AND tenant_id = s.tenant_id AND status = 'received') > credit_limit THEN 'over_limit'
                        WHEN (SELECT COALESCE(SUM(total_amount - paid_amount), 0) 
                              FROM purchases 
                              WHERE supplier_id = s.id AND tenant_id = s.tenant_id AND status = 'received') > 0 THEN 'has_balance'
                        ELSE 'no_balance'
                    END as credit_status
                FROM suppliers s
                WHERE s.id = ? AND s.tenant_id = ?";
        
        return $this->db->fetch($sql, [$supplierId, $this->tenantId]);
    }

    /**
     * Get supplier statistics
     */
    public function getSupplierStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_suppliers,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_suppliers,
                    COUNT(CASE WHEN status = 'inactive' THEN 1 END) as inactive_suppliers,
                    AVG(credit_limit) as avg_credit_limit,
                    SUM(credit_limit) as total_credit_limit
                FROM suppliers 
                WHERE tenant_id = ?";
        
        return $this->db->fetch($sql, [$this->tenantId]);
    }

    /**
     * Search suppliers for autocomplete
     */
    public function searchSuppliers($query, $limit = 10)
    {
        $sql = "SELECT id, name, name_ar, name_ps, name_dr, phone, email, contact_person
                FROM suppliers 
                WHERE tenant_id = ? AND (
                    name LIKE ? OR 
                    name_ar LIKE ? OR 
                    name_ps LIKE ? OR 
                    name_dr LIKE ? OR 
                    phone LIKE ? OR 
                    email LIKE ? OR
                    contact_person LIKE ?
                )
                ORDER BY name ASC
                LIMIT ?";
        
        $searchTerm = '%' . $query . '%';
        $params = [$this->tenantId, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get suppliers with outstanding payments
     */
    public function getSuppliersWithOutstandingPayments($page = 1, $limit = 20)
    {
        $sql = "SELECT s.id, s.name, s.name_ar, s.name_ps, s.name_dr, s.phone, s.email,
                       s.credit_limit,
                       COALESCE(SUM(p.total_amount - p.paid_amount), 0) as outstanding_amount,
                       (s.credit_limit - COALESCE(SUM(p.total_amount - p.paid_amount), 0)) as available_credit
                FROM suppliers s
                LEFT JOIN purchases p ON s.id = p.supplier_id AND p.tenant_id = s.tenant_id AND p.status = 'received'
                WHERE s.tenant_id = ? AND s.status = 'active'
                GROUP BY s.id
                HAVING outstanding_amount > 0
                ORDER BY outstanding_amount DESC";
        
        $params = [$this->tenantId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get suppliers with outstanding payments count
     */
    public function getSuppliersWithOutstandingPaymentsCount()
    {
        $sql = "SELECT COUNT(DISTINCT s.id) as total
                FROM suppliers s
                LEFT JOIN purchases p ON s.id = p.supplier_id AND p.tenant_id = s.tenant_id AND p.status = 'received'
                WHERE s.tenant_id = ? AND s.status = 'active'
                GROUP BY s.id
                HAVING COALESCE(SUM(p.total_amount - p.paid_amount), 0) > 0";
        
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['total'] ?? 0;
    }

    /**
     * Get top suppliers by purchase volume
     */
    public function getTopSuppliersByVolume($limit = 10, $period = 'month')
    {
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

        $sql = "SELECT s.id, s.name, s.name_ar, s.name_ps, s.name_dr, s.phone, s.email,
                       COUNT(p.id) as total_purchases,
                       COALESCE(SUM(p.total_amount), 0) as total_amount
                FROM suppliers s
                LEFT JOIN purchases p ON s.id = p.supplier_id AND p.tenant_id = s.tenant_id AND p.status = 'received' $dateFilter
                WHERE s.tenant_id = ? AND s.status = 'active'
                GROUP BY s.id
                ORDER BY total_amount DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$this->tenantId, $limit]);
    }

    /**
     * Delete supplier (soft delete by setting status to inactive)
     */
    public function delete($supplierId)
    {
        // Check if supplier has any active purchases
        $sql = "SELECT COUNT(*) FROM purchases WHERE supplier_id = ? AND tenant_id = ? AND status IN ('pending', 'ordered', 'received')";
        $result = $this->db->fetch($sql, [$supplierId, $this->tenantId]);
        
        if ($result['count'] > 0) {
            throw new \Exception('Cannot delete supplier with active purchases');
        }

        return $this->db->update('suppliers', ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')], 'id = ? AND tenant_id = ?', [$supplierId, $this->tenantId]);
    }
}