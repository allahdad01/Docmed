// ===== NAVIGATION FUNCTIONS =====

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