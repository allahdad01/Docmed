// Expense Management Module
// Handles doctor expenses, categorization, and financial summaries

export class ExpenseManager {
    constructor(options = {}) {
        this.options = {
            enableAutoCategorization: true,
            enableReceiptUpload: true,
            enableBudgetTracking: true,
            defaultCurrency: 'USD',
            ...options
        };
        
        this.currentExpense = null;
        this.expenseCategories = [
            'Rent', 'Salaries', 'Utilities', 'Supplies', 'Equipment',
            'Marketing', 'Insurance', 'Legal', 'Travel', 'Meals',
            'Office Supplies', 'Technology', 'Maintenance', 'Other'
        ];
        
        this.expenseStatuses = [
            'Pending', 'Approved', 'Rejected', 'Paid', 'Overdue'
        ];
        
        this.paymentMethods = [
            'Cash', 'Credit Card', 'Debit Card', 'Bank Transfer',
            'Check', 'Online Payment', 'Reimbursement'
        ];
        
        this.init();
    }
    
    init() {
        this.setupEventListeners();
        this.loadExpenseSettings();
        this.loadExpenseCategories();
    }
    
    // Setup event listeners for expense forms
    setupEventListeners() {
        // Category change
        const categorySelect = document.getElementById('expenseCategory');
        if (categorySelect) {
            categorySelect.addEventListener('change', () => {
                this.updateCategoryBudget();
            });
        }
        
        // Amount calculation
        const amountInput = document.getElementById('expenseAmount');
        if (amountInput) {
            amountInput.addEventListener('input', () => {
                this.calculateExpenseTotal();
            });
        }
        
        // Date change
        const dateInput = document.getElementById('expenseDate');
        if (dateInput) {
            dateInput.addEventListener('change', () => {
                this.updateExpensePeriod();
            });
        }
        
        // Receipt upload
        const receiptInput = document.getElementById('expenseReceipt');
        if (receiptInput && this.options.enableReceiptUpload) {
            receiptInput.addEventListener('change', (e) => {
                this.handleReceiptUpload(e.target.files[0]);
            });
        }
        
        // Auto-categorization toggle
        const autoCatToggle = document.getElementById('enableAutoCategorization');
        if (autoCatToggle) {
            autoCatToggle.addEventListener('change', () => {
                this.toggleAutoCategorization();
            });
        }
    }
    
    // Load expense settings
    async loadExpenseSettings() {
        try {
            const response = await apiCall('GET', 'api/expense/settings');
            if (response.success) {
                this.expenseSettings = response.data;
                this.populateExpenseSettings();
            }
        } catch (error) {
            console.error('Error loading expense settings:', error);
        }
    }
    
    // Load expense categories
    async loadExpenseCategories() {
        try {
            const response = await apiCall('GET', 'api/expense/categories');
            if (response.success) {
                this.expenseCategories = response.data;
                this.populateExpenseCategories();
            }
        } catch (error) {
            console.error('Error loading expense categories:', error);
        }
    }
    
    // Populate expense settings in UI
    populateExpenseSettings() {
        if (!this.expenseSettings) return;
        
        // Budget limits
        const budgetLimitsContainer = document.getElementById('budgetLimits');
        if (budgetLimitsContainer) {
            budgetLimitsContainer.innerHTML = '';
            
            this.expenseSettings.budget_limits.forEach(budget => {
                const budgetRow = document.createElement('div');
                budgetRow.className = 'row mb-2';
                budgetRow.innerHTML = `
                    <div class="col-md-4">
                        <strong>${budget.category}</strong>
                    </div>
                    <div class="col-md-4">
                        $${budget.monthly_limit}
                    </div>
                    <div class="col-md-4">
                        <button class="btn btn-sm btn-outline-primary" onclick="editBudgetLimit(${budget.id})">
                            <i class="bi bi-pencil"></i>
                        </button>
                    </div>
                `;
                budgetLimitsContainer.appendChild(budgetRow);
            });
        }
        
        // Approval workflow
        const approvalWorkflowContainer = document.getElementById('approvalWorkflow');
        if (approvalWorkflowContainer) {
            approvalWorkflowContainer.innerHTML = this.expenseSettings.approval_workflow || 'Manager approval required for expenses over $500';
        }
    }
    
    // Populate expense categories in UI
    populateExpenseCategories() {
        const categorySelect = document.getElementById('expenseCategory');
        if (categorySelect) {
            categorySelect.innerHTML = '<option value="">Select Category</option>';
            this.expenseCategories.forEach(category => {
                const option = document.createElement('option');
                option.value = category;
                option.textContent = category;
                categorySelect.appendChild(option);
            });
        }
    }
    
