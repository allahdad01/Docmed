        // Global variables
        let currentUser = null;
        let authToken = null;

        // Initialize dashboard
        document.addEventListener('DOMContentLoaded', function() {
            const token = localStorage.getItem('authToken');
            if (token) {
                authToken = token;
                loadDashboard();
            } else {
                window.location.href = 'login.html';
            }
        });

        // Navigation functions
        function showDashboard() {
            hideAllSections();
            document.getElementById('dashboard-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadDashboard();
        }

        function showProducts() {
            hideAllSections();
            document.getElementById('products-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadProducts();
        }

        function showSales() {
            hideAllSections();
            document.getElementById('sales-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadSales();
        }

        function showCustomers() {
            hideAllSections();
            document.getElementById('customers-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadCustomers();
        }

        function showInventory() {
            hideAllSections();
            document.getElementById('inventory-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadInventory();
        }

        function showCategories() {
            hideAllSections();
            document.getElementById('categories-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadCategories();
        }

        function showBranches() {
            hideAllSections();
            document.getElementById('branches-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadBranches();
        }

        function showSuppliers() {
            hideAllSections();
            document.getElementById('suppliers-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadSuppliers();
        }

        function showDoctors() {
            hideAllSections();
            document.getElementById('doctors-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadDoctors();
        }

        function showPatients() {
            hideAllSections();
            document.getElementById('patients-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadPatients();
        }

        function showPrescriptions() {
            hideAllSections();
            document.getElementById('prescriptions-section').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadPrescriptions();
        }

        function showMedicines() {
            hideAllSections();
            document.getElementById('medicines').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadMedicines();
        }

        function showLaboratoryTests() {
            hideAllSections();
            document.getElementById('laboratory-tests').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadLaboratoryTests();
        }

        function showExpenses() {
            hideAllSections();
            document.getElementById('expenses').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadExpenses();
            loadExpenseSummary();
        }

        function showPayments() {
            hideAllSections();
            document.getElementById('payments').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
            event.target.classList.add('active');
            loadPayments();
            loadPaymentSummary();
        }

        function showReports() {
            hideAllSections();
            document.getElementById('reports').style.display = 'block';
            document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
        }

        // ===== PAGINATION HELPER =====

        function displayPagination(pagination, containerId, loadFunction) {
            const container = document.getElementById(containerId);
            if (!container) return;
            
            container.innerHTML = '';
            
            if (pagination.pages <= 1) return;
            
            // Previous button
            const prevLi = document.createElement('li');
            prevLi.className = `page-item ${pagination.page <= 1 ? 'disabled' : ''}`;
            prevLi.innerHTML = `<a class="page-link" href="#" onclick="loadFunction(${pagination.page - 1})">Previous</a>`;
            container.appendChild(prevLi);
            
            // Page numbers
            for (let i = 1; i <= pagination.pages; i++) {
                const li = document.createElement('li');
                li.className = `page-item ${i === pagination.page ? 'active' : ''}`;
                li.innerHTML = `<a class="page-link" href="#" onclick="loadFunction(${i})">${i}</a>`;
                container.appendChild(li);
            }
            
            // Next button
            const nextLi = document.createElement('li');
            nextLi.className = `page-item ${pagination.page >= pagination.pages ? 'disabled' : ''}`;
            nextLi.innerHTML = `<a class="page-link" href="#" onclick="loadFunction(${pagination.page + 1})">Next</a>`;
            container.appendChild(nextLi);
        }

        // ===== UTILITY FUNCTIONS =====

        function hideAllSections() {
            const sections = [
                'dashboard-section', 'products-section', 'sales-section', 'customers-section',
                'inventory-section', 'categories-section', 'branches-section', 'suppliers-section',
                'doctors-section', 'patients-section', 'prescriptions-section', 'medicines',
                'laboratory-tests', 'expenses', 'payments', 'reports'
            ];
            
            sections.forEach(sectionId => {
                const section = document.getElementById(sectionId);
                if (section) {
                    section.style.display = 'none';
                }
            });
        }

        function showAlert(message, type = 'info') {
            const alertDiv = document.createElement('div');
            alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
            alertDiv.innerHTML = `
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            `;
            
            const container = document.querySelector('.container-fluid');
            container.insertBefore(alertDiv, container.firstChild);
            
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 5000);
        }

        // ===== API HELPER FUNCTIONS =====

        async function apiCall(endpoint, options = {}) {
            const defaultOptions = {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${authToken}`
                }
            };
            
            const finalOptions = { ...defaultOptions, ...options };
            
            if (finalOptions.body && typeof finalOptions.body === 'object') {
                finalOptions.body = JSON.stringify(finalOptions.body);
            }
            
            try {
                const response = await fetch(endpoint, finalOptions);
                const data = await response.json();
                
                if (!response.ok) {
                    throw new Error(data.message || 'API request failed');
                }
                
                return data;
            } catch (error) {
                console.error('API Error:', error);
                showAlert(error.message || 'An error occurred', 'danger');
                throw error;
            }
        }

        // ===== DASHBOARD FUNCTIONS =====

        async function loadDashboard() {
            try {
                const [dashboardData, recentSales, lowStock] = await Promise.all([
                    apiCall('api/dashboard'),
                    apiCall('api/sales/recent'),
                    apiCall('api/inventory/low-stock')
                ]);
                
                if (dashboardData.success) {
                    updateDashboardStats(dashboardData.data);
                }
                
                if (recentSales.success) {
                    displayRecentSales(recentSales.data);
                }
                
                if (lowStock.success) {
                    displayLowStockItems(lowStock.data);
                }
            } catch (error) {
                console.error('Error loading dashboard:', error);
            }
        }

        function updateDashboardStats(data) {
            const elements = {
                'totalSales': data.total_sales || '0.00',
                'totalCustomers': data.total_customers || '0',
                'totalProducts': data.total_products || '0',
                'monthlyRevenue': data.monthly_revenue || '0.00'
            };
            
            Object.entries(elements).forEach(([id, value]) => {
                const element = document.getElementById(id);
                if (element) {
                    if (id.includes('Sales') || id.includes('Revenue')) {
                        element.textContent = `$${value}`;
                    } else {
                        element.textContent = value;
                    }
                }
            });
        }

        function displayRecentSales(sales) {
            const container = document.getElementById('recentSalesContainer');
            if (!container) return;
            
            container.innerHTML = sales.map(sale => `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>${sale.customer_name || 'N/A'}</strong>
                        <br><small class="text-muted">${sale.product_name || 'N/A'}</small>
                    </div>
                    <div class="text-end">
                        <strong>$${sale.total_amount || '0.00'}</strong>
                        <br><small class="text-muted">${sale.sale_date || 'N/A'}</small>
                    </div>
                </div>
            `).join('');
        }

        function displayLowStockItems(items) {
            const container = document.getElementById('lowStockContainer');
            if (!container) return;
            
            container.innerHTML = items.map(item => `
                <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                    <div>
                        <strong>${item.name || 'N/A'}</strong>
                        <br><small class="text-muted">${item.category || 'N/A'}</small>
                    </div>
                    <div class="text-end">
                        <span class="badge bg-warning">${item.quantity || '0'}</span>
                        <br><small class="text-muted">Low Stock</small>
                    </div>
                </div>
            `).join('');
        }

        // ===== MEDICINES FUNCTIONS =====

        async function loadMedicines(page = 1) {
            try {
                const searchTerm = document.getElementById('medicineSearch').value;
                const typeFilter = document.getElementById('medicineTypeFilter').value;
                
                const params = new URLSearchParams({
                    page: page,
                    search: searchTerm,
                    type: typeFilter
                });
                
                const data = await apiCall(`api/medicines?${params}`);
                if (data.success) {
                    displayMedicines(data.data);
                    displayPagination(data.pagination, 'medicinesPagination', loadMedicines);
                } else {
                    showAlert('Error loading medicines', 'danger');
                }
            } catch (error) {
                console.error('Error loading medicines:', error);
            }
        }

        function displayMedicines(medicines) {
            const tbody = document.getElementById('medicinesTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = medicines.map(medicine => `
                <tr>
                    <td>${medicine.name || 'N/A'}</td>
                    <td>${medicine.generic_name || 'N/A'}</td>
                    <td>${medicine.type || 'N/A'}</td>
                    <td>${medicine.strength || 'N/A'}</td>
                    <td>${medicine.manufacturer || 'N/A'}</td>
                    <td>${medicine.expiry_date || 'N/A'}</td>
                    <td>${medicine.quantity || '0'}</td>
                    <td>$${medicine.price || '0.00'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editMedicine(${medicine.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteMedicine(${medicine.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function addMedicine() {
            document.getElementById('medicineModalTitle').textContent = 'Add Medicine';
            document.getElementById('medicineForm').reset();
            document.getElementById('medicineId').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('medicineModal'));
            modal.show();
        }

        function editMedicine(id) {
            document.getElementById('medicineModalTitle').textContent = 'Edit Medicine';
            
            // Load medicine data
            apiCall(`api/medicines/${id}`).then(data => {
                if (data.success) {
                    const medicine = data.data;
                    document.getElementById('medicineId').value = medicine.id;
                    document.getElementById('medicineName').value = medicine.name;
                    document.getElementById('medicineGenericName').value = medicine.generic_name;
                    document.getElementById('medicineType').value = medicine.type;
                    document.getElementById('medicineStrength').value = medicine.strength;
                    document.getElementById('medicineManufacturer').value = medicine.manufacturer;
                    document.getElementById('medicineExpiryDate').value = medicine.expiry_date;
                    document.getElementById('medicineQuantity').value = medicine.quantity;
                    document.getElementById('medicinePrice').value = medicine.price;
                    document.getElementById('medicineDescription').value = medicine.description;
                    
                    const modal = new bootstrap.Modal(document.getElementById('medicineModal'));
                    modal.show();
                }
            });
        }

        async function saveMedicine() {
            const form = document.getElementById('medicineForm');
            const formData = new FormData(form);
            
            const medicineData = {
                name: formData.get('name'),
                generic_name: formData.get('generic_name'),
                type: formData.get('type'),
                strength: formData.get('strength'),
                manufacturer: formData.get('manufacturer'),
                expiry_date: formData.get('expiry_date'),
                quantity: formData.get('quantity'),
                price: formData.get('price'),
                description: formData.get('description')
            };
            
            const id = document.getElementById('medicineId').value;
            const endpoint = id ? `api/medicines/${id}` : 'api/medicines';
            const method = id ? 'PUT' : 'POST';
            
            try {
                const data = await apiCall(endpoint, {
                    method: method,
                    body: medicineData
                });
                
                if (data.success) {
                    showAlert(`Medicine ${id ? 'updated' : 'added'} successfully`, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('medicineModal'));
                    modal.hide();
                    loadMedicines();
                }
            } catch (error) {
                console.error('Error saving medicine:', error);
            }
        }

        function deleteMedicine(id) {
            document.getElementById('deleteMedicineModal').setAttribute('data-medicine-id', id);
            const modal = new bootstrap.Modal(document.getElementById('deleteMedicineModal'));
            modal.show();
        }

        async function confirmDeleteMedicine() {
            const id = document.getElementById('deleteMedicineModal').getAttribute('data-medicine-id');
            
            try {
                const data = await apiCall(`api/medicines/${id}`, { method: 'DELETE' });
                if (data.success) {
                    showAlert('Medicine deleted successfully', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteMedicineModal'));
                    modal.hide();
                    loadMedicines();
                }
            } catch (error) {
                console.error('Error deleting medicine:', error);
            }
        }

        // ===== LABORATORY TESTS FUNCTIONS =====

        async function loadLaboratoryTests(page = 1) {
            try {
                const searchTerm = document.getElementById('labTestSearch').value;
                const categoryFilter = document.getElementById('labTestCategoryFilter').value;
                
                const params = new URLSearchParams({
                    page: page,
                    search: searchTerm,
                    category: categoryFilter
                });
                
                const data = await apiCall(`api/laboratory-tests?${params}`);
                if (data.success) {
                    displayLaboratoryTests(data.data);
                    displayPagination(data.pagination, 'laboratoryTestsPagination', loadLaboratoryTests);
                } else {
                    showAlert('Error loading tests', 'danger');
                }
            } catch (error) {
                console.error('Error loading laboratory tests:', error);
            }
        }

        function displayLaboratoryTests(tests) {
            const tbody = document.getElementById('laboratoryTestsTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = tests.map(test => `
                <tr>
                    <td>${test.name || 'N/A'}</td>
                    <td>${test.category || 'N/A'}</td>
                    <td>${test.description || 'N/A'}</td>
                    <td>${test.preparation || 'N/A'}</td>
                    <td>${test.duration || 'N/A'}</td>
                    <td>$${test.price || '0.00'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editLaboratoryTest(${test.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteLaboratoryTest(${test.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function addLaboratoryTest() {
            document.getElementById('laboratoryTestModalTitle').textContent = 'Add Laboratory Test';
            document.getElementById('laboratoryTestForm').reset();
            document.getElementById('laboratoryTestId').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('laboratoryTestModal'));
            modal.show();
        }

        function editLaboratoryTest(id) {
            document.getElementById('laboratoryTestModalTitle').textContent = 'Edit Laboratory Test';
            
            apiCall(`api/laboratory-tests/${id}`).then(data => {
                if (data.success) {
                    const test = data.data;
                    document.getElementById('laboratoryTestId').value = test.id;
                    document.getElementById('laboratoryTestName').value = test.name;
                    document.getElementById('laboratoryTestCategory').value = test.category;
                    document.getElementById('laboratoryTestDescription').value = test.description;
                    document.getElementById('laboratoryTestPreparation').value = test.preparation;
                    document.getElementById('laboratoryTestDuration').value = test.duration;
                    document.getElementById('laboratoryTestPrice').value = test.price;
                    
                    const modal = new bootstrap.Modal(document.getElementById('laboratoryTestModal'));
                    modal.show();
                }
            });
        }

        async function saveLaboratoryTest() {
            const form = document.getElementById('laboratoryTestForm');
            const formData = new FormData(form);
            
            const testData = {
                name: formData.get('name'),
                category: formData.get('category'),
                description: formData.get('description'),
                preparation: formData.get('preparation'),
                duration: formData.get('duration'),
                price: formData.get('price')
            };
            
            const id = document.getElementById('laboratoryTestId').value;
            const endpoint = id ? `api/laboratory-tests/${id}` : 'api/laboratory-tests';
            const method = id ? 'PUT' : 'POST';
            
            try {
                const data = await apiCall(endpoint, {
                    method: method,
                    body: testData
                });
                
                if (data.success) {
                    showAlert(`Laboratory test ${id ? 'updated' : 'added'} successfully`, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('laboratoryTestModal'));
                    modal.hide();
                    loadLaboratoryTests();
                }
            } catch (error) {
                console.error('Error saving laboratory test:', error);
            }
        }

        function deleteLaboratoryTest(id) {
            document.getElementById('deleteLaboratoryTestModal').setAttribute('data-test-id', id);
            const modal = new bootstrap.Modal(document.getElementById('deleteLaboratoryTestModal'));
            modal.show();
        }

        async function confirmDeleteLaboratoryTest() {
            const id = document.getElementById('deleteLaboratoryTestModal').getAttribute('data-test-id');
            
            try {
                const data = await apiCall(`api/laboratory-tests/${id}`, { method: 'DELETE' });
                if (data.success) {
                    showAlert('Laboratory test deleted successfully', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteLaboratoryTestModal'));
                    modal.hide();
                    loadLaboratoryTests();
                }
            } catch (error) {
                console.error('Error deleting laboratory test:', error);
            }
        }

        // ===== EXPENSES FUNCTIONS =====

        async function loadExpenses(page = 1) {
            try {
                const searchTerm = document.getElementById('expenseSearch').value;
                const categoryFilter = document.getElementById('expenseCategoryFilter').value;
                const dateFrom = document.getElementById('expenseDateFrom').value;
                const dateTo = document.getElementById('expenseDateTo').value;
                
                const params = new URLSearchParams({
                    page: page,
                    search: searchTerm,
                    category: categoryFilter,
                    date_from: dateFrom,
                    date_to: dateTo
                });
                
                const data = await apiCall(`api/expenses?${params}`);
                if (data.success) {
                    displayExpenses(data.data);
                    displayPagination(data.pagination, 'expensesPagination', loadExpenses);
                } else {
                    showAlert('Error loading expenses', 'danger');
                }
            } catch (error) {
                console.error('Error loading expenses:', error);
            }
        }

        function displayExpenses(expenses) {
            const tbody = document.getElementById('expensesTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = expenses.map(expense => `
                <tr>
                    <td>${expense.date || 'N/A'}</td>
                    <td>${expense.description || 'N/A'}</td>
                    <td>${expense.category || 'N/A'}</td>
                    <td>$${expense.amount || '0.00'}</td>
                    <td>${expense.payment_method || 'N/A'}</td>
                    <td>${expense.receipt_number || 'N/A'}</td>
                    <td>${expense.notes || 'N/A'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editExpense(${expense.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deleteExpense(${expense.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        async function loadExpenseSummary() {
            try {
                const data = await apiCall('api/expenses/summary');
                if (data.success) {
                    const summary = data.data;
                    document.getElementById('totalExpenses').textContent = `$${summary.total_expenses || '0.00'}`;
                    document.getElementById('monthlyExpenses').textContent = `$${summary.monthly_expenses || '0.00'}`;
                }
            } catch (error) {
                console.error('Error loading expense summary:', error);
            }
        }

        function addExpense() {
            document.getElementById('expenseModalTitle').textContent = 'Add Expense';
            document.getElementById('expenseForm').reset();
            document.getElementById('expenseId').value = '';
            
            const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
            modal.show();
        }

        function editExpense(id) {
            document.getElementById('expenseModalTitle').textContent = 'Edit Expense';
            
            apiCall(`api/expenses/${id}`).then(data => {
                if (data.success) {
                    const expense = data.data;
                    document.getElementById('expenseId').value = expense.id;
                    document.getElementById('expenseDate').value = expense.date;
                    document.getElementById('expenseDescription').value = expense.description;
                    document.getElementById('expenseCategory').value = expense.category;
                    document.getElementById('expenseAmount').value = expense.amount;
                    document.getElementById('expensePaymentMethod').value = expense.payment_method;
                    document.getElementById('expenseReceiptNumber').value = expense.receipt_number;
                    document.getElementById('expenseNotes').value = expense.notes;
                    
                    const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
                    modal.show();
                }
            });
        }

        async function saveExpense() {
            const form = document.getElementById('expenseForm');
            const formData = new FormData(form);
            
            const expenseData = {
                date: formData.get('date'),
                description: formData.get('description'),
                category: formData.get('category'),
                amount: formData.get('amount'),
                payment_method: formData.get('payment_method'),
                receipt_number: formData.get('receipt_number'),
                notes: formData.get('notes')
            };
            
            const id = document.getElementById('expenseId').value;
            const endpoint = id ? `api/expenses/${id}` : 'api/expenses';
            const method = id ? 'PUT' : 'POST';
            
            try {
                const data = await apiCall(endpoint, {
                    method: method,
                    body: expenseData
                });
                
                if (data.success) {
                    showAlert(`Expense ${id ? 'updated' : 'added'} successfully`, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('expenseModal'));
                    modal.hide();
                    loadExpenses();
                    loadExpenseSummary();
                }
            } catch (error) {
                console.error('Error saving expense:', error);
            }
        }

        function deleteExpense(id) {
            document.getElementById('deleteExpenseModal').setAttribute('data-expense-id', id);
            const modal = new bootstrap.Modal(document.getElementById('deleteExpenseModal'));
            modal.show();
        }

        async function confirmDeleteExpense() {
            const id = document.getElementById('deleteExpenseModal').getAttribute('data-expense-id');
            
            try {
                const data = await apiCall(`api/expenses/${id}`, { method: 'DELETE' });
                if (data.success) {
                    showAlert('Expense deleted successfully', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deleteExpenseModal'));
                    modal.hide();
                    loadExpenses();
                    loadExpenseSummary();
                }
            } catch (error) {
                console.error('Error deleting expense:', error);
            }
        }

        // ===== PAYMENTS FUNCTIONS =====

        async function loadPayments(page = 1) {
            try {
                const searchTerm = document.getElementById('paymentSearch').value;
                const typeFilter = document.getElementById('paymentTypeFilter').value;
                const dateFrom = document.getElementById('paymentDateFrom').value;
                const dateTo = document.getElementById('paymentDateTo').value;
                
                const params = new URLSearchParams({
                    page: page,
                    search: searchTerm,
                    type: typeFilter,
                    date_from: dateFrom,
                    date_to: dateTo
                });
                
                const data = await apiCall(`api/payments?${params}`);
                if (data.success) {
                    displayPayments(data.data);
                    displayPagination(data.pagination, 'paymentsPagination', loadPayments);
                } else {
                    showAlert('Error loading payments', 'danger');
                }
            } catch (error) {
                console.error('Error loading payments:', error);
            }
        }

        function displayPayments(payments) {
            const tbody = document.getElementById('paymentsTableBody');
            if (!tbody) return;
            
            tbody.innerHTML = payments.map(payment => `
                <tr>
                    <td>${payment.date || 'N/A'}</td>
                    <td>${payment.patient_name || 'N/A'}</td>
                    <td>${payment.type || 'N/A'}</td>
                    <td>$${payment.amount || '0.00'}</td>
                    <td>${payment.payment_method || 'N/A'}</td>
                    <td>
                        <span class="badge bg-${getStatusBadgeColor(payment.status)}">
                            ${payment.status || 'N/A'}
                        </span>
                    </td>
                    <td>${payment.receipt_number || 'N/A'}</td>
                    <td>
                        <button class="btn btn-sm btn-primary" onclick="editPayment(${payment.id})">
                            <i class="fas fa-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-danger" onclick="deletePayment(${payment.id})">
                            <i class="fas fa-trash"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }

        function getStatusBadgeColor(status) {
            const colors = {
                'pending': 'warning',
                'partial': 'info',
                'completed': 'success',
                'cancelled': 'danger',
                'refunded': 'secondary'
            };
            return colors[status] || 'secondary';
        }

        async function loadPaymentSummary() {
            try {
                const data = await apiCall('api/payments/summary');
                if (data.success) {
                    const summary = data.data;
                    document.getElementById('totalReceived').textContent = `$${summary.total_received || '0.00'}`;
                    document.getElementById('pendingPayments').textContent = `$${summary.pending_payments || '0.00'}`;
                }
            } catch (error) {
                console.error('Error loading payment summary:', error);
            }
        }

        function addPayment() {
            document.getElementById('paymentModalTitle').textContent = 'Add Payment';
            document.getElementById('paymentForm').reset();
            document.getElementById('paymentId').value = '';
            
            // Load patients and prescriptions
            loadPatientsForPayment();
            loadPrescriptionsForPayment();
            
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        }

        function editPayment(id) {
            document.getElementById('paymentModalTitle').textContent = 'Edit Payment';
            
            apiCall(`api/payments/${id}`).then(data => {
                if (data.success) {
                    const payment = data.data;
                    document.getElementById('paymentId').value = payment.id;
                    document.getElementById('paymentDate').value = payment.date;
                    document.getElementById('paymentPatientSelect').value = payment.patient_id;
                    document.getElementById('paymentType').value = payment.type;
                    document.getElementById('paymentAmount').value = payment.amount;
                    document.getElementById('paymentMethod').value = payment.payment_method;
                    document.getElementById('paymentStatus').value = payment.status;
                    document.getElementById('paymentReceiptNumber').value = payment.receipt_number;
                    document.getElementById('paymentNotes').value = payment.notes;
                    
                    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
                    modal.show();
                }
            });
        }

        async function savePayment() {
            const form = document.getElementById('paymentForm');
            const formData = new FormData(form);
            
            const paymentData = {
                date: formData.get('date'),
                patient_id: formData.get('patient_id'),
                type: formData.get('type'),
                amount: formData.get('amount'),
                payment_method: formData.get('payment_method'),
                status: formData.get('status'),
                receipt_number: formData.get('receipt_number'),
                notes: formData.get('notes')
            };
            
            const id = document.getElementById('paymentId').value;
            const endpoint = id ? `api/payments/${id}` : 'api/payments';
            const method = id ? 'PUT' : 'POST';
            
            try {
                const data = await apiCall(endpoint, {
                    method: method,
                    body: paymentData
                });
                
                if (data.success) {
                    showAlert(`Payment ${id ? 'updated' : 'added'} successfully`, 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
                    modal.hide();
                    loadPayments();
                    loadPaymentSummary();
                }
            } catch (error) {
                console.error('Error saving payment:', error);
            }
        }

        function deletePayment(id) {
            document.getElementById('deletePaymentModal').setAttribute('data-payment-id', id);
            const modal = new bootstrap.Modal(document.getElementById('deletePaymentModal'));
            modal.show();
        }

        async function confirmDeletePayment() {
            const id = document.getElementById('deletePaymentModal').getAttribute('data-payment-id');
            
            try {
                const data = await apiCall(`api/payments/${id}`, { method: 'DELETE' });
                if (data.success) {
                    showAlert('Payment deleted successfully', 'success');
                    const modal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
                    modal.hide();
                    loadPayments();
                    loadPaymentSummary();
                }
            } catch (error) {
                console.error('Error deleting payment:', error);
            }
        }

        async function loadPatientsForPayment() {
            try {
                const patients = await apiCall('api/patients');
                if (patients && patients.data) {
                    const select = document.getElementById('paymentPatientSelect');
                    select.innerHTML = '<option value="">Select Patient</option>';
                    patients.data.forEach(patient => {
                        select.innerHTML += `<option value="${patient.id}">${patient.name || 'N/A'} - ${patient.phone || 'N/A'}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error loading patients for payment:', error);
            }
        }

        async function loadPrescriptionsForPayment() {
            try {
                const prescriptions = await apiCall('api/prescriptions');
                if (prescriptions && prescriptions.data) {
                    const select = document.getElementById('paymentPrescriptionSelect');
                    select.innerHTML = '<option value="">Select Prescription</option>';
                    prescriptions.data.forEach(prescription => {
                        select.innerHTML += `<option value="${prescription.id}">Prescription #${prescription.id} - ${prescription.patient_name || 'N/A'}</option>`;
                    });
                }
            } catch (error) {
                console.error('Error loading prescriptions for payment:', error);
            }
        }

        // ===== REPORT FUNCTIONS =====

        function generateReport() {
            const dateFrom = document.getElementById('reportDateFrom').value;
            const dateTo = document.getElementById('reportDateTo').value;
            
            if (!dateFrom || !dateTo) {
                showAlert('Please select both start and end dates', 'warning');
                return;
            }
            
            // Generate report logic here
            showAlert('Report generation started. Please wait...', 'info');
        }

        function exportReport(format) {
            const dateFrom = document.getElementById('reportDateFrom').value;
            const dateTo = document.getElementById('reportDateTo').value;
            
            if (!dateFrom || !dateTo) {
                showAlert('Please select both start and end dates', 'warning');
                return;
            }
            
            // Export logic here
            showAlert(`${format.toUpperCase()} export started. Please wait...`, 'info');
        }

        // ===== OTHER FUNCTIONS =====

        // Add placeholder functions for other sections
        function loadProducts() {
            showAlert('Products section - Coming soon', 'info');
        }

        function loadSales() {
            showAlert('Sales section - Coming soon', 'info');
        }

        function loadCustomers() {
            showAlert('Customers section - Coming soon', 'info');
        }

        function loadInventory() {
            showAlert('Inventory section - Coming soon', 'info');
        }

        function loadCategories() {
            showAlert('Categories section - Coming soon', 'info');
        }

        function loadBranches() {
            showAlert('Branches section - Coming soon', 'info');
        }

        function loadSuppliers() {
            showAlert('Suppliers section - Coming soon', 'info');
        }

        function loadDoctors() {
            showAlert('Doctors section - Coming soon', 'info');
        }

        function loadPatients() {
            showAlert('Patients section - Coming soon', 'info');
        }

        function loadPrescriptions() {
            showAlert('Prescriptions section - Coming soon', 'info');
        }


