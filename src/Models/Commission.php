<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Commission
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = new Database();
        $this->tenantId = $_ENV['TENANT_ID'] ?? 1;
    }

    /**
     * Create pharmacy commission record
     */
    public function createPharmacyCommission($data)
    {
        $sql = "INSERT INTO pharmacy_commissions (
                    doctor_id, pharmacy_id, prescription_id, commission_amount, notes
                ) VALUES (?, ?, ?, ?, ?)";

        $params = [
            $data['doctor_id'],
            $data['pharmacy_id'],
            $data['prescription_id'],
            $data['commission_amount'],
            $data['notes'] ?? null
        ];

        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Create lab commission record
     */
    public function createLabCommission($data)
    {
        $sql = "INSERT INTO lab_commissions (
                    doctor_id, lab_id, lab_test_id, commission_amount, notes
                ) VALUES (?, ?, ?, ?, ?)";

        $params = [
            $data['doctor_id'],
            $data['lab_id'],
            $data['lab_test_id'],
            $data['commission_amount'],
            $data['notes'] ?? null
        ];

        return $this->db->insertRaw($sql, $params);
    }

    /**
     * Get pharmacy commissions with pagination and filtering
     */
    public function getPharmacyCommissions($page = 1, $limit = 50, $doctorId = null, $pharmacyId = null, $status = '')
    {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['pc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'pc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($pharmacyId) {
            $whereConditions[] = 'pc.pharmacy_id = ?';
            $params[] = $pharmacyId;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'pc.status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT pc.*, 
                       d.name as doctor_name, d.specialty as doctor_specialty,
                       b.name as pharmacy_name, b.address as pharmacy_address,
                       p.prescription_number, p.visit_date,
                       pt.name as patient_name
                FROM pharmacy_commissions pc
                JOIN doctors d ON pc.doctor_id = d.id
                JOIN branches b ON pc.pharmacy_id = b.id
                JOIN prescriptions p ON pc.prescription_id = p.id
                JOIN patients pt ON p.patient_id = pt.id
                WHERE {$whereClause} 
                ORDER BY pc.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get lab commissions with pagination and filtering
     */
    public function getLabCommissions($page = 1, $limit = 50, $doctorId = null, $labId = null, $status = '')
    {
        $offset = ($page - 1) * $limit;
        
        $whereConditions = ['lc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'lc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($labId) {
            $whereConditions[] = 'lc.lab_id = ?';
            $params[] = $labId;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'lc.status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT lc.*, 
                       d.name as doctor_name, d.specialty as doctor_specialty,
                       dl.lab_name, dl.lab_address,
                       lt.test_name, lt.test_date,
                       pt.name as patient_name
                FROM lab_commissions lc
                JOIN doctors d ON lc.doctor_id = d.id
                JOIN doctor_labs dl ON lc.lab_id = dl.id
                JOIN lab_tests lt ON lc.lab_test_id = lt.id
                JOIN patients pt ON lt.patient_id = pt.id
                WHERE {$whereClause} 
                ORDER BY lc.created_at DESC 
                LIMIT ? OFFSET ?";
        
        $params[] = $limit;
        $params[] = $offset;
        
        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get total count of pharmacy commissions
     */
    public function getPharmacyCommissionCount($doctorId = null, $pharmacyId = null, $status = '')
    {
        $whereConditions = ['pc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'pc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($pharmacyId) {
            $whereConditions[] = 'pc.pharmacy_id = ?';
            $params[] = $pharmacyId;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'pc.status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT COUNT(*) as count FROM pharmacy_commissions pc WHERE {$whereClause}";
        $result = $this->db->query($sql, $params)->fetch();
        
        return $result['count'] ?? 0;
    }

    /**
     * Get total count of lab commissions
     */
    public function getLabCommissionCount($doctorId = null, $labId = null, $status = '')
    {
        $whereConditions = ['lc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'lc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($labId) {
            $whereConditions[] = 'lc.lab_id = ?';
            $params[] = $labId;
        }
        
        if (!empty($status)) {
            $whereConditions[] = 'lc.status = ?';
            $params[] = $status;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT COUNT(*) as count FROM lab_commissions lc WHERE {$whereClause}";
        $result = $this->db->query($sql, $params)->fetch();
        
        return $result['count'] ?? 0;
    }

    /**
     * Update pharmacy commission status
     */
    public function updatePharmacyCommissionStatus($id, $status, $paymentDate = null, $notes = null)
    {
        $sql = "UPDATE pharmacy_commissions SET 
                    status = ?, payment_date = ?, notes = ?, updated_at = NOW()
                WHERE id = ?";
        
        $params = [$status, $paymentDate, $notes, $id];
        return $this->db->execute($sql, $params);
    }

    /**
     * Update lab commission status
     */
    public function updateLabCommissionStatus($id, $status, $paymentDate = null, $notes = null)
    {
        $sql = "UPDATE lab_commissions SET 
                    status = ?, payment_date = ?, notes = ?, updated_at = NOW()
                WHERE id = ?";
        
        $params = [$status, $paymentDate, $notes, $id];
        return $this->db->execute($sql, $params);
    }

    /**
     * Get pharmacy commission summary
     */
    public function getPharmacyCommissionSummary($doctorId = null, $pharmacyId = null)
    {
        $whereConditions = ['pc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'pc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($pharmacyId) {
            $whereConditions[] = 'pc.pharmacy_id = ?';
            $params[] = $pharmacyId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    SUM(CASE WHEN pc.status = 'pending' THEN pc.commission_amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN pc.status = 'paid' THEN pc.commission_amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN pc.status = 'cancelled' THEN pc.commission_amount ELSE 0 END) as cancelled_amount,
                    COUNT(CASE WHEN pc.status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN pc.status = 'paid' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN pc.status = 'cancelled' THEN 1 END) as cancelled_count
                FROM pharmacy_commissions pc 
                WHERE {$whereClause}";

        return $this->db->query($sql, $params)->fetch();
    }

    /**
     * Get lab commission summary
     */
    public function getLabCommissionSummary($doctorId = null, $labId = null)
    {
        $whereConditions = ['lc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'lc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($labId) {
            $whereConditions[] = 'lc.lab_id = ?';
            $params[] = $labId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    SUM(CASE WHEN lc.status = 'pending' THEN lc.commission_amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN lc.status = 'paid' THEN lc.commission_amount ELSE 0 END) as paid_amount,
                    SUM(CASE WHEN lc.status = 'cancelled' THEN lc.commission_amount ELSE 0 END) as cancelled_amount,
                    COUNT(CASE WHEN lc.status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN lc.status = 'paid' THEN 1 END) as paid_count,
                    COUNT(CASE WHEN lc.status = 'cancelled' THEN 1 END) as cancelled_count
                FROM lab_commissions lc 
                WHERE {$whereClause}";

        return $this->db->query($sql, $params)->fetch();
    }

    /**
     * Get pharmacy due amounts
     */
    public function getPharmacyDueAmounts($doctorId = null)
    {
        $whereConditions = ['pc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'pc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    b.id as pharmacy_id,
                    b.name as pharmacy_name,
                    b.address as pharmacy_address,
                    SUM(CASE WHEN pc.status = 'pending' THEN pc.commission_amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN pc.status = 'paid' THEN pc.commission_amount ELSE 0 END) as paid_amount,
                    COUNT(CASE WHEN pc.status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN pc.status = 'paid' THEN 1 END) as paid_count
                FROM pharmacy_commissions pc
                JOIN branches b ON pc.pharmacy_id = b.id
                WHERE {$whereClause}
                GROUP BY b.id, b.name, b.address
                HAVING pending_amount > 0
                ORDER BY pending_amount DESC";

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get lab due amounts
     */
    public function getLabDueAmounts($doctorId = null)
    {
        $whereConditions = ['lc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'lc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    dl.id as lab_id,
                    dl.lab_name,
                    dl.lab_address,
                    SUM(CASE WHEN lc.status = 'pending' THEN lc.commission_amount ELSE 0 END) as pending_amount,
                    SUM(CASE WHEN lc.status = 'paid' THEN lc.commission_amount ELSE 0 END) as paid_amount,
                    COUNT(CASE WHEN lc.status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN lc.status = 'paid' THEN 1 END) as paid_count
                FROM lab_commissions lc
                JOIN doctor_labs dl ON lc.lab_id = dl.id
                WHERE {$whereClause}
                GROUP BY dl.id, dl.lab_name, dl.lab_address
                HAVING pending_amount > 0
                ORDER BY pending_amount DESC";

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Record pharmacy payment
     */
    public function recordPharmacyPayment($commissionId, $amount, $paymentDate, $notes = null)
    {
        $this->db->beginTransaction();
        
        try {
            // Get current commission
            $sql = "SELECT * FROM pharmacy_commissions WHERE id = ?";
            $commission = $this->db->query($sql, [$commissionId])->fetch();
            
            if (!$commission) {
                throw new \Exception('Commission not found');
            }
            
            if ($commission['status'] === 'paid') {
                throw new \Exception('Commission already paid');
            }
            
            // Update commission status
            $this->updatePharmacyCommissionStatus($commissionId, 'paid', $paymentDate, $notes);
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Record lab payment
     */
    public function recordLabPayment($commissionId, $amount, $paymentDate, $notes = null)
    {
        $this->db->beginTransaction();
        
        try {
            // Get current commission
            $sql = "SELECT * FROM lab_commissions WHERE id = ?";
            $commission = $this->db->query($sql, [$commissionId])->fetch();
            
            if (!$commission) {
                throw new \Exception('Commission not found');
            }
            
            if ($commission['status'] === 'paid') {
                throw new \Exception('Commission already paid');
            }
            
            // Update commission status
            $this->updateLabCommissionStatus($commissionId, 'paid', $paymentDate, $notes);
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Calculate commission amount
     */
    public function calculateCommission($baseAmount, $commissionType, $commissionValue)
    {
        if ($commissionType === 'percentage') {
            return ($baseAmount * $commissionValue) / 100;
        } else {
            return $commissionValue;
        }
    }

    /**
     * Get commission statistics
     */
    public function getCommissionStats($doctorId = null, $dateFrom = null, $dateTo = null)
    {
        $whereConditions = ['pc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $params = [$this->tenantId];
        
        if ($doctorId) {
            $whereConditions[] = 'pc.doctor_id = ?';
            $params[] = $doctorId;
        }
        
        if ($dateFrom) {
            $whereConditions[] = 'pc.created_at >= ?';
            $params[] = $dateFrom;
        }
        
        if ($dateTo) {
            $whereConditions[] = 'pc.created_at <= ?';
            $params[] = $dateTo;
        }
        
        $whereClause = implode(' AND ', $whereConditions);
        
        $sql = "SELECT 
                    SUM(CASE WHEN pc.status = 'pending' THEN pc.commission_amount ELSE 0 END) as total_pending,
                    SUM(CASE WHEN pc.status = 'paid' THEN pc.commission_amount ELSE 0 END) as total_paid,
                    COUNT(CASE WHEN pc.status = 'pending' THEN 1 END) as pending_count,
                    COUNT(CASE WHEN pc.status = 'paid' THEN 1 END) as paid_count
                FROM pharmacy_commissions pc 
                WHERE {$whereClause}";

        $pharmacyStats = $this->db->query($sql, $params)->fetch();
        
        // Get lab commission stats
        $labWhereConditions = ['lc.doctor_id IN (SELECT id FROM doctors WHERE tenant_id = ?)'];
        $labParams = [$this->tenantId];
        
        if ($doctorId) {
            $labWhereConditions[] = 'lc.doctor_id = ?';
            $labParams[] = $doctorId;
        }
        
        if ($dateFrom) {
            $labWhereConditions[] = 'lc.created_at >= ?';
            $labParams[] = $dateFrom;
        }
        
        if ($dateTo) {
            $labWhereConditions[] = 'lc.created_at <= ?';
            $labParams[] = $dateTo;
        }
        
        $labWhereClause = implode(' AND ', $labWhereConditions);
        
        $labSql = "SELECT 
                        SUM(CASE WHEN lc.status = 'pending' THEN lc.commission_amount ELSE 0 END) as total_pending,
                        SUM(CASE WHEN lc.status = 'paid' THEN lc.commission_amount ELSE 0 END) as total_paid,
                        COUNT(CASE WHEN lc.status = 'pending' THEN 1 END) as pending_count,
                        COUNT(CASE WHEN lc.status = 'paid' THEN 1 END) as paid_count
                    FROM lab_commissions lc 
                    WHERE {$labWhereClause}";

        $labStats = $this->db->query($labSql, $labParams)->fetch();
        
        return [
            'pharmacy' => $pharmacyStats,
            'lab' => $labStats,
            'total_pending' => ($pharmacyStats['total_pending'] ?? 0) + ($labStats['total_pending'] ?? 0),
            'total_paid' => ($pharmacyStats['total_paid'] ?? 0) + ($labStats['total_paid'] ?? 0)
        ];
    }
}