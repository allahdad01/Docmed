// Main entry point for the dashboard application
// This file imports all the necessary JavaScript modules

// Import order is important due to dependencies
// Core functions must be loaded first
import './core.js';
import './api-helpers.js';
import './navigation.js';

// Utility functions
import './js/utils.js';

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

// Initialize dashboard stats on load
document.addEventListener('DOMContentLoaded', () => {
    // Load initial dashboard statistics
    import('./js/dashboard-stats.js').then(m => m.loadDashboardStats());
});