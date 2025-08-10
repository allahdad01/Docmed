<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Sale
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getTenantId();
    }

    public function create($data)
    {
        $requiredFields = ['branch_id', 'items', 'payment_method'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        if (empty($data['items']) || !is_array($data['items'])) {
            throw new \Exception("Sale items are required");
        }

        $this->db->beginTransaction();

        try {
            // Calculate totals
            $subtotal = 0;
            $taxAmount = 0;
            $discountAmount = $data['discount_amount'] ?? 0;

            foreach ($data['items'] as $item) {
                $product = $this->getProductWithInventory($item['product_id'], $data['branch_id']);
                if (!$product) {
                    throw new \Exception("Product not found or out of stock");
                }

                $itemTotal = $item['quantity'] * $item['unit_price'];
                $subtotal += $itemTotal;
                
                // Calculate tax
                $taxAmount += ($itemTotal * $product['tax_rate'] / 100);
            }

            $totalAmount = $subtotal + $taxAmount - $discountAmount;

            // Generate invoice number
            $invoiceNumber = $this->generateInvoiceNumber($data['branch_id']);

            // Create sale record
            $saleData = [
                'tenant_id' => $this->tenantId,
                'branch_id' => $data['branch_id'],
                'customer_id' => $data['customer_id'] ?? null,
                'invoice_number' => $invoiceNumber,
                'subtotal' => $subtotal,
                'tax_amount' => $taxAmount,
                'discount_amount' => $discountAmount,
                'total_amount' => $totalAmount,
                'payment_method' => $data['payment_method'],
                'payment_status' => $data['payment_method'] === 'credit' ? 'pending' : 'paid',
                'notes' => $data['notes'] ?? null,
                'created_by' => $data['created_by']
            ];

            $saleId = $this->db->insert('sales', $saleData);

            // Create sale items and update inventory
            foreach ($data['items'] as $item) {
                $this->createSaleItem($saleId, $item, $data['branch_id']);
            }

            // Update customer loyalty points if applicable
            if (!empty($data['customer_id'])) {
                $this->updateCustomerLoyalty($data['customer_id'], $totalAmount);
            }

            $this->db->commit();
            return $saleId;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function createSaleItem($saleId, $item, $branchId)
    {
        // Get product details
        $product = $this->getProductWithInventory($item['product_id'], $branchId);
        
        $saleItemData = [
            'sale_id' => $saleId,
            'product_id' => $item['product_id'],
            'batch_number' => $item['batch_number'] ?? null,
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_price' => $item['quantity'] * $item['unit_price'],
            'discount_amount' => $item['discount_amount'] ?? 0
        ];

        $this->db->insert('sale_items', $saleItemData);

        // Update inventory
        $this->updateInventory($item['product_id'], $branchId, $item['quantity'], $item['batch_number']);
    }

    private function updateInventory($productId, $branchId, $quantity, $batchNumber = null)
    {
        $where = [
            'product_id = :product_id',
            'branch_id = :branch_id',
            'quantity >= :quantity'
        ];
        $params = [
            'product_id' => $productId,
            'branch_id' => $branchId,
            'quantity' => $quantity
        ];

        if ($batchNumber) {
            $where[] = 'batch_number = :batch_number';
            $params['batch_number'] = $batchNumber;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "UPDATE inventory SET quantity = quantity - :quantity WHERE {$whereClause}";
        $this->db->query($sql, $params);
    }

    private function getProductWithInventory($productId, $branchId)
    {
        $sql = "SELECT p.*, i.quantity, i.batch_number, i.expiry_date 
                FROM products p 
                JOIN inventory i ON p.id = i.product_id 
                WHERE p.id = :product_id AND i.branch_id = :branch_id 
                AND p.is_active = 1 AND i.quantity > 0 
                ORDER BY i.expiry_date ASC 
                LIMIT 1";

        return $this->db->fetch($sql, [
            'product_id' => $productId,
            'branch_id' => $branchId
        ]);
    }

    private function generateInvoiceNumber($branchId)
    {
        $prefix = 'INV';
        $branchCode = strtoupper(substr($branchId, -2));
        $date = date('Ymd');
        
        // Get today's count
        $sql = "SELECT COUNT(*) as count FROM sales 
                WHERE branch_id = :branch_id 
                AND DATE(sale_date) = CURDATE()";
        
        $result = $this->db->fetch($sql, ['branch_id' => $branchId]);
        $count = $result['count'] + 1;
        
        return $prefix . $branchCode . $date . str_pad($count, 4, '0', STR_PAD_LEFT);
    }

    private function updateCustomerLoyalty($customerId, $amount)
    {
        // Award 1 point per $1 spent
        $points = floor($amount);
        
        $sql = "UPDATE customers SET loyalty_points = loyalty_points + :points 
                WHERE id = :customer_id AND tenant_id = :tenant_id";
        
        $this->db->query($sql, [
            'points' => $points,
            'customer_id' => $customerId,
            'tenant_id' => $this->tenantId
        ]);
    }

    public function getById($saleId)
    {
        $sql = "SELECT s.*, c.first_name, c.last_name, c.phone, b.name as branch_name, u.first_name as cashier_name 
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
                JOIN branches b ON s.branch_id = b.id 
                JOIN users u ON s.created_by = u.id 
                WHERE s.id = :id AND s.tenant_id = :tenant_id";

        $sale = $this->db->fetch($sql, [
            'id' => $saleId,
            'tenant_id' => $this->tenantId
        ]);

        if ($sale) {
            $sale['items'] = $this->getSaleItems($saleId);
        }

        return $sale;
    }

    public function getSaleItems($saleId)
    {
        $sql = "SELECT si.*, p.name, p.barcode, p.sku, p.type 
                FROM sale_items si 
                JOIN products p ON si.product_id = p.id 
                WHERE si.sale_id = :sale_id 
                ORDER BY si.id";

        return $this->db->fetchAll($sql, ['sale_id' => $saleId]);
    }

    public function getAll($filters = [], $page = 1, $limit = 20)
    {
        $where = ['s.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $this->tenantId];

        if (!empty($filters['branch_id'])) {
            $where[] = 's.branch_id = :branch_id';
            $params['branch_id'] = $filters['branch_id'];
        }

        if (!empty($filters['customer_id'])) {
            $where[] = 's.customer_id = :customer_id';
            $params['customer_id'] = $filters['customer_id'];
        }

        if (!empty($filters['payment_method'])) {
            $where[] = 's.payment_method = :payment_method';
            $params['payment_method'] = $filters['payment_method'];
        }

        if (!empty($filters['status'])) {
            $where[] = 's.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(s.sale_date) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(s.sale_date) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        $whereClause = implode(' AND ', $where);
        
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT s.*, c.first_name, c.last_name, b.name as branch_name, u.first_name as cashier_name 
                FROM sales s 
                LEFT JOIN customers c ON s.customer_id = c.id 
                JOIN branches b ON s.branch_id = b.id 
                JOIN users u ON s.created_by = u.id 
                WHERE {$whereClause} 
                ORDER BY s.sale_date DESC 
                LIMIT :limit OFFSET :offset";
        
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function getDailySales($branchId = null, $date = null)
    {
        $date = $date ?: date('Y-m-d');
        $where = ['s.tenant_id = :tenant_id', 'DATE(s.sale_date) = :date'];
        $params = ['tenant_id' => $this->tenantId, 'date' => $date];

        if ($branchId) {
            $where[] = 's.branch_id = :branch_id';
            $params['branch_id'] = $branchId;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT 
                    COUNT(*) as total_sales,
                    SUM(s.total_amount) as total_revenue,
                    SUM(s.tax_amount) as total_tax,
                    SUM(s.discount_amount) as total_discount,
                    AVG(s.total_amount) as average_sale
                FROM sales s 
                WHERE {$whereClause} AND s.status = 'completed'";

        return $this->db->fetch($sql, $params);
    }

    public function getSalesByPeriod($period = 'month', $branchId = null)
    {
        $where = ['s.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $this->tenantId];

        if ($branchId) {
            $where[] = 's.branch_id = :branch_id';
            $params['branch_id'] = $branchId;
        }

        switch ($period) {
            case 'week':
                $where[] = 'YEARWEEK(s.sale_date) = YEARWEEK(CURDATE())';
                break;
            case 'month':
                $where[] = 'MONTH(s.sale_date) = MONTH(CURDATE()) AND YEAR(s.sale_date) = YEAR(CURDATE())';
                break;
            case 'year':
                $where[] = 'YEAR(s.sale_date) = YEAR(CURDATE())';
                break;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT 
                    DATE(s.sale_date) as sale_date,
                    COUNT(*) as total_sales,
                    SUM(s.total_amount) as total_revenue
                FROM sales s 
                WHERE {$whereClause} AND s.status = 'completed'
                GROUP BY DATE(s.sale_date)
                ORDER BY sale_date";

        return $this->db->fetchAll($sql, $params);
    }

    public function processReturn($saleId, $returnItems, $reason)
    {
        $this->db->beginTransaction();

        try {
            // Update sale status
            $this->db->update('sales', 
                ['status' => 'returned'], 
                'id = :id AND tenant_id = :tenant_id', 
                ['id' => $saleId, 'tenant_id' => $this->tenantId]
            );

            // Process return items
            foreach ($returnItems as $item) {
                $this->processReturnItem($saleId, $item);
            }

            $this->db->commit();
            return true;

        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    private function processReturnItem($saleId, $item)
    {
        // Get original sale item
        $saleItem = $this->db->fetch(
            "SELECT * FROM sale_items WHERE id = :id AND sale_id = :sale_id",
            ['id' => $item['sale_item_id'], 'sale_id' => $saleId]
        );

        if (!$saleItem) {
            throw new \Exception("Sale item not found");
        }

        // Return quantity to inventory
        $sql = "UPDATE inventory SET quantity = quantity + :quantity 
                WHERE product_id = :product_id AND branch_id = :branch_id";
        
        $this->db->query($sql, [
            'quantity' => $item['return_quantity'],
            'product_id' => $saleItem['product_id'],
            'branch_id' => $item['branch_id']
        ]);
    }

    public function getCount($filters = [])
    {
        $where = ['tenant_id = :tenant_id'];
        $params = ['tenant_id' => $this->tenantId];

        if (!empty($filters['branch_id'])) {
            $where[] = 'branch_id = :branch_id';
            $params['branch_id'] = $filters['branch_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) as count FROM sales WHERE {$whereClause}";
        $result = $this->db->fetch($sql, $params);

        return $result['count'];
    }
}