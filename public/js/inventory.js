// Inventory Management Module
// Handles all inventory-related functionality

export function showInventory() {
    hideAllSections();
    document.getElementById('inventory-section').style.display = 'block';
    loadInventory();
}

export async function loadInventory() {
    try {
        const response = await apiCall('GET', 'api/inventory');
        if (response.success) {
            displayInventory(response.data);
        } else {
            showAlert('Error loading inventory: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading inventory:', error);
        showAlert('Error loading inventory', 'danger');
    }
}

export function displayInventory(inventory) {
    const tbody = document.querySelector('#inventory-table tbody');
    tbody.innerHTML = '';

    inventory.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${item.product_name}</td>
            <td>${item.branch_name}</td>
            <td>${item.quantity}</td>
            <td>${item.reorder_level}</td>
            <td>${item.last_updated}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editInventory(${item.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteInventory(${item.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

export function addInventory() {
    document.getElementById('inventoryModalLabel').textContent = 'Add Inventory Item';
    document.getElementById('inventoryForm').reset();
    document.getElementById('inventoryId').value = '';
    document.getElementById('inventoryModal').classList.add('show');
    document.getElementById('inventoryModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

export function editInventory(id) {
    loadInventoryForEdit(id);
}

export async function loadInventoryForEdit(id) {
    try {
        const response = await apiCall('GET', `api/inventory/${id}`);
        if (response.success) {
            const item = response.data;
            document.getElementById('inventoryModalLabel').textContent = 'Edit Inventory Item';
            document.getElementById('inventoryId').value = item.id;
            document.getElementById('inventoryProduct').value = item.product_id;
            document.getElementById('inventoryBranch').value = item.branch_id;
            document.getElementById('inventoryQuantity').value = item.quantity;
            document.getElementById('inventoryReorderLevel').value = item.reorder_level;
            
            document.getElementById('inventoryModal').classList.add('show');
            document.getElementById('inventoryModal').style.display = 'block';
            document.body.classList.add('modal-open');
        } else {
            showAlert('Error loading inventory item: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading inventory item:', error);
        showAlert('Error loading inventory item', 'danger');
    }
}

export async function saveInventory() {
    const id = document.getElementById('inventoryId').value;
    const productId = document.getElementById('inventoryProduct').value;
    const branchId = document.getElementById('inventoryBranch').value;
    const quantity = document.getElementById('inventoryQuantity').value;
    const reorderLevel = document.getElementById('inventoryReorderLevel').value;

    if (!productId || !branchId || !quantity || !reorderLevel) {
        showAlert('Please fill in all fields', 'warning');
        return;
    }

    try {
        const data = {
            product_id: productId,
            branch_id: branchId,
            quantity: quantity,
            reorder_level: reorderLevel
        };

        let response;
        if (id) {
            response = await apiCall('PUT', `api/inventory/${id}`, data);
        } else {
            response = await apiCall('POST', 'api/inventory', data);
        }

        if (response.success) {
            showAlert(id ? 'Inventory updated successfully' : 'Inventory added successfully', 'success');
            document.getElementById('inventoryModal').classList.remove('show');
            document.getElementById('inventoryModal').style.display = 'none';
            document.body.classList.remove('modal-open');
            loadInventory();
        } else {
            showAlert('Error saving inventory: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error saving inventory:', error);
        showAlert('Error saving inventory', 'danger');
    }
}

export function deleteInventory(id) {
    if (confirm('Are you sure you want to delete this inventory item?')) {
        confirmDeleteInventory(id);
    }
}

export async function confirmDeleteInventory(id) {
    try {
        const response = await apiCall('DELETE', `api/inventory/${id}`);
        if (response.success) {
            showAlert('Inventory deleted successfully', 'success');
            loadInventory();
        } else {
            showAlert('Error deleting inventory: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting inventory:', error);
        showAlert('Error deleting inventory', 'danger');
    }
}

export async function loadInventorySummary() {
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

export function closeInventoryModal() {
    document.getElementById('inventoryModal').classList.remove('show');
    document.getElementById('inventoryModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}