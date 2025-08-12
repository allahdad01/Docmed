// Pharmacy Commission Management Module
// Handles doctor-pharmacy partnerships, commission calculations, and tracking

export class PharmacyCommissionManager {
    constructor(options = {}) {
        this.options = {
            enableAutoCalculation: true,
            enableCommissionTracking: true,
            defaultCommissionRate: 0.15, // 15% default commission
            ...options
        };
        
        this.currentCommission = null;
        this.commissionTypes = [
            'Percentage', 'Fixed Amount', 'Tiered', 'Volume Based'
        ];
        
        this.commissionStatuses = [
            'Active', 'Inactive', 'Suspended', 'Terminated'
        ];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadPharmacyPartnerships();
        this.loadCommissionSettings();
    }
    
    // Setup event listeners for commission forms
    setupEventListeners() {
        // Commission type change
        const commissionTypeSelect = document.getElementById('commissionType');
        if (commissionTypeSelect) {
            commissionTypeSelect.addEventListener('change', () => {
                this.toggleCommissionFields();
            });
        }
        
        // Commission rate calculation
        const commissionRateInput = document.getElementById('commissionRate');
        if (commissionRateInput) {
            commissionRateInput.addEventListener('input', () => {
                this.calculateCommission();
            });
        }
        
        // Medicine selection for commission
        const medicineSelect = document.getElementById('medicineId');
        if (medicineSelect) {
            medicineSelect.addEventListener('change', () => {
                this.loadMedicineCommission();
            });
        }
        
        // Auto-calculation toggle
        const autoCalcToggle = document.getElementById('enableAutoCalculation');
        if (autoCalcToggle) {
            autoCalcToggle.addEventListener('change', () => {
                this.toggleAutoCalculation();
            });
        }
    }
    
    // Load pharmacy partnerships
    async loadPharmacyPartnerships() {
        try {
            const response = await apiCall('GET', 'api/pharmacy/partnerships');
            if (response.success) {
                this.pharmacyPartnerships = response.data;
                this.displayPharmacyPartnerships();
            }
        } catch (error) {
            console.error('Error loading pharmacy partnerships:', error);
        }
    }
    
    // Load commission settings
    async loadCommissionSettings() {
        try {
            const response = await apiCall('GET', 'api/pharmacy/commission/settings');
            if (response.success) {
                this.commissionSettings = response.data;
                this.populateCommissionSettings();
            }
        } catch (error) {
            console.error('Error loading commission settings:', error);
        }
    }
    
