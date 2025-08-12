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