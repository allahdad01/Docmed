-- Create expenses table
CREATE TABLE IF NOT EXISTS expenses (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    doctor_id INT,
    category ENUM('rent', 'salaries', 'utilities', 'supplies', 'equipment', 'marketing', 'insurance', 'maintenance', 'travel', 'other') NOT NULL,
    description TEXT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    expense_date DATE NOT NULL,
    payment_method ENUM('cash', 'check', 'credit_card', 'debit_card', 'bank_transfer', 'online_payment') NOT NULL,
    receipt_number VARCHAR(100),
    vendor_name VARCHAR(255),
    is_recurring BOOLEAN DEFAULT FALSE,
    recurring_frequency ENUM('daily', 'weekly', 'monthly', 'quarterly', 'yearly'),
    next_due_date DATE,
    is_approved BOOLEAN DEFAULT FALSE,
    approved_by INT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_category (category),
    INDEX idx_expense_date (expense_date),
    INDEX idx_amount (amount),
    INDEX idx_is_recurring (is_recurring),
    INDEX idx_is_approved (is_approved),
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE,
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE SET NULL
);

-- Insert sample expense categories data
INSERT INTO expenses (tenant_id, doctor_id, category, description, amount, expense_date, payment_method, receipt_number, vendor_name, is_recurring, recurring_frequency, next_due_date, is_approved, notes) VALUES
(1, 1, 'rent', 'Monthly clinic rent payment', 2500.00, '2024-01-01', 'bank_transfer', 'RENT001', 'ABC Properties Ltd', 1, 'monthly', '2024-02-01', 1, 'Standard monthly rent for clinic space'),
(1, 1, 'salaries', 'Receptionist salary - January 2024', 1200.00, '2024-01-31', 'bank_transfer', 'SAL001', 'Staff Payroll', 1, 'monthly', '2024-02-29', 1, 'Monthly salary for clinic receptionist'),
(1, 1, 'utilities', 'Electricity bill - December 2023', 180.50, '2024-01-15', 'online_payment', 'UTIL001', 'City Power Company', 1, 'monthly', '2024-02-15', 1, 'Monthly electricity consumption'),
(1, 1, 'supplies', 'Medical supplies and equipment', 450.75, '2024-01-20', 'credit_card', 'SUP001', 'Medical Supply Co', 0, NULL, NULL, 1, 'Syringes, bandages, and basic medical supplies'),
(1, 1, 'equipment', 'New stethoscope purchase', 89.99, '2024-01-25', 'credit_card', 'EQP001', 'Medical Equipment Store', 0, NULL, NULL, 1, 'Professional grade stethoscope for patient examinations'),
(1, 1, 'marketing', 'Website maintenance and SEO', 200.00, '2024-01-10', 'online_payment', 'MKT001', 'Digital Marketing Agency', 1, 'monthly', '2024-02-10', 1, 'Monthly website maintenance and search engine optimization'),
(1, 1, 'insurance', 'Professional liability insurance', 150.00, '2024-01-05', 'bank_transfer', 'INS001', 'Medical Insurance Co', 1, 'monthly', '2024-02-05', 1, 'Monthly professional liability insurance premium'),
(1, 1, 'maintenance', 'HVAC system maintenance', 120.00, '2024-01-18', 'check', 'MAINT001', 'HVAC Services Ltd', 0, NULL, NULL, 1, 'Regular maintenance of heating and cooling system'),
(1, 1, 'travel', 'Medical conference attendance', 350.00, '2024-01-22', 'credit_card', 'TRAV001', 'Conference Organizers', 0, NULL, NULL, 1, 'Registration fee for annual medical conference'),
(1, 1, 'other', 'Office cleaning services', 75.00, '2024-01-28', 'cash', 'OTH001', 'Clean Pro Services', 1, 'weekly', '2024-02-04', 1, 'Weekly professional cleaning service for clinic');