    // Display pharmacy partnerships
    displayPharmacyPartnerships() {
        const tbody = document.querySelector('#pharmacyPartnerships-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        this.pharmacyPartnerships.forEach(partnership => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${partnership.pharmacy_name}</td>
                <td>${partnership.doctor_name}</td>
                <td>${partnership.commission_type}</td>
                <td>${this.formatCommissionRate(partnership.commission_rate, partnership.commission_type)}</td>
                <td>$${partnership.total_commission || 0}</td>
                <td>$${partnership.pending_commission || 0}</td>
                <td>
                    <span class="badge bg-${this.getCommissionStatusColor(partnership.status)}">
                        ${partnership.status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewPharmacyPartnership(${partnership.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="editPharmacyPartnership(${partnership.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deletePharmacyPartnership(${partnership.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Populate commission settings in UI
    populateCommissionSettings() {
        if (!this.commissionSettings) return;
        
        // Default commission rates
        const defaultRatesContainer = document.getElementById('defaultCommissionRates');
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
                        ${this.formatCommissionRate(rate.rate, rate.type)}
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-sm btn-outline-primary" onclick="editDefaultCommissionRate(${rate.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                `;
                defaultRatesContainer.appendChild(rateRow);
            });
        }
        
        // Commission calculation rules
        const calculationRulesContainer = document.getElementById('commissionCalculationRules');
        if (calculationRulesContainer) {
            calculationRulesContainer.innerHTML = this.commissionSettings.calculation_rules || 'Standard percentage-based calculation';
        }
    }
    
    // Format commission rate display
    formatCommissionRate(rate, type) {
        if (type === 'Percentage') {
            return `${rate}%`;
        } else if (type === 'Fixed Amount') {
            return `$${rate}`;
        } else if (type === 'Tiered') {
            return `Tiered (${rate})`;
        } else if (type === 'Volume Based') {
            return `Volume (${rate})`;
        }
        return rate;
    }
    
    // Get commission status color
    getCommissionStatusColor(status) {
        const statusColors = {
            'Active': 'success',
            'Inactive': 'secondary',
            'Suspended': 'warning',
            'Terminated': 'danger'
        };
        return statusColors[status] || 'secondary';
    }
    
    // Toggle commission fields based on type
    toggleCommissionFields() {
        const commissionType = document.getElementById('commissionType')?.value;
        const percentageFields = document.querySelectorAll('.percentage-fields');
        const fixedAmountFields = document.querySelectorAll('.fixed-amount-fields');
        const tieredFields = document.querySelectorAll('.tiered-fields');
        const volumeFields = document.querySelectorAll('.volume-fields');
        
        // Hide all fields first
        [percentageFields, fixedAmountFields, tieredFields, volumeFields].forEach(fieldGroup => {
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
            case 'Volume Based':
                volumeFields.forEach(field => field.style.display = 'block');
                break;
        }
    }
    
    // Calculate commission based on type and rate
    calculateCommission() {
        const commissionType = document.getElementById('commissionType')?.value;
        const commissionRate = parseFloat(document.getElementById('commissionRate')?.value || 0);
        const medicinePrice = parseFloat(document.getElementById('medicinePrice')?.value || 0);
        const quantity = parseInt(document.getElementById('quantity')?.value || 1);
        
        let commission = 0;
        
        switch (commissionType) {
            case 'Percentage':
                commission = (medicinePrice * quantity * commissionRate) / 100;
                break;
            case 'Fixed Amount':
                commission = commissionRate * quantity;
                break;
            case 'Tiered':
                commission = this.calculateTieredCommission(medicinePrice * quantity, commissionRate);
                break;
            case 'Volume Based':
                commission = this.calculateVolumeCommission(quantity, commissionRate);
                break;
        }
        
        // Update commission display
        const commissionDisplay = document.getElementById('calculatedCommission');
        if (commissionDisplay) {
            commissionDisplay.textContent = `$${commission.toFixed(2)}`;
        }
        
        return commission;
    }
    
    // Calculate tiered commission
    calculateTieredCommission(totalAmount, tierRates) {
        try {
            const tiers = JSON.parse(tierRates);
            let commission = 0;
            
            tiers.forEach(tier => {
                if (totalAmount >= tier.min && totalAmount <= tier.max) {
                    commission = (totalAmount * tier.rate) / 100;
                }
            });
            
            return commission;
        } catch (error) {
            return 0;
        }
    }
    
    // Calculate volume-based commission
    calculateVolumeCommission(quantity, volumeRates) {
        try {
            const rates = JSON.parse(volumeRates);
            let commission = 0;
            
            rates.forEach(rate => {
                if (quantity >= rate.min && quantity <= rate.max) {
                    commission = rate.amount * quantity;
                }
            });
            
            return commission;
        } catch (error) {
            return 0;
        }
    }
    
    // Load medicine commission information
    async loadMedicineCommission() {
        const medicineId = document.getElementById('medicineId')?.value;
        if (!medicineId) return;
        
        try {
            const response = await apiCall('GET', `api/medicines/${medicineId}/commission`);
            if (response.success) {
                this.populateMedicineCommission(response.data);
            }
        } catch (error) {
            console.error('Error loading medicine commission:', error);
        }
    }
    
    // Populate medicine commission information
    populateMedicineCommission(commissionData) {
        const medicinePriceInput = document.getElementById('medicinePrice');
        const defaultCommissionInput = document.getElementById('defaultCommission');
        
        if (medicinePriceInput) {
            medicinePriceInput.value = commissionData.price || 0;
        }
        
        if (defaultCommissionInput) {
            defaultCommissionInput.value = commissionData.default_commission || this.options.defaultCommissionRate;
        }
        
        // Calculate commission
        this.calculateCommission();
    }
    
    // Toggle auto-calculation
    toggleAutoCalculation() {
        const autoCalcToggle = document.getElementById('enableAutoCalculation');
        this.options.enableAutoCalculation = autoCalcToggle?.checked || false;
        
        const autoCalcFields = document.querySelectorAll('.auto-calculation-fields');
        autoCalcFields.forEach(field => {
            field.style.display = this.options.enableAutoCalculation ? 'block' : 'none';
        });
    }
    
    // Create pharmacy partnership
    async createPharmacyPartnership(partnershipData) {
        try {
            const response = await apiCall('POST', 'api/pharmacy/partnerships', partnershipData);
            if (response.success) {
                showAlert('Pharmacy partnership created successfully', 'success');
                this.loadPharmacyPartnerships(); // Refresh list
                return response.data;
            } else {
                showAlert('Error creating partnership: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error creating partnership:', error);
            showAlert('Error creating partnership', 'danger');
            return null;
        }
    }
    
    // Update pharmacy partnership
    async updatePharmacyPartnership(partnershipId, partnershipData) {
        try {
            const response = await apiCall('PUT', `api/pharmacy/partnerships/${partnershipId}`, partnershipData);
            if (response.success) {
                showAlert('Pharmacy partnership updated successfully', 'success');
                this.loadPharmacyPartnerships(); // Refresh list
                return response.data;
            } else {
                showAlert('Error updating partnership: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error updating partnership:', error);
            showAlert('Error updating partnership', 'danger');
            return null;
        }
    }
    
    // Delete pharmacy partnership
    async deletePharmacyPartnership(partnershipId) {
        if (!confirm('Are you sure you want to delete this pharmacy partnership?')) {
            return;
        }
        
        try {
            const response = await apiCall('DELETE', `api/pharmacy/partnerships/${partnershipId}`);
            if (response.success) {
                showAlert('Pharmacy partnership deleted successfully', 'success');
                this.loadPharmacyPartnerships(); // Refresh list
                return true;
            } else {
                showAlert('Error deleting partnership: ' + response.message, 'danger');
                return false;
            }
        } catch (error) {
            console.error('Error deleting partnership:', error);
            showAlert('Error deleting partnership', 'danger');
            return false;
        }
    }
    
    // Calculate commission for a transaction
    async calculateTransactionCommission(transactionData) {
        try {
            const response = await apiCall('POST', 'api/pharmacy/commission/calculate', transactionData);
            if (response.success) {
                return response.data;
            } else {
                showAlert('Error calculating commission: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error calculating commission:', error);
            showAlert('Error calculating commission', 'danger');
            return null;
        }
    }
    
    // Record commission payment
    async recordCommissionPayment(commissionId, paymentData) {
        try {
            const response = await apiCall('POST', `api/pharmacy/commission/${commissionId}/payment`, paymentData);
            if (response.success) {
                showAlert('Commission payment recorded successfully', 'success');
                this.loadCommissionPayments(); // Refresh payments
                return response.data;
            } else {
                showAlert('Error recording commission payment: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error recording commission payment:', error);
            showAlert('Error recording commission payment', 'danger');
            return null;
        }
    }
    
    // Load commission payments
    async loadCommissionPayments(filters = {}) {
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const response = await apiCall('GET', `api/pharmacy/commission/payments?${queryParams}`);
            
            if (response.success) {
                this.displayCommissionPayments(response.data);
                return response.data;
            } else {
                showAlert('Error loading commission payments: ' + response.message, 'danger');
                return [];
            }
        } catch (error) {
            console.error('Error loading commission payments:', error);
            showAlert('Error loading commission payments', 'danger');
            return [];
        }
    }
    
    // Display commission payments
    displayCommissionPayments(payments) {
        const tbody = document.querySelector('#commissionPayments-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        payments.forEach(payment => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${payment.pharmacy_name}</td>
                <td>${payment.doctor_name}</td>
                <td>${payment.medicine_name}</td>
                <td>${payment.quantity}</td>
                <td>$${payment.medicine_price}</td>
                <td>$${payment.commission_amount}</td>
                <td>${payment.payment_date}</td>
                <td>
                    <span class="badge bg-${payment.status === 'Paid' ? 'success' : 'warning'}">
                        ${payment.status}
                    </span>
                </td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewCommissionPayment(${payment.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    ${payment.status !== 'Paid' ? `
                        <button class="btn btn-sm btn-success" onclick="markCommissionPaid(${payment.id})">
                            <i class="bi bi-check"></i>
                        </button>
                    ` : ''}
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // Mark commission as paid
    async markCommissionPaid(commissionId) {
        try {
            const response = await apiCall('PUT', `api/pharmacy/commission/${commissionId}/mark-paid`);
            if (response.success) {
                showAlert('Commission marked as paid successfully', 'success');
                this.loadCommissionPayments(); // Refresh payments
                return response.data;
            } else {
                showAlert('Error marking commission as paid: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error marking commission as paid:', error);
            showAlert('Error marking commission as paid', 'danger');
            return null;
        }
    }
    
    // Generate commission report
    async generateCommissionReport(filters = {}) {
        try {
            const response = await apiCall('POST', 'api/pharmacy/commission/report', filters);
            if (response.success) {
                this.downloadCommissionReport(response.data);
                return response.data;
            } else {
                showAlert('Error generating commission report: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error generating commission report:', error);
            showAlert('Error generating commission report', 'danger');
            return null;
        }
    }
    
    // Download commission report
    downloadCommissionReport(reportData) {
        const csvContent = this.convertCommissionReportToCSV(reportData);
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `commission-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    // Convert commission report to CSV
    convertCommissionReportToCSV(reportData) {
        const headers = ['Date', 'Pharmacy', 'Doctor', 'Medicine', 'Quantity', 'Price', 'Commission', 'Status'];
        const rows = reportData.map(payment => [
            payment.date,
            payment.pharmacy_name,
            payment.doctor_name,
            payment.medicine_name,
            payment.quantity,
            payment.medicine_price,
            payment.commission_amount,
            payment.status
        ]);
        
        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }
    
    // Get commission statistics
    async getCommissionStatistics(period = 'month') {
        try {
            const response = await apiCall('GET', `api/pharmacy/commission/statistics?period=${period}`);
            if (response.success) {
                return response.data;
            } else {
                showAlert('Error loading commission statistics: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error loading commission statistics:', error);
            showAlert('Error loading commission statistics', 'danger');
            return null;
        }
    }
    
    // Display commission statistics
    displayCommissionStatistics(statistics) {
        if (!statistics) return;
        
        // Update dashboard cards
        const elements = {
            'totalCommission': statistics.total_commission,
            'pendingCommission': statistics.pending_commission,
            'paidCommission': statistics.paid_commission,
            'monthlyGrowth': statistics.monthly_growth
        };
        
        Object.keys(elements).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                if (key === 'monthlyGrowth') {
                    element.textContent = `${elements[key]}%`;
                    element.className = `text-${elements[key] >= 0 ? 'success' : 'danger'}`;
                } else {
                    element.textContent = `$${elements[key].toLocaleString()}`;
                }
            }
        });
        
        // Update charts if they exist
        this.updateCommissionCharts(statistics);
    }
    
    // Update commission charts
    updateCommissionCharts(statistics) {
        // Commission trend chart
        const commissionChart = document.getElementById('commissionChart');
        if (commissionChart && statistics.commission_chart_data) {
            this.updateChart(commissionChart, statistics.commission_chart_data, 'Commission Trend');
        }
        
        // Pharmacy performance chart
        const pharmacyChart = document.getElementById('pharmacyPerformanceChart');
        if (pharmacyChart && statistics.pharmacy_performance_data) {
            this.updateChart(pharmacyChart, statistics.pharmacy_performance_data, 'Pharmacy Performance');
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
                    borderColor: 'rgb(75, 192, 192)',
                    backgroundColor: 'rgba(75, 192, 192, 0.2)',
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
    
    // View pharmacy partnership details
    async viewPharmacyPartnership(partnershipId) {
        try {
            const response = await apiCall('GET', `api/pharmacy/partnerships/${partnershipId}`);
            if (response.success) {
                this.showPartnershipDetailsModal(response.data);
            } else {
                showAlert('Error loading partnership details: ' + response.message, 'danger');
            }
        } catch (error) {
            console.error('Error loading partnership details:', error);
            showAlert('Error loading partnership details', 'danger');
        }
    }
    
    // Show partnership details modal
    showPartnershipDetailsModal(partnership) {
        const modalId = 'partnershipDetailsModal';
        const modalContent = `
            <div class="modal-header">
                <h5 class="modal-title">Pharmacy Partnership Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Pharmacy Information</h6>
                        <p><strong>Name:</strong> ${partnership.pharmacy_name}</p>
                        <p><strong>Address:</strong> ${partnership.pharmacy_address || 'N/A'}</p>
                        <p><strong>Phone:</strong> ${partnership.pharmacy_phone || 'N/A'}</p>
                        <p><strong>Email:</strong> ${partnership.pharmacy_email || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Partnership Details</h6>
                        <p><strong>Commission Type:</strong> ${partnership.commission_type}</p>
                        <p><strong>Commission Rate:</strong> ${this.formatCommissionRate(partnership.commission_rate, partnership.commission_type)}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${this.getCommissionStatusColor(partnership.status)}">${partnership.status}</span></p>
                        <p><strong>Start Date:</strong> ${partnership.start_date}</p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Commission Summary</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Period</th>
                                        <th>Total Sales</th>
                                        <th>Commission Earned</th>
                                        <th>Commission Paid</th>
                                        <th>Pending</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${partnership.commission_summary?.map(summary => `
                                        <tr>
                                            <td>${summary.period}</td>
                                            <td>$${summary.total_sales}</td>
                                            <td>$${summary.commission_earned}</td>
                                            <td>$${summary.commission_paid}</td>
                                            <td>$${summary.commission_pending}</td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="5">No commission data available</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="editPharmacyPartnership(${partnership.id})">
                    Edit Partnership
                </button>
            </div>
        `;
        
        createModal(modalId, 'Partnership Details', modalContent, {
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

// Utility functions for pharmacy commission management
export function createPharmacyCommissionForm(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
    const form = document.createElement('form');
    form.id = 'pharmacyCommissionForm';
    form.className = 'pharmacy-commission-form';
    
    form.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>Partnership Details</h5>
                <div class="mb-3">
                    <label class="form-label">Pharmacy</label>
                    <select class="form-select" id="pharmacyId" required>
                        <option value="">Select Pharmacy</option>
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
                    <select class="form-select" id="commissionType" required>
                        <option value="">Select Type</option>
                        <option value="Percentage">Percentage</option>
                        <option value="Fixed Amount">Fixed Amount</option>
                        <option value="Tiered">Tiered</option>
                        <option value="Volume Based">Volume Based</option>
                    </select>
                </div>
            </div>
            <div class="col-md-6">
                <h5>Commission Settings</h5>
                <div class="mb-3 percentage-fields" style="display: none;">
                    <label class="form-label">Commission Rate (%)</label>
                    <input type="number" class="form-control" id="commissionRate" step="0.1" min="0" max="100">
                </div>
                <div class="mb-3 fixed-amount-fields" style="display: none;">
                    <label class="form-label">Commission Amount ($)</label>
                    <input type="number" class="form-control" id="commissionRate" step="0.01" min="0">
                </div>
                <div class="mb-3 tiered-fields" style="display: none;">
                    <label class="form-label">Tiered Commission Structure</label>
                    <textarea class="form-control" id="commissionRate" rows="3" placeholder='[{"min": 0, "max": 100, "rate": 10}, {"min": 101, "max": 500, "rate": 15}]'></textarea>
                </div>
                <div class="mb-3 volume-fields" style="display: none;">
                    <label class="form-label">Volume Commission Structure</label>
                    <textarea class="form-control" id="commissionRate" rows="3" placeholder='[{"min": 1, "max": 10, "amount": 2}, {"min": 11, "max": 50, "amount": 1.5}]'></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Status</label>
                    <select class="form-select" id="partnershipStatus">
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
                        <label class="form-label">Medicine</label>
                        <select class="form-select" id="medicineId">
                            <option value="">Select Medicine</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Price</label>
                        <input type="number" class="form-control" id="medicinePrice" step="0.01" min="0" readonly>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" id="quantity" min="1" value="1">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Commission</label>
                        <div class="form-control-plaintext" id="calculatedCommission">$0.00</div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="enableAutoCalculation" checked>
                    <label class="form-check-label" for="enableAutoCalculation">
                        Enable automatic commission calculation
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-actions mt-3">
            <button type="submit" class="btn btn-primary">Save Partnership</button>
            <button type="button" class="btn btn-secondary" onclick="closePharmacyCommissionModal()">Cancel</button>
        </div>
    `;
    
    container.appendChild(form);
    
    // Initialize pharmacy commission manager
    const commissionManager = new PharmacyCommissionManager();
    
    return commissionManager;
}

// Global functions for HTML onclick handlers
window.viewPharmacyPartnership = function(partnershipId) {
    if (window.currentPharmacyCommissionManager) {
        window.currentPharmacyCommissionManager.viewPharmacyPartnership(partnershipId);
    }
};

window.editPharmacyPartnership = function(partnershipId) {
    // Implementation for editing pharmacy partnerships
    console.log('Edit pharmacy partnership:', partnershipId);
};

window.deletePharmacyPartnership = function(partnershipId) {
    if (window.currentPharmacyCommissionManager) {
        window.currentPharmacyCommissionManager.deletePharmacyPartnership(partnershipId);
    }
};

window.viewCommissionPayment = function(paymentId) {
    // Implementation for viewing commission payments
    console.log('View commission payment:', paymentId);
};

window.markCommissionPaid = function(commissionId) {
    if (window.currentPharmacyCommissionManager) {
        window.currentPharmacyCommissionManager.markCommissionPaid(commissionId);
    }
};

window.editDefaultCommissionRate = function(rateId) {
    // Implementation for editing default commission rates
    console.log('Edit default commission rate:', rateId);
};