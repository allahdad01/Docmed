<?php

namespace PharmacySaaS\Models;

use PDO;

class Medicine {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new medicine
     */
    public function create($data) {
        $sql = "INSERT INTO medicines (
            tenant_id, name, generic_name, brand_name, medicine_type, 
            dosage_form, strength, unit, manufacturer, description, 
            active_ingredients, side_effects, contraindications, 
            storage_conditions, expiry_date, is_active, created_at
        ) VALUES (
            :tenant_id, :name, :generic_name, :brand_name, :medicine_type,
            :dosage_form, :strength, :unit, :manufacturer, :description,
            :active_ingredients, :side_effects, :contraindications,
            :storage_conditions, :expiry_date, :is_active, NOW()
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $data['tenant_id']);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':generic_name', $data['generic_name']);
        $stmt->bindParam(':brand_name', $data['brand_name']);
        $stmt->bindParam(':medicine_type', $data['medicine_type']);
        $stmt->bindParam(':dosage_form', $data['dosage_form']);
        $stmt->bindParam(':strength', $data['strength']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':manufacturer', $data['manufacturer']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':active_ingredients', $data['active_ingredients']);
        $stmt->bindParam(':side_effects', $data['side_effects']);
        $stmt->bindParam(':contraindications', $data['contraindications']);
        $stmt->bindParam(':storage_conditions', $data['storage_conditions']);
        $stmt->bindParam(':expiry_date', $data['expiry_date']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        return $stmt->execute();
    }
    
    /**
     * Get medicine by ID
     */
    public function getById($id, $tenantId) {
        $sql = "SELECT * FROM medicines WHERE id = :id AND tenant_id = :tenant_id AND is_active = 1";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all medicines with search and pagination
     */
    public function getAll($tenantId, $search = '', $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE tenant_id = :tenant_id AND is_active = 1";
        $params = [':tenant_id' => $tenantId];
        
        if (!empty($search)) {
            $whereClause .= " AND (name LIKE :search OR generic_name LIKE :search OR brand_name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $sql = "SELECT * FROM medicines {$whereClause} ORDER BY name ASC LIMIT :limit OFFSET :offset";
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
     * Get total count of medicines
     */
    public function getTotalCount($tenantId, $search = '') {
        $whereClause = "WHERE tenant_id = :tenant_id AND is_active = 1";
        $params = [':tenant_id' => $tenantId];
        
        if (!empty($search)) {
            $whereClause .= " AND (name LIKE :search OR generic_name LIKE :search OR brand_name LIKE :search)";
            $params[':search'] = "%{$search}%";
        }
        
        $sql = "SELECT COUNT(*) as total FROM medicines {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Search medicines for auto-suggestion
     */
    public function searchForSuggestion($query, $tenantId, $limit = 10) {
        $sql = "SELECT id, name, generic_name, brand_name, medicine_type, dosage_form, strength, unit 
                FROM medicines 
                WHERE tenant_id = :tenant_id AND is_active = 1 
                AND (name LIKE :query OR generic_name LIKE :query OR brand_name LIKE :query)
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
     * Get medicines by doctor (personal medicine list)
     */
    public function getByDoctor($doctorId, $tenantId) {
        $sql = "SELECT m.* FROM medicines m
                INNER JOIN doctor_medicines dm ON m.id = dm.medicine_id
                WHERE dm.doctor_id = :doctor_id AND m.tenant_id = :tenant_id AND m.is_active = 1
                ORDER BY m.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get medicines by pharmacy
     */
    public function getByPharmacy($pharmacyId, $tenantId) {
        $sql = "SELECT m.* FROM medicines m
                INNER JOIN pharmacy_medicines pm ON m.id = pm.medicine_id
                WHERE pm.pharmacy_id = :pharmacy_id AND m.tenant_id = :tenant_id AND m.is_active = 1
                ORDER BY m.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':pharmacy_id', $pharmacyId);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update medicine
     */
    public function update($id, $data, $tenantId) {
        $sql = "UPDATE medicines SET 
                name = :name, generic_name = :generic_name, brand_name = :brand_name,
                medicine_type = :medicine_type, dosage_form = :dosage_form, strength = :strength,
                unit = :unit, manufacturer = :manufacturer, description = :description,
                active_ingredients = :active_ingredients, side_effects = :side_effects,
                contraindications = :contraindications, storage_conditions = :storage_conditions,
                expiry_date = :expiry_date, is_active = :is_active, updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':name', $data['name']);
        $stmt->bindParam(':generic_name', $data['generic_name']);
        $stmt->bindParam(':brand_name', $data['brand_name']);
        $stmt->bindParam(':medicine_type', $data['medicine_type']);
        $stmt->bindParam(':dosage_form', $data['dosage_form']);
        $stmt->bindParam(':strength', $data['strength']);
        $stmt->bindParam(':unit', $data['unit']);
        $stmt->bindParam(':manufacturer', $data['manufacturer']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':active_ingredients', $data['active_ingredients']);
        $stmt->bindParam(':side_effects', $data['side_effects']);
        $stmt->bindParam(':contraindications', $data['contraindications']);
        $stmt->bindParam(':storage_conditions', $data['storage_conditions']);
        $stmt->bindParam(':expiry_date', $data['expiry_date']);
        $stmt->bindParam(':is_active', $data['is_active']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete medicine (soft delete)
     */
    public function delete($id, $tenantId) {
        $sql = "UPDATE medicines SET is_active = 0, deleted_at = NOW() WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        
        return $stmt->execute();
    }
    
    /**
     * Get medicine statistics
     */
    public function getStats($tenantId) {
        $sql = "SELECT 
                COUNT(*) as total_medicines,
                COUNT(CASE WHEN medicine_type = 'tablet' THEN 1 END) as tablets,
                COUNT(CASE WHEN medicine_type = 'capsule' THEN 1 END) as capsules,
                COUNT(CASE WHEN medicine_type = 'syrup' THEN 1 END) as syrups,
                COUNT(CASE WHEN medicine_type = 'injection' THEN 1 END) as injections,
                COUNT(CASE WHEN expiry_date < DATE_ADD(NOW(), INTERVAL 30 DAY) THEN 1 END) as expiring_soon
                FROM medicines 
                WHERE tenant_id = :tenant_id AND is_active = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}