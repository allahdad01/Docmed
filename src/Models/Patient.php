<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Patient
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = new Database();
        $this->tenantId = $_ENV['TENANT_ID'] ?? 1;
    }

    /**
     * Get all patients with pagination and filtering
     */
    public function getAll($page = 1, $limit = 50, $search = '', $doctorId = null)
    {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['p.tenant_id = ?'];
        $params = [$this->tenantId];
        
        if (!empty($search)) {
            $whereConditions[] = '(p.name LIKE ? OR p.phone LIKE ? OR p.email LIKE ?)';
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($doctorId) {
            $whereConditions[] = 'p.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT p.*, d.name as doctor_name, d.specialty as doctor_specialty
                FROM patients p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                WHERE {$whereClause} 
                ORDER BY p.name ASC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get total count of patients
     */
    public function getCount($search = '', $doctorId = null)
    {
        $whereConditions = ['tenant_id = ?'];
        $params = [$this->tenantId];
        
        if (!empty($search)) {
            $whereConditions[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($doctorId) {
            $whereConditions[] = 'doctor_id = ?';
            $params[] = $doctorId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT COUNT(*) as count FROM patients WHERE {$whereClause}";
        $result = $this->db->query($sql, $params)->fetch();
        
        return $result['count'] ?? 0;
    }

    /**
     * Get patient by ID
     */
    public function getById($id)
    {
        $sql = "SELECT p.*, d.name as doctor_name, d.specialty as doctor_specialty
                FROM patients p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                WHERE p.id = ? AND p.tenant_id = ?";
        
        $result = $this->db->query($sql, [$id, $this->tenantId])->fetch();
        
        return $result ?: null;
    }

    /**
     * Create new patient
     */
    public function create($data)
    {
        $sql = "INSERT INTO patients (
                    tenant_id, doctor_id, name, age, phone, email,
                    address, emergency_contact, medical_history
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

        $params = [
            $this->tenantId,
            $data['doctor_id'],
            $data['name'],
            $data['age'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['emergency_contact'] ?? null,
            $data['medical_history'] ?? null
        ];

        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Update patient
     */
    public function update($id, $data)
    {
        $sql = "UPDATE patients SET
                    doctor_id = ?, name = ?, age = ?, phone = ?, email = ?,
                    address = ?, emergency_contact = ?, medical_history = ?,
                    updated_at = NOW()
                WHERE id = ? AND tenant_id = ?";

        $params = [
            $data['doctor_id'],
            $data['name'],
            $data['age'] ?? null,
            $data['phone'] ?? null,
            $data['email'] ?? null,
            $data['address'] ?? null,
            $data['emergency_contact'] ?? null,
            $data['medical_history'] ?? null,
            $id,
            $this->tenantId
        ];

        return $this->db->execute($sql, $params);
    }

    /**
     * Delete patient
     */
    public function delete($id)
    {
        // Check if patient has associated prescriptions
        $sql = "SELECT COUNT(*) as count FROM prescriptions WHERE patient_id = ? AND tenant_id = ?";
        $result = $this->db->query($sql, [$id, $this->tenantId])->fetch();
        
        if ($result['count'] > 0) {
            throw new \Exception('Cannot delete patient with existing prescriptions');
        }

        $sql = "DELETE FROM patients WHERE id = ? AND tenant_id = ?";
        return $this->db->execute($sql, [$id, $this->tenantId]);
    }

    /**
     * Get patients by doctor
     */
    public function getByDoctor($doctorId, $page = 1, $limit = 50)
    {
        $offset = ($page - 1) * $limit;
        
        $sql = "SELECT * FROM patients 
                WHERE doctor_id = ? AND tenant_id = ? 
                ORDER BY name ASC 
                LIMIT ? OFFSET ?";
        
        return $this->db->query($sql, [$doctorId, $this->tenantId, $limit, $offset])->fetchAll();
    }

    /**
     * Search patients
     */
    public function search($query, $doctorId = null, $limit = 10)
    {
        $whereConditions = ['tenant_id = ?'];
        $params = [$this->tenantId];
        
        if (!empty($query)) {
            $whereConditions[] = '(name LIKE ? OR phone LIKE ? OR email LIKE ?)';
            $searchParam = "%{$query}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        if ($doctorId) {
            $whereConditions[] = 'doctor_id = ?';
            $params[] = $doctorId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT id, name, age, phone, email 
                FROM patients 
                WHERE {$whereClause} 
                ORDER BY name ASC 
                LIMIT ?";
        
        $params[] = $limit;
        
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get patient medical history
     */
    public function getMedicalHistory($patientId)
    {
        $sql = "SELECT p.*, pr.prescription_number, pr.visit_date, pr.diagnosis, pr.symptoms,
                       d.name as doctor_name, d.specialty as doctor_specialty
                FROM prescriptions pr
                JOIN patients p ON pr.patient_id = p.id
                JOIN doctors d ON pr.doctor_id = d.id
                WHERE pr.patient_id = ? AND pr.tenant_id = ?
                ORDER BY pr.visit_date DESC";
        
        return $this->db->query($sql, [$patientId, $this->tenantId])->fetchAll();
    }

    /**
     * Get patient statistics
     */
    public function getStats($patientId = null)
    {
        $whereClause = 'p.tenant_id = ?';
        $params = [$this->tenantId];
        
        if ($patientId) {
            $whereClause .= ' AND p.id = ?';
            $params[] = $patientId;
        }

        $sql = "SELECT 
                    COUNT(DISTINCT p.id) as total_patients,
                    COUNT(DISTINCT pr.id) as total_prescriptions,
                    AVG(p.age) as avg_age,
                    COUNT(DISTINCT CASE WHEN p.emergency_contact IS NOT NULL THEN p.id END) as patients_with_emergency_contact
                FROM patients p
                LEFT JOIN prescriptions pr ON p.id = pr.patient_id
                WHERE {$whereClause}";

        return $this->db->query($sql, $params)->fetch();
    }

    /**
     * Check if patient exists
     */
    public function exists($phone, $email = null, $excludeId = null)
    {
        $whereConditions = ['tenant_id = ?'];
        $params = [$this->tenantId];
        
        if ($phone) {
            $whereConditions[] = 'phone = ?';
            $params[] = $phone;
        }
        
        if ($email) {
            $whereConditions[] = 'email = ?';
            $params[] = $email;
        }
        
        if ($excludeId) {
            $whereConditions[] = 'id != ?';
            $params[] = $excludeId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT COUNT(*) as count FROM patients WHERE {$whereClause}";
        $result = $this->db->query($sql, $params)->fetch();
        
        return $result['count'] > 0;
    }
}