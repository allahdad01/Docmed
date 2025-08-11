-- Create medicines table
CREATE TABLE IF NOT EXISTS medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tenant_id INT NOT NULL,
    name VARCHAR(255) NOT NULL,
    generic_name VARCHAR(255),
    brand_name VARCHAR(255),
    medicine_type ENUM('tablet', 'capsule', 'syrup', 'injection', 'cream', 'ointment', 'drops', 'inhaler', 'other') NOT NULL,
    dosage_form VARCHAR(100),
    strength DECIMAL(10,2),
    unit VARCHAR(50),
    manufacturer VARCHAR(255),
    description TEXT,
    active_ingredients TEXT,
    side_effects TEXT,
    contraindications TEXT,
    storage_conditions TEXT,
    expiry_date DATE,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at TIMESTAMP NULL,
    
    INDEX idx_tenant_id (tenant_id),
    INDEX idx_name (name),
    INDEX idx_generic_name (generic_name),
    INDEX idx_brand_name (brand_name),
    INDEX idx_medicine_type (medicine_type),
    INDEX idx_is_active (is_active),
    INDEX idx_expiry_date (expiry_date),
    
    FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
);

-- Create doctor_medicines table for personal medicine lists
CREATE TABLE IF NOT EXISTS doctor_medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    doctor_id INT NOT NULL,
    medicine_id INT NOT NULL,
    is_preferred BOOLEAN DEFAULT FALSE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_doctor_id (doctor_id),
    INDEX idx_medicine_id (medicine_id),
    
    FOREIGN KEY (doctor_id) REFERENCES doctors(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    UNIQUE KEY unique_doctor_medicine (doctor_id, medicine_id)
);

-- Create pharmacy_medicines table
CREATE TABLE IF NOT EXISTS pharmacy_medicines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pharmacy_id INT NOT NULL,
    medicine_id INT NOT NULL,
    stock_quantity INT DEFAULT 0,
    unit_price DECIMAL(10,2),
    is_available BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_pharmacy_id (pharmacy_id),
    INDEX idx_medicine_id (medicine_id),
    
    FOREIGN KEY (pharmacy_id) REFERENCES branches(id) ON DELETE CASCADE,
    FOREIGN KEY (medicine_id) REFERENCES medicines(id) ON DELETE CASCADE,
    UNIQUE KEY unique_pharmacy_medicine (pharmacy_id, medicine_id)
);

-- Insert sample medicines data
INSERT INTO medicines (tenant_id, name, generic_name, brand_name, medicine_type, dosage_form, strength, unit, manufacturer, description, active_ingredients, side_effects, contraindications, storage_conditions, expiry_date, is_active) VALUES
(1, 'Paracetamol', 'Acetaminophen', 'Tylenol', 'tablet', 'Oral', 500, 'mg', 'Johnson & Johnson', 'Pain reliever and fever reducer', 'Acetaminophen', 'Nausea, stomach upset', 'Liver disease, alcohol abuse', 'Store at room temperature', '2025-12-31', 1),
(1, 'Ibuprofen', 'Ibuprofen', 'Advil', 'tablet', 'Oral', 400, 'mg', 'Pfizer', 'Anti-inflammatory pain reliever', 'Ibuprofen', 'Stomach irritation, dizziness', 'Stomach ulcers, kidney disease', 'Store at room temperature', '2025-12-31', 1),
(1, 'Amoxicillin', 'Amoxicillin', 'Amoxil', 'capsule', 'Oral', 250, 'mg', 'GlaxoSmithKline', 'Antibiotic for bacterial infections', 'Amoxicillin trihydrate', 'Diarrhea, nausea, rash', 'Penicillin allergy', 'Store in refrigerator', '2025-12-31', 1),
(1, 'Omeprazole', 'Omeprazole', 'Prilosec', 'capsule', 'Oral', 20, 'mg', 'AstraZeneca', 'Proton pump inhibitor for acid reflux', 'Omeprazole magnesium', 'Headache, diarrhea', 'Pregnancy, liver disease', 'Store at room temperature', '2025-12-31', 1),
(1, 'Cetirizine', 'Cetirizine', 'Zyrtec', 'tablet', 'Oral', 10, 'mg', 'Johnson & Johnson', 'Antihistamine for allergies', 'Cetirizine hydrochloride', 'Drowsiness, dry mouth', 'Kidney disease, pregnancy', 'Store at room temperature', '2025-12-31', 1);