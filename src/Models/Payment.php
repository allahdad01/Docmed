<?php

namespace PharmacySaaS\Models;

use PDO;

class Payment {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new payment
     */
    public function create($data) {
        $sql = "INSERT INTO payments (
            tenant_id, doctor_id, patient_id, prescription_id, payment_type,
            amount, payment_method, payment_date, receipt_number, 
            consultation_fee, medicine_cost, lab_test_cost, total_amount,
            discount_amount, tax_amount, payment_status, notes, created_at
        ) VALUES (
            :tenant_id, :doctor_id, :patient_id, :prescription_id, :payment_type,
            :amount, :payment_method, :payment_date, :receipt_number,
            :consultation_fee, :medicine_cost, :lab_test_cost, :total_amount,
            :discount_amount, :tax_amount, :payment_status, :notes, NOW()
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $data['tenant_id']);
        $stmt->bindParam(':doctor_id', $data['doctor_id']);
        $stmt->bindParam(':patient_id', $data['patient_id']);
        $stmt->bindParam(':prescription_id', $data['prescription_id']);
        $stmt->bindParam(':payment_type', $data['payment_type']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':payment_date', $data['payment_date']);
        $stmt->bindParam(':receipt_number', $data['receipt_number']);
        $stmt->bindParam(':consultation_fee', $data['consultation_fee']);
        $stmt->bindParam(':medicine_cost', $data['medicine_cost']);
        $stmt->bindParam(':lab_test_cost', $data['lab_test_cost']);
        $stmt->bindParam(':total_amount', $data['total_amount']);
        $stmt->bindParam(':discount_amount', $data['discount_amount']);
        $stmt->bindParam(':tax_amount', $data['tax_amount']);
        $stmt->bindParam(':payment_status', $data['payment_status']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Get payment by ID
     */
    public function getById($id, $tenantId) {
        $sql = "SELECT p.*, d.name as doctor_name, pt.name as patient_name, pr.visit_date
                FROM payments p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                LEFT JOIN patients pt ON p.patient_id = pt.id
                LEFT JOIN prescriptions pr ON p.prescription_id = pr.id
                WHERE p.id = :id AND p.tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all payments with search and pagination
     */
    public function getAll($tenantId, $filters = [], $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE p.tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($filters['doctor_id'])) {
            $whereClause .= " AND p.doctor_id = :doctor_id";
            $params[':doctor_id'] = $filters['doctor_id'];
        }
        
        if (!empty($filters['patient_id'])) {
            $whereClause .= " AND p.patient_id = :patient_id";
            $params[':patient_id'] = $filters['patient_id'];
        }
        
        if (!empty($filters['payment_status'])) {
            $whereClause .= " AND p.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND p.payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND p.payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (p.receipt_number LIKE :search OR pt.name LIKE :search OR d.name LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT p.*, d.name as doctor_name, pt.name as patient_name, pr.visit_date
                FROM payments p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                LEFT JOIN patients pt ON p.patient_id = pt.id
                LEFT JOIN prescriptions pr ON p.prescription_id = pr.id
                {$whereClause} 
                ORDER BY p.payment_date DESC, p.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
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
     * Get total count of payments
     */
    public function getTotalCount($tenantId, $filters = []) {
        $whereClause = "WHERE p.tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($filters['doctor_id'])) {
            $whereClause .= " AND p.doctor_id = :doctor_id";
            $params[':doctor_id'] = $filters['doctor_id'];
        }
        
        if (!empty($filters['patient_id'])) {
            $whereClause .= " AND p.patient_id = :patient_id";
            $params[':patient_id'] = $filters['patient_id'];
        }
        
        if (!empty($filters['payment_status'])) {
            $whereClause .= " AND p.payment_status = :payment_status";
            $params[':payment_status'] = $filters['payment_status'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND p.payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND p.payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (p.receipt_number LIKE :search OR p.receipt_number LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT COUNT(*) as total FROM payments p {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Get payments by patient
     */
    public function getByPatient($patientId, $tenantId) {
        $sql = "SELECT p.*, d.name as doctor_name, pr.visit_date
                FROM payments p
                LEFT JOIN doctors d ON p.doctor_id = d.id
                LEFT JOIN prescriptions pr ON p.prescription_id = pr.id
                WHERE p.patient_id = :patient_id AND p.tenant_id = :tenant_id
                ORDER BY p.payment_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':patient_id', $patientId);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get payments by doctor
     */
    public function getByDoctor($doctorId, $tenantId) {
        $sql = "SELECT p.*, pt.name as patient_name, pr.visit_date
                FROM payments p
                LEFT JOIN patients pt ON p.patient_id = pt.id
                LEFT JOIN prescriptions pr ON p.prescription_id = pr.id
                WHERE p.doctor_id = :doctor_id AND p.tenant_id = :tenant_id
                ORDER BY p.payment_date DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':doctor_id', $doctorId);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Update payment
     */
    public function update($id, $data, $tenantId) {
        $sql = "UPDATE payments SET 
                doctor_id = :doctor_id, patient_id = :patient_id, prescription_id = :prescription_id,
                payment_type = :payment_type, amount = :amount, payment_method = :payment_method,
                payment_date = :payment_date, receipt_number = :receipt_number,
                consultation_fee = :consultation_fee, medicine_cost = :medicine_cost,
                lab_test_cost = :lab_test_cost, total_amount = :total_amount,
                discount_amount = :discount_amount, tax_amount = :tax_amount,
                payment_status = :payment_status, notes = :notes, updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':doctor_id', $data['doctor_id']);
        $stmt->bindParam(':patient_id', $data['patient_id']);
        $stmt->bindParam(':prescription_id', $data['prescription_id']);
        $stmt->bindParam(':payment_type', $data['payment_type']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':payment_date', $data['payment_date']);
        $stmt->bindParam(':receipt_number', $data['receipt_number']);
        $stmt->bindParam(':consultation_fee', $data['consultation_fee']);
        $stmt->bindParam(':medicine_cost', $data['medicine_cost']);
        $stmt->bindParam(':lab_test_cost', $data['lab_test_cost']);
        $stmt->bindParam(':total_amount', $data['total_amount']);
        $stmt->bindParam(':discount_amount', $data['discount_amount']);
        $stmt->bindParam(':tax_amount', $data['tax_amount']);
        $stmt->bindParam(':payment_status', $data['payment_status']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete payment
     */
    public function delete($id, $tenantId) {
        $sql = "DELETE FROM payments WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        
        return $stmt->execute();
    }
    
    /**
     * Get payment statistics
     */
    public function getStats($tenantId, $filters = []) {
        $whereClause = "WHERE tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['doctor_id'])) {
            $whereClause .= " AND doctor_id = :doctor_id";
            $params[':doctor_id'] = $filters['doctor_id'];
        }
        
        $sql = "SELECT 
                COUNT(*) as total_payments,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                SUM(consultation_fee) as total_consultation_fees,
                SUM(medicine_cost) as total_medicine_cost,
                SUM(lab_test_cost) as total_lab_test_cost,
                COUNT(CASE WHEN payment_status = 'completed' THEN 1 END) as completed_payments,
                COUNT(CASE WHEN payment_status = 'pending' THEN 1 END) as pending_payments,
                COUNT(CASE WHEN payment_status = 'partial' THEN 1 END) as partial_payments
                FROM payments {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly payment summary
     */
    public function getMonthlySummary($tenantId, $year) {
        $sql = "SELECT 
                MONTH(payment_date) as month,
                YEAR(payment_date) as year,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                SUM(consultation_fee) as total_consultation_fees,
                SUM(medicine_cost) as total_medicine_cost,
                SUM(lab_test_cost) as total_lab_test_cost
                FROM payments 
                WHERE tenant_id = :tenant_id AND YEAR(payment_date) = :year
                GROUP BY MONTH(payment_date), YEAR(payment_date)
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get payment methods summary
     */
    public function getPaymentMethodsSummary($tenantId, $filters = []) {
        $whereClause = "WHERE tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND payment_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND payment_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        $sql = "SELECT 
                payment_method,
                COUNT(*) as count,
                SUM(amount) as total_amount
                FROM payments {$whereClause}
                GROUP BY payment_method
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Generate receipt number
     */
    public function generateReceiptNumber($tenantId) {
        $sql = "SELECT MAX(CAST(SUBSTRING(receipt_number, 5) AS UNSIGNED)) as last_number 
                FROM payments 
                WHERE tenant_id = :tenant_id AND receipt_number LIKE 'RCPT%'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $lastNumber = $result['last_number'] ?? 0;
        
        return 'RCPT' . str_pad($lastNumber + 1, 6, '0', STR_PAD_LEFT);
    }
}