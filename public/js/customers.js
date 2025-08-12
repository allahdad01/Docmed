// Customers Management Module
// Handles all customer-related functionality

export function showCustomers() {
    hideAllSections();
    document.getElementById('customers-section').style.display = 'block';
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    event.target.classList.add('active');
    loadCustomers();
}

export async function loadCustomers() {
    try {
        const customers = await apiCall('api/customers');
        if (customers && customers.data) {
            displayCustomers(customers.data);
            if (customers.pagination) {
                displayPagination(customers.pagination, 'customersPagination', loadCustomers);
            }
        }
    } catch (error) {
        console.error('Error loading customers:', error);
        showAlert('Failed to load customers', 'danger');
    }
}

export function displayCustomers(customers) {
    const container = document.getElementById('customersTableBody');
    if (!container) return;
    
    container.innerHTML = '';
    
    customers.forEach(customer => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${customer.name || 'N/A'}</td>
            <td>${customer.email || 'N/A'}</td>
            <td>${customer.phone || 'N/A'}</td>
            <td>${customer.address || 'N/A'}</td>
            <td>${customer.created_at || 'N/A'}</td>
            <td>
                <span class="badge ${customer.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                    ${customer.status || 'inactive'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editCustomer(${customer.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-info" onclick="viewCustomerHistory(${customer.id})">
                    <i class="bi bi-clock-history"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteCustomer(${customer.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(row);
    });
}

export function addCustomer() {
    // Reset form
    document.getElementById('customerForm').reset();
    document.getElementById('customerId').value = '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('customerModal'));
    modal.show();
}

export function editCustomer(id) {
    // Load customer data and populate form
    loadCustomerForEdit(id);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('customerModal'));
    modal.show();
}

export async function loadCustomerForEdit(id) {
    try {
        const customer = await apiCall(`api/customers/${id}`);
        if (customer) {
            document.getElementById('customerId').value = customer.id;
            document.getElementById('customerName').value = customer.name || '';
            document.getElementById('customerEmail').value = customer.email || '';
            document.getElementById('customerPhone').value = customer.phone || '';
            document.getElementById('customerAddress').value = customer.address || '';
            document.getElementById('customerStatus').value = customer.status || 'active';
        }
    } catch (error) {
        console.error('Error loading customer for edit:', error);
        showAlert('Failed to load customer details', 'danger');
    }
}

export async function saveCustomer() {
    const formData = new FormData(document.getElementById('customerForm'));
    const customerId = document.getElementById('customerId').value;
    
    const customerData = {
        name: formData.get('name'),
        email: formData.get('email'),
        phone: formData.get('phone'),
        address: formData.get('address'),
        status: formData.get('status')
    };
    
    try {
        let response;
        if (customerId) {
            // Update existing customer
            response = await apiCall(`api/customers/${customerId}`, {
                method: 'PUT',
                body: JSON.stringify(customerData)
            });
        } else {
            // Create new customer
            response = await apiCall('api/customers', {
                method: 'POST',
                body: JSON.stringify(customerData)
            });
        }
        
        if (response) {
            showAlert(customerId ? 'Customer updated successfully' : 'Customer created successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('customerModal'));
            modal.hide();
            loadCustomers();
        }
    } catch (error) {
        console.error('Error saving customer:', error);
        showAlert('Failed to save customer', 'danger');
    }
}

export function deleteCustomer(id) {
    if (confirm('Are you sure you want to delete this customer?')) {
        confirmDeleteCustomer(id);
    }
}

export async function confirmDeleteCustomer(id) {
    try {
        const response = await apiCall(`api/customers/${id}`, {
            method: 'DELETE'
        });
        
        if (response) {
            showAlert('Customer deleted successfully', 'success');
            loadCustomers();
        }
    } catch (error) {
        console.error('Error deleting customer:', error);
        showAlert('Failed to delete customer', 'danger');
    }
}

export async function viewCustomerHistory(id) {
    try {
        const history = await apiCall(`api/customers/${id}/history`);
        if (history) {
            displayCustomerHistory(history);
            
            // Show modal
            const modal = new bootstrap.Modal(document.getElementById('customerHistoryModal'));
            modal.show();
        }
    } catch (error) {
        console.error('Error loading customer history:', error);
        showAlert('Failed to load customer history', 'danger');
    }
}

export function displayCustomerHistory(history) {
    const container = document.getElementById('customerHistoryContent');
    if (!container) return;
    
    container.innerHTML = '';
    
    if (history.purchases && history.purchases.length > 0) {
        const purchasesSection = document.createElement('div');
        purchasesSection.innerHTML = '<h6>Purchase History</h6>';
        
        history.purchases.forEach(purchase => {
            const item = document.createElement('div');
            item.className = 'history-item';
            item.innerHTML = `
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <strong>${purchase.product_name || 'N/A'}</strong>
                        <br>
                        <small class="text-muted">Quantity: ${purchase.quantity || '0'}</small>
                    </div>
                    <div class="text-end">
                        <strong>$${purchase.total_amount || '0.00'}</strong>
                        <br>
                        <small class="text-muted">${purchase.purchase_date || 'N/A'}</small>
                    </div>
                </div>
            `;
            purchasesSection.appendChild(item);
        });
        
        container.appendChild(purchasesSection);
    } else {
        container.innerHTML = '<p class="text-muted">No purchase history available.</p>';
    }
}