// Laboratory Commission Management Module
// Handles doctor-laboratory partnerships, test commission calculations, and tracking

export class LaboratoryCommissionManager {
    constructor(options = {}) {
        this.options = {
            enableAutoCalculation: true,
            enableCommissionTracking: true,
            defaultCommissionRate: 0.20, // 20% default commission for lab tests
            ...options
        };
        
        this.currentCommission = null;
        this.commissionTypes = [
            'Percentage', 'Fixed Amount', 'Tiered', 'Test Category Based'
        ];
        
        this.testCategories = [
            'Blood Tests', 'Urine Tests', 'Imaging', 'Pathology',
            'Microbiology', 'Biochemistry', 'Hematology', 'Immunology'
        ];
        
        this.commissionStatuses = [
            'Active', 'Inactive', 'Suspended', 'Terminated'
        ];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadLaboratoryPartnerships();
        this.loadCommissionSettings();
    }
    
    // Setup event listeners for commission forms
    setupEventListeners() {
        // Commission type change
        const commissionTypeSelect = document.getElementById('labCommissionType');
        if (commissionTypeSelect) {
            commissionTypeSelect.addEventListener('change', () => {
                this.toggleLabCommissionFields();
            });
        }
        
        // Commission rate calculation
        const commissionRateInput = document.getElementById('labCommissionRate');
        if (commissionRateInput) {
            commissionRateInput.addEventListener('input', () => {
                this.calculateLabCommission();
            });
        }
        
        // Test selection for commission
        const testSelect = document.getElementById('labTestId');
        if (testSelect) {
            testSelect.addEventListener('change', () => {
                this.loadLabTestCommission();
            });
        }
        
        // Test category change
        const testCategorySelect = document.getElementById('testCategory');
        if (testCategorySelect) {
            testCategorySelect.addEventListener('change', () => {
                this.updateTestCategoryCommission();
            });
        }
        
        // Auto-calculation toggle
        const autoCalcToggle = document.getElementById('enableLabAutoCalculation');
        if (autoCalcToggle) {
            autoCalcToggle.addEventListener('change', () => {
                this.toggleLabAutoCalculation();
            });
        }
    }
    
    // Load laboratory partnerships
    async loadLaboratoryPartnerships() {
        try {
            const response = await apiCall('GET', 'api/laboratory/partnerships');
            if (response.success) {
                this.laboratoryPartnerships = response.data;
                this.displayLaboratoryPartnerships();
            }
        } catch (error) {
            console.error('Error loading laboratory partnerships:', error);
        }
    }
    
    // Load commission settings
    async loadCommissionSettings() {
        try {
            const response = await apiCall('GET', 'api/laboratory/commission/settings');
            if (response.success) {
                this.commissionSettings = response.data;
                this.populateLabCommissionSettings();
            }
        } catch (error) {
            console.error('Error loading laboratory commission settings:', error);
        }
    }
    
