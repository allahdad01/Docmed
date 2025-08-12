<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Prescription
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = new Database();
        $this->tenantId = $_ENV['TENANT_ID'] ?? 1;
    }

    /**
     * Generate unique prescription number
     */
    private function generatePrescriptionNumber()
    {
        $prefix = 'RX';
        $date = date('Ymd');
        $random = strtoupper(substr(md5(uniqid()), 0, 6));
        return $prefix . $date . $random;
    }

    /**
     * Get all prescriptions with pagination and filtering
     */
    public function getAll($page = 1, $limit = 50, $search = '', $doctorId = null, $patientId = null, $status = '')
    {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['p.tenant_id = ?'];
        $params = [$this->tenantId];
        
        if (!empty($search)) {
            $whereConditions[] = '(p.prescription_number LIKE ? OR p.diagnosis LIKE ? OR p.symptoms LIKE ?)';
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($doctorId) {
            $whereConditions[] = 'p.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($patientId) {
            $whereConditions[] = 'p.patient_id = ?';
            $params[] = $patientId;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'p.status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT p.*, 
                       d.name as doctor_name, d.specialty as doctor_specialty,
                       pt.name as patient_name, pt.age as patient_age, pt.phone as patient_phone
                FROM prescriptions p
                JOIN doctors d ON p.doctor_id = d.id
                JOIN patients pt ON p.patient_id = pt.id
                WHERE {$whereClause} 
                ORDER BY p.visit_date DESC, p.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get total count of prescriptions
     */
    public function getCount($search = '', $doctorId = null, $patientId = null, $status = '')
    {
        $whereConditions = ['tenant_id = ?'];
        $params = [$this->tenantId];
        
        if (!empty($search)) {
            $whereConditions[] = '(prescription_number LIKE ? OR diagnosis LIKE ? OR symptoms LIKE ?)';
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($doctorId) {
            $whereConditions[] = 'doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($patientId) {
            $whereConditions[] = 'patient_id = ?';
            $params[] = $patientId;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT COUNT(*) as count FROM prescriptions WHERE {$whereClause}";
        $result = $this->db->query($sql, $params)->fetch();
        
        return $result['count'] ?? 0;
    }

    /**
     * Get prescription by ID
     */
    public function getById($id)
    {
        $sql = "SELECT p.*, 
                       d.name as doctor_name, d.specialty as doctor_specialty, d.phone as doctor_phone,
                       d.clinic_name, d.clinic_address, d.website,
                       pt.name as patient_name, pt.age as patient_age, pt.phone as patient_phone,
                       pt.email as patient_email, pt.address as patient_address
                FROM prescriptions p
                JOIN doctors d ON p.doctor_id = d.id
                JOIN patients pt ON p.patient_id = pt.id
                WHERE p.id = ? AND p.tenant_id = ?";
        
        $result = $this->db->query($sql, [$id, $this->tenantId])->fetch();
        
        if ($result) {
            // Get prescription items
            $result['items'] = $this->getItems($id);
        }
        
        return $result ?: null;
    }

    /**
     * Get prescription items
     */
    public function getItems($prescriptionId)
    {
        $sql = "SELECT * FROM prescription_items WHERE prescription_id = ? ORDER BY id ASC";
        return $this->db->query($sql, [$prescriptionId])->fetchAll();
    }

    /**
     * Create new prescription
     */
    public function create($data)
    {
        $this->db->beginTransaction();
        
        try {
            // Create prescription
            $prescriptionNumber = $this->generatePrescriptionNumber();
            
            $sql = "INSERT INTO prescriptions (
                        tenant_id, doctor_id, patient_id, prescription_number,
                        visit_date, diagnosis, symptoms, notes, consultation_fee
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

            $params = [
                $this->tenantId,
                $data['doctor_id'],
                $data['patient_id'],
                $prescriptionNumber,
                $data['visit_date'],
                $data['diagnosis'] ?? null,
                $data['symptoms'] ?? null,
                $data['notes'] ?? null,
                $data['consultation_fee'] ?? 0.00
            ];

            $prescriptionId = $this->db->insertRaw($sql, $params);
            
            // Create prescription items
            if (!empty($data['items']) && is_array($data['items'])) {
                foreach ($data['items'] as $item) {
                    $this->createItem($prescriptionId, $item);
                }
            }
            
            $this->db->commit();
            return $prescriptionId;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Create prescription item
     */
    public function createItem($prescriptionId, $item)
    {
        $sql = "INSERT INTO prescription_items (
                    prescription_id, medicine_name, dosage_frequency, custom_frequency,
                    timing, duration_days, duration_weeks, medicine_type, injection_type,
                    dosage_instructions, additional_notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $prescriptionId,
            $item['medicine_name'],
            $item['dosage_frequency'],
            $item['custom_frequency'] ?? null,
            $item['timing'],
            $item['duration_days'] ?? null,
            $item['duration_weeks'] ?? null,
            $item['medicine_type'],
            $item['injection_type'] ?? null,
            $item['dosage_instructions'] ?? null,
            $item['additional_notes'] ?? null
        ];

        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Update prescription
     */
    public function update($id, $data)
    {
        $this->db->beginTransaction();
        
        try {
            // Update prescription
            $sql = "UPDATE prescriptions SET
                        doctor_id = ?, patient_id = ?, visit_date = ?, diagnosis = ?,
                        symptoms = ?, notes = ?, consultation_fee = ?, updated_at = NOW()
                    WHERE id = ? AND tenant_id = ?";

            $params = [
                $data['doctor_id'],
                $data['patient_id'],
                $data['visit_date'],
                $data['diagnosis'] ?? null,
                $data['symptoms'] ?? null,
                $data['notes'] ?? null,
                $data['consultation_fee'] ?? 0.00,
                $id,
                $this->tenantId
            ];

            $this->db->execute($sql, $params);
            
            // Update items if provided
            if (isset($data['items']) && is_array($data['items'])) {
                // Delete existing items
                $this->deleteItems($id);
                
                // Create new items
                foreach ($data['items'] as $item) {
                    $this->createItem($id, $item);
                }
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Delete prescription items
     */
    private function deleteItems($prescriptionId)
    {
        $sql = "DELETE FROM prescription_items WHERE prescription_id = ?";
        return $this->db->execute($sql, [$prescriptionId]);
    }

    /**
     * Delete prescription
     */
    public function delete($id)
    {
        $this->db->beginTransaction();
        
        try {
            // Delete prescription items first
            $this->deleteItems($id);
            
            // Delete prescription
            $sql = "DELETE FROM prescriptions WHERE id = ? AND tenant_id = ?";
            $this->db->execute($sql, [$id, $this->tenantId]);
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update prescription status
     */
    public function updateStatus($id, $status)
    {
        $sql = "UPDATE prescriptions SET status = ?, updated_at = NOW() WHERE id = ? AND tenant_id = ?";
        return $this->db->execute($sql, [$status, $id, $this->tenantId]);
    }

    /**
     * Get prescriptions by doctor
     */
    public function getByDoctor($doctorId, $page = 1, $limit = 50)
    {
        return $this->getAll($page, $limit, '', $doctorId, null, '');
    }

    /**
     * Get prescriptions by patient
     */
    public function getByPatient($patientId, $page = 1, $limit = 50)
    {
        return $this->getAll($page, $limit, '', null, $patientId, '');
    }

    /**
     * Get prescription statistics
     */
    public function getStats($doctorId = null, $dateFrom = null, $dateTo = null)
    {
        $whereConditions = ['p.tenant_id = ?'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'p.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($dateFrom) {
            $whereConditions[] = 'p.visit_date >= ?';
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $whereConditions[] = 'p.visit_date <= ?';
            $params[] = $dateTo;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as total_prescriptions,
                    COUNT(DISTINCT p.patient_id) as unique_patients,
                    SUM(p.consultation_fee) as total_fees,
                    AVG(p.consultation_fee) as avg_fee,
                    COUNT(DISTINCT CASE WHEN p.status = 'active' THEN p.id END) as active_prescriptions,
                    COUNT(DISTINCT CASE WHEN p.status = 'completed' THEN p.id END) as completed_prescriptions
                FROM prescriptions p 
                WHERE {$whereClause}";

        return $this->db->query($sql, $params)->fetch();
    }

    /**
     * Get most prescribed medicines
     */
    public function getMostPrescribedMedicines($doctorId = null, $limit = 10)
    {
        $whereConditions = ['pi.prescription_id IN (SELECT id FROM prescriptions WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'pi.prescription_id IN (SELECT id FROM prescriptions WHERE doctor_id = ? AND tenant_id = ?)';
            $params[] = $doctorId;
            $params[] = $this->tenantId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    pi.medicine_name,
                    COUNT(*) as prescription_count,
                    COUNT(DISTINCT pi.prescription_id) as unique_prescriptions
                FROM prescription_items pi
                WHERE {$whereClause}
                GROUP BY pi.medicine_name
                ORDER BY prescription_count DESC
                LIMIT ?";
        
        $params[] = $limit;
        
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Check if prescription exists
     */
    public function exists($prescriptionNumber, $excludeId = null)
    {
        $whereConditions = ['prescription_number = ?'];
        $params = [$prescriptionNumber];
        
        if ($excludeId) {
            $whereConditions[] = 'id != ?';
            $params[] = $excludeId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT COUNT(*) as count FROM prescriptions WHERE {$whereClause}";
        $result = $this->db->query($sql, $params)->fetch();
        
        return $result['count'] > 0;
    }
}