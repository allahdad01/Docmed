// Main entry point for the dashboard application
// This file imports all the necessary JavaScript modules

// Import order is important due to dependencies
// Core functions must be loaded first
import './core.js';
import './api-helpers.js';
import './navigation.js';

// Utility functions
import './js/utils.js';

// Specialized modules
import './js/modal-manager.js';
import './js/search-filters.js';
import './js/form-validation.js';
import './js/data-tables.js';

// Core business modules
import './js/inventory.js';
import './js/categories.js';
import './js/branches.js';
import './js/suppliers.js';
import './js/products.js';
import './js/purchases.js';
import './js/sales.js';
import './js/customers.js';

// Advanced features modules
import './js/medicines.js';
import './js/laboratory-tests.js';
import './js/expenses.js';
import './js/payments.js';
import './js/reports.js';

// NEW: Advanced Feature Modules
import './js/prescription-generator.js';
import './js/payment-management.js';
import './js/pharmacy-commission.js';
import './js/laboratory-commission.js';
import './js/expense-management.js';

// Dashboard statistics
import './js/dashboard-stats.js';

// Legacy dashboard module (will be further broken down)
import './dashboard.js';

// Placeholder functions for any remaining functionality
import './placeholders.js';

// Initialize the application
console.log('Dashboard application loaded successfully');

// Make all functions globally available for onclick handlers
window.showInventory = window.showInventory || (() => import('./js/inventory.js').then(m => m.showInventory()));
window.showCategories = window.showCategories || (() => import('./js/categories.js').then(m => m.showCategories()));
window.showBranches = window.showBranches || (() => import('./js/branches.js').then(m => m.showBranches()));
window.showSuppliers = window.showSuppliers || (() => import('./js/suppliers.js').then(m => m.showSuppliers()));
window.showProducts = window.showProducts || (() => import('./js/products.js').then(m => m.showProducts()));
window.showPurchases = window.showPurchases || (() => import('./js/purchases.js').then(m => m.showPurchases()));
window.showSales = window.showSales || (() => import('./js/sales.js').then(m => m.showSales()));
window.showCustomers = window.showCustomers || (() => import('./js/customers.js').then(m => m.showCustomers()));

// Make utility functions globally available
window.hideAllSections = window.hideAllSections || (() => import('./js/utils.js').then(m => m.hideAllSections()));
window.showAlert = window.showAlert || (() => import('./js/utils.js').then(m => m.showAlert()));

// Make modal functions globally available
window.openModal = window.openModal || (() => import('./js/modal-manager.js').then(m => m.openModal()));
window.closeModal = window.closeModal || (() => import('./js/modal-manager.js').then(m => m.closeModal()));

// Make advanced feature functions globally available
window.addMedicineItem = window.addMedicineItem || (() => import('./js/prescription-generator.js').then(m => m.addMedicineItem()));
window.removeMedicineItem = window.removeMedicineItem || (() => import('./js/prescription-generator.js').then(m => m.removeMedicineItem()));
window.generatePrescriptionPDF = window.generatePrescriptionPDF || (() => import('./js/prescription-generator.js').then(m => m.generatePrescriptionPDF()));
window.printPrescription = window.printPrescription || (() => import('./js/prescription-generator.js').then(m => m.printPrescription()));

window.viewPayment = window.viewPayment || (() => import('./js/payment-management.js').then(m => m.viewPayment()));
window.recordPayment = window.recordPayment || (() => import('./js/payment-management.js').then(m => m.recordPayment()));
window.submitPaymentRecord = window.submitPaymentRecord || (() => import('./js/payment-management.js').then(m => m.submitPaymentRecord()));

window.viewPharmacyPartnership = window.viewPharmacyPartnership || (() => import('./js/pharmacy-commission.js').then(m => m.viewPharmacyPartnership()));
window.deletePharmacyPartnership = window.deletePharmacyPartnership || (() => import('./js/pharmacy-commission.js').then(m => m.deletePharmacyPartnership()));
window.markCommissionPaid = window.markCommissionPaid || (() => import('./js/pharmacy-commission.js').then(m => m.markCommissionPaid()));

window.viewLaboratoryPartnership = window.viewLaboratoryPartnership || (() => import('./js/laboratory-commission.js').then(m => m.viewLaboratoryPartnership()));
window.deleteLaboratoryPartnership = window.deleteLaboratoryPartnership || (() => import('./js/laboratory-commission.js').then(m => m.deleteLaboratoryPartnership()));
window.markLabCommissionPaid = window.markLabCommissionPaid || (() => import('./js/laboratory-commission.js').then(m => m.markLabCommissionPaid()));

window.viewExpense = window.viewExpense || (() => import('./js/expense-management.js').then(m => m.viewExpense()));
window.deleteExpense = window.deleteExpense || (() => import('./js/expense-management.js').then(m => m.deleteExpense()));
window.removeReceipt = window.removeReceipt || (() => import('./js/expense-management.js').then(m => m.removeReceipt()));

// Initialize dashboard stats on load
document.addEventListener('DOMContentLoaded', () => {
    // Load initial dashboard statistics
    import('./js/dashboard-stats.js').then(m => m.loadDashboardStats());
    
    // Initialize global utilities
    console.log('All modules loaded successfully');
    
    // Initialize advanced features
    console.log('Advanced features loaded: Prescription Generator, Payment Management, Pharmacy Commission, Laboratory Commission, Expense Management');
});