// Prescription Generation Module
// Handles prescription creation, medicine auto-suggestion, and digital signatures

export class PrescriptionGenerator {
    constructor(options = {}) {
        this.options = {
            autoSave: true,
            autoSaveInterval: 30000, // 30 seconds
            enableDigitalSignature: true,
            enableMedicineAutoSuggestion: true,
            ...options
        };
        
        this.currentPrescription = null;
        this.autoSaveTimer = null;
        this.medicineSuggestions = [];
        this.dosageOptions = [
            '1/2 tablet', '1 tablet', '1.5 tablets', '2 tablets',
            '1/4 teaspoon', '1/2 teaspoon', '1 teaspoon',
            '1 drop', '2 drops', '3 drops',
            '1 ml', '2 ml', '5 ml', '10 ml'
        ];
        
        this.timingOptions = [
            'Once daily', 'Twice daily', 'Three times daily',
            'Every 4 hours', 'Every 6 hours', 'Every 8 hours',
            'Before meals', 'After meals', 'With meals',
            'Morning', 'Afternoon', 'Evening', 'Bedtime'
        ];
        
        this.durationOptions = [
            '3 days', '5 days', '7 days', '10 days',
            '2 weeks', '3 weeks', '1 month', '2 months',
            '3 months', '6 months', '1 year', 'As needed'
        ];
        
        this.injectionTypes = [
            'Intramuscular (IM)', 'Intravenous (IV)', 'Subcutaneous (SC)',
            'Intradermal (ID)', 'Intra-articular', 'Epidural'
        ];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadMedicineDatabase();
        this.loadDoctorProfile();
        this.loadClinicSettings();
        
        if (this.options.autoSave) {
            this.startAutoSave();
        }
    }
    
