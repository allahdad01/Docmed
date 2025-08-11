<?php

namespace PharmacySaaS\Models;

use PDO;

class Expense {
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance()->getConnection();
    }
    
    /**
     * Create a new expense
     */
    public function create($data) {
        $sql = "INSERT INTO expenses (
            tenant_id, doctor_id, category, description, amount, 
            expense_date, payment_method, receipt_number, vendor_name,
            is_recurring, recurring_frequency, next_due_date, 
            is_approved, approved_by, notes, created_at
        ) VALUES (
            :tenant_id, :doctor_id, :category, :description, :amount,
            :expense_date, :payment_method, :receipt_number, :vendor_name,
            :is_recurring, :recurring_frequency, :next_due_date,
            :is_approved, :approved_by, :notes, NOW()
        )";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $data['tenant_id']);
        $stmt->bindParam(':doctor_id', $data['doctor_id']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':expense_date', $data['expense_date']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':receipt_number', $data['receipt_number']);
        $stmt->bindParam(':vendor_name', $data['vendor_name']);
        $stmt->bindParam(':is_recurring', $data['is_recurring']);
        $stmt->bindParam(':recurring_frequency', $data['recurring_frequency']);
        $stmt->bindParam(':next_due_date', $data['next_due_date']);
        $stmt->bindParam(':is_approved', $data['is_approved']);
        $stmt->bindParam(':approved_by', $data['approved_by']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Get expense by ID
     */
    public function getById($id, $tenantId) {
        $sql = "SELECT e.*, d.name as doctor_name 
                FROM expenses e
                LEFT JOIN doctors d ON e.doctor_id = d.id
                WHERE e.id = :id AND e.tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get all expenses with search and pagination
     */
    public function getAll($tenantId, $filters = [], $page = 1, $limit = 50) {
        $offset = ($page - 1) * $limit;
        
        $whereClause = "WHERE e.tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($filters['doctor_id'])) {
            $whereClause .= " AND e.doctor_id = :doctor_id";
            $params[':doctor_id'] = $filters['doctor_id'];
        }
        
        if (!empty($filters['category'])) {
            $whereClause .= " AND e.category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND e.expense_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND e.expense_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (e.description LIKE :search OR e.vendor_name LIKE :search OR e.receipt_number LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT e.*, d.name as doctor_name 
                FROM expenses e
                LEFT JOIN doctors d ON e.doctor_id = d.id
                {$whereClause} 
                ORDER BY e.expense_date DESC, e.created_at DESC 
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
     * Get total count of expenses
     */
    public function getTotalCount($tenantId, $filters = []) {
        $whereClause = "WHERE tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($filters['doctor_id'])) {
            $whereClause .= " AND doctor_id = :doctor_id";
            $params[':doctor_id'] = $filters['doctor_id'];
        }
        
        if (!empty($filters['category'])) {
            $whereClause .= " AND category = :category";
            $params[':category'] = $filters['category'];
        }
        
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND expense_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND expense_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['search'])) {
            $whereClause .= " AND (description LIKE :search OR vendor_name LIKE :search OR receipt_number LIKE :search)";
            $params[':search'] = "%{$filters['search']}%";
        }
        
        $sql = "SELECT COUNT(*) as total FROM expenses {$whereClause}";
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }
    
    /**
     * Get expense categories
     */
    public function getCategories($tenantId) {
        $sql = "SELECT DISTINCT category FROM expenses WHERE tenant_id = :tenant_id ORDER BY category ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    /**
     * Update expense
     */
    public function update($id, $data, $tenantId) {
        $sql = "UPDATE expenses SET 
                doctor_id = :doctor_id, category = :category, description = :description,
                amount = :amount, expense_date = :expense_date, payment_method = :payment_method,
                receipt_number = :receipt_number, vendor_name = :vendor_name, is_recurring = :is_recurring,
                recurring_frequency = :recurring_frequency, next_due_date = :next_due_date,
                is_approved = :is_approved, approved_by = :approved_by, notes = :notes,
                updated_at = NOW()
                WHERE id = :id AND tenant_id = :tenant_id";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':doctor_id', $data['doctor_id']);
        $stmt->bindParam(':category', $data['category']);
        $stmt->bindParam(':description', $data['description']);
        $stmt->bindParam(':amount', $data['amount']);
        $stmt->bindParam(':expense_date', $data['expense_date']);
        $stmt->bindParam(':payment_method', $data['payment_method']);
        $stmt->bindParam(':receipt_number', $data['receipt_number']);
        $stmt->bindParam(':vendor_name', $data['vendor_name']);
        $stmt->bindParam(':is_recurring', $data['is_recurring']);
        $stmt->bindParam(':recurring_frequency', $data['recurring_frequency']);
        $stmt->bindParam(':next_due_date', $data['next_due_date']);
        $stmt->bindParam(':is_approved', $data['is_approved']);
        $stmt->bindParam(':approved_by', $data['approved_by']);
        $stmt->bindParam(':notes', $data['notes']);
        
        return $stmt->execute();
    }
    
    /**
     * Delete expense
     */
    public function delete($id, $tenantId) {
        $sql = "DELETE FROM expenses WHERE id = :id AND tenant_id = :tenant_id";
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':id', $id);
        $stmt->bindParam(':tenant_id', $tenantId);
        
        return $stmt->execute();
    }
    
    /**
     * Get expense statistics
     */
    public function getStats($tenantId, $filters = []) {
        $whereClause = "WHERE tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND expense_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND expense_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['doctor_id'])) {
            $whereClause .= " AND doctor_id = :doctor_id";
            $params[':doctor_id'] = $filters['doctor_id'];
        }
        
        $sql = "SELECT 
                COUNT(*) as total_expenses,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount,
                MIN(amount) as min_amount,
                MAX(amount) as max_amount,
                COUNT(CASE WHEN is_approved = 1 THEN 1 END) as approved_expenses,
                COUNT(CASE WHEN is_approved = 0 THEN 1 END) as pending_expenses
                FROM expenses {$whereClause}";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get expenses by category summary
     */
    public function getByCategorySummary($tenantId, $filters = []) {
        $whereClause = "WHERE tenant_id = :tenant_id";
        $params = [':tenant_id' => $tenantId];
        
        // Apply filters
        if (!empty($filters['date_from'])) {
            $whereClause .= " AND expense_date >= :date_from";
            $params[':date_from'] = $filters['date_from'];
        }
        
        if (!empty($filters['date_to'])) {
            $whereClause .= " AND expense_date <= :date_to";
            $params[':date_to'] = $filters['date_to'];
        }
        
        if (!empty($filters['doctor_id'])) {
            $whereClause .= " AND doctor_id = :doctor_id";
            $params[':doctor_id'] = $filters['doctor_id'];
        }
        
        $sql = "SELECT 
                category,
                COUNT(*) as count,
                SUM(amount) as total_amount,
                AVG(amount) as avg_amount
                FROM expenses {$whereClause}
                GROUP BY category
                ORDER BY total_amount DESC";
        
        $stmt = $this->db->prepare($sql);
        
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get monthly expense summary
     */
    public function getMonthlySummary($tenantId, $year) {
        $sql = "SELECT 
                MONTH(expense_date) as month,
                YEAR(expense_date) as year,
                COUNT(*) as count,
                SUM(amount) as total_amount
                FROM expenses 
                WHERE tenant_id = :tenant_id AND YEAR(expense_date) = :year
                GROUP BY MONTH(expense_date), YEAR(expense_date)
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->bindParam(':year', $year);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Get recurring expenses
     */
    public function getRecurringExpenses($tenantId) {
        $sql = "SELECT * FROM expenses 
                WHERE tenant_id = :tenant_id AND is_recurring = 1 
                ORDER BY next_due_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->bindParam(':tenant_id', $tenantId);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}