<?php

namespace PharmacySaaS\Models;

use PDO;

class LaboratoryTest {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new laboratory test
     */
    public function create($data) {
        $sql = "INSERT INTO laboratory_tests (
            tenant_id, name, test_code, category, description, 
            preparation_instructions, normal_range, unit, price, 
            commission_amount, commission_type, is_active, created_at
        ) VALUES (
            :tenant_id, :name, :test_code, :category, :description,
            :preparation_instructions, :normal_range, :unit, :price,
            :commission_amount, :commission_type, :is_active, NOW()
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $data['tenant_id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':test_code', $data['test_code']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':preparation_instructions', $data['preparation_instructions']);
        $stmt->bindParam(':normal_range', $data['normal_range']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':commission_amount', $data['commission_amount']);
        $stmt->bindParam(':commission_type', $data['commission_type']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        return $stmt->execute();
    }
    
    /**
     * Get test by ID
     */
    public function getById($id, $tenantId) {
        $sql = "SELECT * FROM laboratory_tests WHERE id = :id AND tenant_id = :tenant_id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all tests with search and pagination
     */
    public function getAll($tenantId, $search = '', $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE tenant_id = :tenant_id AND is_active = 1";
        $params = [':tenant_id' => $tenantId];
        
        if (!empty($search)) {
            $whereClause .= " AND (name LIKE :search OR test_code LIKE :search OR category LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $sql = "SELECT * FROM laboratory_tests {$whereClause} ORDER BY name ASC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get total count of tests
     */
    public function getTotalCount($tenantId, $search = '') {
        $whereClause = "WHERE tenant_id = :tenant_id AND is_active = 1";
        $params = [':tenant_id' => $tenantId];
        
        if (!empty($search)) {
            $whereClause .= " AND (name LIKE :search OR test_code LIKE :search OR category LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $sql = "SELECT COUNT(*) as total FROM laboratory_tests {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Search tests for auto-suggestion
     */
    public function searchForSuggestion($query, $tenantId, $limit = 10) {
        $sql = "SELECT id, name, test_code, category, price, commission_amount, commission_type 
                FROM laboratory_tests 
                WHERE tenant_id = :tenant_id AND is_active = 1 
                AND (name LIKE :query OR test_code LIKE :query OR category LIKE :query)
                ORDER BY 
                    CASE 
                        WHEN name LIKE :exact_query THEN 1
                        WHEN name LIKE :start_query THEN 2
                        ELSE 3
                    END,
                    name ASC
                LIMIT :limit";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':tenant_id', $tenantId);
        $stmt->bindValue(':query', "%{$query}%");
        $stmt->bindValue(':exact_query', $query);
        $stmt->bindValue(':start_query', "{$query}%");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get tests by category
     */
    public function getByCategory($category, $tenantId) {
        $sql = "SELECT * FROM laboratory_tests 
                WHERE category = :category AND tenant_id = :tenant_id AND is_active = 1 
                ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':category', $category);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get test categories
     */
    public function getCategories($tenantId) {
        $sql = "SELECT DISTINCT category FROM laboratory_tests 
                WHERE tenant_id = :tenant_id AND is_active = 1 
                ORDER BY category ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Update test
     */
    public function update($id, $data, $tenantId) {
        $sql = "UPDATE laboratory_tests SET 
                name = :name, test_code = :test_code, category = :category, 
                description = :description, preparation_instructions = :preparation_instructions,
                normal_range = :normal_range, unit = :unit, price = :price,
                commission_amount = :commission_amount, commission_type = :commission_type,
                is_active = :is_active, updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':test_code', $data['test_code']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':preparation_instructions', $data['preparation_instructions']);
        $stmt->bindParam(':normal_range', $data['normal_range']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':price', $data['price']);
        $stmt->bindParam(':commission_amount', $data['commission_amount']);
        $stmt->bindParam(':commission_type', $data['commission_type']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete test (soft delete)
     */
    public function delete($id, $tenantId) {
        $sql = "UPDATE laboratory_tests SET is_active = 0, deleted_at = NOW() WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        
        return $stmt->execute();
    }
    
    /**
     * Get test statistics
     */
    public function getStats($tenantId) {
        $sql = "SELECT 
                COUNT(*) as total_tests,
                COUNT(CASE WHEN commission_type = 'percentage' THEN 1 END) as percentage_commission,
                COUNT(CASE WHEN commission_type = 'fixed' THEN 1 END) as fixed_commission,
                AVG(price) as avg_price,
                AVG(commission_amount) as avg_commission
                FROM laboratory_tests 
                WHERE tenant_id = :tenant_id AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}