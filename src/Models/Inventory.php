<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Inventory
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getTenantId();
    }

    /**
     * Add stock to inventory (stock-in)
     */
    public function addStock($data)
    {
        $this->db->beginTransaction();
        
        try {
            $inventoryData = [
                'tenant_id' => $this->tenantId,
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'batch_number' => $data['batch_number'] ?? null,
                'expiry_date' => $data['expiry_date'] ?? null,
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'supplier_id' => $data['supplier_id'] ?? null,
                'purchase_date' => $data['purchase_date'] ?? date('Y-m-d'),
                'notes' => $data['notes'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $inventoryId = $this->db->insert('inventory', $inventoryData);

            // Update product stock
            $this->updateProductStock($data['product_id'], $data['branch_id'], $data['quantity']);

            // Log the stock-in
            $this->logStockMovement([
                'tenant_id' => $this->tenantId,
                'inventory_id' => $inventoryId,
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'movement_type' => 'stock_in',
                'quantity' => $data['quantity'],
                'batch_number' => $data['batch_number'] ?? null,
                'notes' => $data['notes'] ?? 'Stock added',
                'created_by' => $data['created_by'] ?? null
            ]);

            $this->db->commit();
            return $inventoryId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Remove stock from inventory (stock-out)
     */
    public function removeStock($data)
    {
        $this->db->beginTransaction();
        
        try {
            $inventoryData = [
                'tenant_id' => $this->tenantId,
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'batch_number' => $data['batch_number'] ?? null,
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'reason' => $data['reason'] ?? 'stock_out',
                'notes' => $data['notes'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $inventoryId = $this->db->insert('inventory', $inventoryData);

            // Update product stock (negative quantity)
            $this->updateProductStock($data['product_id'], $data['branch_id'], -$data['quantity']);

            // Log the stock-out
            $this->logStockMovement([
                'tenant_id' => $this->tenantId,
                'inventory_id' => $inventoryId,
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'movement_type' => 'stock_out',
                'quantity' => -$data['quantity'],
                'batch_number' => $data['batch_number'] ?? null,
                'notes' => $data['notes'] ?? 'Stock removed',
                'created_by' => $data['created_by'] ?? null
            ]);

            $this->db->commit();
            return $inventoryId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update product stock in product_inventory table
     */
    private function updateProductStock($productId, $branchId, $quantity)
    {
        $sql = "SELECT id, quantity FROM product_inventory 
                WHERE tenant_id = ? AND product_id = ? AND branch_id = ?";
        $existing = $this->db->fetch($sql, [$this->tenantId, $productId, $branchId]);

        if ($existing) {
            $newQuantity = $existing['quantity'] + $quantity;
            $this->db->update('product_inventory', 
                ['quantity' => $newQuantity], 
                'id = ?', 
                [$existing['id']]
            );
        } else {
            $this->db->insert('product_inventory', [
                'tenant_id' => $this->tenantId,
                'product_id' => $productId,
                'branch_id' => $branchId,
                'quantity' => $quantity,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }
    }

    /**
     * Log stock movement for audit trail
     */
    private function logStockMovement($data)
    {
        $this->db->insert('stock_movements', $data);
    }

    /**
     * Get current stock levels for a product across all branches
     */
    public function getProductStock($productId, $branchId = null)
    {
        $sql = "SELECT pi.*, b.name as branch_name 
                FROM product_inventory pi 
                JOIN branches b ON pi.branch_id = b.id 
                WHERE pi.tenant_id = ? AND pi.product_id = ?";
        $params = [$this->tenantId, $productId];

        if ($branchId) {
            $sql .= " AND pi.branch_id = ?";
            $params[] = $branchId;
        }

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get low stock products
     */
    public function getLowStock($branchId = null, $threshold = 10)
    {
        $sql = "SELECT pi.*, p.name, p.name_ar, p.name_ps, p.name_dr, p.barcode, 
                       b.name as branch_name, c.name as category_name
                FROM product_inventory pi 
                JOIN products p ON pi.product_id = p.id 
                JOIN branches b ON pi.branch_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE pi.tenant_id = ? AND pi.quantity <= ?";
        $params = [$this->tenantId, $threshold];

        if ($branchId) {
            $sql .= " AND pi.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY pi.quantity ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get products expiring soon
     */
    public function getExpiringSoon($days = 30, $branchId = null)
    {
        $sql = "SELECT i.*, p.name, p.name_ar, p.name_ps, p.name_dr, p.barcode,
                       b.name as branch_name, c.name as category_name
                FROM inventory i 
                JOIN products p ON i.product_id = p.id 
                JOIN branches b ON i.branch_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE i.tenant_id = ? AND i.expiry_date IS NOT NULL 
                AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL ? DAY)
                AND i.quantity > 0";
        $params = [$this->tenantId, $days];

        if ($branchId) {
            $sql .= " AND i.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY i.expiry_date ASC";
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get inventory valuation report
     */
    public function getInventoryValuation($branchId = null, $asOfDate = null)
    {
        if (!$asOfDate) {
            $asOfDate = date('Y-m-d');
        }

        $sql = "SELECT pi.*, p.name, p.name_ar, p.name_ps, p.name_dr, p.barcode,
                       p.unit_cost, p.selling_price, b.name as branch_name,
                       c.name as category_name,
                       (pi.quantity * p.unit_cost) as total_cost,
                       (pi.quantity * p.selling_price) as total_value
                FROM product_inventory pi 
                JOIN products p ON pi.product_id = p.id 
                JOIN branches b ON pi.branch_id = b.id 
                LEFT JOIN categories c ON p.category_id = c.id
                WHERE pi.tenant_id = ? AND pi.quantity > 0";
        $params = [$this->tenantId];

        if ($branchId) {
            $sql .= " AND pi.branch_id = ?";
            $params[] = $branchId;
        }

        $sql .= " ORDER BY c.name, p.name";
        
        $inventory = $this->db->fetchAll($sql, $params);

        // Calculate totals
        $totalCost = 0;
        $totalValue = 0;
        foreach ($inventory as &$item) {
            $totalCost += $item['total_cost'];
            $totalValue += $item['total_value'];
        }

        return [
            'inventory' => $inventory,
            'summary' => [
                'total_items' => count($inventory),
                'total_cost' => $totalCost,
                'total_value' => $totalValue,
                'potential_profit' => $totalValue - $totalCost
            ]
        ];
    }

    /**
     * Get stock movement history
     */
    public function getStockMovements($filters = [], $page = 1, $limit = 20)
    {
        $sql = "SELECT sm.*, p.name as product_name, p.barcode, b.name as branch_name,
                       u.username as created_by_user
                FROM stock_movements sm 
                JOIN products p ON sm.product_id = p.id 
                JOIN branches b ON sm.branch_id = b.id 
                LEFT JOIN users u ON sm.created_by = u.id
                WHERE sm.tenant_id = ?";
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['product_id'])) {
            $sql .= " AND sm.product_id = ?";
            $params[] = $filters['product_id'];
        }

        if (!empty($filters['branch_id'])) {
            $sql .= " AND sm.branch_id = ?";
            $params[] = $filters['branch_id'];
        }

        if (!empty($filters['movement_type'])) {
            $sql .= " AND sm.movement_type = ?";
            $params[] = $filters['movement_type'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sm.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sm.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY sm.created_at DESC";
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get stock movement count for pagination
     */
    public function getStockMovementsCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM stock_movements sm 
                WHERE sm.tenant_id = ?";
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['product_id'])) {
            $sql .= " AND sm.product_id = ?";
            $params[] = $filters['product_id'];
        }

        if (!empty($filters['branch_id'])) {
            $sql .= " AND sm.branch_id = ?";
            $params[] = $filters['branch_id'];
        }

        if (!empty($filters['movement_type'])) {
            $sql .= " AND sm.movement_type = ?";
            $params[] = $filters['movement_type'];
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(sm.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(sm.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Process damaged/expired products
     */
    public function processDamagedExpired($data)
    {
        $this->db->beginTransaction();
        
        try {
            $inventoryData = [
                'tenant_id' => $this->tenantId,
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'batch_number' => $data['batch_number'] ?? null,
                'quantity' => $data['quantity'],
                'unit_cost' => $data['unit_cost'] ?? 0,
                'reason' => $data['reason'], // 'damaged' or 'expired'
                'notes' => $data['notes'] ?? '',
                'created_at' => date('Y-m-d H:i:s')
            ];

            $inventoryId = $this->db->insert('inventory', $inventoryData);

            // Update product stock (negative quantity)
            $this->updateProductStock($data['product_id'], $data['branch_id'], -$data['quantity']);

            // Log the movement
            $this->logStockMovement([
                'tenant_id' => $this->tenantId,
                'inventory_id' => $inventoryId,
                'product_id' => $data['product_id'],
                'branch_id' => $data['branch_id'],
                'movement_type' => $data['reason'],
                'quantity' => -$data['quantity'],
                'batch_number' => $data['batch_number'] ?? null,
                'notes' => $data['notes'] ?? 'Product ' . $data['reason'],
                'created_by' => $data['created_by'] ?? null
            ]);

            $this->db->commit();
            return $inventoryId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Get inventory summary for dashboard
     */
    public function getDashboardSummary($branchId = null)
    {
        $sql = "SELECT 
                    COUNT(DISTINCT pi.product_id) as total_products,
                    SUM(pi.quantity) as total_quantity,
                    COUNT(CASE WHEN pi.quantity <= 10 THEN 1 END) as low_stock_count,
                    COUNT(CASE WHEN pi.quantity = 0 THEN 1 END) as out_of_stock_count
                FROM product_inventory pi 
                WHERE pi.tenant_id = ?";
        $params = [$this->tenantId];

        if ($branchId) {
            $sql .= " AND pi.branch_id = ?";
            $params[] = $branchId;
        }

        $summary = $this->db->fetch($sql, $params);

        // Get expiring soon count
        $expiringSql = "SELECT COUNT(DISTINCT i.product_id) as expiring_count
                       FROM inventory i 
                       WHERE i.tenant_id = ? AND i.expiry_date IS NOT NULL 
                       AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)
                       AND i.quantity > 0";
        $expiringParams = [$this->tenantId];

        if ($branchId) {
            $expiringSql .= " AND i.branch_id = ?";
            $expiringParams[] = $branchId;
        }

        $expiring = $this->db->fetch($expiringSql, $expiringParams);
        $summary['expiring_soon_count'] = $expiring['expiring_count'] ?? 0;

        return $summary;
    }

    /**
     * Get all inventory items with pagination and filters
     */
    public function getAll($filters = [], $page = 1, $limit = 20)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT 
                    i.id,
                    i.product_id,
                    p.name as product_name,
                    i.branch_id,
                    b.name as branch_name,
                    i.batch_number,
                    i.expiry_date,
                    i.quantity,
                    i.unit_cost,
                    i.supplier_id,
                    s.name as supplier_name,
                    i.purchase_date,
                    i.notes,
                    i.created_at
                FROM inventory i
                LEFT JOIN products p ON i.product_id = p.id
                LEFT JOIN branches b ON i.branch_id = b.id
                LEFT JOIN suppliers s ON i.supplier_id = s.id
                WHERE i.tenant_id = ?";
        
        $params = [$this->tenantId];
        
        // Apply filters
        if (!empty($filters['product_id'])) {
            $sql .= " AND i.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['branch_id'])) {
            $sql .= " AND i.branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        
        if (!empty($filters['supplier_id'])) {
            $sql .= " AND i.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }
        
        if (!empty($filters['low_stock'])) {
            $sql .= " AND i.quantity <= 10";
        }
        
        if (!empty($filters['out_of_stock'])) {
            $sql .= " AND i.quantity = 0";
        }
        
        if (!empty($filters['expiring_soon'])) {
            $sql .= " AND i.expiry_date IS NOT NULL AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        }
        
        $sql .= " ORDER BY i.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get total count of inventory items with filters
     */
    public function getCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM inventory i WHERE i.tenant_id = ?";
        $params = [$this->tenantId];
        
        // Apply filters
        if (!empty($filters['product_id'])) {
            $sql .= " AND i.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['branch_id'])) {
            $sql .= " AND i.branch_id = ?";
            $params[] = $filters['branch_id'];
        }
        
        if (!empty($filters['supplier_id'])) {
            $sql .= " AND i.supplier_id = ?";
            $params[] = $filters['supplier_id'];
        }
        
        if (!empty($filters['low_stock'])) {
            $sql .= " AND i.quantity <= 10";
        }
        
        if (!empty($filters['out_of_stock'])) {
            $sql .= " AND i.quantity = 0";
        }
        
        if (!empty($filters['expiring_soon'])) {
            $sql .= " AND i.expiry_date IS NOT NULL AND i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
        }
        
        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Get inventory item by ID
     */
    public function getById($id)
    {
        $sql = "SELECT 
                    i.id,
                    i.product_id,
                    p.name as product_name,
                    i.branch_id,
                    b.name as branch_name,
                    i.batch_number,
                    i.expiry_date,
                    i.quantity,
                    i.unit_cost,
                    i.supplier_id,
                    s.name as supplier_name,
                    i.purchase_date,
                    i.notes,
                    i.created_at
                FROM inventory i
                LEFT JOIN products p ON i.product_id = p.id
                LEFT JOIN branches b ON i.branch_id = b.id
                LEFT JOIN suppliers s ON i.supplier_id = s.id
                WHERE i.id = ? AND i.tenant_id = ?";
        
        return $this->db->fetch($sql, [$id, $this->tenantId]);
    }

    /**
     * Create new inventory item
     */
    public function create($data)
    {
        $sql = "INSERT INTO inventory (
                    tenant_id, product_id, branch_id, supplier_id, 
                    batch_number, expiry_date, quantity, unit_cost, 
                    purchase_date, notes, created_at
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
        
        $params = [
            $this->tenantId,
            $data['product_id'],
            $data['branch_id'],
            $data['supplier_id'] ?? null,
            $data['batch_number'] ?? null,
            $data['expiry_date'] ?? null,
            $data['quantity'],
            $data['unit_cost'] ?? null,
            $data['purchase_date'] ?? null,
            $data['notes'] ?? null
        ];
        
        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Update inventory item
     */
    public function update($id, $data)
    {
        $sql = "UPDATE inventory SET 
                    product_id = ?, branch_id = ?, supplier_id = ?, 
                    batch_number = ?, expiry_date = ?, quantity = ?, 
                    unit_cost = ?, purchase_date = ?, notes = ?, 
                    updated_at = NOW()
                WHERE id = ? AND tenant_id = ?";
        
        $params = [
            $data['product_id'],
            $data['branch_id'],
            $data['supplier_id'] ?? null,
            $data['batch_number'] ?? null,
            $data['expiry_date'] ?? null,
            $data['quantity'],
            $data['unit_cost'] ?? null,
            $data['purchase_date'] ?? null,
            $data['notes'] ?? null,
            $id,
            $this->tenantId
        ];
        
        return $this->db->execute($sql, $params);
    }

    /**
     * Delete inventory item
     */
    public function delete($id)
    {
        $sql = "DELETE FROM inventory WHERE id = ? AND tenant_id = ?";
        return $this->db->execute($sql, [$id, $this->tenantId]);
    }
}