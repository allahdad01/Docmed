// Suppliers Management Module
// Handles all supplier-related functionality

export function showSuppliers() {
    hideAllSections();
    document.getElementById('suppliers-section').style.display = 'block';
    loadSuppliers();
}

export async function loadSuppliers() {
    try {
        const response = await apiCall('GET', 'api/suppliers');
        if (response.success) {
            displaySuppliers(response.data);
        } else {
            showAlert('Error loading suppliers: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading suppliers:', error);
        showAlert('Error loading suppliers', 'danger');
    }
}

export function displaySuppliers(suppliers) {
    const tbody = document.querySelector('#suppliers-table tbody');
    tbody.innerHTML = '';

    suppliers.forEach(supplier => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${supplier.name}</td>
            <td>${supplier.contact_person || '-'}</td>
            <td>${supplier.phone || '-'}</td>
            <td>${supplier.email || '-'}</td>
            <td>${supplier.address || '-'}</td>
            <td>${supplier.status}</td>
            <td>${supplier.created_at}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editSupplier(${supplier.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteSupplier(${supplier.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

export function addSupplier() {
    document.getElementById('supplierModalLabel').textContent = 'Add Supplier';
    document.getElementById('supplierForm').reset();
    document.getElementById('supplierId').value = '';
    document.getElementById('supplierModal').classList.add('show');
    document.getElementById('supplierModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

export function editSupplier(id) {
    loadSupplierForEdit(id);
}

export async function loadSupplierForEdit(id) {
    try {
        const response = await apiCall('GET', `api/suppliers/${id}`);
        if (response.success) {
            const supplier = response.data;
            document.getElementById('supplierModalLabel').textContent = 'Edit Supplier';
            document.getElementById('supplierId').value = supplier.id;
            document.getElementById('supplierName').value = supplier.name;
            document.getElementById('supplierContactPerson').value = supplier.contact_person || '';
            document.getElementById('supplierPhone').value = supplier.phone || '';
            document.getElementById('supplierEmail').value = supplier.email || '';
            document.getElementById('supplierAddress').value = supplier.address || '';
            document.getElementById('supplierStatus').value = supplier.status;
            
            document.getElementById('supplierModal').classList.add('show');
            document.getElementById('supplierModal').style.display = 'block';
            document.body.classList.add('modal-open');
        } else {
            showAlert('Error loading supplier: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading supplier:', error);
        showAlert('Error loading supplier', 'danger');
    }
}

export async function saveSupplier() {
    const id = document.getElementById('supplierId').value;
    const name = document.getElementById('supplierName').value.trim();
    const contactPerson = document.getElementById('supplierContactPerson').value.trim();
    const phone = document.getElementById('supplierPhone').value.trim();
    const email = document.getElementById('supplierEmail').value.trim();
    const address = document.getElementById('supplierAddress').value.trim();
    const status = document.getElementById('supplierStatus').value;

    if (!name) {
        showAlert('Please enter a supplier name', 'warning');
        return;
    }

    try {
        const data = {
            name: name,
            contact_person: contactPerson,
            phone: phone,
            email: email,
            address: address,
            status: status
        };

        let response;
        if (id) {
            response = await apiCall('PUT', `api/suppliers/${id}`, data);
        } else {
            response = await apiCall('POST', 'api/suppliers', data);
        }

        if (response.success) {
            showAlert(id ? 'Supplier updated successfully' : 'Supplier added successfully', 'success');
            document.getElementById('supplierModal').classList.remove('show');
            document.getElementById('supplierModal').style.display = 'none';
            document.body.classList.remove('modal-open');
            loadSuppliers();
        } else {
            showAlert('Error saving supplier: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error saving supplier:', error);
        showAlert('Error saving supplier', 'danger');
    }
}

export function deleteSupplier(id) {
    if (confirm('Are you sure you want to delete this supplier? This will also remove all purchases associated with this supplier.')) {
        confirmDeleteSupplier(id);
    }
}

export async function confirmDeleteSupplier(id) {
    try {
        const response = await apiCall('DELETE', `api/suppliers/${id}`);
        if (response.success) {
            showAlert('Supplier deleted successfully', 'success');
            loadSuppliers();
        } else {
            showAlert('Error deleting supplier: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting supplier:', error);
        showAlert('Error deleting supplier', 'danger');
    }
}

export function closeSupplierModal() {
    document.getElementById('supplierModal').classList.remove('show');
    document.getElementById('supplierModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}

export async function loadSuppliersForDropdown() {
    try {
        const response = await apiCall('GET', 'api/suppliers');
        if (response.success) {
            const select = document.getElementById('purchaseSupplier');
            if (select) {
                select.innerHTML = '<option value="">Select Supplier</option>';
                response.data.forEach(supplier => {
                    const option = document.createElement('option');
                    option.value = supplier.id;
                    option.textContent = supplier.name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading suppliers for dropdown:', error);
    }
}