    // Display laboratory partnerships
    displayLaboratoryPartnerships() {
        const tbody = document.querySelector('#laboratoryPartnerships-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        this.laboratoryPartnerships.forEach(partnership => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${partnership.laboratory_name}</td>
                <td>${partnership.doctor_name}</td>
                <td>${partnership.commission_type}</td>
                <td>${this.formatLabCommissionRate(partnership.commission_rate, partnership.commission_type)}</td>
                <td>$${partnership.total_commission || 0}</td>
                <td>$${partnership.pending_commission || 0}</td>
                <td>
                    <span class="badge bg-${this.getLabCommissionStatusColor(partnership.status)}">
                        ${partnership.status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewLaboratoryPartnership(${partnership.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="editLaboratoryPartnership(${partnership.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteLaboratoryPartnership(${partnership.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Populate laboratory commission settings in UI
    populateLabCommissionSettings() {
        if (!this.commissionSettings) return;
        
        // Default commission rates by test category
        const defaultRatesContainer = document.getElementById('defaultLabCommissionRates');
        if (defaultRatesContainer) {
            defaultRatesContainer.innerHTML = '';
            
            this.commissionSettings.default_rates.forEach(rate => {
                const rateRow = document.createElement('div');
                rateRow.className = 'row mb-2';
                rateRow.innerHTML = `
                    <div class="col-md-4">
                        <strong>${rate.category}</strong>
                    </div>
                    <div class="col-md-4">
                        ${this.formatLabCommissionRate(rate.rate, rate.type)}
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-sm btn-outline-primary" onclick="editDefaultLabCommissionRate(${rate.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                `;
                defaultRatesContainer.appendChild(rateRow);
            });
        }
        
        // Commission calculation rules
        const calculationRulesContainer = document.getElementById('labCommissionCalculationRules');
        if (calculationRulesContainer) {
            calculationRulesContainer.innerHTML = this.commissionSettings.calculation_rules || 'Standard percentage-based calculation for laboratory tests';
        }
    }
    
    // Format laboratory commission rate display
    formatLabCommissionRate(rate, type) {
        if (type === 'Percentage') {
            return `${rate}%`;
        } else if (type === 'Fixed Amount') {
            return `$${rate}`;
        } else if (type === 'Tiered') {
            return `Tiered (${rate})`;
        } else if (type === 'Test Category Based') {
            return `Category-based (${rate})`;
        }
        return rate;
    }
    
    // Get laboratory commission status color
    getLabCommissionStatusColor(status) {
        const statusColors = {
            'Active': 'success',
            'Inactive': 'secondary',
            'Suspended': 'warning',
            'Terminated': 'danger'
        };
        return statusColors[status] || 'secondary';
    }
    
    // Toggle laboratory commission fields based on type
    toggleLabCommissionFields() {
        const commissionType = document.getElementById('labCommissionType')?.value;
        const percentageFields = document.querySelectorAll('.lab-percentage-fields');
        const fixedAmountFields = document.querySelectorAll('.lab-fixed-amount-fields');
        const tieredFields = document.querySelectorAll('.lab-tiered-fields');
        const categoryFields = document.querySelectorAll('.lab-category-fields');
        
        // Hide all fields first
        [percentageFields, fixedAmountFields, tieredFields, categoryFields].forEach(fieldGroup => {
            fieldGroup.forEach(field => {
                field.style.display = 'none';
            });
        });
        
        // Show relevant fields
        switch (commissionType) {
            case 'Percentage':
                percentageFields.forEach(field => field.style.display = 'block');
                break;
            case 'Fixed Amount':
                fixedAmountFields.forEach(field => field.style.display = 'block');
                break;
            case 'Tiered':
                tieredFields.forEach(field => field.style.display = 'block');
                break;
            case 'Test Category Based':
                categoryFields.forEach(field => field.style.display = 'block');
                break;
        }
    }
    
    // Calculate laboratory commission based on type and rate
    calculateLabCommission() {
        const commissionType = document.getElementById('labCommissionType')?.value;
        const commissionRate = parseFloat(document.getElementById('labCommissionRate')?.value || 0);
        const testPrice = parseFloat(document.getElementById('labTestPrice')?.value || 0);
        const testCategory = document.getElementById('testCategory')?.value;
        
        let commission = 0;
        
        switch (commissionType) {
            case 'Percentage':
                commission = (testPrice * commissionRate) / 100;
                break;
            case 'Fixed Amount':
                commission = commissionRate;
                break;
            case 'Tiered':
                commission = this.calculateLabTieredCommission(testPrice, commissionRate);
                break;
            case 'Test Category Based':
                commission = this.calculateCategoryBasedCommission(testPrice, testCategory, commissionRate);
                break;
        }
        
        // Update commission display
        const commissionDisplay = document.getElementById('calculatedLabCommission');
        if (commissionDisplay) {
            commissionDisplay.textContent = `$${commission.toFixed(2)}`;
        }
        
        return commission;
    }
    
    // Calculate tiered laboratory commission
    calculateLabTieredCommission(testPrice, tierRates) {
        try {
            const tiers = JSON.parse(tierRates);
            let commission = 0;
            
            tiers.forEach(tier => {
                if (testPrice >= tier.min && testPrice <= tier.max) {
                    if (tier.type === 'percentage') {
                        commission = (testPrice * tier.rate) / 100;
                    } else {
                        commission = tier.rate;
                    }
                }
            });
            
            return commission;
        } catch (error) {
            return 0;
        }
    }
    
    // Calculate category-based commission
    calculateCategoryBasedCommission(testPrice, testCategory, categoryRates) {
        try {
            const rates = JSON.parse(categoryRates);
            let commission = 0;
            
            const categoryRate = rates.find(rate => rate.category === testCategory);
            if (categoryRate) {
                if (categoryRate.type === 'percentage') {
                    commission = (testPrice * categoryRate.rate) / 100;
                } else {
                    commission = categoryRate.rate;
                }
            }
            
            return commission;
        } catch (error) {
            return 0;
        }
    }
    
    // Load laboratory test commission information
    async loadLabTestCommission() {
        const testId = document.getElementById('labTestId')?.value;
        if (!testId) return;
        
        try {
            const response = await apiCall('GET', `api/laboratory-tests/${testId}/commission`);
            if (response.success) {
                this.populateLabTestCommission(response.data);
            }
        } catch (error) {
            console.error('Error loading laboratory test commission:', error);
        }
    }
    
    // Populate laboratory test commission information
    populateLabTestCommission(commissionData) {
        const testPriceInput = document.getElementById('labTestPrice');
        const testCategoryInput = document.getElementById('testCategory');
        const defaultCommissionInput = document.getElementById('defaultLabCommission');
        
        if (testPriceInput) {
            testPriceInput.value = commissionData.price || 0;
        }
        
        if (testCategoryInput) {
            testCategoryInput.value = commissionData.category || '';
        }
        
        if (defaultCommissionInput) {
            defaultCommissionInput.value = commissionData.default_commission || this.options.defaultCommissionRate;
        }
        
        // Calculate commission
        this.calculateLabCommission();
    }
    
    // Update test category commission
    updateTestCategoryCommission() {
        const testCategory = document.getElementById('testCategory')?.value;
        if (testCategory) {
            this.calculateLabCommission();
        }
    }
    
    // Toggle laboratory auto-calculation
    toggleLabAutoCalculation() {
        const autoCalcToggle = document.getElementById('enableLabAutoCalculation');
        this.options.enableAutoCalculation = autoCalcToggle?.checked || false;
        
        const autoCalcFields = document.querySelectorAll('.lab-auto-calculation-fields');
        autoCalcFields.forEach(field => {
            field.style.display = this.options.enableAutoCalculation ? 'block' : 'none';
        });
    }
    
    // Create laboratory partnership
    async createLaboratoryPartnership(partnershipData) {
        try {
            const response = await apiCall('POST', 'api/laboratory/partnerships', partnershipData);
            if (response.success) {
                showAlert('Laboratory partnership created successfully', 'success');
                this.loadLaboratoryPartnerships(); // Refresh list
                return response.data;
            } else {
                showAlert('Error creating partnership: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error creating laboratory partnership:', error);
            showAlert('Error creating partnership', 'danger');
            return null;
        }
    }
    
    // Update laboratory partnership
    async updateLaboratoryPartnership(partnershipId, partnershipData) {
        try {
            const response = await apiCall('PUT', `api/laboratory/partnerships/${partnershipId}`, partnershipData);
            if (response.success) {
                showAlert('Laboratory partnership updated successfully', 'success');
                this.loadLaboratoryPartnerships(); // Refresh list
                return response.data;
            } else {
                showAlert('Error updating partnership: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error updating laboratory partnership:', error);
            showAlert('Error updating partnership', 'danger');
            return null;
        }
    }
    
    // Delete laboratory partnership
    async deleteLaboratoryPartnership(partnershipId) {
        if (!confirm('Are you sure you want to delete this laboratory partnership?')) {
            return;
        }
        
        try {
            const response = await apiCall('DELETE', `api/laboratory/partnerships/${partnershipId}`);
            if (response.success) {
                showAlert('Laboratory partnership deleted successfully', 'success');
                this.loadLaboratoryPartnerships(); // Refresh list
                return true;
            } else {
                showAlert('Error deleting partnership: ' + response.message, 'danger');
                return false;
            }
        } catch (error) {
            console.error('Error deleting laboratory partnership:', error);
            showAlert('Error deleting partnership', 'danger');
            return false;
        }
    }
    
    // Calculate commission for a laboratory test transaction
    async calculateLabTestCommission(transactionData) {
        try {
            const response = await apiCall('POST', 'api/laboratory/commission/calculate', transactionData);
            if (response.success) {
                return response.data;
            } else {
                showAlert('Error calculating laboratory commission: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error calculating laboratory commission:', error);
            showAlert('Error calculating commission', 'danger');
            return null;
        }
    }
    
    // Record laboratory commission payment
    async recordLabCommissionPayment(commissionId, paymentData) {
        try {
            const response = await apiCall('POST', `api/laboratory/commission/${commissionId}/payment`, paymentData);
            if (response.success) {
                showAlert('Laboratory commission payment recorded successfully', 'success');
                this.loadLabCommissionPayments(); // Refresh payments
                return response.data;
            } else {
                showAlert('Error recording laboratory commission payment: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error recording laboratory commission payment:', error);
            showAlert('Error recording commission payment', 'danger');
            return null;
        }
    }
    
    // Load laboratory commission payments
    async loadLabCommissionPayments(filters = {}) {
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const response = await apiCall('GET', `api/laboratory/commission/payments?${queryParams}`);
            
            if (response.success) {
                this.displayLabCommissionPayments(response.data);
                return response.data;
            } else {
                showAlert('Error loading laboratory commission payments: ' + response.message, 'danger');
                return [];
            }
        } catch (error) {
            console.error('Error loading laboratory commission payments:', error);
            showAlert('Error loading commission payments', 'danger');
            return [];
        }
    }
    
    // Display laboratory commission payments
    displayLabCommissionPayments(payments) {
        const tbody = document.querySelector('#labCommissionPayments-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        payments.forEach(payment => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${payment.laboratory_name}</td>
                <td>${payment.doctor_name}</td>
                <td>${payment.test_name}</td>
                <td>${payment.test_category}</td>
                <td>$${payment.test_price}</td>
                <td>$${payment.commission_amount}</td>
                <td>${payment.payment_date}</td>
                <td>
                    <span class="badge bg-${payment.status === 'Paid' ? 'success' : 'warning'}">
                        ${payment.status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewLabCommissionPayment(${payment.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${payment.status !== 'Paid' ? `
                        <button class="btn btn-sm btn-success" onclick="markLabCommissionPaid(${payment.id})">
                            <i class="bi bi-check"></i>
                        </button>
                    ` : ''}
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Mark laboratory commission as paid
    async markLabCommissionPaid(commissionId) {
        try {
            const response = await apiCall('PUT', `api/laboratory/commission/${commissionId}/mark-paid`);
            if (response.success) {
                showAlert('Laboratory commission marked as paid successfully', 'success');
                this.loadLabCommissionPayments(); // Refresh payments
                return response.data;
            } else {
                showAlert('Error marking laboratory commission as paid: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error marking laboratory commission as paid:', error);
            showAlert('Error marking commission as paid', 'danger');
            return null;
        }
    }
    
    // Generate laboratory commission report
    async generateLabCommissionReport(filters = {}) {
        try {
            const response = await apiCall('POST', 'api/laboratory/commission/report', filters);
            if (response.success) {
                this.downloadLabCommissionReport(response.data);
                return response.data;
            } else {
                showAlert('Error generating laboratory commission report: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error generating laboratory commission report:', error);
            showAlert('Error generating commission report', 'danger');
            return null;
        }
    }
    
    // Download laboratory commission report
    downloadLabCommissionReport(reportData) {
        const csvContent = this.convertLabCommissionReportToCSV(reportData);
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `lab-commission-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    // Convert laboratory commission report to CSV
    convertLabCommissionReportToCSV(reportData) {
        const headers = ['Date', 'Laboratory', 'Doctor', 'Test Name', 'Category', 'Price', 'Commission', 'Status'];
        const rows = reportData.map(payment => [
            payment.date,
            payment.laboratory_name,
            payment.doctor_name,
            payment.test_name,
            payment.test_category,
            payment.test_price,
            payment.commission_amount,
            payment.status
        ]);
        
        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }
    
    // Get laboratory commission statistics
    async getLabCommissionStatistics(period = 'month') {
        try {
            const response = await apiCall('GET', `api/laboratory/commission/statistics?period=${period}`);
            if (response.success) {
                return response.data;
            } else {
                showAlert('Error loading laboratory commission statistics: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error loading laboratory commission statistics:', error);
            showAlert('Error loading commission statistics', 'danger');
            return null;
        }
    }
    
    // Display laboratory commission statistics
    displayLabCommissionStatistics(statistics) {
        if (!statistics) return;
        
        // Update dashboard cards
        const elements = {
            'totalLabCommission': statistics.total_commission,
            'pendingLabCommission': statistics.pending_commission,
            'paidLabCommission': statistics.paid_commission,
            'labMonthlyGrowth': statistics.monthly_growth
        };
        
        Object.keys(elements).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                if (key === 'labMonthlyGrowth') {
                    element.textContent = `${elements[key]}%`;
                    element.className = `text-${elements[key] >= 0 ? 'success' : 'danger'}`;
                } else {
                    element.textContent = `$${elements[key].toLocaleString()}`;
                }
            }
        });
        
        // Update charts if they exist
        this.updateLabCommissionCharts(statistics);
    }
    
    // Update laboratory commission charts
    updateLabCommissionCharts(statistics) {
        // Commission trend chart
        const labCommissionChart = document.getElementById('labCommissionChart');
        if (labCommissionChart && statistics.commission_chart_data) {
            this.updateChart(labCommissionChart, statistics.commission_chart_data, 'Laboratory Commission Trend');
        }
        
        // Test category performance chart
        const testCategoryChart = document.getElementById('testCategoryChart');
        if (testCategoryChart && statistics.test_category_data) {
            this.updateChart(testCategoryChart, statistics.test_category_data, 'Test Category Performance');
        }
    }
    
    // Update chart with new data
    updateChart(canvas, data, label) {
        if (window[canvas.id]) {
            window[canvas.id].destroy();
        }
        
        const ctx = canvas.getContext('2d');
        window[canvas.id] = new Chart(ctx, {
            type: 'line',
            data: {
                labels: data.labels,
                datasets: [{
                    label: label,
                    data: data.values,
                    borderColor: 'rgb(255, 99, 132)',
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });
    }
    
    // View laboratory partnership details
    async viewLaboratoryPartnership(partnershipId) {
        try {
            const response = await apiCall('GET', `api/laboratory/partnerships/${partnershipId}`);
            if (response.success) {
                this.showLabPartnershipDetailsModal(response.data);
            } else {
                showAlert('Error loading laboratory partnership details: ' + response.message, 'danger');
            }
        } catch (error) {
            console.error('Error loading laboratory partnership details:', error);
            showAlert('Error loading partnership details', 'danger');
        }
    }
    
    // Show laboratory partnership details modal
    showLabPartnershipDetailsModal(partnership) {
        const modalId = 'labPartnershipDetailsModal';
        const modalContent = `
            <div class="modal-header">
                <h5 class="modal-title">Laboratory Partnership Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Laboratory Information</h6>
                        <p><strong>Name:</strong> ${partnership.laboratory_name}</p>
                        <p><strong>Address:</strong> ${partnership.laboratory_address || 'N/A'}</p>
                        <p><strong>Phone:</strong> ${partnership.laboratory_phone || 'N/A'}</p>
                        <p><strong>Email:</strong> ${partnership.laboratory_email || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Partnership Details</h6>
                        <p><strong>Commission Type:</strong> ${partnership.commission_type}</p>
                        <p><strong>Commission Rate:</strong> ${this.formatLabCommissionRate(partnership.commission_rate, partnership.commission_type)}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${this.getLabCommissionStatusColor(partnership.status)}">${partnership.status}</span></p>
                        <p><strong>Start Date:</strong> ${partnership.start_date}</p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Commission Summary by Test Category</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Test Category</th>
                                        <th>Total Tests</th>
                                        <th>Total Revenue</th>
                                        <th>Commission Earned</th>
                                        <th>Commission Paid</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${partnership.commission_summary?.map(summary => `
                                        <tr>
                                            <td>${summary.test_category}</td>
                                            <td>${summary.total_tests}</td>
                                            <td>$${summary.total_revenue}</td>
                                            <td>$${summary.commission_earned}</td>
                                            <td>$${summary.commission_paid}</td>
                                            <td>$${summary.commission_pending}</td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="6">No commission data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="editLaboratoryPartnership(${partnership.id})">
                    Edit Partnership
                </button>
            </div>
        `;
        
        createModal(modalId, 'Laboratory Partnership Details', modalContent, {
            size: 'modal-lg',
            showFooter: true
        });
        
        openModal(modalId);
    }
    
    // Destroy instance and cleanup
    destroy() {
        // Cleanup event listeners and timers
    }
}

// Utility functions for laboratory commission management
export function createLaboratoryCommissionForm(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
    const form = document.createElement('form');
    form.id = 'laboratoryCommissionForm';
    form.className = 'laboratory-commission-form';
    
    form.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>Partnership Details</h5>
                <div class="mb-3">
                    <label class="form-label">Laboratory</label>
                    <select class="form-select" id="laboratoryId" required>
                        <option value="">Select Laboratory</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Doctor</label>
                    <select class="form-select" id="doctorId" required>
                        <option value="">Select Doctor</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Commission Type</label>
                    <select class="form-select" id="labCommissionType" required>
                        <option value="">Select Type</option>
                        <option value="Percentage">Percentage</option>
                        <option value="Fixed Amount">Fixed Amount</option>
                        <option value="Tiered">Tiered</option>
                        <option value="Test Category Based">Test Category Based</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <h5>Commission Settings</h5>
                <div class="mb-3 lab-percentage-fields" style="display: none;">
                    <label class="form-label">Commission Rate (%)</label>
                    <input type="number" class="form-control" id="labCommissionRate" step="0.1" min="0" max="100">
                </div>
                <div class="mb-3 lab-fixed-amount-fields" style="display: none;">
                    <label class="form-label">Commission Amount ($)</label>
                    <input type="number" class="form-control" id="labCommissionRate" step="0.01" min="0">
                </div>
                <div class="mb-3 lab-tiered-fields" style="display: none;">
                    <label class="form-label">Tiered Commission Structure</label>
                    <textarea class="form-control" id="labCommissionRate" rows="3" placeholder='[{"min": 0, "max": 100, "type": "percentage", "rate": 10}, {"min": 101, "max": 500, "type": "fixed", "rate": 15}]'></textarea>
                </div>
                <div class="mb-3 lab-category-fields" style="display: none;">
                    <label class="form-label">Category Commission Structure</label>
                    <textarea class="form-control" id="labCommissionRate" rows="3" placeholder='[{"category": "Blood Tests", "type": "percentage", "rate": 15}, {"category": "Imaging", "type": "fixed", "rate": 25}]'></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="labPartnershipStatus">
                        <option value="Active">Active</option>
                        <option value="Inactive">Inactive</option>
                        <option value="Suspended">Suspended</option>
                    </select>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h5>Commission Calculation</h5>
                <div class="row">
                    <div class="col-md-3">
                        <label class="form-label">Laboratory Test</label>
                        <select class="form-select" id="labTestId">
                            <option value="">Select Test</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Test Category</label>
                        <select class="form-select" id="testCategory">
                            <option value="">Select Category</option>
                            ${this.testCategories.map(category => `<option value="${category}">${category}</option>`).join('')}
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Test Price</label>
                        <input type="number" class="form-control" id="labTestPrice" step="0.01" min="0" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Commission</label>
                        <div class="form-control-plaintext" id="calculatedLabCommission">$0.00</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="enableLabAutoCalculation" checked>
                    <label class="form-check-label" for="enableLabAutoCalculation">
                        Enable automatic commission calculation
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-actions mt-3">
            <button type="submit" class="btn btn-primary">Save Partnership</button>
            <button type="button" class="btn btn-secondary" onclick="closeLaboratoryCommissionModal()">Cancel</button>
        </div>
    `;
    
    container.appendChild(form);
    
    // Initialize laboratory commission manager
    const commissionManager = new LaboratoryCommissionManager();
    
    return commissionManager;
}

// Global functions for HTML onclick handlers
window.viewLaboratoryPartnership = function(partnershipId) {
    if (window.currentLaboratoryCommissionManager) {
        window.currentLaboratoryCommissionManager.viewLaboratoryPartnership(partnershipId);
    }
};

window.editLaboratoryPartnership = function(partnershipId) {
    // Implementation for editing laboratory partnerships
    console.log('Edit laboratory partnership:', partnershipId);
};

window.deleteLaboratoryPartnership = function(partnershipId) {
    if (window.currentLaboratoryCommissionManager) {
        window.currentLaboratoryCommissionManager.deleteLaboratoryPartnership(partnershipId);
    }
};

window.viewLabCommissionPayment = function(paymentId) {
    // Implementation for viewing laboratory commission payments
    console.log('View laboratory commission payment:', paymentId);
};

window.markLabCommissionPaid = function(commissionId) {
    if (window.currentLaboratoryCommissionManager) {
        window.currentLaboratoryCommissionManager.markLabCommissionPaid(commissionId);
    }
};

window.editDefaultLabCommissionRate = function(rateId) {
    // Implementation for editing default laboratory commission rates
    console.log('Edit default laboratory commission rate:', rateId);
};