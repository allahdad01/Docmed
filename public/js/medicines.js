// ===== MEDICINES FUNCTIONS =====

async function loadMedicines(page = 1) {
    try {
        const searchTerm = document.getElementById('medicineSearch').value;
        const typeFilter = document.getElementById('medicineTypeFilter').value;
        
        const params = new URLSearchParams({
            page: page,
            search: searchTerm,
            type: typeFilter
        });
        
        const data = await apiCall(`api/medicines?${params}`);
        if (data.success) {
            displayMedicines(data.data);
            displayPagination(data.pagination, 'medicinesPagination', loadMedicines);
        } else {
            showAlert('Error loading medicines', 'danger');
        }
    } catch (error) {
        console.error('Error loading medicines:', error);
    }
}

function displayMedicines(medicines) {
    const tbody = document.getElementById('medicinesTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = medicines.map(medicine => `
        <tr>
            <td>${medicine.name || 'N/A'}</td>
            <td>${medicine.generic_name || 'N/A'}</td>
            <td>${medicine.type || 'N/A'}</td>
            <td>${medicine.strength || 'N/A'}</td>
            <td>${medicine.manufacturer || 'N/A'}</td>
            <td>${medicine.expiry_date || 'N/A'}</td>
            <td>${medicine.quantity || '0'}</td>
            <td>$${medicine.price || '0.00'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editMedicine(${medicine.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteMedicine(${medicine.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function addMedicine() {
    document.getElementById('medicineModalTitle').textContent = 'Add Medicine';
    document.getElementById('medicineForm').reset();
    document.getElementById('medicineId').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('medicineModal'));
    modal.show();
}

function editMedicine(id) {
    document.getElementById('medicineModalTitle').textContent = 'Edit Medicine';
    
    // Load medicine data
    apiCall(`api/medicines/${id}`).then(data => {
        if (data.success) {
            const medicine = data.data;
            document.getElementById('medicineId').value = medicine.id;
            document.getElementById('medicineName').value = medicine.name;
            document.getElementById('medicineGenericName').value = medicine.generic_name;
            document.getElementById('medicineType').value = medicine.type;
            document.getElementById('medicineStrength').value = medicine.strength;
            document.getElementById('medicineManufacturer').value = medicine.manufacturer;
            document.getElementById('medicineExpiryDate').value = medicine.expiry_date;
            document.getElementById('medicineQuantity').value = medicine.quantity;
            document.getElementById('medicinePrice').value = medicine.price;
            document.getElementById('medicineDescription').value = medicine.description;
            
            const modal = new bootstrap.Modal(document.getElementById('medicineModal'));
            modal.show();
        }
    });
}

async function saveMedicine() {
    const form = document.getElementById('medicineForm');
    const formData = new FormData(form);
    
    const medicineData = {
        name: formData.get('name'),
        generic_name: formData.get('generic_name'),
        type: formData.get('type'),
        strength: formData.get('strength'),
        manufacturer: formData.get('manufacturer'),
        expiry_date: formData.get('expiry_date'),
        quantity: formData.get('quantity'),
        price: formData.get('price'),
        description: formData.get('description')
    };
    
    const id = document.getElementById('medicineId').value;
    const endpoint = id ? `api/medicines/${id}` : 'api/medicines';
    const method = id ? 'PUT' : 'POST';
    
    try {
        const data = await apiCall(endpoint, {
            method: method,
            body: medicineData
        });
        
        if (data.success) {
            showAlert(`Medicine ${id ? 'updated' : 'added'} successfully`, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('medicineModal'));
            modal.hide();
            loadMedicines();
        }
    } catch (error) {
        console.error('Error saving medicine:', error);
    }
}

function deleteMedicine(id) {
    document.getElementById('deleteMedicineModal').setAttribute('data-medicine-id', id);
    const modal = new bootstrap.Modal(document.getElementById('deleteMedicineModal'));
    modal.show();
}

async function confirmDeleteMedicine() {
    const id = document.getElementById('deleteMedicineModal').getAttribute('data-medicine-id');
    
    try {
        const data = await apiCall(`api/medicines/${id}`, { method: 'DELETE' });
        if (data.success) {
            showAlert('Medicine deleted successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteMedicineModal'));
            modal.hide();
            loadMedicines();
        }
    } catch (error) {
        console.error('Error deleting medicine:', error);
    }
}