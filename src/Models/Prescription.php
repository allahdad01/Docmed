<?php

namespace PharmacySaaS\Models;

use PharmacySaaS\Core\Database;

class Prescription
{
    private $db;
    private $tenantId;

    public function __construct()
    {
        $this->db = Database::getInstance();
        $this->tenantId = $this->db->getTenantId();
    }

    /**
     * Create a new prescription
     */
    public function create($data)
    {
        $this->db->beginTransaction();
        
        try {
            // Create prescription header
            $prescriptionData = [
                'tenant_id' => $this->tenantId,
                'prescription_number' => $this->generatePrescriptionNumber(),
                'customer_id' => $data['customer_id'],
                'doctor_id' => $data['doctor_id'] ?? null,
                'doctor_name' => $data['doctor_name'] ?? null,
                'doctor_license' => $data['doctor_license'] ?? null,
                'prescription_date' => $data['prescription_date'] ?? date('Y-m-d'),
                'expiry_date' => $data['expiry_date'] ?? null,
                'diagnosis' => $data['diagnosis'] ?? null,
                'allergies' => $data['allergies'] ?? null,
                'notes' => $data['notes'] ?? null,
                'status' => $data['status'] ?? 'pending',
                'priority' => $data['priority'] ?? 'normal',
                'source' => $data['source'] ?? 'manual', // manual, scanned, digital
                'file_path' => $data['file_path'] ?? null,
                'created_by' => $data['created_by'],
                'created_at' => date('Y-m-d H:i:s')
            ];

            $prescriptionId = $this->db->insert('prescriptions', $prescriptionData);

            // Add prescription items
            foreach ($data['items'] as $item) {
                $itemData = [
                    'tenant_id' => $this->tenantId,
                    'prescription_id' => $prescriptionId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'dosage' => $item['dosage'] ?? null,
                    'frequency' => $item['frequency'] ?? null,
                    'duration' => $item['duration'] ?? null,
                    'instructions' => $item['instructions'] ?? null,
                    'substitution_allowed' => $item['substitution_allowed'] ?? true,
                    'notes' => $item['notes'] ?? null
                ];

                $this->db->insert('prescription_items', $itemData);
            }

            $this->db->commit();
            return $prescriptionId;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Update prescription
     */
    public function update($prescriptionId, $data)
    {
        $this->db->beginTransaction();
        
        try {
            // Update prescription header
            $updateData = array_intersect_key($data, array_flip([
                'customer_id', 'doctor_id', 'doctor_name', 'doctor_license', 'prescription_date',
                'expiry_date', 'diagnosis', 'allergies', 'notes', 'status', 'priority'
            ]));

            $updateData['updated_at'] = date('Y-m-d H:i:s');

            $this->db->update('prescriptions', $updateData, 'id = ? AND tenant_id = ?', [$prescriptionId, $this->tenantId]);

            // Update items if provided
            if (isset($data['items'])) {
                // Delete existing items
                $this->db->delete('prescription_items', 'prescription_id = ? AND tenant_id = ?', [$prescriptionId, $this->tenantId]);

                // Add new items
                foreach ($data['items'] as $item) {
                    $itemData = [
                        'tenant_id' => $this->tenantId,
                        'prescription_id' => $prescriptionId,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'dosage' => $item['dosage'] ?? null,
                        'frequency' => $item['frequency'] ?? null,
                        'duration' => $item['duration'] ?? null,
                        'instructions' => $item['instructions'] ?? null,
                        'substitution_allowed' => $item['substitution_allowed'] ?? true,
                        'notes' => $item['notes'] ?? null
                    ];

                    $this->db->insert('prescription_items', $itemData);
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
     * Get prescription by ID
     */
    public function getById($prescriptionId)
    {
        $sql = "SELECT p.*, c.name as customer_name, c.name_ar as customer_name_ar,
                       c.name_ps as customer_name_ps, c.name_dr as customer_name_dr,
                       c.phone as customer_phone, c.email as customer_email,
                       u.username as created_by_name
                FROM prescriptions p
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ? AND p.tenant_id = ?";
        
        return $this->db->fetch($sql, [$prescriptionId, $this->tenantId]);
    }

    /**
     * Get prescription items
     */
    public function getItems($prescriptionId)
    {
        $sql = "SELECT pi.*, p.name as product_name, p.name_ar as product_name_ar,
                       p.name_ps as product_name_ps, p.name_dr as product_name_dr,
                       p.barcode, p.sku, p.strength, p.form
                FROM prescription_items pi
                JOIN products p ON pi.product_id = p.id
                WHERE pi.prescription_id = ? AND pi.tenant_id = ?
                ORDER BY pi.id ASC";
        
        return $this->db->fetchAll($sql, [$prescriptionId, $this->tenantId]);
    }

    /**
     * Get all prescriptions with filters and pagination
     */
    public function getAll($filters = [], $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, c.name as customer_name, c.name_ar as customer_name_ar,
                       c.name_ps as customer_name_ps, c.name_dr as customer_name_dr,
                       c.phone as customer_phone, u.username as created_by_name
                FROM prescriptions p
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ?";
        
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['customer_id'])) {
            $sql .= " AND p.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['doctor_id'])) {
            $sql .= " AND p.doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND p.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['prescription_date_from'])) {
            $sql .= " AND p.prescription_date >= ?";
            $params[] = $filters['prescription_date_from'];
        }

        if (!empty($filters['prescription_date_to'])) {
            $sql .= " AND p.prescription_date <= ?";
            $params[] = $filters['prescription_date_to'];
        }

        if (!empty($filters['prescription_number'])) {
            $sql .= " AND p.prescription_number LIKE ?";
            $params[] = '%' . $filters['prescription_number'] . '%';
        }

        if (!empty($filters['customer_name'])) {
            $sql .= " AND (c.name LIKE ? OR c.name_ar LIKE ? OR c.name_ps LIKE ? OR c.name_dr LIKE ?)";
            $searchTerm = '%' . $filters['customer_name'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $sql .= " ORDER BY p.created_at DESC";
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get prescription count for pagination
     */
    public function getCount($filters = [])
    {
        $sql = "SELECT COUNT(*) as total FROM prescriptions p 
                JOIN customers c ON p.customer_id = c.id 
                WHERE p.tenant_id = ?";
        $params = [$this->tenantId];

        // Apply filters
        if (!empty($filters['customer_id'])) {
            $sql .= " AND p.customer_id = ?";
            $params[] = $filters['customer_id'];
        }

        if (!empty($filters['doctor_id'])) {
            $sql .= " AND p.doctor_id = ?";
            $params[] = $filters['doctor_id'];
        }

        if (!empty($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['priority'])) {
            $sql .= " AND p.priority = ?";
            $params[] = $filters['priority'];
        }

        if (!empty($filters['prescription_date_from'])) {
            $sql .= " AND p.prescription_date >= ?";
            $params[] = $filters['prescription_date_from'];
        }

        if (!empty($filters['prescription_date_to'])) {
            $sql .= " AND p.prescription_date <= ?";
            $params[] = $filters['prescription_date_to'];
        }

        if (!empty($filters['prescription_number'])) {
            $sql .= " AND p.prescription_number LIKE ?";
            $params[] = '%' . $filters['prescription_number'] . '%';
        }

        if (!empty($filters['customer_name'])) {
            $sql .= " AND (c.name LIKE ? OR c.name_ar LIKE ? OR c.name_ps LIKE ? OR c.name_dr LIKE ?)";
            $searchTerm = '%' . $filters['customer_name'] . '%';
            $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm, $searchTerm]);
        }

        $result = $this->db->fetch($sql, $params);
        return $result['total'] ?? 0;
    }

    /**
     * Update prescription status
     */
    public function updateStatus($prescriptionId, $status, $userId, $notes = null)
    {
        $this->db->beginTransaction();
        
        try {
            $currentPrescription = $this->getById($prescriptionId);
            if (!$currentPrescription) {
                throw new \Exception('Prescription not found');
            }

            // Update prescription status
            $this->db->update('prescriptions', [
                'status' => $status,
                'updated_at' => date('Y-m-d H:i:s')
            ], 'id = ? AND tenant_id = ?', [$prescriptionId, $this->tenantId]);

            // Log status change
            $this->db->insert('prescription_status_logs', [
                'tenant_id' => $this->tenantId,
                'prescription_id' => $prescriptionId,
                'old_status' => $currentPrescription['status'],
                'new_status' => $status,
                'changed_by' => $userId,
                'changed_at' => date('Y-m-d H:i:s'),
                'notes' => $notes ?? "Status changed from {$currentPrescription['status']} to {$status}"
            ]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    /**
     * Check drug interactions for prescription
     */
    public function checkDrugInteractions($prescriptionId)
    {
        $items = $this->getItems($prescriptionId);
        $interactions = [];

        if (count($items) < 2) {
            return $interactions; // No interactions possible with single drug
        }

        // Get product IDs
        $productIds = array_column($items, 'product_id');
        
        // Check for known interactions
        $sql = "SELECT i.*, p1.name as drug1_name, p2.name as drug2_name,
                       i.severity, i.description, i.recommendation
                FROM drug_interactions i
                JOIN products p1 ON i.drug1_id = p1.id
                JOIN products p2 ON i.drug2_id = p2.id
                WHERE i.tenant_id = ? AND (
                    (i.drug1_id IN (" . implode(',', array_fill(0, count($productIds), '?')) . ") 
                    AND i.drug2_id IN (" . implode(',', array_fill(0, count($productIds), '?')) . "))
                )";
        
        $params = array_merge([$this->tenantId], $productIds, $productIds);
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get prescriptions by status
     */
    public function getByStatus($status, $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, c.name as customer_name, c.name_ar as customer_name_ar,
                       c.name_ps as customer_name_ps, c.name_dr as customer_name_dr,
                       c.phone as customer_phone, u.username as created_by_name
                FROM prescriptions p
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ? AND p.status = ?
                ORDER BY p.created_at DESC";
        
        $params = [$this->tenantId, $status];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get prescriptions by priority
     */
    public function getByPriority($priority, $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, c.name as customer_name, c.name_ar as customer_name_ar,
                       c.name_ps as customer_name_ps, c.name_dr as customer_name_dr,
                       c.phone as customer_phone, u.username as created_by_name
                FROM prescriptions p
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ? AND p.priority = ?
                ORDER BY p.created_at DESC";
        
        $params = [$this->tenantId, $priority];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get prescriptions by customer
     */
    public function getByCustomer($customerId, $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, c.name as customer_name, c.name_ar as customer_name_ar,
                       c.name_ps as customer_name_ps, c.name_dr as customer_name_dr,
                       c.phone as customer_phone, u.username as created_by_name
                FROM prescriptions p
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ? AND p.customer_id = ?
                ORDER BY p.created_at DESC";
        
        $params = [$this->tenantId, $customerId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get prescriptions by doctor
     */
    public function getByDoctor($doctorId, $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, c.name as customer_name, c.name_ar as customer_name_ar,
                       c.name_ps as customer_name_ps, c.name_dr as customer_name_dr,
                       c.phone as customer_phone, u.username as created_by_name
                FROM prescriptions p
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ? AND p.doctor_id = ?
                ORDER BY p.created_at DESC";
        
        $params = [$this->tenantId, $doctorId];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get prescription statistics
     */
    public function getPrescriptionStats($period = 'month')
    {
        $dateFilter = '';
        switch ($period) {
            case 'week':
                $dateFilter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 WEEK)";
                break;
            case 'month':
                $dateFilter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)";
                break;
            case 'quarter':
                $dateFilter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)";
                break;
            case 'year':
                $dateFilter = "AND p.created_at >= DATE_SUB(NOW(), INTERVAL 1 YEAR)";
                break;
        }

        $sql = "SELECT 
                    COUNT(*) as total_prescriptions,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_prescriptions,
                    COUNT(CASE WHEN status = 'processing' THEN 1 END) as processing_prescriptions,
                    COUNT(CASE WHEN status = 'ready' THEN 1 END) as ready_prescriptions,
                    COUNT(CASE WHEN status = 'dispensed' THEN 1 END) as dispensed_prescriptions,
                    COUNT(CASE WHEN status = 'cancelled' THEN 1 END) as cancelled_prescriptions,
                    COUNT(CASE WHEN priority = 'urgent' THEN 1 END) as urgent_prescriptions,
                    COUNT(CASE WHEN priority = 'high' THEN 1 END) as high_priority_prescriptions,
                    COUNT(CASE WHEN priority = 'normal' THEN 1 END) as normal_priority_prescriptions
                FROM prescriptions 
                WHERE tenant_id = ? $dateFilter";
        
        return $this->db->fetch($sql, [$this->tenantId]);
    }

    /**
     * Search prescriptions for autocomplete
     */
    public function searchPrescriptions($query, $limit = 10)
    {
        $sql = "SELECT p.id, p.prescription_number, p.prescription_date, p.status, p.priority,
                       c.name as customer_name, c.name_ar as customer_name_ar,
                       c.name_ps as customer_name_ps, c.name_dr as customer_name_dr,
                       c.phone as customer_phone
                FROM prescriptions p
                JOIN customers c ON p.customer_id = c.id
                WHERE p.tenant_id = ? AND (
                    p.prescription_number LIKE ? OR 
                    c.name LIKE ? OR 
                    c.name_ar LIKE ? OR 
                    c.name_ps LIKE ? OR 
                    c.name_dr LIKE ? OR 
                    c.phone LIKE ?
                )
                ORDER BY p.created_at DESC
                LIMIT ?";
        
        $searchTerm = '%' . $query . '%';
        $params = [$this->tenantId, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $searchTerm, $limit];
        
        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Get expiring prescriptions
     */
    public function getExpiringPrescriptions($days = 7, $page = 1, $limit = 20)
    {
        $sql = "SELECT p.*, c.name as customer_name, c.name_ar as customer_name_ar,
                       c.name_ps as customer_name_ps, c.name_dr as customer_name_dr,
                       c.phone as customer_phone, u.username as created_by_name
                FROM prescriptions p
                JOIN customers c ON p.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.tenant_id = ? AND p.expiry_date IS NOT NULL 
                AND p.expiry_date <= DATE_ADD(NOW(), INTERVAL ? DAY)
                AND p.status IN ('pending', 'processing')
                ORDER BY p.expiry_date ASC";
        
        $params = [$this->tenantId, $days];
        
        // Add pagination
        $offset = ($page - 1) * $limit;
        $sql .= " LIMIT ? OFFSET ?";
        $params[] = $limit;
        $params[] = $offset;

        return $this->db->fetchAll($sql, $params);
    }

    /**
     * Generate unique prescription number
     */
    private function generatePrescriptionNumber()
    {
        $prefix = 'RX';
        $year = date('Y');
        $month = date('m');
        
        // Get last prescription number for this month
        $sql = "SELECT prescription_number FROM prescriptions 
                WHERE tenant_id = ? AND prescription_number LIKE ? 
                ORDER BY id DESC LIMIT 1";
        
        $pattern = $prefix . $year . $month . '%';
        $result = $this->db->fetch($sql, [$this->tenantId, $pattern]);
        
        if ($result) {
            $lastNumber = (int)substr($result['prescription_number'], -4);
            $newNumber = $lastNumber + 1;
        } else {
            $newNumber = 1;
        }
        
        return $prefix . $year . $month . str_pad($newNumber, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Delete prescription (only if not dispensed)
     */
    public function delete($prescriptionId)
    {
        $prescription = $this->getById($prescriptionId);
        if (!$prescription) {
            throw new \Exception('Prescription not found');
        }

        if ($prescription['status'] === 'dispensed') {
            throw new \Exception('Cannot delete dispensed prescription');
        }

        $this->db->beginTransaction();
        
        try {
            // Delete prescription items
            $this->db->delete('prescription_items', 'prescription_id = ? AND tenant_id = ?', [$prescriptionId, $this->tenantId]);
            
            // Delete prescription
            $this->db->delete('prescriptions', 'id = ? AND tenant_id = ?', [$prescriptionId, $this->tenantId]);

            $this->db->commit();
            return true;
        } catch (\Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }
}