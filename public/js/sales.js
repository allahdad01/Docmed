// Sales Management Module
// Handles all sales-related functionality

export function showSales() {
    hideAllSections();
    document.getElementById('sales-section').style.display = 'block';
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    event.target.classList.add('active');
    loadSales();
}

export async function loadSales() {
    try {
        const sales = await apiCall('api/sales');
        if (sales && sales.data) {
            displaySales(sales.data);
            if (sales.pagination) {
                displayPagination(sales.pagination, 'salesPagination', loadSales);
            }
        }
    } catch (error) {
        console.error('Error loading sales:', error);
        showAlert('Failed to load sales', 'danger');
    }
}

export function displaySales(sales) {
    const container = document.getElementById('salesTableBody');
    if (!container) return;
    
    container.innerHTML = '';
    
    sales.forEach(sale => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${sale.id}</td>
            <td>${sale.customer_name || 'N/A'}</td>
            <td>${sale.product_name || 'N/A'}</td>
            <td>${sale.quantity || '0'}</td>
            <td>$${sale.total_amount || '0.00'}</td>
            <td>${sale.sale_date || 'N/A'}</td>
            <td>
                <span class="badge ${sale.status === 'completed' ? 'bg-success' : 'bg-warning'}">
                    ${sale.status || 'pending'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editSale(${sale.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteSale(${sale.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(row);
    });
}

export function addSale() {
    // Reset form
    document.getElementById('saleForm').reset();
    document.getElementById('saleId').value = '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('saleModal'));
    modal.show();
}

export function editSale(id) {
    // Load sale data and populate form
    loadSaleForEdit(id);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('saleModal'));
    modal.show();
}

export async function loadSaleForEdit(id) {
    try {
        const sale = await apiCall(`api/sales/${id}`);
        if (sale) {
            document.getElementById('saleId').value = sale.id;
            document.getElementById('saleCustomer').value = sale.customer_id || '';
            document.getElementById('saleProduct').value = sale.product_id || '';
            document.getElementById('saleQuantity').value = sale.quantity || '';
            document.getElementById('saleAmount').value = sale.total_amount || '';
            document.getElementById('saleDate').value = sale.sale_date || '';
            document.getElementById('saleStatus').value = sale.status || 'pending';
        }
    } catch (error) {
        console.error('Error loading sale for edit:', error);
        showAlert('Failed to load sale details', 'danger');
    }
}

export async function saveSale() {
    const formData = new FormData(document.getElementById('saleForm'));
    const saleId = document.getElementById('saleId').value;
    
    const saleData = {
        customer_id: formData.get('customer_id'),
        product_id: formData.get('product_id'),
        quantity: parseInt(formData.get('quantity')),
        total_amount: parseFloat(formData.get('total_amount')),
        sale_date: formData.get('sale_date'),
        status: formData.get('status')
    };
    
    try {
        let response;
        if (saleId) {
            // Update existing sale
            response = await apiCall(`api/sales/${saleId}`, {
                method: 'PUT',
                body: JSON.stringify(saleData)
            });
        } else {
            // Create new sale
            response = await apiCall('api/sales', {
                method: 'POST',
                body: JSON.stringify(saleData)
            });
        }
        
        if (response) {
            showAlert(saleId ? 'Sale updated successfully' : 'Sale created successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('saleModal'));
            modal.hide();
            loadSales();
        }
    } catch (error) {
        console.error('Error saving sale:', error);
        showAlert('Failed to save sale', 'danger');
    }
}

export function deleteSale(id) {
    if (confirm('Are you sure you want to delete this sale?')) {
        confirmDeleteSale(id);
    }
}

export async function confirmDeleteSale(id) {
    try {
        const response = await apiCall(`api/sales/${id}`, {
            method: 'DELETE'
        });
        
        if (response) {
            showAlert('Sale deleted successfully', 'success');
            loadSales();
        }
    } catch (error) {
        console.error('Error deleting sale:', error);
        showAlert('Failed to delete sale', 'danger');
    }
}

export function displayRecentSales(sales) {
    const container = document.getElementById('recent-sales');
    if (!container) return;
    
    container.innerHTML = '';
    
    sales.forEach(sale => {
        const item = document.createElement('div');
        item.className = 'recent-sale-item';
        item.innerHTML = `
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <strong>${sale.customer_name || 'N/A'}</strong>
                    <br>
                    <small class="text-muted">${sale.product_name || 'N/A'}</small>
                </div>
                <div class="text-end">
                    <strong>$${sale.total_amount || '0.00'}</strong>
                    <br>
                    <small class="text-muted">${sale.sale_date || 'N/A'}</small>
                </div>
            </div>
        `;
        container.appendChild(item);
    });
}