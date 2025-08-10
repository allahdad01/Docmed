<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Customer
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getTenantId();
    }

    /**
     * Create a new customer
     */
    public function create($data)
    {
        $customerData = [
            'tenant_id' => $this->tenantId,
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_ps' => $data['name_ps'] ?? null,
            'name_dr' => $data['name_dr'] ?? null,
            'phone' => $data['phone'] ?? null,
            'email' => $data['email'] ?? null,
            'address' => $data['address'] ?? null,
            'city' => $data['city'] ?? null,
            'country' => $data['country'] ?? null,
            'date_of_birth' => $data['date_of_birth'] ?? null,
            'gender' => $data['gender'] ?? null,
            'blood_group' => $data['blood_group'] ?? null,
            'allergies' => $data['allergies'] ?? null,
            'medical_conditions' => $data['medical_conditions'] ?? null,
            'emergency_contact' => $data['emergency_contact'] ?? null,
            'emergency_phone' => $data['emergency_phone'] ?? null,
            'loyalty_points' => $data['loyalty_points'] ?? 0,
            'credit_limit' => $data['credit_limit'] ?? 0,
            'credit_balance' => $data['credit_balance'] ?? 0,
            'status' => $data['status'] ?? 'active',
            'created_at' => date('Y-m-d H:i:s')
        ];

        return $this->db->insert('customers', $customerData);
    }

    /**
     * Update customer information
     */
    public function update($customerId, $data)
    {
        $updateData = array_intersect_key($data, array_flip([
            'name', 'name_ar', 'name_ps', 'name_dr', 'phone', 'email', 'address',
            'city', 'country', 'date_of_birth', 'gender', 'blood_group',
            'allergies', 'medical_conditions', 'emergency_contact', 'emergency_phone',
            'loyalty_points', 'credit_limit', 'credit_balance', 'status'
        ]));

        $updateData['updated_at'] = date('Y-m-d H:i:s');

        return $this->db->update('customers', $updateData, 'id = ? AND tenant_id = ?', [$customerId, $this->tenantId]);
    }

    /**
     * Get customer by ID
     */
    public function getById($customerId)
    {
        $sql = "SELECT * FROM customers WHERE id = ? AND tenant_id = ?";
        return $this->db->fetch($sql, [$customerId, $this->tenantId]);
    }

    /**
     * Get customer by phone or email
     */
    public function getByContact($contact, $type = 'phone')
    {
        $sql = "SELECT * FROM customers WHERE $type = ? AND tenant_id = ?";
        return $this->db->fetch($sql, [$contact, $this->tenantId]);
    }

    /**
     * Get all customers with filters and pagination
     */
    public function getAll($filters = [], $page = 1, $limit = 20)
    {
        $sql = "SELECT * FROM customers WHERE tenant_id = ?";
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
     * Get customer count for pagination
     */
    public function getCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM customers WHERE tenant_id = ?";
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
     * Get customer purchase history
     */
    public function getPurchaseHistory($customerId, $page = 1, $limit = 20)
    {
        $sql = "SELECT s.*, b.name as branch_name, u.username as cashier_name
                FROM sales s 
                JOIN branches b ON s.branch_id = b.id 
                LEFT JOIN users u ON s.created_by = u.id
                WHERE s.tenant_id = ? AND s.customer_id = ?
                ORDER BY s.created_at DESC";
        
        $params = [$this->tenantId, $customerId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get customer purchase history count
     */
    public function getPurchaseHistoryCount($customerId)
    {
        $sql = "SELECT COUNT(*) as total FROM sales WHERE tenant_id = ? AND customer_id = ?";
        $result = $this->db->fetch($sql, [$this->tenantId, $customerId]);
        return $result['total'] ?? 0;
    }

    /**
     * Get customer credit summary
     */
    public function getCreditSummary($customerId)
    {
        $sql = "SELECT 
                    credit_limit,
                    credit_balance,
                    (credit_limit - credit_balance) as available_credit,
                    CASE 
                        WHEN credit_balance > credit_limit THEN 'over_limit'
                        WHEN credit_balance > 0 THEN 'has_balance'
                        ELSE 'no_balance'
                    END as credit_status
                FROM customers 
                WHERE id = ? AND tenant_id = ?";
        
        return $this->db->fetch($sql, [$customerId, $this->tenantId]);
    }

    /**
     * Update customer credit balance
     */
    public function updateCreditBalance($customerId, $amount, $type = 'add')
    {
        $sql = "UPDATE customers SET 
                credit_balance = CASE 
                    WHEN ? = 'add' THEN credit_balance + ?
                    WHEN ? = 'subtract' THEN credit_balance - ?
                    ELSE credit_balance
                END,
                updated_at = NOW()
                WHERE id = ? AND tenant_id = ?";
        
        return $this->db->query($sql, [$type, $amount, $type, $amount, $customerId, $this->tenantId]);
    }

    /**
     * Add loyalty points to customer
     */
    public function addLoyaltyPoints($customerId, $points, $reason = 'purchase')
    {
        $this->db->beginTransaction();
        
        try {
            // Update customer loyalty points
            $sql = "UPDATE customers SET 
                    loyalty_points = loyalty_points + ?,
                    updated_at = NOW()
                    WHERE id = ? AND tenant_id = ?";
            
            $this->db->query($sql, [$points, $customerId, $this->tenantId]);

            // Log loyalty points transaction
            $this->db->insert('loyalty_transactions', [
                'tenant_id' => $this->tenantId,
                'customer_id' => $customerId,
                'points' => $points,
                'type' => 'earned',
                'reason' => $reason,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Redeem loyalty points
     */
    public function redeemLoyaltyPoints($customerId, $points, $reason = 'redemption')
    {
        $this->db->beginTransaction();
        
        try {
            // Check if customer has enough points
            $customer = $this->getById($customerId);
            if ($customer['loyalty_points'] < $points) {
                throw new \Exception('Insufficient loyalty points');
            }

            // Update customer loyalty points
            $sql = "UPDATE customers SET 
                    loyalty_points = loyalty_points - ?,
                    updated_at = NOW()
                    WHERE id = ? AND tenant_id = ?";
            
            $this->db->query($sql, [$points, $customerId, $this->tenantId]);

            // Log loyalty points transaction
            $this->db->insert('loyalty_transactions', [
                'tenant_id' => $this->tenantId,
                'customer_id' => $customerId,
                'points' => -$points,
                'type' => 'redeemed',
                'reason' => $reason,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get customer loyalty transaction history
     */
    public function getLoyaltyHistory($customerId, $page = 1, $limit = 20)
    {
        $sql = "SELECT * FROM loyalty_transactions 
                WHERE tenant_id = ? AND customer_id = ?
                ORDER BY created_at DESC";
        
        $params = [$this->tenantId, $customerId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get customers with credit balance
     */
    public function getCustomersWithCredit($page = 1, $limit = 20)
    {
        $sql = "SELECT id, name, name_ar, name_ps, name_dr, phone, email,
                       credit_limit, credit_balance, (credit_limit - credit_balance) as available_credit
                FROM customers 
                WHERE tenant_id = ? AND credit_balance > 0
                ORDER BY credit_balance DESC";
        
        $params = [$this->tenantId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get customers with credit balance count
     */
    public function getCustomersWithCreditCount()
    {
        $sql = "SELECT COUNT(*) as total FROM customers WHERE tenant_id = ? AND credit_balance > 0";
        $result = $this->db->fetch($sql, [$this->tenantId]);
        return $result['total'] ?? 0;
    }

    /**
     * Get top customers by loyalty points
     */
    public function getTopLoyaltyCustomers($limit = 10)
    {
        $sql = "SELECT id, name, name_ar, name_ps, name_dr, phone, email,
                       loyalty_points, 
                       (SELECT COUNT(*) FROM sales WHERE customer_id = c.id AND tenant_id = c.tenant_id) as total_purchases
                FROM customers c
                WHERE tenant_id = ? AND loyalty_points > 0
                ORDER BY loyalty_points DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$this->tenantId, $limit]);
    }

    /**
     * Get customer statistics
     */
    public function getCustomerStats()
    {
        $sql = "SELECT 
                    COUNT(*) as total_customers,
                    COUNT(CASE WHEN status = 'active' THEN 1 END) as active_customers,
                    COUNT(CASE WHEN credit_balance > 0 THEN 1 END) as customers_with_credit,
                    COUNT(CASE WHEN loyalty_points > 0 THEN 1 END) as customers_with_points,
                    AVG(loyalty_points) as avg_loyalty_points,
                    SUM(credit_balance) as total_credit_outstanding
                FROM customers 
                WHERE tenant_id = ?";
        
        return $this->db->fetch($sql, [$this->tenantId]);
    }

    /**
     * Search customers for autocomplete
     */
    public function searchCustomers($query, $limit = 10)
    {
        $sql = "SELECT id, name, name_ar, name_ps, name_dr, phone, email
                FROM customers 
                WHERE tenant_id = ? AND (
                    name LIKE ? OR 
                    name_ar LIKE ? OR 
                    name_ps LIKE ? OR 
                    name_dr LIKE ? OR 
                    phone LIKE ? OR 
                    email LIKE ?
                )
                ORDER BY name ASC
                LIMIT ?";
        
        $searchTerm = '%' . $query . '%';
        $params = [$this->tenantId, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        
        return $this->db->fetchAll($sql, $params);
    }
}