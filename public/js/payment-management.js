// Fee & Payment Management Module
// Handles consultation fees, patient payments, and payment tracking

export class PaymentManager {
    constructor(options = {}) {
        this.options = {
            enablePartialPayments: true,
            enablePaymentReminders: true,
            autoCalculateTax: true,
            taxRate: 0.08, // 8% tax rate
            ...options
        };
        
        this.currentPayment = null;
        this.paymentMethods = [
            'Cash', 'Credit Card', 'Debit Card', 'Bank Transfer',
            'Mobile Payment', 'Insurance', 'Check', 'Online Payment'
        ];
        
        this.paymentStatuses = [
            'Pending', 'Partial', 'Completed', 'Overdue', 'Cancelled', 'Refunded'
        ];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadPaymentSettings();
        this.loadPaymentMethods();
    }
    
    // Setup event listeners for payment forms
    setupEventListeners() {
        // Consultation fee calculation
        const consultationTypeSelect = document.getElementById('consultationType');
        if (consultationTypeSelect) {
            consultationTypeSelect.addEventListener('change', () => {
                this.calculateConsultationFee();
            });
        }
        
        // Payment amount calculation
        const paymentAmountInput = document.getElementById('paymentAmount');
        if (paymentAmountInput) {
            paymentAmountInput.addEventListener('input', () => {
                this.calculatePaymentBreakdown();
            });
        }
        
        // Partial payment handling
        if (this.options.enablePartialPayments) {
            this.setupPartialPaymentHandling();
        }
        
        // Tax calculation
        if (this.options.autoCalculateTax) {
            this.setupTaxCalculation();
        }
    }
    
    // Load payment settings from backend
    async loadPaymentSettings() {
        try {
            const response = await apiCall('GET', 'api/payment/settings');
            if (response.success) {
                this.paymentSettings = response.data;
                this.populatePaymentSettings();
            }
        } catch (error) {
            console.error('Error loading payment settings:', error);
        }
    }
    
    // Load available payment methods
    async loadPaymentMethods() {
        try {
            const response = await apiCall('GET', 'api/payment/methods');
            if (response.success) {
                this.availablePaymentMethods = response.data;
            }
        } catch (error) {
            console.error('Error loading payment methods:', error);
        }
    }
    
    // Populate payment settings in UI
    populatePaymentSettings() {
        if (!this.paymentSettings) return;
        
        // Consultation fees
        const consultationFeesContainer = document.getElementById('consultationFees');
        if (consultationFeesContainer) {
            consultationFeesContainer.innerHTML = '';
            
            this.paymentSettings.consultation_fees.forEach(fee => {
                const feeRow = document.createElement('div');
                feeRow.className = 'row mb-2';
                feeRow.innerHTML = `
                    <div class="col-md-4">
                        <strong>${fee.type}</strong>
                    </div>
                    <div class="col-md-4">
                        $${fee.amount}
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-sm btn-outline-primary" onclick="editConsultationFee(${fee.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                `;
                consultationFeesContainer.appendChild(feeRow);
            });
        }
        
        // Payment terms
        const paymentTermsContainer = document.getElementById('paymentTerms');
        if (paymentTermsContainer) {
            paymentTermsContainer.innerHTML = this.paymentSettings.payment_terms || 'Net 30 days';
        }
        
        // Late payment fees
        const lateFeeContainer = document.getElementById('latePaymentFee');
        if (lateFeeContainer) {
            lateFeeContainer.innerHTML = `${this.paymentSettings.late_payment_fee || 0}%`;
        }
    }
    
    // Calculate consultation fee based on type
    calculateConsultationFee() {
        const consultationType = document.getElementById('consultationType')?.value;
        const feeDisplay = document.getElementById('consultationFee');
        
        if (!consultationType || !feeDisplay) return;
        
        const fee = this.paymentSettings?.consultation_fees?.find(f => f.type === consultationType);
        if (fee) {
            feeDisplay.textContent = `$${fee.amount}`;
            this.calculatePaymentBreakdown();
        }
    }
    
