// Purchases Management Module
// Handles all purchase-related functionality

export function showPurchases() {
    hideAllSections();
    document.getElementById('purchases-section').style.display = 'block';
    loadPurchases();
}

export async function loadPurchases() {
    try {
        const response = await apiCall('GET', 'api/purchases');
        if (response.success) {
            displayPurchases(response.data);
        } else {
            showAlert('Error loading purchases: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading purchases:', error);
        showAlert('Error loading purchases', 'danger');
    }
}

export function displayPurchases(purchases) {
    const tbody = document.querySelector('#purchases-table tbody');
    tbody.innerHTML = '';

    purchases.forEach(purchase => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${purchase.reference_number}</td>
            <td>${purchase.supplier_name}</td>
            <td>${purchase.branch_name}</td>
            <td>${purchase.total_amount}</td>
            <td>${purchase.status}</td>
            <td>${purchase.purchase_date}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editPurchase(${purchase.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deletePurchase(${purchase.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

export function addPurchase() {
    document.getElementById('purchaseModalLabel').textContent = 'Add Purchase';
    document.getElementById('purchaseForm').reset();
    document.getElementById('purchaseId').value = '';
    document.getElementById('purchaseItems').innerHTML = '';
    addPurchaseItem();
    document.getElementById('purchaseModal').classList.add('show');
    document.getElementById('purchaseModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

export function editPurchase(id) {
    loadPurchaseForEdit(id);
}

export async function loadPurchaseForEdit(id) {
    try {
        const response = await apiCall('GET', `api/purchases/${id}`);
        if (response.success) {
            const purchase = response.data;
            document.getElementById('purchaseModalLabel').textContent = 'Edit Purchase';
            document.getElementById('purchaseId').value = purchase.id;
            document.getElementById('purchaseReference').value = purchase.reference_number;
            document.getElementById('purchaseSupplier').value = purchase.supplier_id;
            document.getElementById('purchaseBranch').value = purchase.branch_id;
            document.getElementById('purchaseDate').value = purchase.purchase_date;
            document.getElementById('purchaseStatus').value = purchase.status;
            document.getElementById('purchaseNotes').value = purchase.notes || '';
            
            // Load purchase items
            loadPurchaseItems(purchase.items || []);
            
            document.getElementById('purchaseModal').classList.add('show');
            document.getElementById('purchaseModal').style.display = 'block';
            document.body.classList.add('modal-open');
        } else {
            showAlert('Error loading purchase: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading purchase:', error);
        showAlert('Error loading purchase', 'danger');
    }
}

export function addPurchaseItem() {
    const container = document.getElementById('purchaseItems');
    const itemDiv = document.createElement('div');
    itemDiv.className = 'purchase-item border p-3 mb-3';
    itemDiv.innerHTML = `
        <div class="row">
            <div class="col-md-3">
                <label class="form-label">Product</label>
                <select class="form-select purchase-product" required>
                    <option value="">Select Product</option>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label">Quantity</label>
                <input type="number" class="form-control purchase-quantity" min="1" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Unit Cost</label>
                <input type="number" class="form-control purchase-unit-cost" step="0.01" min="0" required>
            </div>
            <div class="col-md-2">
                <label class="form-label">Total</label>
                <input type="text" class="form-control purchase-total" readonly>
            </div>
            <div class="col-md-2">
                <label class="form-label">&nbsp;</label>
                <button type="button" class="btn btn-danger btn-sm d-block" onclick="removePurchaseItem(this)">
                    <i class="bi bi-trash"></i>
                </button>
            </div>
        </div>
    `;
    container.appendChild(itemDiv);
    
    // Load products for this dropdown
    loadProductsForPurchaseDropdown(itemDiv.querySelector('.purchase-product'));
    
    // Add event listeners
    const quantityInput = itemDiv.querySelector('.purchase-quantity');
    const unitCostInput = itemDiv.querySelector('.purchase-unit-cost');
    const totalInput = itemDiv.querySelector('.purchase-total');
    
    quantityInput.addEventListener('input', () => updatePurchaseItemTotal(quantityInput, unitCostInput, totalInput));
    unitCostInput.addEventListener('input', () => updatePurchaseItemTotal(quantityInput, unitCostInput, totalInput));
}

export function removePurchaseItem(button) {
    button.closest('.purchase-item').remove();
    updatePurchaseTotals();
}

export function updatePurchaseItemTotal(quantityInput, unitCostInput, totalInput) {
    const quantity = parseFloat(quantityInput.value) || 0;
    const unitCost = parseFloat(unitCostInput.value) || 0;
    const total = quantity * unitCost;
    totalInput.value = total.toFixed(2);
    updatePurchaseTotals();
}

export function updatePurchaseTotals() {
    const items = document.querySelectorAll('.purchase-item');
    let total = 0;
    
    items.forEach(item => {
        const totalInput = item.querySelector('.purchase-total');
        total += parseFloat(totalInput.value) || 0;
    });
    
    document.getElementById('purchaseTotal').value = total.toFixed(2);
}

export async function savePurchase() {
    const id = document.getElementById('purchaseId').value;
    const reference = document.getElementById('purchaseReference').value.trim();
    const supplierId = document.getElementById('purchaseSupplier').value;
    const branchId = document.getElementById('purchaseBranch').value;
    const purchaseDate = document.getElementById('purchaseDate').value;
    const status = document.getElementById('purchaseStatus').value;
    const notes = document.getElementById('purchaseNotes').value.trim();

    if (!reference || !supplierId || !branchId || !purchaseDate) {
        showAlert('Please fill in all required fields', 'warning');
        return;
    }

    const items = [];
    const itemElements = document.querySelectorAll('.purchase-item');
    
    if (itemElements.length === 0) {
        showAlert('Please add at least one purchase item', 'warning');
        return;
    }

    itemElements.forEach(item => {
        const productId = item.querySelector('.purchase-product').value;
        const quantity = item.querySelector('.purchase-quantity').value;
        const unitCost = item.querySelector('.purchase-unit-cost').value;
        
        if (productId && quantity && unitCost) {
            items.push({
                product_id: productId,
                quantity: quantity,
                unit_cost: unitCost
            });
        }
    });

    if (items.length === 0) {
        showAlert('Please fill in all item details', 'warning');
        return;
    }

    try {
        const data = {
            reference_number: reference,
            supplier_id: supplierId,
            branch_id: branchId,
            purchase_date: purchaseDate,
            status: status,
            notes: notes,
            items: items
        };

        let response;
        if (id) {
            response = await apiCall('PUT', `api/purchases/${id}`, data);
        } else {
            response = await apiCall('POST', 'api/purchases', data);
        }

        if (response.success) {
            showAlert(id ? 'Purchase updated successfully' : 'Purchase added successfully', 'success');
            document.getElementById('purchaseModal').classList.remove('show');
            document.getElementById('purchaseModal').style.display = 'none';
            document.body.classList.remove('modal-open');
            loadPurchases();
        } else {
            showAlert('Error saving purchase: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error saving purchase:', error);
        showAlert('Error saving purchase', 'danger');
    }
}

export function deletePurchase(id) {
    if (confirm('Are you sure you want to delete this purchase?')) {
        confirmDeletePurchase(id);
    }
}

export async function confirmDeletePurchase(id) {
    try {
        const response = await apiCall('DELETE', `api/purchases/${id}`);
        if (response.success) {
            showAlert('Purchase deleted successfully', 'success');
            loadPurchases();
        } else {
            showAlert('Error deleting purchase: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting purchase:', error);
        showAlert('Error deleting purchase', 'danger');
    }
}

export function closePurchaseModal() {
    document.getElementById('purchaseModal').classList.remove('show');
    document.getElementById('purchaseModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}

export async function loadProductsForPurchaseDropdown(selectElement) {
    try {
        const response = await apiCall('GET', 'api/products');
        if (response.success) {
            selectElement.innerHTML = '<option value="">Select Product</option>';
            response.data.forEach(product => {
                const option = document.createElement('option');
                option.value = product.id;
                option.textContent = product.name;
                selectElement.appendChild(option);
            });
        }
    } catch (error) {
        console.error('Error loading products for purchase dropdown:', error);
    }
}

export function loadPurchaseItems(items) {
    const container = document.getElementById('purchaseItems');
    container.innerHTML = '';
    
    if (items.length === 0) {
        addPurchaseItem();
        return;
    }
    
    items.forEach(item => {
        addPurchaseItem();
        const lastItem = container.lastElementChild;
        lastItem.querySelector('.purchase-product').value = item.product_id;
        lastItem.querySelector('.purchase-quantity').value = item.quantity;
        lastItem.querySelector('.purchase-unit-cost').value = item.unit_cost;
        lastItem.querySelector('.purchase-total').value = (item.quantity * item.unit_cost).toFixed(2);
    });
    
    updatePurchaseTotals();
}