    // Update category budget
    updateCategoryBudget() {
        const category = document.getElementById('expenseCategory')?.value;
        const budgetDisplay = document.getElementById('categoryBudget');
        
        if (!category || !budgetDisplay) return;
        
        const budget = this.expenseSettings?.budget_limits?.find(b => b.category === category);
        if (budget) {
            budgetDisplay.textContent = `$${budget.monthly_limit}`;
            this.checkBudgetLimit(category, budget.monthly_limit);
        }
    }
    
    // Check budget limit
    checkBudgetLimit(category, limit) {
        const currentAmount = this.getCurrentMonthExpenses(category);
        const remainingBudget = limit - currentAmount;
        
        const budgetWarning = document.getElementById('budgetWarning');
        if (budgetWarning) {
            if (remainingBudget < 0) {
                budgetWarning.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="bi bi-exclamation-triangle"></i>
                        Budget exceeded by $${Math.abs(remainingBudget).toFixed(2)} for ${category}
                    </div>
                `;
            } else if (remainingBudget < (limit * 0.2)) {
                budgetWarning.innerHTML = `
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        Only $${remainingBudget.toFixed(2)} remaining for ${category}
                    </div>
                `;
            } else {
                budgetWarning.innerHTML = '';
            }
        }
    }
    
    // Get current month expenses for a category
    getCurrentMonthExpenses(category) {
        // This would typically fetch from the database
        // For now, return a placeholder value
        return 0;
    }
    
    // Calculate expense total
    calculateExpenseTotal() {
        const amount = parseFloat(document.getElementById('expenseAmount')?.value || 0);
        const tax = parseFloat(document.getElementById('expenseTax')?.value || 0);
        const discount = parseFloat(document.getElementById('expenseDiscount')?.value || 0);
        
        const total = amount + tax - discount;
        
        const totalDisplay = document.getElementById('expenseTotal');
        if (totalDisplay) {
            totalDisplay.textContent = `$${total.toFixed(2)}`;
        }
        
        return total;
    }
    
    // Update expense period
    updateExpensePeriod() {
        const date = document.getElementById('expenseDate')?.value;
        if (!date) return;
        
        const expenseDate = new Date(date);
        const month = expenseDate.getMonth();
        const year = expenseDate.getFullYear();
        
        const periodDisplay = document.getElementById('expensePeriod');
        if (periodDisplay) {
            periodDisplay.textContent = `${this.getMonthName(month)} ${year}`;
        }
    }
    
    // Get month name
    getMonthName(month) {
        const months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        return months[month];
    }
    
    // Handle receipt upload
    async handleReceiptUpload(file) {
        if (!file) return;
        
        try {
            const formData = new FormData();
            formData.append('receipt', file);
            
            const response = await apiCall('POST', 'api/expense/upload-receipt', formData);
            if (response.success) {
                showAlert('Receipt uploaded successfully', 'success');
                this.displayReceiptPreview(response.data.receipt_url);
            } else {
                showAlert('Error uploading receipt: ' + response.message, 'danger');
            }
        } catch (error) {
            console.error('Error uploading receipt:', error);
            showAlert('Error uploading receipt', 'danger');
        }
    }
    
    // Display receipt preview
    displayReceiptPreview(receiptUrl) {
        const receiptPreview = document.getElementById('receiptPreview');
        if (receiptPreview) {
            receiptPreview.innerHTML = `
                <div class="receipt-preview">
                    <img src="${receiptUrl}" alt="Receipt" class="img-fluid" style="max-width: 200px;">
                    <button type="button" class="btn btn-sm btn-outline-danger mt-2" onclick="removeReceipt()">
                        <i class="bi bi-trash"></i> Remove
                    </button>
                </div>
            `;
        }
    }
    
    // Toggle auto-categorization
    toggleAutoCategorization() {
        const autoCatToggle = document.getElementById('enableAutoCategorization');
        this.options.enableAutoCategorization = autoCatToggle?.checked || false;
        
        const autoCatFields = document.querySelectorAll('.auto-categorization-fields');
        autoCatFields.forEach(field => {
            field.style.display = this.options.enableAutoCategorization ? 'block' : 'none';
        });
    }
    
    // Create new expense
    async createExpense(expenseData) {
        try {
            const response = await apiCall('POST', 'api/expenses', expenseData);
            if (response.success) {
                this.currentExpense = response.data;
                showAlert('Expense created successfully', 'success');
                this.loadExpenses(); // Refresh expense list
                return response.data;
            } else {
                showAlert('Error creating expense: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error creating expense:', error);
            showAlert('Error creating expense', 'danger');
            return null;
        }
    }
    
    // Update expense
    async updateExpense(expenseId, expenseData) {
        try {
            const response = await apiCall('PUT', `api/expenses/${expenseId}`, expenseData);
            if (response.success) {
                showAlert('Expense updated successfully', 'success');
                this.loadExpenses(); // Refresh expense list
                return response.data;
            } else {
                showAlert('Error updating expense: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error updating expense:', error);
            showAlert('Error updating expense', 'danger');
            return null;
        }
    }
    
    // Delete expense
    async deleteExpense(expenseId) {
        if (!confirm('Are you sure you want to delete this expense?')) {
            return;
        }
        
        try {
            const response = await apiCall('DELETE', `api/expenses/${expenseId}`);
            if (response.success) {
                showAlert('Expense deleted successfully', 'success');
                this.loadExpenses(); // Refresh expense list
                return true;
            } else {
                showAlert('Error deleting expense: ' + response.message, 'danger');
                return false;
            }
        } catch (error) {
            console.error('Error deleting expense:', error);
            showAlert('Error deleting expense', 'danger');
            return false;
        }
    }
    
    // Get expense status color
    getExpenseStatusColor(status) {
        const statusColors = {
            'Pending': 'warning',
            'Approved': 'success',
            'Rejected': 'danger',
            'Paid': 'info',
            'Overdue': 'danger'
        };
        return statusColors[status] || 'secondary';
    }
    
    // Load expenses list
    async loadExpenses(filters = {}) {
        try {
            const queryParams = new URLSearchParams(filters).toString();
            const response = await apiCall('GET', `api/expenses?${queryParams}`);
            
            if (response.success) {
                this.displayExpenses(response.data);
                return response.data;
            } else {
                showAlert('Error loading expenses: ' + response.message, 'danger');
                return [];
            }
        } catch (error) {
            console.error('Error loading expenses:', error);
            showAlert('Error loading expenses', 'danger');
            return [];
        }
    }
    
    // Display expenses in table
    displayExpenses(expenses) {
        const tbody = document.querySelector('#expenses-table tbody');
        if (!tbody) return;
        
        tbody.innerHTML = '';
        
        expenses.forEach(expense => {
            const statusBadge = this.getExpenseStatusColor(expense.status);
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${expense.description}</td>
                <td>${expense.category}</td>
                <td>$${expense.amount}</td>
                <td>${expense.date}</td>
                <td>
                    <span class="badge bg-${statusBadge}">${expense.status}</span>
                </td>
                <td>${expense.payment_method || 'N/A'}</td>
                <td>
                    <button class="btn btn-sm btn-primary" onclick="viewExpense(${expense.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-success" onclick="editExpense(${expense.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteExpense(${expense.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            tbody.appendChild(row);
        });
    }
    
    // View expense details
    async viewExpense(expenseId) {
        try {
            const response = await apiCall('GET', `api/expenses/${expenseId}`);
            if (response.success) {
                this.showExpenseDetailsModal(response.data);
            } else {
                showAlert('Error loading expense details: ' + response.message, 'danger');
            }
        } catch (error) {
            console.error('Error loading expense details:', error);
            showAlert('Error loading expense details', 'danger');
        }
    }
    
    // Show expense details modal
    showExpenseDetailsModal(expense) {
        const modalId = 'expenseDetailsModal';
        const modalContent = `
            <div class="modal-header">
                <h5 class="modal-title">Expense Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-6">
                        <h6>Expense Information</h6>
                        <p><strong>Description:</strong> ${expense.description}</p>
                        <p><strong>Category:</strong> ${expense.category}</p>
                        <p><strong>Amount:</strong> $${expense.amount}</p>
                        <p><strong>Date:</strong> ${expense.date}</p>
                        <p><strong>Status:</strong> <span class="badge bg-${this.getExpenseStatusColor(expense.status)}">${expense.status}</span></p>
                    </div>
                    <div class="col-md-6">
                        <h6>Payment Details</h6>
                        <p><strong>Payment Method:</strong> ${expense.payment_method || 'N/A'}</p>
                        <p><strong>Reference:</strong> ${expense.reference_number || 'N/A'}</p>
                        <p><strong>Notes:</strong> ${expense.notes || 'N/A'}</p>
                        <p><strong>Created:</strong> ${expense.created_at}</p>
                    </div>
                </div>
                
                ${expense.receipt_url ? `
                    <div class="row mt-3">
                        <div class="col-12">
                            <h6>Receipt</h6>
                            <img src="${expense.receipt_url}" alt="Receipt" class="img-fluid" style="max-width: 300px;">
                        </div>
                    </div>
                ` : ''}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-success" onclick="editExpense(${expense.id})">
                    Edit Expense
                </button>
            </div>
        `;
        
        createModal(modalId, 'Expense Details', modalContent, {
            size: 'modal-lg',
            showFooter: true
        });
        
        openModal(modalId);
    }
    
    // Generate expense report
    async generateExpenseReport(filters = {}) {
        try {
            const response = await apiCall('POST', 'api/expenses/report', filters);
            if (response.success) {
                this.downloadExpenseReport(response.data);
                return response.data;
            } else {
                showAlert('Error generating expense report: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error generating expense report:', error);
            showAlert('Error generating expense report', 'danger');
        }
    }
    
    // Download expense report
    downloadExpenseReport(reportData) {
        const csvContent = this.convertExpenseReportToCSV(reportData);
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = `expense-report-${new Date().toISOString().split('T')[0]}.csv`;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    // Convert expense report to CSV
    convertExpenseReportToCSV(reportData) {
        const headers = ['Date', 'Description', 'Category', 'Amount', 'Status', 'Payment Method', 'Notes'];
        const rows = reportData.map(expense => [
            expense.date,
            expense.description,
            expense.category,
            expense.amount,
            expense.status,
            expense.payment_method || '',
            expense.notes || ''
        ]);
        
        return [headers, ...rows].map(row => row.join(',')).join('\n');
    }
    
    // Get expense statistics
    async getExpenseStatistics(period = 'month') {
        try {
            const response = await apiCall('GET', `api/expenses/statistics?period=${period}`);
            if (response.success) {
                return response.data;
            } else {
                showAlert('Error loading expense statistics: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error loading expense statistics:', error);
            showAlert('Error loading expense statistics', 'danger');
            return null;
        }
    }
    
    // Display expense statistics
    displayExpenseStatistics(statistics) {
        if (!statistics) return;
        
        // Update dashboard cards
        const elements = {
            'totalExpenses': statistics.total_expenses,
            'monthlyExpenses': statistics.monthly_expenses,
            'pendingExpenses': statistics.pending_expenses,
            'expenseGrowth': statistics.expense_growth
        };
        
        Object.keys(elements).forEach(key => {
            const element = document.getElementById(key);
            if (element) {
                if (key === 'expenseGrowth') {
                    element.textContent = `${elements[key]}%`;
                    element.className = `text-${elements[key] >= 0 ? 'danger' : 'success'}`;
                } else {
                    element.textContent = `$${elements[key].toLocaleString()}`;
                }
            }
        });
        
        // Update charts if they exist
        this.updateExpenseCharts(statistics);
    }
    
    // Update expense charts
    updateExpenseCharts(statistics) {
        // Expense trend chart
        const expenseChart = document.getElementById('expenseChart');
        if (expenseChart && statistics.expense_chart_data) {
            this.updateChart(expenseChart, statistics.expense_chart_data, 'Expense Trend');
        }
        
        // Category breakdown chart
        const categoryChart = document.getElementById('expenseCategoryChart');
        if (categoryChart && statistics.category_data) {
            this.updateChart(categoryChart, statistics.category_data, 'Expense by Category');
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
                    borderColor: 'rgb(255, 159, 64)',
                    backgroundColor: 'rgba(255, 159, 64, 0.2)',
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
    
    // Get monthly expense summary
    async getMonthlyExpenseSummary(year, month) {
        try {
            const response = await apiCall('GET', `api/expenses/monthly-summary?year=${year}&month=${month}`);
            if (response.success) {
                return response.data;
            } else {
                showAlert('Error loading monthly summary: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error loading monthly summary:', error);
            showAlert('Error loading monthly summary', 'danger');
            return null;
        }
    }
    
    // Get yearly expense summary
    async getYearlyExpenseSummary(year) {
        try {
            const response = await apiCall('GET', `api/expenses/yearly-summary?year=${year}`);
            if (response.success) {
                return response.data;
            } else {
                showAlert('Error loading yearly summary: ' + response.message, 'danger');
                return null;
            }
        } catch (error) {
            console.error('Error loading yearly summary:', error);
            showAlert('Error loading yearly summary', 'danger');
            return null;
        }
    }
    
    // Destroy instance and cleanup
    destroy() {
        // Cleanup event listeners and timers
    }
}

// Utility functions for expense management
export function createExpenseForm(containerId) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
    const form = document.createElement('form');
    form.id = 'expenseForm';
    form.className = 'expense-form';
    
    form.innerHTML = `
        <div class="row">
            <div class="col-md-6">
                <h5>Expense Details</h5>
                <div class="mb-3">
                    <label class="form-label">Description</label>
                    <input type="text" class="form-control" id="expenseDescription" placeholder="Enter expense description" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Category</label>
                    <select class="form-select" id="expenseCategory" required>
                        <option value="">Select Category</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Amount</label>
                    <input type="number" class="form-control" id="expenseAmount" step="0.01" min="0" required>
                </div>
                <div class="mb-3">
                    <label class="form-label">Date</label>
                    <input type="date" class="form-control" id="expenseDate" value="${new Date().toISOString().split('T')[0]}" required>
                </div>
            </div>
            <div class="col-md-6">
                <h5>Additional Details</h5>
                <div class="mb-3">
                    <label class="form-label">Tax</label>
                    <input type="number" class="form-control" id="expenseTax" step="0.01" min="0" value="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Discount</label>
                    <input type="number" class="form-control" id="expenseDiscount" step="0.01" min="0" value="0">
                </div>
                <div class="mb-3">
                    <label class="form-label">Payment Method</label>
                    <select class="form-select" id="expensePaymentMethod">
                        <option value="">Select Payment Method</option>
                        <option value="Cash">Cash</option>
                        <option value="Credit Card">Credit Card</option>
                        <option value="Debit Card">Debit Card</option>
                        <option value="Bank Transfer">Bank Transfer</option>
                        <option value="Check">Check</option>
                        <option value="Online Payment">Online Payment</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Reference Number</label>
                    <input type="text" class="form-control" id="expenseReference" placeholder="Invoice #, Receipt #, etc.">
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <h5>Budget Information</h5>
                <div class="row">
                    <div class="col-md-4">
                        <label class="form-label">Category Budget</label>
                        <div class="form-control-plaintext" id="categoryBudget">$0.00</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Expense Period</label>
                        <div class="form-control-plaintext" id="expensePeriod">-</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Total Amount</label>
                        <div class="form-control-plaintext" id="expenseTotal">$0.00</div>
                    </div>
                </div>
                <div id="budgetWarning"></div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-md-6">
                <h5>Receipt Upload</h5>
                <div class="mb-3">
                    <label class="form-label">Upload Receipt</label>
                    <input type="file" class="form-control" id="expenseReceipt" accept="image/*,.pdf">
                </div>
                <div id="receiptPreview"></div>
            </div>
            <div class="col-md-6">
                <h5>Notes</h5>
                <div class="mb-3">
                    <textarea class="form-control" id="expenseNotes" rows="4" placeholder="Additional notes about this expense"></textarea>
                </div>
            </div>
        </div>
        
        <div class="row mt-3">
            <div class="col-12">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" id="enableAutoCategorization" checked>
                    <label class="form-check-label" for="enableAutoCategorization">
                        Enable automatic expense categorization
                    </label>
                </div>
            </div>
        </div>
        
        <div class="form-actions mt-3">
            <button type="submit" class="btn btn-primary">Save Expense</button>
            <button type="button" class="btn btn-secondary" onclick="closeExpenseModal()">Cancel</button>
        </div>
    `;
    
    container.appendChild(form);
    
    // Initialize expense manager
    const expenseManager = new ExpenseManager();
    
    return expenseManager;
}

// Global functions for HTML onclick handlers
window.viewExpense = function(expenseId) {
    if (window.currentExpenseManager) {
        window.currentExpenseManager.viewExpense(expenseId);
    }
};

window.editExpense = function(expenseId) {
    // Implementation for editing expenses
    console.log('Edit expense:', expenseId);
};

window.deleteExpense = function(expenseId) {
    if (window.currentExpenseManager) {
        window.currentExpenseManager.deleteExpense(expenseId);
    }
};

window.removeReceipt = function() {
    const receiptPreview = document.getElementById('receiptPreview');
    if (receiptPreview) {
        receiptPreview.innerHTML = '';
    }
    
    const receiptInput = document.getElementById('expenseReceipt');
    if (receiptInput) {
        receiptInput.value = '';
    }
};

window.editBudgetLimit = function(budgetId) {
    // Implementation for editing budget limits
    console.log('Edit budget limit:', budgetId);
};