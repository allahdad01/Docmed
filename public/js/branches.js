// Branches Management Module
// Handles all branch-related functionality

export function showBranches() {
    hideAllSections();
    document.getElementById('branches-section').style.display = 'block';
    loadBranches();
}

export async function loadBranches() {
    try {
        const response = await apiCall('GET', 'api/branches');
        if (response.success) {
            displayBranches(response.data);
        } else {
            showAlert('Error loading branches: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading branches:', error);
        showAlert('Error loading branches', 'danger');
    }
}

export function displayBranches(branches) {
    const tbody = document.querySelector('#branches-table tbody');
    tbody.innerHTML = '';

    branches.forEach(branch => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${branch.name}</td>
            <td>${branch.address || '-'}</td>
            <td>${branch.phone || '-'}</td>
            <td>${branch.email || '-'}</td>
            <td>${branch.status}</td>
            <td>${branch.created_at}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editBranch(${branch.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteBranch(${branch.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

export function addBranch() {
    document.getElementById('branchModalLabel').textContent = 'Add Branch';
    document.getElementById('branchForm').reset();
    document.getElementById('branchId').value = '';
    document.getElementById('branchModal').classList.add('show');
    document.getElementById('branchModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

export function editBranch(id) {
    loadBranchForEdit(id);
}

export async function loadBranchForEdit(id) {
    try {
        const response = await apiCall('GET', `api/branches/${id}`);
        if (response.success) {
            const branch = response.data;
            document.getElementById('branchModalLabel').textContent = 'Edit Branch';
            document.getElementById('branchId').value = branch.id;
            document.getElementById('branchName').value = branch.name;
            document.getElementById('branchAddress').value = branch.address || '';
            document.getElementById('branchPhone').value = branch.phone || '';
            document.getElementById('branchEmail').value = branch.email || '';
            document.getElementById('branchStatus').value = branch.status;
            
            document.getElementById('branchModal').classList.add('show');
            document.getElementById('branchModal').style.display = 'block';
            document.body.classList.add('modal-open');
        } else {
            showAlert('Error loading branch: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading branch:', error);
        showAlert('Error loading branch', 'danger');
    }
}

export async function saveBranch() {
    const id = document.getElementById('branchId').value;
    const name = document.getElementById('branchName').value.trim();
    const address = document.getElementById('branchAddress').value.trim();
    const phone = document.getElementById('branchPhone').value.trim();
    const email = document.getElementById('branchEmail').value.trim();
    const status = document.getElementById('branchStatus').value;

    if (!name) {
        showAlert('Please enter a branch name', 'warning');
        return;
    }

    try {
        const data = {
            name: name,
            address: address,
            phone: phone,
            email: email,
            status: status
        };

        let response;
        if (id) {
            response = await apiCall('PUT', `api/branches/${id}`, data);
        } else {
            response = await apiCall('POST', 'api/branches', data);
        }

        if (response.success) {
            showAlert(id ? 'Branch updated successfully' : 'Branch added successfully', 'success');
            document.getElementById('branchModal').classList.remove('show');
            document.getElementById('branchModal').style.display = 'none';
            document.body.classList.remove('modal-open');
            loadBranches();
        } else {
            showAlert('Error saving branch: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error saving branch:', error);
        showAlert('Error saving branch', 'danger');
    }
}

export function deleteBranch(id) {
    if (confirm('Are you sure you want to delete this branch? This will also remove all inventory and sales associated with this branch.')) {
        confirmDeleteBranch(id);
    }
}

export async function confirmDeleteBranch(id) {
    try {
        const response = await apiCall('DELETE', `api/branches/${id}`);
        if (response.success) {
            showAlert('Branch deleted successfully', 'success');
            loadBranches();
        } else {
            showAlert('Error deleting branch: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting branch:', error);
        showAlert('Error deleting branch', 'danger');
    }
}

export function closeBranchModal() {
    document.getElementById('branchModal').classList.remove('show');
    document.getElementById('branchModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}

export async function loadBranchesForDropdown() {
    try {
        const response = await apiCall('GET', 'api/branches');
        if (response.success) {
            const select = document.getElementById('inventoryBranch');
            if (select) {
                select.innerHTML = '<option value="">Select Branch</option>';
                response.data.forEach(branch => {
                    const option = document.createElement('option');
                    option.value = branch.id;
                    option.textContent = branch.name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading branches for dropdown:', error);
    }
}