    // Calculate payment breakdown including tax and discounts
    calculatePaymentBreakdown() {
        const consultationFee = parseFloat(document.getElementById('consultationFee')?.textContent.replace('$', '') || 0);
        const additionalCharges = parseFloat(document.getElementById('additionalCharges')?.value || 0);
        const discountPercentage = parseFloat(document.getElementById('discountPercentage')?.value || 0);
        const discountAmount = parseFloat(document.getElementById('discountAmount')?.value || 0);
        
        let subtotal = consultationFee + additionalCharges;
        
        // Apply percentage discount
        if (discountPercentage > 0) {
            const percentageDiscount = (subtotal * discountPercentage) / 100;
            subtotal -= percentageDiscount;
        }
        
        // Apply fixed discount
        if (discountAmount > 0) {
            subtotal -= discountAmount;
        }
        
        // Calculate tax
        let taxAmount = 0;
        if (this.options.autoCalculateTax) {
            taxAmount = subtotal * this.options.taxRate;
        }
        
        const total = subtotal + taxAmount;
        
        // Update UI
        this.updatePaymentBreakdown({
            consultationFee,
            additionalCharges,
            discountPercentage,
            discountAmount,
            subtotal,
            taxAmount,
            total
        });
    }
    
    // Update payment breakdown display
    updatePaymentBreakdown(breakdown) {
        const elements = {
            'subtotal': breakdown.subtotal,
            'taxAmount': breakdown.taxAmount,
            'total': breakdown.total
        };
        
        Object.keys(elements).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                element.textContent = `$${elements[key].toFixed(2)}`;
            }
        });
        
        // Update payment amount input
        const paymentAmountInput = document.getElementById('paymentAmount');
        if (paymentAmountInput) {
            paymentAmountInput.value = breakdown.total.toFixed(2);
        }
    }
    
    // Setup partial payment handling
    setupPartialPaymentHandling() {
        const partialPaymentToggle = document.getElementById('enablePartialPayment');
        if (partialPaymentToggle) {
            partialPaymentToggle.addEventListener('change', () => {
                this.togglePartialPaymentFields();
            });
        }
    }
    
    // Toggle partial payment fields
    togglePartialPaymentFields() {
        const partialPaymentToggle = document.getElementById('enablePartialPayment');
        const partialPaymentFields = document.querySelectorAll('.partial-payment-fields');
        
        partialPaymentFields.forEach(field => {
            field.style.display = partialPaymentToggle?.checked ? 'block' : 'none';
        });
    }
    
    // Setup tax calculation
    setupTaxCalculation() {
        const taxExemptToggle = document.getElementById('taxExempt');
        if (taxExemptToggle) {
            taxExemptToggle.addEventListener('change', () => {
                this.toggleTaxCalculation();
            });
        }
    }
    
    // Toggle tax calculation
    toggleTaxCalculation() {
        const taxExemptToggle = document.getElementById('taxExempt');
        const taxFields = document.querySelectorAll('.tax-fields');
        
        taxFields.forEach(field => {
            field.style.display = taxExemptToggle?.checked ? 'none' : 'block';
        });
        
        if (taxExemptToggle?.checked) {
            this.options.autoCalculateTax = false;
        } else {
            this.options.autoCalculateTax = true;
        }
        
        this.calculatePaymentBreakdown();
    }
    
    // Create new payment
    async createPayment(paymentData) {
        try {
            const response = await apiCall('POST', 'api/payments', paymentData);
            if (response.success) {
                this.currentPayment = response.data;
                showAlert('Payment created successfully', 'success');
                return response.data;
            } else {
                showAlert('Error creating payment: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error creating payment:', error);
            showAlert('Error creating payment', 'danger');
            return null;
        }
    }
    
    // Process payment
    async processPayment(paymentId, paymentMethod, amount) {
        try {
            const response = await apiCall('POST', `api/payments/${paymentId}/process`, {
                payment_method: paymentMethod,
                amount: amount,
                processed_at: new Date().toISOString()
            });
            
            if (response.success) {
                showAlert('Payment processed successfully', 'success');
                this.loadPayments(); // Refresh payment list
                return response.data;
            } else {
                showAlert('Error processing payment: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error processing payment:', error);
            showAlert('Error processing payment', 'danger');
            return null;
        }
    }
    
    // Record partial payment
    async recordPartialPayment(paymentId, amount, paymentMethod, notes = '') {
        try {
            const response = await apiCall('POST', `api/payments/${paymentId}/partial`, {
                amount: amount,
                payment_method: paymentMethod,
                notes: notes,
                recorded_at: new Date().toISOString()
            });
            
            if (response.success) {
                showAlert('Partial payment recorded successfully', 'success');
                this.loadPayments(); // Refresh payment list
                return response.data;
            } else {
                showAlert('Error recording partial payment: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error recording partial payment:', error);
            showAlert('Error recording partial payment', 'danger');
            return null;
        }
    }
    
    // Get payment status
    getPaymentStatus(payment) {
        if (payment.status === 'Completed') return 'success';
        if (payment.status === 'Partial') return 'warning';
        if (payment.status === 'Overdue') return 'danger';
        if (payment.status === 'Pending') return 'info';
        return 'secondary';
    }
    
    // Calculate payment progress
    calculatePaymentProgress(payment) {
        const totalAmount = parseFloat(payment.total_amount);
        const paidAmount = parseFloat(payment.paid_amount || 0);
        const progress = (paidAmount / totalAmount) * 100;
        
        return {
            progress: Math.min(progress, 100),
            remaining: totalAmount - paidAmount,
            percentage: progress.toFixed(1)
        };
    }
    
    // Load payments list
    async loadPayments(filters = {}) {
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const response = await apiCall('GET', `api/payments?${queryParams}`);
            
            if (response.success) {
                this.displayPayments(response.data);
                return response.data;
            } else {
                showAlert('Error loading payments: ' + response.message, 'danger');
                return [];
            }
        } catch (error) {
            console.error('Error loading payments:', error);
            showAlert('Error loading payments', 'danger');
            return [];
        }
    }
    
    // Display payments in table
    displayPayments(payments) {
        const tbody = document.querySelector('#payments-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        payments.forEach(payment => {
            const progress = this.calculatePaymentProgress(payment);
            const statusBadge = this.getPaymentStatus(payment);
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${payment.patient_name}</td>
                <td>${payment.consultation_type}</td>
                <td>$${payment.total_amount}</td>
                <td>$${payment.paid_amount || 0}</td>
                <td>$${progress.remaining.toFixed(2)}</td>
                <td>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-${statusBadge}" style="width: ${progress.progress}%">
                            ${progress.percentage}%
                        </div>
                    </div>
                </td>
                <td>
                    <span class="badge bg-${statusBadge}">${payment.status}</span>
                </td>
                <td>${payment.created_at}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewPayment(${payment.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="recordPayment(${payment.id})">
                        <i class="bi bi-cash"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="editPayment(${payment.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // View payment details
    async viewPayment(paymentId) {
        try {
            const response = await apiCall('GET', `api/payments/${paymentId}`);
            if (response.success) {
                this.showPaymentDetailsModal(response.data);
            } else {
                showAlert('Error loading payment details: ' + response.message, 'danger');
            }
        } catch (error) {
            console.error('Error loading payment details:', error);
            showAlert('Error loading payment details', 'danger');
        }
    }
    
    // Show payment details modal
    showPaymentDetailsModal(payment) {
        const modalId = 'paymentDetailsModal';
        const modalContent = `
            <div class="modal-header">
                <h5 class="modal-title">Payment Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Patient Information</h6>
                        <p><strong>Name:</strong> ${payment.patient_name}</p>
                        <p><strong>ID:</strong> ${payment.patient_id}</p>
                        <p><strong>Phone:</strong> ${payment.patient_phone || 'N/A'}</p>
                    </div>
                    <div class="col-md-6">
                        <h6>Payment Information</h6>
                        <p><strong>Total Amount:</strong> $${payment.total_amount}</p>
                        <p><strong>Paid Amount:</strong> $${payment.paid_amount || 0}</p>
                        <p><strong>Remaining:</strong> $${(payment.total_amount - (payment.paid_amount || 0)).toFixed(2)}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${this.getPaymentStatus(payment)}">${payment.status}</span></p>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-12">
                        <h6>Payment History</h6>
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    ${payment.payment_history?.map(payment => `
                                        <tr>
                                            <td>${payment.date}</td>
                                            <td>$${payment.amount}</td>
                                            <td>${payment.method}</td>
                                            <td>${payment.notes || '-'}</td>
                                        </tr>
                                    `).join('') || '<tr><td colspan="4">No payment history</td></tr>'}
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="recordPayment(${payment.id})">
                    Record Payment
                </button>
            </div>
        `;
        
        // Create modal using modal manager
        createModal(modalId, 'Payment Details', modalContent, {
            size: 'modal-lg',
            showFooter: true
        });
        
        openModal(modalId);
    }
    
    // Record payment modal
    async showRecordPaymentModal(paymentId) {
        const modalId = 'recordPaymentModal';
        const modalContent = `
            <div class="modal-header">
                <h5 class="modal-title">Record Payment</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <form id="recordPaymentForm">
                    <div class="row">
                        <div class="col-md-6">
                            <label class="form-label">Payment Amount</label>
                            <input type="number" class="form-control" id="paymentAmount" step="0.01" min="0" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Payment Method</label>
                            <select class="form-select" id="paymentMethod" required>
                                <option value="">Select Method</option>
                                ${this.paymentMethods.map(method => `<option value="${method}">${method}</option>`).join('')}
                            </select>
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-md-6">
                            <label class="form-label">Payment Date</label>
                            <input type="date" class="form-control" id="paymentDate" value="${new Date().toISOString().split('T')[0]}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">Reference Number</label>
                            <input type="text" class="form-control" id="referenceNumber" placeholder="Transaction ID, Check #, etc.">
                        </div>
                    </div>
                    <div class="row mt-3">
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="paymentNotes" rows="3" placeholder="Additional notes about this payment"></textarea>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" onclick="submitPaymentRecord(${paymentId})">
                    Record Payment
                </button>
            </div>
        `;
        
        createModal(modalId, 'Record Payment', modalContent, {
            size: 'modal-md',
            showFooter: true
        });
        
        openModal(modalId);
    }
    
    // Submit payment record
    async submitPaymentRecord(paymentId) {
        const form = document.getElementById('recordPaymentForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }
        
        const paymentData = {
            amount: parseFloat(document.getElementById('paymentAmount').value),
            payment_method: document.getElementById('paymentMethod').value,
            payment_date: document.getElementById('paymentDate').value,
            reference_number: document.getElementById('referenceNumber').value,
            notes: document.getElementById('paymentNotes').value
        };
        
        try {
            const result = await this.recordPartialPayment(paymentId, paymentData.amount, paymentData.payment_method, paymentData.notes);
            if (result) {
                closeModal('recordPaymentModal');
                this.loadPayments(); // Refresh payment list
            }
        } catch (error) {
            console.error('Error recording payment:', error);
        }
    }
    
    // Generate payment report
    async generatePaymentReport(filters = {}) {
        try {
            const response = await apiCall('POST', 'api/payments/report', filters);
            if (response.success) {
                this.downloadPaymentReport(response.data);
                return response.data;
            } else {
                showAlert('Error generating payment report: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error generating payment report:', error);
            showAlert('Error generating payment report', 'danger');
            return null;
        }
    }
    
    // Download payment report
    downloadPaymentReport(reportData) {
        const csvContent = this.convertReportToCSV(reportData);
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `payment-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    // Convert report data to CSV
    convertReportToCSV(reportData) {
        const headers = ['Date', 'Patient Name', 'Consultation Type', 'Total Amount', 'Paid Amount', 'Remaining', 'Status'];
        const rows = reportData.map(payment => [
            payment.date,
            payment.patient_name,
            payment.consultation_type,
            payment.total_amount,
            payment.paid_amount || 0,
            (payment.total_amount - (payment.paid_amount || 0)).toFixed(2),
            payment.status
        ]);
        
        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }
    
    // Send payment reminder
    async sendPaymentReminder(paymentId) {
        try {
            const response = await apiCall('POST', `api/payments/${paymentId}/reminder`);
            if (response.success) {
                showAlert('Payment reminder sent successfully', 'success');
                return response.data;
            } else {
                showAlert('Error sending payment reminder: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error sending payment reminder:', error);
            showAlert('Error sending payment reminder', 'danger');
            return null;
        }
    }
    
    // Update consultation fees
    async updateConsultationFees(fees) {
        try {
            const response = await apiCall('PUT', 'api/payment/settings/fees', { fees });
            if (response.success) {
                showAlert('Consultation fees updated successfully', 'success');
                this.loadPaymentSettings(); // Refresh settings
                return response.data;
            } else {
                showAlert('Error updating consultation fees: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error updating consultation fees:', error);
            showAlert('Error updating consultation fees', 'danger');
            return null;
        }
    }
    
    // Get payment statistics
    async getPaymentStatistics(period = 'month') {
        try {
            const response = await apiCall('GET', `api/payments/statistics?period=${period}`);
            if (response.success) {
                return response.data;
            } else {
                showAlert('Error loading payment statistics: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error loading payment statistics:', error);
            showAlert('Error loading payment statistics', 'danger');
            return null;
        }
    }
    
    // Display payment statistics
    displayPaymentStatistics(statistics) {
        if (!statistics) return;
        
        // Update dashboard cards
        const elements = {
            'totalRevenue': statistics.total_revenue,
            'pendingPayments': statistics.pending_payments,
            'overduePayments': statistics.overdue_payments,
            'monthlyGrowth': statistics.monthly_growth
        };
        
        Object.keys(elements).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                if (key === 'monthlyGrowth') {
                    element.textContent = `${elements[key]}%`;
                    element.className = `text-${elements[key] >= 0 ? 'success' : 'danger'}`;
                } else if (key.includes('Revenue')) {
                    element.textContent = `$${elements[key].toLocaleString()}`;
                } else {
                    element.textContent = elements[key];
                }
            }
        });
        
        // Update charts if they exist
        this.updatePaymentCharts(statistics);
    }
    
    // Update payment charts
    updatePaymentCharts(statistics) {
        // Revenue chart
        const revenueChart = document.getElementById('revenueChart');
        if (revenueChart && statistics.revenue_chart_data) {
            this.updateChart(revenueChart, statistics.revenue_chart_data, 'Revenue Trend');
        }
        
        // Payment methods chart
        const paymentMethodsChart = document.getElementById('paymentMethodsChart');
        if (paymentMethodsChart && statistics.payment_methods_data) {
            this.updateChart(paymentMethodsChart, statistics.payment_methods_data, 'Payment Methods');
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
    
    // Destroy instance and cleanup
    destroy() {
        // Cleanup event listeners and timers
    }
}

// Utility functions for payment management
export function createPaymentForm(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
    const form = document.createElement('form');
    form.id = 'paymentForm';
    form.className = 'payment-form';
    
    form.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>Consultation Details</h5>
                <div class="mb-3">
                    <label class="form-label">Consultation Type</label>
                    <select class="form-select" id="consultationType" required>
                        <option value="">Select Type</option>
                        <option value="General Consultation">General Consultation</option>
                        <option value="Specialist Consultation">Specialist Consultation</option>
                        <option value="Emergency Consultation">Emergency Consultation</option>
                        <option value="Follow-up">Follow-up</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Consultation Fee</label>
                    <div class="form-control-plaintext" id="consultationFee">$0.00</div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Additional Charges</label>
                    <input type="number" class="form-control" id="additionalCharges" step="0.01" min="0" value="0">
                </div>
            </div>
            <div class="col-md-6">
                <h5>Payment Details</h5>
                <div class="mb-3">
                    <label class="form-label">Discount (%)</label>
                    <input type="number" class="form-control" id="discountPercentage" step="0.1" min="0" max="100" value="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Discount Amount ($)</label>
                    <input type="number" class="form-control" id="discountAmount" step="0.01" min="0" value="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Tax Exempt</label>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="taxExempt">
                        <label class="form-check-label" for="taxExempt">
                            Mark as tax exempt
                        </label>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h5>Payment Breakdown</h5>
                <div class="table-responsive">
                    <table class="table table-bordered">
                        <tbody>
                            <tr>
                                <td><strong>Subtotal:</strong></td>
                                <td id="subtotal">$0.00</td>
                            </tr>
                            <tr class="tax-fields">
                                <td><strong>Tax (8%):</strong></td>
                                <td id="taxAmount">$0.00</td>
                            </tr>
                            <tr class="table-active">
                                <td><strong>Total:</strong></td>
                                <td id="total">$0.00</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="enablePartialPayment">
                    <label class="form-check-label" for="enablePartialPayment">
                        Enable partial payments
                    </label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">Payment Amount</label>
                <input type="number" class="form-control" id="paymentAmount" step="0.01" min="0" readonly>
            </div>
        </div>
    `;
    
    container.appendChild(form);
    
    // Initialize payment manager
    const paymentManager = new PaymentManager();
    
    return paymentManager;
}

// Global functions for HTML onclick handlers
window.viewPayment = function(paymentId) {
    if (window.currentPaymentManager) {
        window.currentPaymentManager.viewPayment(paymentId);
    }
};

window.recordPayment = function(paymentId) {
    if (window.currentPaymentManager) {
        window.currentPaymentManager.showRecordPaymentModal(paymentId);
    }
};

window.submitPaymentRecord = function(paymentId) {
    if (window.currentPaymentManager) {
        window.currentPaymentManager.submitPaymentRecord(paymentId);
    }
};

window.editConsultationFee = function(feeId) {
    // Implementation for editing consultation fees
    console.log('Edit consultation fee:', feeId);
};

window.editPayment = function(paymentId) {
    // Implementation for editing payments
    console.log('Edit payment:', paymentId);
};