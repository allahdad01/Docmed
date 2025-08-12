-- Create laboratory_tests table
CREATE TABLE IF NOT EXISTS laboratory_tests (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    test_code VARCHAR(50) UNIQUE,
    category VARCHAR(100),
    description TEXT,
    preparation_instructions TEXT,
    normal_range VARCHAR(255),
    unit VARCHAR(50),
    price DECIMAL(10,2) DEFAULT 0.00,
    commission_amount DECIMAL(10,2) DEFAULT 0.00,
    commission_type ENUM('percentage', 'fixed') DEFAULT 'fixed',
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_name (name),
    INDEX idx_test_code (test_code),
    INDEX idx_category (category),
    INDEX idx_price (price),
    INDEX idx_is_active (is_active),
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Insert sample laboratory tests data
INSERT INTO laboratory_tests (tenant_id, name, test_code, category, description, preparation_instructions, normal_range, unit, price, commission_amount, commission_type, is_active) VALUES
(1, 'Complete Blood Count', 'CBC001', 'Hematology', 'Complete blood count including RBC, WBC, hemoglobin, and platelets', 'Fasting for 8-12 hours, no food or drink except water', 'RBC: 4.5-5.5, WBC: 4.5-11.0, Hb: 12-16', 'cells/μL', 25.00, 5.00, 'fixed', 1),
(1, 'Blood Glucose Fasting', 'BGF001', 'Biochemistry', 'Fasting blood glucose level measurement', 'Fasting for 8-12 hours, no food or drink except water', '70-100', 'mg/dL', 15.00, 3.00, 'fixed', 1),
(1, 'Lipid Profile', 'LIP001', 'Biochemistry', 'Complete lipid profile including cholesterol and triglycerides', 'Fasting for 12-14 hours, no food or drink except water', 'Total Chol: <200, HDL: >40, LDL: <100', 'mg/dL', 35.00, 7.00, 'fixed', 1),
(1, 'Liver Function Test', 'LFT001', 'Biochemistry', 'Liver function tests including ALT, AST, bilirubin', 'Fasting for 8-12 hours, avoid alcohol for 24 hours', 'ALT: 7-55, AST: 8-48, Bilirubin: 0.3-1.2', 'U/L', 40.00, 8.00, 'fixed', 1),
(1, 'Kidney Function Test', 'KFT001', 'Biochemistry', 'Kidney function tests including creatinine and BUN', 'Fasting for 8-12 hours, avoid strenuous exercise', 'Creatinine: 0.6-1.2, BUN: 7-20', 'mg/dL', 30.00, 6.00, 'fixed', 1),
(1, 'Thyroid Function Test', 'TFT001', 'Endocrinology', 'Thyroid function tests including TSH, T3, T4', 'No special preparation required', 'TSH: 0.4-4.0, T3: 80-200, T4: 4.5-11.2', 'μIU/mL', 45.00, 9.00, 'fixed', 1),
(1, 'Urine Analysis', 'URA001', 'Urinalysis', 'Complete urinalysis including physical, chemical, and microscopic examination', 'Collect mid-stream urine in sterile container', 'pH: 4.5-8.0, Specific Gravity: 1.005-1.030', 'N/A', 20.00, 4.00, 'fixed', 1),
(1, 'X-Ray Chest', 'XRC001', 'Radiology', 'Chest X-ray for lung and heart examination', 'Remove metal objects, wear hospital gown', 'Normal lung fields, normal cardiac silhouette', 'N/A', 60.00, 12.00, 'fixed', 1),
(1, 'ECG', 'ECG001', 'Cardiology', 'Electrocardiogram for heart rhythm and function', 'No special preparation required, lie still during procedure', 'Normal sinus rhythm, normal intervals', 'N/A', 50.00, 10.00, 'fixed', 1),
(1, 'Ultrasound Abdomen', 'USAB001', 'Radiology', 'Abdominal ultrasound for organ examination', 'Fasting for 6-8 hours, full bladder for pelvic examination', 'Normal organ size and echogenicity', 'N/A', 120.00, 24.00, 'fixed', 1);