    // Setup event listeners for prescription form
    setupEventListeners() {
        // Medicine auto-suggestion
        const medicineInput = document.getElementById('medicineName');
        if (medicineInput && this.options.enableMedicineAutoSuggestion) {
            medicineInput.addEventListener('input', (e) => {
                this.handleMedicineInput(e.target.value);
            });
            
            medicineInput.addEventListener('focus', () => {
                this.showMedicineSuggestions();
            });
        }
        
        // Dosage calculation
        const dosageInputs = document.querySelectorAll('.dosage-input');
        dosageInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.calculateDosage();
            });
        });
        
        // Duration calculation
        const startDateInput = document.getElementById('startDate');
        const durationInput = document.getElementById('duration');
        if (startDateInput && durationInput) {
            startDateInput.addEventListener('change', () => {
                this.calculateEndDate();
            });
            durationInput.addEventListener('change', () => {
                this.calculateEndDate();
            });
        }
        
        // Digital signature
        if (this.options.enableDigitalSignature) {
            this.setupDigitalSignature();
        }
        
        // Form submission
        const prescriptionForm = document.getElementById('prescriptionForm');
        if (prescriptionForm) {
            prescriptionForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.savePrescription();
            });
        }
    }
    
    // Load medicine database for auto-suggestion
    async loadMedicineDatabase() {
        try {
            const response = await apiCall('GET', 'api/medicines');
            if (response.success) {
                this.medicineDatabase = response.data;
            }
        } catch (error) {
            console.error('Error loading medicine database:', error);
        }
    }
    
    // Load doctor profile information
    async loadDoctorProfile() {
        try {
            const response = await apiCall('GET', 'api/doctors/profile');
            if (response.success) {
                this.doctorProfile = response.data;
                this.populateDoctorInfo();
            }
        } catch (error) {
            console.error('Error loading doctor profile:', error);
        }
    }
    
    // Load clinic settings
    async loadClinicSettings() {
        try {
            const response = await apiCall('GET', 'api/clinic/settings');
            if (response.success) {
                this.clinicSettings = response.data;
                this.populateClinicInfo();
            }
        } catch (error) {
            console.error('Error loading clinic settings:', error);
        }
    }
    
    // Handle medicine input for auto-suggestion
    handleMedicineInput(query) {
        if (query.length < 2) {
            this.hideMedicineSuggestions();
            return;
        }
        
        const suggestions = this.medicineDatabase.filter(medicine => 
            medicine.name.toLowerCase().includes(query.toLowerCase()) ||
            medicine.generic_name.toLowerCase().includes(query.toLowerCase())
        ).slice(0, 10);
        
        this.medicineSuggestions = suggestions;
        this.showMedicineSuggestions();
    }
    
    // Show medicine suggestions dropdown
    showMedicineSuggestions() {
        let suggestionsContainer = document.getElementById('medicineSuggestions');
        if (!suggestionsContainer) {
            suggestionsContainer = document.createElement('div');
            suggestionsContainer.id = 'medicineSuggestions';
            suggestionsContainer.className = 'medicine-suggestions dropdown-menu show position-absolute';
            suggestionsContainer.style.zIndex = '1050';
            
            const medicineInput = document.getElementById('medicineName');
            if (medicineInput) {
                medicineInput.parentNode.style.position = 'relative';
                medicineInput.parentNode.appendChild(suggestionsContainer);
            }
        }
        
        if (this.medicineSuggestions.length === 0) {
            suggestionsContainer.style.display = 'none';
            return;
        }
        
        suggestionsContainer.innerHTML = '';
        suggestionsContainer.style.display = 'block';
        
        this.medicineSuggestions.forEach(medicine => {
            const item = document.createElement('div');
            item.className = 'dropdown-item medicine-suggestion-item';
            item.innerHTML = `
                <div class="d-flex justify-content-between">
                    <strong>${medicine.name}</strong>
                    <small class="text-muted">${medicine.generic_name}</small>
                </div>
                <small class="text-muted">${medicine.strength} - ${medicine.form}</small>
            `;
            
            item.addEventListener('click', () => {
                this.selectMedicine(medicine);
            });
            
            suggestionsContainer.appendChild(item);
        });
    }
    
    // Hide medicine suggestions
    hideMedicineSuggestions() {
        const suggestionsContainer = document.getElementById('medicineSuggestions');
        if (suggestionsContainer) {
            suggestionsContainer.style.display = 'none';
        }
    }
    
    // Select medicine from suggestions
    selectMedicine(medicine) {
        const medicineInput = document.getElementById('medicineName');
        const genericInput = document.getElementById('genericName');
        const strengthInput = document.getElementById('strength');
        const formInput = document.getElementById('form');
        
        if (medicineInput) medicineInput.value = medicine.name;
        if (genericInput) genericInput.value = medicine.generic_name;
        if (strengthInput) strengthInput.value = medicine.strength;
        if (formInput) formInput.value = medicine.form;
        
        this.hideMedicineSuggestions();
        
        // Auto-fill common dosage and timing
        this.autoFillCommonDosage(medicine);
    }
    
    // Auto-fill common dosage for selected medicine
    autoFillCommonDosage(medicine) {
        const dosageInput = document.getElementById('dosage');
        const timingInput = document.getElementById('timing');
        const durationInput = document.getElementById('duration');
        
        // Common dosage patterns based on medicine type
        if (medicine.category === 'Antibiotic') {
            if (dosageInput) dosageInput.value = '1 tablet';
            if (timingInput) timingInput.value = 'Twice daily';
            if (durationInput) durationInput.value = '7 days';
        } else if (medicine.category === 'Pain Relief') {
            if (dosageInput) dosageInput.value = '1 tablet';
            if (timingInput) timingInput.value = 'As needed';
            if (durationInput) durationInput.value = 'As needed';
        } else if (medicine.category === 'Vitamin') {
            if (dosageInput) dosageInput.value = '1 tablet';
            if (timingInput) timingInput.value = 'Once daily';
            if (durationInput) durationInput.value = '1 month';
        }
    }
    
    // Calculate dosage based on patient weight/age
    calculateDosage() {
        const weightInput = document.getElementById('patientWeight');
        const ageInput = document.getElementById('patientAge');
        const baseDosageInput = document.getElementById('baseDosage');
        const calculatedDosageInput = document.getElementById('calculatedDosage');
        
        if (!weightInput || !ageInput || !baseDosageInput) return;
        
        const weight = parseFloat(weightInput.value) || 0;
        const age = parseInt(ageInput.value) || 0;
        const baseDosage = parseFloat(baseDosageInput.value) || 0;
        
        if (weight > 0 && baseDosage > 0) {
            let calculatedDosage = baseDosage;
            
            // Pediatric dosing (Clark's rule: weight in kg / 70)
            if (age < 18) {
                calculatedDosage = (weight / 70) * baseDosage;
            }
            
            // Adjust for weight-based dosing
            if (weight < 50) {
                calculatedDosage *= 0.8;
            } else if (weight > 100) {
                calculatedDosage *= 1.2;
            }
            
            if (calculatedDosageInput) {
                calculatedDosageInput.value = calculatedDosage.toFixed(2);
            }
        }
    }
    
    // Calculate end date based on start date and duration
    calculateEndDate() {
        const startDateInput = document.getElementById('startDate');
        const durationInput = document.getElementById('duration');
        const endDateInput = document.getElementById('endDate');
        
        if (!startDateInput || !durationInput || !endDateInput) return;
        
        const startDate = new Date(startDateInput.value);
        const duration = durationInput.value;
        
        if (startDate && duration) {
            let endDate = new Date(startDate);
            
            if (duration.includes('days')) {
                const days = parseInt(duration);
                endDate.setDate(endDate.getDate() + days);
            } else if (duration.includes('weeks')) {
                const weeks = parseInt(duration);
                endDate.setDate(endDate.getDate() + (weeks * 7));
            } else if (duration.includes('months')) {
                const months = parseInt(duration);
                endDate.setMonth(endDate.getMonth() + months);
            }
            
            endDateInput.value = endDate.toISOString().split('T')[0];
        }
    }
    
    // Setup digital signature functionality
    setupDigitalSignature() {
        const signatureCanvas = document.getElementById('signatureCanvas');
        if (!signatureCanvas) return;
        
        const ctx = signatureCanvas.getContext('2d');
        let isDrawing = false;
        let lastX = 0;
        let lastY = 0;
        
        // Set canvas size
        signatureCanvas.width = signatureCanvas.offsetWidth;
        signatureCanvas.height = signatureCanvas.offsetHeight;
        
        // Drawing event listeners
        signatureCanvas.addEventListener('mousedown', startDrawing);
        signatureCanvas.addEventListener('mousemove', draw);
        signatureCanvas.addEventListener('mouseup', stopDrawing);
        signatureCanvas.addEventListener('mouseout', stopDrawing);
        
        // Touch support for mobile
        signatureCanvas.addEventListener('touchstart', handleTouch);
        signatureCanvas.addEventListener('touchmove', handleTouch);
        signatureCanvas.addEventListener('touchend', stopDrawing);
        
        function startDrawing(e) {
            isDrawing = true;
            const pos = getPosition(e);
            lastX = pos.x;
            lastY = pos.y;
        }
        
        function draw(e) {
            if (!isDrawing) return;
            
            const pos = getPosition(e);
            ctx.beginPath();
            ctx.moveTo(lastX, lastY);
            ctx.lineTo(pos.x, pos.y);
            ctx.strokeStyle = '#000';
            ctx.lineWidth = 2;
            ctx.stroke();
            
            lastX = pos.x;
            lastY = pos.y;
        }
        
        function stopDrawing() {
            isDrawing = false;
        }
        
        function getPosition(e) {
            const rect = signatureCanvas.getBoundingClientRect();
            const x = (e.clientX || e.touches[0].clientX) - rect.left;
            const y = (e.clientY || e.touches[0].clientX) - rect.top;
            return { x, y };
        }
        
        function handleTouch(e) {
            e.preventDefault();
            const touch = e.touches[0];
            const mouseEvent = new MouseEvent(e.type === 'touchstart' ? 'mousedown' : 
                                            e.type === 'touchmove' ? 'mousemove' : 'mouseup');
            signatureCanvas.dispatchEvent(mouseEvent);
        }
        
        // Clear signature button
        const clearSignatureBtn = document.getElementById('clearSignature');
        if (clearSignatureBtn) {
            clearSignatureBtn.addEventListener('click', () => {
                ctx.clearRect(0, 0, signatureCanvas.width, signatureCanvas.height);
            });
        }
    }
    
    // Populate doctor information in prescription header
    populateDoctorInfo() {
        if (!this.doctorProfile) return;
        
        const doctorNameElement = document.getElementById('doctorName');
        const doctorLicenseElement = document.getElementById('doctorLicense');
        const doctorSpecialtyElement = document.getElementById('doctorSpecialty');
        const doctorPhoneElement = document.getElementById('doctorPhone');
        const doctorEmailElement = document.getElementById('doctorEmail');
        
        if (doctorNameElement) doctorNameElement.textContent = this.doctorProfile.name;
        if (doctorLicenseElement) doctorLicenseElement.textContent = this.doctorProfile.license_number;
        if (doctorSpecialtyElement) doctorSpecialtyElement.textContent = this.doctorProfile.specialty;
        if (doctorPhoneElement) doctorPhoneElement.textContent = this.doctorProfile.phone;
        if (doctorEmailElement) doctorEmailElement.textContent = this.doctorProfile.email;
    }
    
    // Populate clinic information in prescription footer
    populateClinicInfo() {
        if (!this.clinicSettings) return;
        
        const clinicNameElement = document.getElementById('clinicName');
        const clinicAddressElement = document.getElementById('clinicAddress');
        const clinicPhoneElement = document.getElementById('clinicPhone');
        const clinicWebsiteElement = document.getElementById('clinicWebsite');
        
        if (clinicNameElement) clinicNameElement.textContent = this.clinicSettings.name;
        if (clinicAddressElement) clinicAddressElement.textContent = this.clinicSettings.address;
        if (clinicPhoneElement) clinicPhoneElement.textContent = this.clinicSettings.phone;
        if (clinicWebsiteElement) clinicWebsiteElement.textContent = this.clinicSettings.website;
    }
    
    // Add medicine item to prescription
    addMedicineItem() {
        const medicineItemsContainer = document.getElementById('medicineItems');
        if (!medicineItemsContainer) return;
        
        const itemDiv = document.createElement('div');
        itemDiv.className = 'medicine-item border p-3 mb-3';
        itemDiv.innerHTML = `
            <div class="row">
                <div class="col-md-3">
                    <label class="form-label">Medicine Name</label>
                    <input type="text" class="form-control medicine-name" placeholder="Enter medicine name" required>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Dosage</label>
                    <select class="form-select dosage">
                        ${this.dosageOptions.map(option => `<option value="${option}">${option}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Timing</label>
                    <select class="form-select timing">
                        ${this.timingOptions.map(option => `<option value="${option}">${option}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Duration</label>
                    <select class="form-select duration">
                        ${this.durationOptions.map(option => `<option value="${option}">${option}</option>`).join('')}
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">Type</label>
                    <select class="form-select medicine-type">
                        <option value="tablet">Tablet</option>
                        <option value="capsule">Capsule</option>
                        <option value="syrup">Syrup</option>
                        <option value="injection">Injection</option>
                        <option value="cream">Cream</option>
                        <option value="drops">Drops</option>
                    </select>
                </div>
                <div class="col-md-1">
                    <label class="form-label">&nbsp;</label>
                    <button type="button" class="btn btn-danger btn-sm d-block" onclick="removeMedicineItem(this)">
                        <i class="bi bi-trash"></i>
                    </button>
                </div>
            </div>
            <div class="row mt-2">
                <div class="col-md-6">
                    <label class="form-label">Special Instructions</label>
                    <input type="text" class="form-control special-instructions" placeholder="e.g., Take with food">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Quantity</label>
                    <input type="number" class="form-control quantity" min="1" value="1">
                </div>
                <div class="col-md-3">
                    <label class="form-label">Price</label>
                    <input type="number" class="form-control price" step="0.01" min="0">
                </div>
            </div>
        `;
        
        medicineItemsContainer.appendChild(itemDiv);
        
        // Add medicine auto-suggestion to new item
        const medicineNameInput = itemDiv.querySelector('.medicine-name');
        if (medicineNameInput) {
            medicineNameInput.addEventListener('input', (e) => {
                this.handleMedicineInput(e.target.value);
            });
        }
    }
    
    // Remove medicine item from prescription
    removeMedicineItem(button) {
        button.closest('.medicine-item').remove();
    }
    
    // Toggle injection type fields
    toggleInjectionType(medicineType) {
        const injectionFields = document.querySelectorAll('.injection-fields');
        injectionFields.forEach(field => {
            field.style.display = medicineType === 'injection' ? 'block' : 'none';
        });
    }
    
    // Calculate prescription total
    calculatePrescriptionTotal() {
        const medicineItems = document.querySelectorAll('.medicine-item');
        let total = 0;
        
        medicineItems.forEach(item => {
            const quantity = parseFloat(item.querySelector('.quantity').value) || 0;
            const price = parseFloat(item.querySelector('.price').value) || 0;
            total += quantity * price;
        });
        
        const totalElement = document.getElementById('prescriptionTotal');
        if (totalElement) {
            totalElement.textContent = total.toFixed(2);
        }
        
        return total;
    }
    
    // Save prescription
    async savePrescription() {
        const formData = this.getPrescriptionFormData();
        
        if (!this.validatePrescriptionForm(formData)) {
            return;
        }
        
        try {
            showModalLoading('prescriptionModal', true);
            
            const response = await apiCall('POST', 'api/prescriptions', formData);
            
            if (response.success) {
                showAlert('Prescription saved successfully', 'success');
                this.currentPrescription = response.data;
                this.closePrescriptionModal();
                this.loadPrescriptions(); // Refresh prescription list
            } else {
                showAlert('Error saving prescription: ' + response.message, 'danger');
            }
        } catch (error) {
            console.error('Error saving prescription:', error);
            showAlert('Error saving prescription', 'danger');
        } finally {
            showModalLoading('prescriptionModal', false);
        }
    }
    
    // Get prescription form data
    getPrescriptionFormData() {
        const form = document.getElementById('prescriptionForm');
        if (!form) return {};
        
        const formData = new FormData(form);
        const data = {};
        
        for (let [key, value] of formData.entries()) {
            data[key] = value;
        }
        
        // Add medicine items
        data.medicine_items = [];
        const medicineItems = document.querySelectorAll('.medicine-item');
        medicineItems.forEach(item => {
            const medicineData = {
                name: item.querySelector('.medicine-name').value,
                dosage: item.querySelector('.dosage').value,
                timing: item.querySelector('.timing').value,
                duration: item.querySelector('.duration').value,
                type: item.querySelector('.medicine-type').value,
                special_instructions: item.querySelector('.special-instructions').value,
                quantity: item.querySelector('.quantity').value,
                price: item.querySelector('.price').value
            };
            data.medicine_items.push(medicineData);
        });
        
        // Add digital signature if enabled
        if (this.options.enableDigitalSignature) {
            const signatureCanvas = document.getElementById('signatureCanvas');
            if (signatureCanvas) {
                data.digital_signature = signatureCanvas.toDataURL();
            }
        }
        
        return data;
    }
    
    // Validate prescription form
    validatePrescriptionForm(data) {
        if (!data.patient_name || !data.patient_age || !data.patient_gender) {
            showAlert('Please fill in all patient information', 'warning');
            return false;
        }
        
        if (!data.medicine_items || data.medicine_items.length === 0) {
            showAlert('Please add at least one medicine', 'warning');
            return false;
        }
        
        // Validate medicine items
        for (let i = 0; i < data.medicine_items.length; i++) {
            const item = data.medicine_items[i];
            if (!item.name || !item.dosage || !item.timing || !item.duration) {
                showAlert(`Please fill in all fields for medicine item ${i + 1}`, 'warning');
                return false;
            }
        }
        
        return true;
    }
    
    // Start auto-save functionality
    startAutoSave() {
        this.autoSaveTimer = setInterval(() => {
            if (this.currentPrescription) {
                this.autoSavePrescription();
            }
        }, this.options.autoSaveInterval);
    }
    
    // Stop auto-save functionality
    stopAutoSave() {
        if (this.autoSaveTimer) {
            clearInterval(this.autoSaveTimer);
            this.autoSaveTimer = null;
        }
    }
    
    // Auto-save prescription
    async autoSavePrescription() {
        try {
            const formData = this.getPrescriptionFormData();
            if (Object.keys(formData).length > 0) {
                await apiCall('PUT', `api/prescriptions/${this.currentPrescription.id}`, formData);
                console.log('Prescription auto-saved');
            }
        } catch (error) {
            console.error('Auto-save failed:', error);
        }
    }
    
    // Generate prescription PDF
    async generatePrescriptionPDF(prescriptionId) {
        try {
            const response = await apiCall('GET', `api/prescriptions/${prescriptionId}/pdf`);
            if (response.success) {
                // Download PDF
                const link = document.createElement('a');
                link.href = response.data.pdf_url;
                link.download = `prescription-${prescriptionId}.pdf`;
                link.click();
            }
        } catch (error) {
            console.error('Error generating PDF:', error);
            showAlert('Error generating PDF', 'danger');
        }
    }
    
    // Print prescription
    printPrescription(prescriptionId) {
        const printWindow = window.open(`/prescriptions/${prescriptionId}/print`, '_blank');
        if (printWindow) {
            printWindow.print();
        }
    }
    
    // Close prescription modal
    closePrescriptionModal() {
        const modal = document.getElementById('prescriptionModal');
        if (modal) {
            closeModal('prescriptionModal');
        }
    }
    
    // Load prescriptions list
    async loadPrescriptions() {
        try {
            const response = await apiCall('GET', 'api/prescriptions');
            if (response.success) {
                this.displayPrescriptions(response.data);
            }
        } catch (error) {
            console.error('Error loading prescriptions:', error);
        }
    }
    
    // Display prescriptions in table
    displayPrescriptions(prescriptions) {
        const tbody = document.querySelector('#prescriptions-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        prescriptions.forEach(prescription => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${prescription.patient_name}</td>
                <td>${prescription.patient_age} years</td>
                <td>${prescription.medicine_items.length} medicines</td>
                <td>${prescription.created_at}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="editPrescription(${prescription.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="generatePrescriptionPDF(${prescription.id})">
                        <i class="bi bi-file-pdf"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="printPrescription(${prescription.id})">
                        <i class="bi bi-printer"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Destroy instance and cleanup
    destroy() {
        this.stopAutoSave();
        // Remove event listeners and cleanup
    }
}

// Utility functions for prescription management
export function createPrescriptionForm(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
    const form = document.createElement('form');
    form.id = 'prescriptionForm';
    form.className = 'prescription-form';
    
    form.innerHTML = `
        <!-- Prescription Header -->
        <div class="prescription-header border-bottom pb-3 mb-3">
            <div class="row">
                <div class="col-md-6">
                    <h4>Doctor Information</h4>
                    <p><strong>Name:</strong> <span id="doctorName">Dr. John Doe</span></p>
                    <p><strong>License:</strong> <span id="doctorLicense">MD12345</span></p>
                    <p><strong>Specialty:</strong> <span id="doctorSpecialty">General Medicine</span></p>
                    <p><strong>Phone:</strong> <span id="doctorPhone">+1-555-0123</span></p>
                    <p><strong>Email:</strong> <span id="doctorEmail">doctor@clinic.com</span></p>
                </div>
                <div class="col-md-6">
                    <h4>Patient Information</h4>
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Patient Name</label>
                            <input type="text" class="form-control" name="patient_name" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Age</label>
                            <input type="number" class="form-control" name="patient_age" min="0" max="150" required>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <label class="form-label">Gender</label>
                            <select class="form-select" name="patient_gender" required>
                                <option value="">Select Gender</option>
                                <option value="male">Male</option>
                                <option value="female">Female</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Weight (kg)</label>
                            <input type="number" class="form-control" name="patient_weight" step="0.1" min="0">
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Prescription Body -->
        <div class="prescription-body mb-3">
            <h4>Medicines</h4>
            <div id="medicineItems">
                <!-- Medicine items will be added here -->
            </div>
            <button type="button" class="btn btn-outline-primary" onclick="addMedicineItem()">
                <i class="bi bi-plus"></i> Add Medicine
            </button>
        </div>
        
        <!-- Prescription Footer -->
        <div class="prescription-footer border-top pt-3">
            <div class="row">
                <div class="col-md-6">
                    <h4>Clinic Information</h4>
                    <p><strong>Name:</strong> <span id="clinicName">City Medical Clinic</span></p>
                    <p><strong>Address:</strong> <span id="clinicAddress">123 Medical Center Dr, City, State</span></p>
                    <p><strong>Phone:</strong> <span id="clinicPhone">+1-555-0123</span></p>
                    <p><strong>Website:</strong> <span id="clinicWebsite">www.clinic.com</span></p>
                </div>
                <div class="col-md-6">
                    <h4>Digital Signature</h4>
                    <canvas id="signatureCanvas" class="border" width="300" height="100"></canvas>
                    <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="clearSignature">
                        Clear Signature
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Form Actions -->
        <div class="form-actions mt-3">
            <button type="submit" class="btn btn-primary">Save Prescription</button>
            <button type="button" class="btn btn-secondary" onclick="closePrescriptionModal()">Cancel</button>
        </div>
    `;
    
    container.appendChild(form);
    
    // Initialize prescription generator
    const prescriptionGenerator = new PrescriptionGenerator();
    
    return prescriptionGenerator;
}

// Global functions for HTML onclick handlers
window.addMedicineItem = function() {
    if (window.currentPrescriptionGenerator) {
        window.currentPrescriptionGenerator.addMedicineItem();
    }
};

window.removeMedicineItem = function(button) {
    if (window.currentPrescriptionGenerator) {
        window.currentPrescriptionGenerator.removeMedicineItem(button);
    }
};

window.generatePrescriptionPDF = function(prescriptionId) {
    if (window.currentPrescriptionGenerator) {
        window.currentPrescriptionGenerator.generatePrescriptionPDF(prescriptionId);
    }
};

window.printPrescription = function(prescriptionId) {
    if (window.currentPrescriptionGenerator) {
        window.currentPrescriptionGenerator.printPrescription(prescriptionId);
    }
};