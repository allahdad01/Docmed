-- Create payments table
CREATE TABLE IF NOT EXISTS payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    doctor_id INT,
    patient_id INT,
    prescription_id INT,
    payment_type ENUM('consultation', 'medicine', 'lab_test', 'combined', 'other') NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    payment_method ENUM('cash', 'check', 'credit_card', 'debit_card', 'bank_transfer', 'online_payment', 'insurance') NOT NULL,
    payment_date DATE NOT NULL,
    receipt_number VARCHAR(100) UNIQUE,
    consultation_fee DECIMAL(10,2) DEFAULT 0.00,
    medicine_cost DECIMAL(10,2) DEFAULT 0.00,
    lab_test_cost DECIMAL(10,2) DEFAULT 0.00,
    total_amount DECIMAL(10,2) NOT NULL,
    discount_amount DECIMAL(10,2) DEFAULT 0.00,
    tax_amount DECIMAL(10,2) DEFAULT 0.00,
    payment_status ENUM('pending', 'partial', 'completed', 'cancelled', 'refunded') DEFAULT 'pending',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_patient_id (patient_id),
    INDEX idx_prescription_id (prescription_id),
    INDEX idx_payment_date (payment_date),
    INDEX idx_payment_status (payment_status),
    INDEX idx_receipt_number (receipt_number),
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL,
    FOREIGN KEY (patient_id) REFERENCES patients(id) ON DELETE SET NULL,
    FOREIGN KEY (prescription_id) REFERENCES prescriptions(id) ON DELETE SET NULL
);

-- Insert sample payment data
INSERT INTO payments (tenant_id, doctor_id, patient_id, prescription_id, payment_type, amount, payment_method, payment_date, receipt_number, consultation_fee, medicine_cost, lab_test_cost, total_amount, discount_amount, tax_amount, payment_status, notes) VALUES
(1, 1, 1, 1, 'combined', 150.00, 'credit_card', '2024-01-15', 'RCPT000001', 50.00, 80.00, 20.00, 150.00, 0.00, 0.00, 'completed', 'Full payment for consultation, medicines, and lab tests'),
(1, 1, 2, 2, 'consultation', 50.00, 'cash', '2024-01-16', 'RCPT000002', 50.00, 0.00, 0.00, 50.00, 0.00, 0.00, 'completed', 'Consultation fee only'),
(1, 1, 3, 3, 'medicine', 120.00, 'bank_transfer', '2024-01-17', 'RCPT000003', 0.00, 120.00, 0.00, 120.00, 10.00, 0.00, 'completed', 'Medicine cost with discount'),
(1, 1, 1, 4, 'lab_test', 75.00, 'online_payment', '2024-01-18', 'RCPT000004', 0.00, 0.00, 75.00, 75.00, 0.00, 0.00, 'completed', 'Laboratory test payment'),
(1, 1, 2, 5, 'combined', 200.00, 'credit_card', '2024-01-19', 'RCPT000005', 50.00, 120.00, 30.00, 200.00, 0.00, 0.00, 'pending', 'Pending payment for combined services'),
(1, 1, 3, 6, 'consultation', 50.00, 'cash', '2024-01-20', 'RCPT000006', 50.00, 0.00, 0.00, 50.00, 0.00, 0.00, 'completed', 'Follow-up consultation'),
(1, 1, 1, 7, 'medicine', 95.00, 'check', '2024-01-21', 'RCPT000007', 0.00, 95.00, 0.00, 95.00, 0.00, 0.00, 'completed', 'Prescription medicines'),
(1, 1, 2, 8, 'lab_test', 60.00, 'online_payment', '2024-01-22', 'RCPT000008', 0.00, 0.00, 60.00, 60.00, 0.00, 0.00, 'completed', 'Blood test payment'),
(1, 1, 3, 9, 'combined', 180.00, 'credit_card', '2024-01-23', 'RCPT000009', 50.00, 100.00, 30.00, 180.00, 0.00, 0.00, 'partial', 'Partial payment received'),
(1, 1, 1, 10, 'consultation', 50.00, 'cash', '2024-01-24', 'RCPT000010', 50.00, 0.00, 0.00, 50.00, 0.00, 0.00, 'completed', 'Regular consultation');