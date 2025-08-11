// Utility Functions Module
// Contains common utility functions used across the application

export function hideAllSections() {
    const sections = [
        'dashboard-section',
        'inventory-section',
        'categories-section',
        'branches-section',
        'suppliers-section',
        'products-section',
        'purchases-section',
        'sales-section',
        'customers-section',
        'doctors-section',
        'patients-section',
        'prescriptions-section',
        'commissions-section',
        'medicines',
        'laboratory-tests',
        'expenses',
        'payments',
        'reports'
    ];
    
    sections.forEach(sectionId => {
        const section = document.getElementById(sectionId);
        if (section) {
            section.style.display = 'none';
        }
    });
}

export function showAlert(message, type = 'info') {
    const alertContainer = document.getElementById('alert-container');
    if (!alertContainer) return;
    
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    alertContainer.appendChild(alertDiv);
    
    // Auto-remove after 5 seconds
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

export function showConfirmDialog(message, onConfirm, onCancel) {
    if (confirm(message)) {
        if (typeof onConfirm === 'function') {
            onConfirm();
        }
    } else {
        if (typeof onCancel === 'function') {
            onCancel();
        }
    }
}

export function formatCurrency(amount) {
    if (amount === null || amount === undefined) return '$0.00';
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(parseFloat(amount));
}

export function formatNumber(number) {
    if (number === null || number === undefined) return '0';
    return new Intl.NumberFormat('en-US').format(parseFloat(number));
}

export function formatDate(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

export function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    if (isNaN(date.getTime())) return '-';
    
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

export function getStatusBadge(status, customMap = null) {
    const defaultStatusMap = {
        'active': 'success',
        'inactive': 'secondary',
        'pending': 'warning',
        'completed': 'success',
        'cancelled': 'danger',
        'processing': 'info',
        'approved': 'success',
        'rejected': 'danger',
        'draft': 'secondary',
        'published': 'success'
    };
    
    const statusMap = customMap || defaultStatusMap;
    const color = statusMap[status.toLowerCase()] || 'secondary';
    return `<span class="badge bg-${color}">${status}</span>`;
}

export function calculatePercentage(value, total) {
    if (total === 0) return 0;
    return parseFloat(((value / total) * 100).toFixed(1));
}

export function updateProgressBar(elementId, percentage) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.width = percentage + '%';
        element.setAttribute('aria-valuenow', percentage);
    }
}

export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

export function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

export function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

export function validatePhone(phone) {
    const re = /^[\+]?[1-9][\d]{0,15}$/;
    return re.test(phone.replace(/[\s\-\(\)]/g, ''));
}

export function validateRequired(value) {
    return value !== null && value !== undefined && value.toString().trim() !== '';
}

export function sanitizeInput(input) {
    if (typeof input !== 'string') return input;
    return input
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#x27;');
}

export function generateRandomId(length = 8) {
    const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    let result = '';
    for (let i = 0; i < length; i++) {
        result += chars.charAt(Math.floor(Math.random() * chars.length));
    }
    return result;
}

export function copyToClipboard(text) {
    if (navigator.clipboard && window.isSecureContext) {
        return navigator.clipboard.writeText(text);
    } else {
        // Fallback for older browsers
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        
        try {
            document.execCommand('copy');
            textArea.remove();
            return Promise.resolve();
        } catch (err) {
            textArea.remove();
            return Promise.reject(err);
        }
    }
}

export function downloadFile(data, filename, mimeType = 'text/plain') {
    const blob = new Blob([data], { type: mimeType });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = filename;
    document.body.appendChild(a);
    a.click();
    window.URL.revokeObjectURL(url);
    document.body.removeChild(a);
}

export function exportToCSV(data, filename) {
    if (!Array.isArray(data) || data.length === 0) {
        showAlert('No data to export', 'warning');
        return;
    }
    
    const headers = Object.keys(data[0]);
    const csvContent = [
        headers.join(','),
        ...data.map(row => headers.map(header => {
            const value = row[header];
            return typeof value === 'string' && value.includes(',') ? `"${value}"` : value;
        }).join(','))
    ].join('\n');
    
    downloadFile(csvContent, filename, 'text/csv');
}

export function exportToJSON(data, filename) {
    const jsonContent = JSON.stringify(data, null, 2);
    downloadFile(jsonContent, filename, 'application/json');
}

export function getQueryParam(name) {
    const urlParams = new URLSearchParams(window.location.search);
    return urlParams.get(name);
}

export function setQueryParam(name, value) {
    const url = new URL(window.location);
    url.searchParams.set(name, value);
    window.history.pushState({}, '', url);
}

export function removeQueryParam(name) {
    const url = new URL(window.location);
    url.searchParams.delete(name);
    window.history.pushState({}, '', url);
}

export function isMobile() {
    return window.innerWidth <= 768;
}

export function isTablet() {
    return window.innerWidth > 768 && window.innerWidth <= 1024;
}

export function isDesktop() {
    return window.innerWidth > 1024;
}

export function addResizeListener(callback) {
    let resizeTimeout;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(callback, 250);
    });
}

export function scrollToElement(elementId, offset = 0) {
    const element = document.getElementById(elementId);
    if (element) {
        const elementPosition = element.offsetTop - offset;
        window.scrollTo({
            top: elementPosition,
            behavior: 'smooth'
        });
    }
}

export function scrollToTop() {
    window.scrollTo({
        top: 0,
        behavior: 'smooth'
    });
}