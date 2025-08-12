<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Purchase
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getTenantId();
    }

    /**
     * Create a new purchase order
     */
    public function create($data)
    {
        $this->db->beginTransaction();
        
        try {
            // Create purchase header
            $purchaseData = [
                'tenant_id' => $this->tenantId,
                'supplier_id' => $data['supplier_id'],
                'branch_id' => $data['branch_id'],
                'purchase_number' => $this->generatePurchaseNumber(),
                'purchase_date' => $data['purchase_date'] ?? date('Y-m-d'),
                'expected_delivery_date' => $data['expected_delivery_date'] ?? null,
                'total_amount' => 0, // Will be calculated from items
                'tax_amount' => $data['tax_amount'] ?? 0,
                'discount_amount' => $data['discount_amount'] ?? 0,
                'shipping_amount' => $data['shipping_amount'] ?? 0,
                'grand_total' => 0, // Will be calculated
                'paid_amount' => $data['paid_amount'] ?? 0,
                'payment_method' => $data['payment_method'] ?? 'credit',
                'status' => $data['status'] ?? 'pending',
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $purchaseId = $this->db->insert('purchases', $purchaseData);

            // Add purchase items
            $totalAmount = 0;
            foreach ($data['items'] as $item) {
                $itemData = [
                    'tenant_id' => $this->tenantId,
                    'purchase_id' => $purchaseId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_cost' => $item['unit_cost'],
                    'total_cost' => $item['quantity'] * $item['unit_cost'],
                    'batch_number' => $item['batch_number'] ?? null,
                    'expiry_date' => $item['expiry_date'] ?? null,
                    'manufacturer' => $item['manufacturer'] ?? null,
                    'notes' => $item['notes'] ?? null
                ];

                $this->db->insert('purchase_items', $itemData);
                $totalAmount += $itemData['total_cost'];
            }

            // Update purchase totals
            $grandTotal = $totalAmount + $purchaseData['tax_amount'] + $purchaseData['shipping_amount'] - $purchaseData['discount_amount'];
            
            $this->db->update('purchases', [
                'total_amount' => $totalAmount,
                'grand_total' => $grandTotal
            ], 'id = ?', [$purchaseId]);

            $this->db->commit();
            return $purchaseId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update purchase order
     */
    public function update($purchaseId, $data)
    {
        $this->db->beginTransaction();
        
        try {
            // Update purchase header
            $updateData = array_intersect_key($data, array_flip([
                'supplier_id', 'branch_id', 'purchase_date', 'expected_delivery_date',
                'tax_amount', 'discount_amount', 'shipping_amount', 'paid_amount',
                'payment_method', 'status', 'notes'
            ]));

            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $this->db->update('purchases', $updateData, 'id = ? AND tenant_id = ?', [$purchaseId, $this->tenantId]);

            // Update items if provided
            if (isset($data['items'])) {
                // Delete existing items
                $this->db->delete('purchase_items', 'purchase_id = ? AND tenant_id = ?', [$purchaseId, $this->tenantId]);

                // Add new items
                $totalAmount = 0;
                foreach ($data['items'] as $item) {
                    $itemData = [
                        'tenant_id' => $this->tenantId,
                        'purchase_id' => $purchaseId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'unit_cost' => $item['unit_cost'],
                        'total_cost' => $item['quantity'] * $item['unit_cost'],
                        'batch_number' => $item['batch_number'] ?? null,
                        'expiry_date' => $item['expiry_date'] ?? null,
                        'manufacturer' => $item['manufacturer'] ?? null,
                        'notes' => $item['notes'] ?? null
                    ];

                    $this->db->insert('purchase_items', $itemData);
                    $totalAmount += $itemData['total_cost'];
                }

                // Update purchase totals
                $purchase = $this->getById($purchaseId);
                $grandTotal = $totalAmount + $purchase['tax_amount'] + $purchase['shipping_amount'] - $purchase['discount_amount'];
                
                $this->db->update('purchases', [
                    'total_amount' => $totalAmount,
                    'grand_total' => $grandTotal
                ], 'id = ?', [$purchaseId]);
            }

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get purchase by ID
     */
    public function getById($purchaseId)
    {
        $sql = "SELECT p.*, s.name as supplier_name, s.name_ar as supplier_name_ar, 
                       s.name_ps as supplier_name_ps, s.name_dr as supplier_name_dr,
                       b.name as branch_name, u.username as created_by_name
                FROM purchases p
                JOIN suppliers s ON p.supplier_id = s.id
                JOIN branches b ON p.branch_id = b.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ? AND p.tenant_id = ?";
        
        return $this->db->fetch($sql, [$purchaseId, $this->tenantId]);
    }

    /**
     * Get purchase items
     */
    public function getItems($purchaseId)
    {
        $sql = "SELECT pi.*, p.name as product_name, p.name_ar as product_name_ar,
                       p.name_ps as product_name_ps, p.name_dr as product_name_dr,
                       p.barcode, p.sku
                FROM purchase_items pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.purchase_id = ? AND pi.tenant_id = ?
                ORDER BY pi.id ASC";
        
        return $this->db->fetchAll($sql, [$purchaseId, $this->tenantId]);
    }

    /**
     * Get all purchases with filters and pagination
     */
    public function getAll($filters = [], $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, s.name as supplier_name, s.name_ar as supplier_name_ar,
                       s.name_ps as supplier_name_ps, s.name_dr as supplier_name_dr,
                       b.name as branch_name, u.username as created_by_name
                FROM purchases p
                JOIN suppliers s ON p.supplier_id = s.id
                JOIN branches b ON p.branch_id = b.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ?";
        
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['supplier_id'])) {
            $sql .= " AND p.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }

        if (!empty($filters['branch_id'])) {
            $sql .= " AND p.branch_id = ?";
            $params[] = $filters['branch_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['purchase_date_from'])) {
            $sql .= " AND p.purchase_date >= ?";
            $params[] = $filters['purchase_date_from'];
        }

        if (!empty($filters['purchase_date_to'])) {
            $sql .= " AND p.purchase_date <= ?";
            $params[] = $filters['purchase_date_to'];
        }

        if (!empty($filters['purchase_number'])) {
            $sql .= " AND p.purchase_number LIKE ?";
            $params[] = '%' . $filters['purchase_number'] . '%';
        }

        $sql .= " ORDER BY p.created_at DESC";
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get purchase count for pagination
     */
    public function getCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM purchases p WHERE p.tenant_id = ?";
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['supplier_id'])) {
            $sql .= " AND p.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }

        if (!empty($filters['branch_id'])) {
            $sql .= " AND p.branch_id = ?";
            $params[] = $filters['branch_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['purchase_date_from'])) {
            $sql .= " AND p.purchase_date >= ?";
            $params[] = $filters['purchase_date_from'];
        }

        if (!empty($filters['purchase_date_to'])) {
            $sql .= " AND p.purchase_date <= ?";
            $params[] = $filters['purchase_date_to'];
        }

        if (!empty($filters['purchase_number'])) {
            $sql .= " AND p.purchase_number LIKE ?";
            $params[] = '%' . $filters['purchase_number'] . '%';
        }

        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Update purchase status
     */
    public function updateStatus($purchaseId, $status, $userId)
    {
        $this->db->beginTransaction();
        
        try {
            $currentPurchase = $this->getById($purchaseId);
            if (!$currentPurchase) {
                throw new \Exception('Purchase not found');
            }

            // Update purchase status
            $this->db->update('purchases', [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ? AND tenant_id = ?', [$purchaseId, $this->tenantId]);

            // If status is 'received', add items to inventory
            if ($status === 'received') {
                $this->addItemsToInventory($purchaseId, $userId);
            }

            // Log status change
            $this->db->insert('purchase_status_logs', [
                'tenant_id' => $this->tenantId,
                'purchase_id' => $purchaseId,
                'old_status' => $currentPurchase['status'],
                'new_status' => $status,
                'changed_by' => $userId,
                'changed_at' => date('Y-m-d H:i:s'),
                'notes' => "Status changed from {$currentPurchase['status']} to {$status}"
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Add items to inventory when purchase is received
     */
    private function addItemsToInventory($purchaseId, $userId)
    {
        $items = $this->getItems($purchaseId);
        
        foreach ($items as $item) {
            // Add to inventory
            $inventory = new Inventory($this->tenantId);
            $inventory->addStock(
                $item['product_id'],
                $item['branch_id'] ?? $this->getBranchId($purchaseId),
                $item['quantity'],
                $item['unit_cost'],
                'purchase',
                $purchaseId,
                $item['batch_number'],
                $item['expiry_date'],
                $userId
            );
        }
    }

    /**
     * Get branch ID from purchase
     */
    private function getBranchId($purchaseId)
    {
        $sql = "SELECT branch_id FROM purchases WHERE id = ? AND tenant_id = ?";
        $result = $this->db->fetch($sql, [$purchaseId, $this->tenantId]);
        return $result['branch_id'] ?? null;
    }

    /**
     * Record payment for purchase
     */
    public function recordPayment($purchaseId, $amount, $paymentMethod, $reference, $userId)
    {
        $this->db->beginTransaction();
        
        try {
            $purchase = $this->getById($purchaseId);
            if (!$purchase) {
                throw new \Exception('Purchase not found');
            }

            // Record payment
            $this->db->insert('purchase_payments', [
                'tenant_id' => $this->tenantId,
                'purchase_id' => $purchaseId,
                'amount' => $amount,
                'payment_method' => $paymentMethod,
                'reference' => $reference,
                'payment_date' => date('Y-m-d'),
                'created_by' => $userId,
                'created_at' => date('Y-m-d H:i:s')
            ]);

            // Update purchase paid amount
            $newPaidAmount = $purchase['paid_amount'] + $amount;
            $this->db->update('purchases', [
                'paid_amount' => $newPaidAmount,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ?', [$purchaseId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get purchase payments
     */
    public function getPayments($purchaseId)
    {
        $sql = "SELECT pp.*, u.username as created_by_name
                FROM purchase_payments pp
                LEFT JOIN users u ON pp.created_by = u.id
                WHERE pp.purchase_id = ? AND pp.tenant_id = ?
                ORDER BY pp.created_at ASC";
        
        return $this->db->fetchAll($sql, [$purchaseId, $this->tenantId]);
    }

    /**
     * Get purchase statistics
     */
    public function getPurchaseStats($period = 'month')
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

        $sql = "SELECT 
                    COUNT(*) as total_purchases,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_purchases,
                    COUNT(CASE WHEN status = 'ordered' THEN 1 END) as ordered_purchases,
                    COUNT(CASE WHEN status = 'received' THEN 1 END) as received_purchases,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_purchases,
                    COALESCE(SUM(grand_total), 0) as total_amount,
                    COALESCE(SUM(paid_amount), 0) as total_paid,
                    COALESCE(SUM(grand_total - paid_amount), 0) as total_outstanding
                FROM purchases 
                WHERE tenant_id = ? $dateFilter";
        
        return $this->db->fetch($sql, [$this->tenantId]);
    }

    /**
     * Get purchases by status
     */
    public function getByStatus($status, $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, s.name as supplier_name, s.name_ar as supplier_name_ar,
                       s.name_ps as supplier_name_ps, s.name_dr as supplier_name_dr,
                       b.name as branch_name, u.username as created_by_name
                FROM purchases p
                JOIN suppliers s ON p.supplier_id = s.id
                JOIN branches b ON p.branch_id = b.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ? AND p.status = ?
                ORDER BY p.created_at DESC";
        
        $params = [$this->tenantId, $status];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get purchases due for delivery
     */
    public function getDueForDelivery($page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, s.name as supplier_name, s.name_ar as supplier_name_ar,
                       s.name_ps as supplier_name_ps, s.name_dr as supplier_name_dr,
                       b.name as branch_name, u.username as created_by_name
                FROM purchases p
                JOIN suppliers s ON p.supplier_id = s.id
                JOIN branches b ON p.branch_id = b.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ? AND p.status = 'ordered' 
                AND p.expected_delivery_date <= DATE_ADD(NOW(), INTERVAL 3 DAY)
                ORDER BY p.expected_delivery_date ASC";
        
        $params = [$this->tenantId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Generate unique purchase number
     */
    private function generatePurchaseNumber()
    {
        $prefix = 'PO';
        $year = date('Y');
        $month = date('m');
        
        // Get last purchase number for this month
        $sql = "SELECT purchase_number FROM purchases 
                WHERE tenant_id = ? AND purchase_number LIKE ? 
                ORDER BY id DESC LIMIT 1";
        
        $pattern = $prefix . $year . $month . '%';
        $result = $this->db->fetch($sql, [$this->tenantId, $pattern]);
        
        if ($result) {
            $lastNumber = (int)substr($result['purchase_number'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Delete purchase (only if not received)
     */
    public function delete($purchaseId)
    {
        $purchase = $this->getById($purchaseId);
        if (!$purchase) {
            throw new \Exception('Purchase not found');
        }

        if ($purchase['status'] === 'received') {
            throw new \Exception('Cannot delete received purchase');
        }

        $this->db->beginTransaction();
        
        try {
            // Delete purchase items
            $this->db->delete('purchase_items', 'purchase_id = ? AND tenant_id = ?', [$purchaseId, $this->tenantId]);
            
            // Delete purchase payments
            $this->db->delete('purchase_payments', 'purchase_id = ? AND tenant_id = ?', [$purchaseId, $this->tenantId]);
            
            // Delete purchase
            $this->db->delete('purchases', 'id = ? AND tenant_id = ?', [$purchaseId, $this->tenantId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}