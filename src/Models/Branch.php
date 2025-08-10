<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Branch
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getTenantId();
    }

    /**
     * Create a new branch
     */
    public function create($data)
    {
        $branchData = [
            'tenant_id' => $this->tenantId,
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_ps' => $data['name_ps'] ?? null,
            'name_dr' => $data['name_dr'] ?? null,
            'code' => $data['code'] ?? $this->generateBranchCode(),
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'state' => $data['state'] ?? null,
            'country' => $data['country'] ?? null,
            'postal_code' => $data['postal_code'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
            'opening_hours' => $data['opening_hours'] ?? null,
            'timezone' => $data['timezone'] ?? 'UTC',
            'currency' => $data['currency'] ?? 'AFN',
            'tax_rate' => $data['tax_rate'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'notes' => $data['notes'] ?? null,
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('branches', $branchData);
    }

    /**
     * Update branch information
     */
    public function update($branchId, $data)
    {
        $updateData = array_intersect_key($data, array_flip([
            'name', 'name_ar', 'name_ps', 'name_dr', 'code', 'address', 'city', 'state',
            'country', 'postal_code', 'phone', 'email', 'manager_id', 'opening_hours',
            'timezone', 'currency', 'tax_rate', 'status', 'notes'
        ]));

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('branches', $updateData, 'id = ? AND tenant_id = ?', [$branchId, $this->tenantId]);
    }

    /**
     * Get branch by ID
     */
    public function getById($branchId)
    {
        $sql = "SELECT b.*, u.username as manager_name, u.email as manager_email
                FROM branches b
                LEFT JOIN users u ON b.manager_id = u.id
                WHERE b.id = ? AND b.tenant_id = ?";
        
        return $this->db->fetch($sql, [$branchId, $this->tenantId]);
    }

    /**
     * Get branch by code
     */
    public function getByCode($code)
    {
        $sql = "SELECT b.*, u.username as manager_name, u.email as manager_email
                FROM branches b
                LEFT JOIN users u ON b.manager_id = u.id
                WHERE b.code = ? AND b.tenant_id = ?";
        
        return $this->db->fetch($sql, [$code, $this->tenantId]);
    }

    /**
     * Get all branches with filters and pagination
     */
    public function getAll($filters = [], $page = 1, $limit = 20)
    {
        $sql = "SELECT b.*, u.username as manager_name, u.email as manager_email
                FROM branches b
                LEFT JOIN users u ON b.manager_id = u.id
                WHERE b.tenant_id = ?";
        
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['name'])) {
            $sql .= " AND (b.name LIKE ? OR b.name_ar LIKE ? OR b.name_ps LIKE ? OR b.name_dr LIKE ?)";
            $searchTerm = '%' . $filters['name'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['code'])) {
            $sql .= " AND b.code LIKE ?";
            $params[] = '%' . $filters['code'] . '%';
        }

        if (!empty($filters['city'])) {
            $sql .= " AND b.city = ?";
            $params[] = $filters['city'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['manager_id'])) {
            $sql .= " AND b.manager_id = ?";
            $params[] = $filters['manager_id'];
        }

        $sql .= " ORDER BY b.name ASC";
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get branch count for pagination
     */
    public function getCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM branches b WHERE b.tenant_id = ?";
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['name'])) {
            $sql .= " AND (b.name LIKE ? OR b.name_ar LIKE ? OR b.name_ps LIKE ? OR b.name_dr LIKE ?)";
            $searchTerm = '%' . $filters['name'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        if (!empty($filters['code'])) {
            $sql .= " AND b.code LIKE ?";
            $params[] = '%' . $filters['code'] . '%';
        }

        if (!empty($filters['city'])) {
            $sql .= " AND b.city = ?";
            $params[] = $filters['city'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND b.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['manager_id'])) {
            $sql .= " AND b.manager_id = ?";
            $params[] = $filters['manager_id'];
        }

        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Get active branches
     */
    public function getActiveBranches()
    {
        $sql = "SELECT b.*, u.username as manager_name
                FROM branches b
                LEFT JOIN users u ON b.manager_id = u.id
                WHERE b.tenant_id = ? AND b.status = 'active'
                ORDER BY b.name ASC";
        
        return $this->db->fetchAll($sql, [$this->tenantId]);
    }

    /**
     * Get branch statistics
     */
    public function getBranchStats($branchId = null)
    {
        $whereClause = $branchId ? "AND branch_id = ?" : "";
        $params = $branchId ? [$this->tenantId, $branchId] : [$this->tenantId];

        $sql = "SELECT 
                    b.id as branch_id,
                    b.name as branch_name,
                    b.code as branch_code,
                    COUNT(DISTINCT s.id) as total_sales,
                    COALESCE(SUM(s.grand_total), 0) as total_revenue,
                    COUNT(DISTINCT c.id) as total_customers,
                    COUNT(DISTINCT p.id) as total_products,
                    COUNT(DISTINCT u.id) as total_users
                FROM branches b
                LEFT JOIN sales s ON b.id = s.branch_id AND s.tenant_id = b.tenant_id
                LEFT JOIN customers c ON b.id = c.branch_id AND c.tenant_id = b.tenant_id
                LEFT JOIN products p ON b.id = p.branch_id AND p.tenant_id = b.tenant_id
                LEFT JOIN users u ON b.id = u.branch_id AND u.tenant_id = b.tenant_id
                WHERE b.tenant_id = ? $whereClause
                GROUP BY b.id, b.name, b.code
                ORDER BY b.name ASC";
        
        if ($branchId) {
            return $this->db->fetch($sql, $params);
        } else {
            return $this->db->fetchAll($sql, $params);
        }
    }

    /**
     * Get branch sales statistics
     */
    public function getBranchSalesStats($branchId, $period = 'month')
    {
        $dateFilter = '';
        switch ($period) {
            case 'week':
                $dateFilter = "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateFilter = "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'quarter':
                $dateFilter = "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                break;
            case 'year':
                $dateFilter = "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }

        $sql = "SELECT 
                    COUNT(*) as total_sales,
                    COUNT(CASE WHEN s.payment_method = 'cash' THEN 1 END) as cash_sales,
                    COUNT(CASE WHEN s.payment_method = 'card' THEN 1 END) as card_sales,
                    COUNT(CASE WHEN s.payment_method = 'credit' THEN 1 END) as credit_sales,
                    COALESCE(SUM(s.grand_total), 0) as total_revenue,
                    COALESCE(SUM(s.tax_amount), 0) as total_tax,
                    COALESCE(SUM(s.discount_amount), 0) as total_discounts,
                    COALESCE(AVG(s.grand_total), 0) as avg_sale_amount,
                    COUNT(DISTINCT s.customer_id) as unique_customers
                FROM sales s
                WHERE s.tenant_id = ? AND s.branch_id = ? $dateFilter";
        
        return $this->db->fetch($sql, [$this->tenantId, $branchId]);
    }

    /**
     * Get branch inventory statistics
     */
    public function getBranchInventoryStats($branchId)
    {
        $sql = "SELECT 
                    COUNT(DISTINCT i.product_id) as total_products,
                    COUNT(CASE WHEN i.quantity > 0 THEN 1 END) as in_stock_products,
                    COUNT(CASE WHEN i.quantity = 0 THEN 1 END) as out_of_stock_products,
                    COUNT(CASE WHEN i.quantity <= i.reorder_level THEN 1 END) as low_stock_products,
                    COUNT(CASE WHEN i.expiry_date <= DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon,
                    COALESCE(SUM(i.quantity * i.unit_cost), 0) as total_inventory_value,
                    COALESCE(SUM(i.quantity), 0) as total_quantity
                FROM inventory i
                WHERE i.tenant_id = ? AND i.branch_id = ?";
        
        return $this->db->fetch($sql, [$this->tenantId, $branchId]);
    }

    /**
     * Get branch users
     */
    public function getBranchUsers($branchId, $page = 1, $limit = 20)
    {
        $sql = "SELECT u.*, r.name as role_name
                FROM users u
                JOIN roles r ON u.role_id = r.id
                WHERE u.tenant_id = ? AND u.branch_id = ?
                ORDER BY u.username ASC";
        
        $params = [$this->tenantId, $branchId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get branch users count
     */
    public function getBranchUsersCount($branchId)
    {
        $sql = "SELECT COUNT(*) as total FROM users WHERE tenant_id = ? AND branch_id = ?";
        $result = $this->db->fetch($sql, [$this->tenantId, $branchId]);
        return $result['total'] ?? 0;
    }

    /**
     * Assign user to branch
     */
    public function assignUser($branchId, $userId)
    {
        return $this->db->update('users', [
            'branch_id' => $branchId,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ? AND tenant_id = ?', [$userId, $this->tenantId]);
    }

    /**
     * Remove user from branch
     */
    public function removeUser($branchId, $userId)
    {
        return $this->db->update('users', [
            'branch_id' => null,
            'updated_at' => date('Y-m-d H:i:s')
        ], 'id = ? AND tenant_id = ? AND branch_id = ?', [$userId, $this->tenantId, $branchId]);
    }

    /**
     * Get branch settings
     */
    public function getBranchSettings($branchId)
    {
        $sql = "SELECT * FROM branch_settings WHERE tenant_id = ? AND branch_id = ?";
        return $this->db->fetch($sql, [$this->tenantId, $branchId]);
    }

    /**
     * Update branch settings
     */
    public function updateBranchSettings($branchId, $settings)
    {
        $existingSettings = $this->getBranchSettings($branchId);
        
        if ($existingSettings) {
            return $this->db->update('branch_settings', $settings, 'tenant_id = ? AND branch_id = ?', [$this->tenantId, $branchId]);
        } else {
            $settings['tenant_id'] = $this->tenantId;
            $settings['branch_id'] = $branchId;
            $settings['created_at'] = date('Y-m-d H:i:s');
            return $this->db->insert('branch_settings', $settings);
        }
    }

    /**
     * Get branch opening hours
     */
    public function getBranchHours($branchId)
    {
        $sql = "SELECT * FROM branch_hours WHERE tenant_id = ? AND branch_id = ? ORDER BY day_of_week ASC";
        return $this->db->fetchAll($sql, [$this->tenantId, $branchId]);
    }

    /**
     * Update branch opening hours
     */
    public function updateBranchHours($branchId, $hours)
    {
        $this->db->beginTransaction();
        
        try {
            // Delete existing hours
            $this->db->delete('branch_hours', 'tenant_id = ? AND branch_id = ?', [$this->tenantId, $branchId]);
            
            // Insert new hours
            foreach ($hours as $hour) {
                $hour['tenant_id'] = $this->tenantId;
                $hour['branch_id'] = $branchId;
                $hour['created_at'] = date('Y-m-d H:i:s');
                $this->db->insert('branch_hours', $hour);
            }
            
            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Check if branch is open
     */
    public function isBranchOpen($branchId)
    {
        $currentTime = date('H:i:s');
        $currentDay = date('N'); // 1 (Monday) through 7 (Sunday)
        
        $sql = "SELECT * FROM branch_hours 
                WHERE tenant_id = ? AND branch_id = ? AND day_of_week = ? 
                AND ? BETWEEN open_time AND close_time";
        
        $result = $this->db->fetch($sql, [$this->tenantId, $branchId, $currentDay, $currentTime]);
        return !empty($result);
    }

    /**
     * Get branch performance metrics
     */
    public function getBranchPerformance($branchId, $period = 'month')
    {
        $dateFilter = '';
        switch ($period) {
            case 'week':
                $dateFilter = "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateFilter = "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'quarter':
                $dateFilter = "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                break;
            case 'year':
                $dateFilter = "AND s.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }

        $sql = "SELECT 
                    DATE(s.created_at) as date,
                    COUNT(*) as sales_count,
                    COALESCE(SUM(s.grand_total), 0) as revenue,
                    COALESCE(AVG(s.grand_total), 0) as avg_sale,
                    COUNT(DISTINCT s.customer_id) as unique_customers
                FROM sales s
                WHERE s.tenant_id = ? AND s.branch_id = ? $dateFilter
                GROUP BY DATE(s.created_at)
                ORDER BY date DESC";
        
        return $this->db->fetchAll($sql, [$this->tenantId, $branchId]);
    }

    /**
     * Search branches for autocomplete
     */
    public function searchBranches($query, $limit = 10)
    {
        $sql = "SELECT id, name, name_ar, name_ps, name_dr, code, city, phone
                FROM branches 
                WHERE tenant_id = ? AND (
                    name LIKE ? OR 
                    name_ar LIKE ? OR 
                    name_ps LIKE ? OR 
                    name_dr LIKE ? OR 
                    code LIKE ? OR 
                    city LIKE ?
                )
                ORDER BY name ASC
                LIMIT ?";
        
        $searchTerm = '%' . $query . '%';
        $params = [$this->tenantId, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Generate unique branch code
     */
    private function generateBranchCode()
    {
        $prefix = 'BR';
        $year = date('Y');
        
        // Get last branch code for this year
        $sql = "SELECT code FROM branches 
                WHERE tenant_id = ? AND code LIKE ? 
                ORDER BY id DESC LIMIT 1";
        
        $pattern = $prefix . $year . '%';
        $result = $this->db->fetch($sql, [$this->tenantId, $pattern]);
        
        if ($result) {
            $lastNumber = (int)substr($result['code'], -3);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . str_pad($newNumber, 3, '0', STR_PAD_LEFT);
    }

    /**
     * Delete branch (soft delete by setting status to inactive)
     */
    public function delete($branchId)
    {
        // Check if branch has any active sales or inventory
        $sql = "SELECT 
                    (SELECT COUNT(*) FROM sales WHERE branch_id = ? AND tenant_id = ?) as sales_count,
                    (SELECT COUNT(*) FROM inventory WHERE branch_id = ? AND tenant_id = ?) as inventory_count,
                    (SELECT COUNT(*) FROM users WHERE branch_id = ? AND tenant_id = ?) as users_count";
        
        $result = $this->db->fetch($sql, [$branchId, $this->tenantId, $branchId, $this->tenantId, $branchId, $this->tenantId]);
        
        if ($result['sales_count'] > 0 || $result['inventory_count'] > 0 || $result['users_count'] > 0) {
            throw new \Exception('Cannot delete branch with active sales, inventory, or users');
        }

        return $this->db->update('branches', ['status' => 'inactive', 'updated_at' => date('Y-m-d H:i:s')], 'id = ? AND tenant_id = ?', [$branchId, $this->tenantId]);
    }
}