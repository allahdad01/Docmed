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