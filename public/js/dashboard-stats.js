// Dashboard Statistics Module
// Handles all dashboard statistics and summary functionality

export async function loadDashboardStats() {
    try {
        const response = await apiCall('GET', 'api/dashboard/stats');
        if (response.success) {
            displayDashboardStats(response.data);
        } else {
            showAlert('Error loading dashboard stats: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading dashboard stats:', error);
        showAlert('Error loading dashboard stats', 'danger');
    }
}

export function displayDashboardStats(stats) {
    // Update summary cards
    document.getElementById('totalSales').textContent = formatCurrency(stats.total_sales || 0);
    document.getElementById('totalProducts').textContent = stats.total_products || 0;
    document.getElementById('totalCustomers').textContent = stats.total_customers || 0;
    document.getElementById('totalOrders').textContent = stats.total_orders || 0;
    
    // Update recent sales
    if (stats.recent_sales) {
        displayRecentSales(stats.recent_sales);
    }
    
    // Update low stock alerts
    if (stats.low_stock_items) {
        displayLowStockAlerts(stats.low_stock_items);
    }
    
    // Update top selling products
    if (stats.top_selling_products) {
        displayTopSellingProducts(stats.top_selling_products);
    }
    
    // Update monthly sales chart
    if (stats.monthly_sales) {
        updateMonthlySalesChart(stats.monthly_sales);
    }
}

export function displayRecentSales(sales) {
    const container = document.getElementById('recentSales');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (sales.length === 0) {
        container.innerHTML = '<p class="text-muted">No recent sales</p>';
        return;
    }
    
    sales.forEach(sale => {
        const saleDiv = document.createElement('div');
        saleDiv.className = 'recent-sale-item border-bottom pb-2 mb-2';
        saleDiv.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${sale.customer_name || 'Walk-in Customer'}</strong>
                    <br>
                    <small class="text-muted">${sale.sale_date}</small>
                </div>
                <div class="text-end">
                    <strong class="text-success">${formatCurrency(sale.total_amount)}</strong>
                    <br>
                    <small class="text-muted">${sale.items_count} items</small>
                </div>
            </div>
        `;
        container.appendChild(saleDiv);
    });
}

export function displayLowStockAlerts(items) {
    const container = document.getElementById('lowStockAlerts');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (items.length === 0) {
        container.innerHTML = '<p class="text-muted">All items are well stocked</p>';
        return;
    }
    
    items.forEach(item => {
        const alertDiv = document.createElement('div');
        alertDiv.className = 'alert alert-warning alert-sm mb-2';
        alertDiv.innerHTML = `
            <strong>${item.product_name}</strong> - 
            ${item.quantity} remaining (Reorder: ${item.reorder_level})
        `;
        container.appendChild(alertDiv);
    });
}

export function displayTopSellingProducts(products) {
    const container = document.getElementById('topSellingProducts');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (products.length === 0) {
        container.innerHTML = '<p class="text-muted">No sales data available</p>';
        return;
    }
    
    products.forEach((product, index) => {
        const productDiv = document.createElement('div');
        productDiv.className = 'top-product-item d-flex justify-content-between align-items-center border-bottom pb-2 mb-2';
        productDiv.innerHTML = `
            <div>
                <span class="badge bg-primary me-2">#${index + 1}</span>
                <strong>${product.product_name}</strong>
            </div>
            <div class="text-end">
                <strong>${product.total_sold}</strong> sold
                <br>
                <small class="text-muted">${formatCurrency(product.total_revenue)}</small>
            </div>
        `;
        container.appendChild(productDiv);
    });
}

export function updateMonthlySalesChart(monthlyData) {
    const ctx = document.getElementById('monthlySalesChart');
    if (!ctx) return;
    
    // Destroy existing chart if it exists
    if (window.monthlySalesChart) {
        window.monthlySalesChart.destroy();
    }
    
    const months = monthlyData.map(item => item.month);
    const sales = monthlyData.map(item => item.total_sales);
    
    window.monthlySalesChart = new Chart(ctx, {
        type: 'line',
        data: {
            labels: months,
            datasets: [{
                label: 'Monthly Sales',
                data: sales,
                borderColor: 'rgb(75, 192, 192)',
                backgroundColor: 'rgba(75, 192, 192, 0.2)',
                tension: 0.1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return formatCurrency(value);
                        }
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            return formatCurrency(context.parsed.y);
                        }
                    }
                }
            }
        }
    });
}

export function formatCurrency(amount) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: 'USD'
    }).format(amount);
}

export function formatNumber(number) {
    return new Intl.NumberFormat('en-US').format(number);
}

export function formatDate(dateString) {
    const date = new Date(dateString);
    return date.toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric'
    });
}

export function formatDateTime(dateString) {
    const date = new Date(dateString);
    return date.toLocaleString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    });
}

export function getStatusBadge(status) {
    const statusMap = {
        'active': 'success',
        'inactive': 'secondary',
        'pending': 'warning',
        'completed': 'success',
        'cancelled': 'danger',
        'processing': 'info'
    };
    
    const color = statusMap[status.toLowerCase()] || 'secondary';
    return `<span class="badge bg-${color}">${status}</span>`;
}

export function calculatePercentage(value, total) {
    if (total === 0) return 0;
    return ((value / total) * 100).toFixed(1);
}

export function updateProgressBar(elementId, percentage) {
    const element = document.getElementById(elementId);
    if (element) {
        element.style.width = percentage + '%';
        element.setAttribute('aria-valuenow', percentage);
    }
}

export function refreshDashboard() {
    loadDashboardStats();
    loadInventorySummary();
    loadRecentSales();
}

export function loadInventorySummary() {
    try {
        const response = await apiCall('GET', 'api/inventory/summary');
        if (response.success) {
            const summary = response.data;
            document.getElementById('totalProducts').textContent = summary.total_products || 0;
            document.getElementById('lowStockItems').textContent = summary.low_stock_items || 0;
            document.getElementById('outOfStockItems').textContent = summary.out_of_stock_items || 0;
        }
    } catch (error) {
        console.error('Error loading inventory summary:', error);
    }
}

export function loadRecentSales() {
    try {
        const response = await apiCall('GET', 'api/sales/recent');
        if (response.success) {
            displayRecentSales(response.data);
        }
    } catch (error) {
        console.error('Error loading recent sales:', error);
    }
}