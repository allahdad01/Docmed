<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Product
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
        $requiredFields = ['name', 'category_id', 'selling_price'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw new \Exception("Missing required field: {$field}");
            }
        }

        $productData = [
            'tenant_id' => $this->tenantId,
            'name' => $data['name'],
            'name_ar' => $data['name_ar'] ?? null,
            'name_ps' => $data['name_ps'] ?? null,
            'name_dr' => $data['name_dr'] ?? null,
            'category_id' => $data['category_id'],
            'description' => $data['description'] ?? null,
            'strength' => $data['strength'] ?? null,
            'brand' => $data['brand'] ?? null,
            'type' => $data['type'] ?? 'other',
            'composition' => $data['composition'] ?? null,
            'barcode' => $data['barcode'] ?? $this->generateBarcode(),
            'sku' => $data['sku'] ?? $this->generateSKU(),
            'unit' => $data['unit'] ?? 'piece',
            'purchase_price' => $data['purchase_price'] ?? 0.00,
            'selling_price' => $data['selling_price'],
            'retail_price' => $data['retail_price'] ?? $data['selling_price'],
            'wholesale_price' => $data['wholesale_price'] ?? $data['selling_price'],
            'tax_rate' => $data['tax_rate'] ?? 0.00,
            'requires_prescription' => $data['requires_prescription'] ?? false,
            'is_active' => true
        ];

        $productId = $this->db->insert('products', $productData);

        // Handle images if provided
        if (!empty($data['images'])) {
            $this->addImages($productId, $data['images']);
        }

        return $productId;
    }

    public function update($productId, $data)
    {
        $allowedFields = [
            'name', 'name_ar', 'name_ps', 'name_dr', 'category_id', 'description',
            'strength', 'brand', 'type', 'composition', 'barcode', 'sku', 'unit',
            'purchase_price', 'selling_price', 'retail_price', 'wholesale_price',
            'tax_rate', 'requires_prescription', 'is_active'
        ];

        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        if (empty($updateData)) {
            return false;
        }

        return $this->db->update('products', $updateData, 'id = :id AND tenant_id = :tenant_id', [
            'id' => $productId,
            'tenant_id' => $this->tenantId
        ]);
    }

    public function delete($productId)
    {
        // Check if product has inventory or sales
        $hasInventory = $this->db->fetch(
            "SELECT COUNT(*) as count FROM inventory WHERE product_id = :product_id",
            ['product_id' => $productId]
        );

        $hasSales = $this->db->fetch(
            "SELECT COUNT(*) as count FROM sale_items WHERE product_id = :product_id",
            ['product_id' => $productId]
        );

        if ($hasInventory['count'] > 0 || $hasSales['count'] > 0) {
            // Soft delete instead
            return $this->db->update('products', 
                ['is_active' => false], 
                'id = :id AND tenant_id = :tenant_id', 
                ['id' => $productId, 'tenant_id' => $this->tenantId]
            );
        }

        return $this->db->delete('products', 'id = :id AND tenant_id = :tenant_id', [
            'id' => $productId,
            'tenant_id' => $this->tenantId
        ]);
    }

    public function getById($productId)
    {
        $sql = "SELECT p.*, c.name as category_name, c.name_ar as category_name_ar, 
                       c.name_ps as category_name_ps, c.name_dr as category_name_dr
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.id = :id AND p.tenant_id = :tenant_id";
        
        return $this->db->fetch($sql, [
            'id' => $productId,
            'tenant_id' => $this->tenantId
        ]);
    }

    public function getAll($filters = [], $page = 1, $limit = 20)
    {
        $where = ['p.tenant_id = :tenant_id'];
        $params = ['tenant_id' => $this->tenantId];

        if (!empty($filters['category_id'])) {
            $where[] = 'p.category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :search OR p.barcode LIKE :search OR p.sku LIKE :search)';
            $params['search'] = '%' . $filters['search'] . '%';
        }

        if (isset($filters['is_active'])) {
            $where[] = 'p.is_active = :is_active';
            $params['is_active'] = $filters['is_active'];
        }

        if (!empty($filters['type'])) {
            $where[] = 'p.type = :type';
            $params['type'] = $filters['type'];
        }

        $whereClause = implode(' AND ', $where);
        
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE {$whereClause} 
                ORDER BY p.name 
                LIMIT :limit OFFSET :offset";
        
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    public function getByBarcode($barcode)
    {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.barcode = :barcode AND p.tenant_id = :tenant_id AND p.is_active = 1";
        
        return $this->db->fetch($sql, [
            'barcode' => $barcode,
            'tenant_id' => $this->tenantId
        ]);
    }

    public function getLowStock($branchId = null)
    {
        $where = ['p.tenant_id = :tenant_id', 'i.quantity <= i.reorder_level'];
        $params = ['tenant_id' => $this->tenantId];

        if ($branchId) {
            $where[] = 'i.branch_id = :branch_id';
            $params['branch_id'] = $branchId;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT p.*, i.quantity, i.reorder_level, i.branch_id, b.name as branch_name 
                FROM products p 
                JOIN inventory i ON p.id = i.product_id 
                JOIN branches b ON i.branch_id = b.id 
                WHERE {$whereClause} 
                ORDER BY i.quantity ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getExpiringSoon($days = 30, $branchId = null)
    {
        $where = [
            'p.tenant_id = :tenant_id',
            'i.expiry_date <= DATE_ADD(CURDATE(), INTERVAL :days DAY)',
            'i.expiry_date >= CURDATE()',
            'i.quantity > 0'
        ];
        $params = [
            'tenant_id' => $this->tenantId,
            'days' => $days
        ];

        if ($branchId) {
            $where[] = 'i.branch_id = :branch_id';
            $params['branch_id'] = $branchId;
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT p.*, i.quantity, i.expiry_date, i.branch_id, b.name as branch_name 
                FROM products p 
                JOIN inventory i ON p.id = i.product_id 
                JOIN branches b ON i.branch_id = b.id 
                WHERE {$whereClause} 
                ORDER BY i.expiry_date ASC";

        return $this->db->fetchAll($sql, $params);
    }

    public function getTopSelling($limit = 10, $period = 'month')
    {
        $dateFilter = '';
        $params = ['tenant_id' => $this->tenantId, 'limit' => $limit];

        switch ($period) {
            case 'week':
                $dateFilter = 'AND YEARWEEK(s.sale_date) = YEARWEEK(CURDATE())';
                break;
            case 'month':
                $dateFilter = 'AND MONTH(s.sale_date) = MONTH(CURDATE()) AND YEAR(s.sale_date) = YEAR(CURDATE())';
                break;
            case 'year':
                $dateFilter = 'AND YEAR(s.sale_date) = YEAR(CURDATE())';
                break;
        }

        $sql = "SELECT p.id, p.name, p.selling_price, 
                       SUM(si.quantity) as total_quantity,
                       SUM(si.total_price) as total_revenue
                FROM products p 
                JOIN sale_items si ON p.id = si.product_id 
                JOIN sales s ON si.sale_id = s.id 
                WHERE s.tenant_id = :tenant_id {$dateFilter}
                GROUP BY p.id 
                ORDER BY total_quantity DESC 
                LIMIT :limit";

        return $this->db->fetchAll($sql, $params);
    }

    public function addImages($productId, $images)
    {
        foreach ($images as $index => $image) {
            $imageData = [
                'product_id' => $productId,
                'image_url' => $image,
                'is_primary' => $index === 0,
                'sort_order' => $index
            ];
            $this->db->insert('product_images', $imageData);
        }
    }

    public function removeImage($imageId)
    {
        return $this->db->delete('product_images', 'id = :id', ['id' => $imageId]);
    }

    private function generateBarcode()
    {
        // Generate unique barcode
        do {
            $barcode = 'P' . str_pad(mt_rand(1, 999999), 6, '0', STR_PAD_LEFT);
            $exists = $this->db->fetch(
                "SELECT id FROM products WHERE barcode = :barcode",
                ['barcode' => $barcode]
            );
        } while ($exists);

        return $barcode;
    }

    private function generateSKU()
    {
        // Generate unique SKU
        do {
            $sku = 'SKU' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);
            $exists = $this->db->fetch(
                "SELECT id FROM products WHERE sku = :sku",
                ['sku' => $sku]
            );
        } while ($exists);

        return $sku;
    }

    public function getCount($filters = [])
    {
        $where = ['tenant_id = :tenant_id'];
        $params = ['tenant_id' => $this->tenantId];

        if (!empty($filters['category_id'])) {
            $where[] = 'category_id = :category_id';
            $params['category_id'] = $filters['category_id'];
        }

        if (isset($filters['is_active'])) {
            $where[] = 'is_active = :is_active';
            $params['is_active'] = $filters['is_active'];
        }

        $whereClause = implode(' AND ', $where);

        $sql = "SELECT COUNT(*) as count FROM products WHERE {$whereClause}";
        $result = $this->db->fetch($sql, $params);

        return $result['count'];
    }
}