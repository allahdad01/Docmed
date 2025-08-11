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