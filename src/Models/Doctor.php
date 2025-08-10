<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Doctor
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = new Database();
        $this->tenantId = $_ENV['TENANT_ID'] ?? 1;
    }

    /**
     * Get all doctors with pagination and filtering
     */
    public function getAll($page = 1, $limit = 50, $search = '', $status = '')
    {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['tenant_id = ?'];
        $params = [$this->tenantId];
        
        if (!empty($search)) {
            $whereConditions[] = '(name LIKE ? OR specialty LIKE ? OR clinic_name LIKE ?)';
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT * FROM doctors WHERE {$whereClause} ORDER BY name ASC LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get total count of doctors
     */
    public function getCount($search = '', $status = '')
    {
        $whereConditions = ['tenant_id = ?'];
        $params = [$this->tenantId];
        
        if (!empty($search)) {
            $whereConditions[] = '(name LIKE ? OR specialty LIKE ? OR clinic_name LIKE ?)';
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT COUNT(*) as count FROM doctors WHERE {$whereClause}";
        $result = $this->db->query($sql, $params)->fetch();
        
        return $result['count'] ?? 0;
    }

    /**
     * Get doctor by ID
     */
    public function getById($id)
    {
        $sql = "SELECT * FROM doctors WHERE id = ? AND tenant_id = ?";
        $result = $this->db->query($sql, [$id, $this->tenantId])->fetch();
        
        return $result ?: null;
    }

    /**
     * Create new doctor
     */
    public function create($data)
    {
        $sql = "INSERT INTO doctors (
                    tenant_id, name, phone, specialty, clinic_name,
                    clinic_address, website, consultation_fee, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $this->tenantId,
            $data['name'],
            $data['phone'] ?? null,
            $data['specialty'] ?? null,
            $data['clinic_name'] ?? null,
            $data['clinic_address'] ?? null,
            $data['website'] ?? null,
            $data['consultation_fee'] ?? 0.00,
            $data['status'] ?? 'active'
        ];

        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Update doctor
     */
    public function update($id, $data)
    {
        $sql = "UPDATE doctors SET
                    name = ?, phone = ?, specialty = ?, clinic_name = ?,
                    clinic_address = ?, website = ?, consultation_fee = ?,
                    status = ?, updated_at = NOW()
                WHERE id = ? AND tenant_id = ?";

        $params = [
            $data['name'],
            $data['phone'] ?? null,
            $data['specialty'] ?? null,
            $data['clinic_name'] ?? null,
            $data['clinic_address'] ?? null,
            $data['website'] ?? null,
            $data['consultation_fee'] ?? 0.00,
            $data['status'] ?? 'active',
            $id,
            $this->tenantId
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Delete doctor
     */
    public function delete($id)
    {
        // Check if doctor has associated prescriptions
        $sql = "SELECT COUNT(*) as count FROM prescriptions WHERE doctor_id = ? AND tenant_id = ?";
        $result = $this->db->query($sql, [$id, $this->tenantId])->fetch();
        
        if ($result['count'] > 0) {
            throw new \Exception('Cannot delete doctor with existing prescriptions');
        }

        $sql = "DELETE FROM doctors WHERE id = ? AND tenant_id = ?";
        return $this->db->execute($sql, [$id, $this->tenantId]);
    }

    /**
     * Get active doctors for dropdowns
     */
    public function getActive()
    {
        $sql = "SELECT id, name, specialty FROM doctors WHERE status = 'active' AND tenant_id = ? ORDER BY name ASC";
        return $this->db->query($sql, [$this->tenantId])->fetchAll();
    }

    /**
     * Get doctor statistics
     */
    public function getStats($doctorId = null)
    {
        $whereClause = 'tenant_id = ?';
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereClause .= ' AND doctor_id = ?';
            $params[] = $doctorId;
        }

        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as total_prescriptions,
                    COUNT(DISTINCT p.patient_id) as total_patients,
                    SUM(p.consultation_fee) as total_fees,
                    AVG(p.consultation_fee) as avg_fee
                FROM prescriptions p 
                WHERE {$whereClause}";

        return $this->db->query($sql, $params)->fetch();
    }

    /**
     * Get doctor's personal medicine list
     */
    public function getPersonalMedicines($doctorId)
    {
        $sql = "SELECT * FROM doctor_medicines WHERE doctor_id = ? ORDER BY medicine_name ASC";
        return $this->db->query($sql, [$doctorId])->fetchAll();
    }

    /**
     * Add medicine to doctor's personal list
     */
    public function addPersonalMedicine($doctorId, $data)
    {
        $sql = "INSERT INTO doctor_medicines (
                    doctor_id, medicine_name, dosage_frequency, timing,
                    duration, medicine_type, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $doctorId,
            $data['medicine_name'],
            $data['dosage_frequency'] ?? null,
            $data['timing'] ?? null,
            $data['duration'] ?? null,
            $data['medicine_type'] ?? null,
            $data['notes'] ?? null
        ];

        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Remove medicine from doctor's personal list
     */
    public function removePersonalMedicine($medicineId, $doctorId)
    {
        $sql = "DELETE FROM doctor_medicines WHERE id = ? AND doctor_id = ?";
        return $this->db->execute($sql, [$medicineId, $doctorId]);
    }

    /**
     * Get doctor's partner pharmacies
     */
    public function getPartnerPharmacies($doctorId)
    {
        $sql = "SELECT dp.*, b.name as pharmacy_name, b.address as pharmacy_address
                FROM doctor_pharmacies dp
                JOIN branches b ON dp.pharmacy_id = b.id
                WHERE dp.doctor_id = ? AND dp.status = 'active'
                ORDER BY b.name ASC";
        
        return $this->db->query($sql, [$doctorId])->fetchAll();
    }

    /**
     * Get doctor's partner laboratories
     */
    public function getPartnerLabs($doctorId)
    {
        $sql = "SELECT * FROM doctor_labs 
                WHERE doctor_id = ? AND status = 'active'
                ORDER BY lab_name ASC";
        
        return $this->db->query($sql, [$doctorId])->fetchAll();
    }

    /**
     * Search medicines (personal + pharmacy)
     */
    public function searchMedicines($doctorId, $query, $limit = 10)
    {
        // Search personal medicines
        $personalSql = "SELECT 
                            medicine_name, 'personal' as source, dosage_frequency, timing, duration, medicine_type
                        FROM doctor_medicines 
                        WHERE doctor_id = ? AND medicine_name LIKE ?";
        
        $personalMedicines = $this->db->query($personalSql, [$doctorId, "%{$query}%"])->fetchAll();
        
        // Search pharmacy medicines (if linked)
        $pharmacySql = "SELECT 
                            p.name as medicine_name, 'pharmacy' as source, 
                            p.description, p.category_id
                        FROM products p
                        JOIN doctor_pharmacies dp ON dp.pharmacy_id = p.branch_id
                        WHERE dp.doctor_id = ? AND p.name LIKE ? AND p.status = 'active'
                        LIMIT ?";
        
        $pharmacyMedicines = $this->db->query($pharmacySql, [$doctorId, "%{$query}%", $limit])->fetchAll();
        
        return [
            'personal' => $personalMedicines,
            'pharmacy' => $pharmacyMedicines
        ];
    }
}