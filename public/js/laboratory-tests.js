// ===== LABORATORY TESTS FUNCTIONS =====

async function loadLaboratoryTests(page = 1) {
    try {
        const searchTerm = document.getElementById('labTestSearch').value;
        const categoryFilter = document.getElementById('labTestCategoryFilter').value;
        
        const params = new URLSearchParams({
            page: page,
            search: searchTerm,
            category: categoryFilter
        });
        
        const data = await apiCall(`api/laboratory-tests?${params}`);
        if (data.success) {
            displayLaboratoryTests(data.data);
            displayPagination(data.pagination, 'laboratoryTestsPagination', loadLaboratoryTests);
        } else {
            showAlert('Error loading tests', 'danger');
        }
    } catch (error) {
        console.error('Error loading laboratory tests:', error);
    }
}

function displayLaboratoryTests(tests) {
    const tbody = document.getElementById('laboratoryTestsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = tests.map(test => `
        <tr>
            <td>${test.name || 'N/A'}</td>
            <td>${test.category || 'N/A'}</td>
            <td>${test.description || 'N/A'}</td>
            <td>${test.preparation || 'N/A'}</td>
            <td>${test.duration || 'N/A'}</td>
            <td>$${test.price || '0.00'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editLaboratoryTest(${test.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteLaboratoryTest(${test.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function addLaboratoryTest() {
    document.getElementById('laboratoryTestModalTitle').textContent = 'Add Laboratory Test';
    document.getElementById('laboratoryTestForm').reset();
    document.getElementById('laboratoryTestId').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('laboratoryTestModal'));
    modal.show();
}

function editLaboratoryTest(id) {
    document.getElementById('laboratoryTestModalTitle').textContent = 'Edit Laboratory Test';
    
    apiCall(`api/laboratory-tests/${id}`).then(data => {
        if (data.success) {
            const test = data.data;
            document.getElementById('laboratoryTestId').value = test.id;
            document.getElementById('laboratoryTestName').value = test.name;
            document.getElementById('laboratoryTestCategory').value = test.category;
            document.getElementById('laboratoryTestDescription').value = test.description;
            document.getElementById('laboratoryTestPreparation').value = test.preparation;
            document.getElementById('laboratoryTestDuration').value = test.duration;
            document.getElementById('laboratoryTestPrice').value = test.price;
            
            const modal = new bootstrap.Modal(document.getElementById('laboratoryTestModal'));
            modal.show();
        }
    });
}

async function saveLaboratoryTest() {
    const form = document.getElementById('laboratoryTestForm');
    const formData = new FormData(form);
    
    const testData = {
        name: formData.get('name'),
        category: formData.get('category'),
        description: formData.get('description'),
        preparation: formData.get('preparation'),
        duration: formData.get('duration'),
        price: formData.get('price')
    };
    
    const id = document.getElementById('laboratoryTestId').value;
    const endpoint = id ? `api/laboratory-tests/${id}` : 'api/laboratory-tests';
    const method = id ? 'PUT' : 'POST';
    
    try {
        const data = await apiCall(endpoint, {
            method: method,
            body: testData
        });
        
        if (data.success) {
            showAlert(`Laboratory test ${id ? 'updated' : 'added'} successfully`, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('laboratoryTestModal'));
            modal.hide();
            loadLaboratoryTests();
        }
    } catch (error) {
        console.error('Error saving laboratory test:', error);
    }
}

function deleteLaboratoryTest(id) {
    document.getElementById('deleteLaboratoryTestModal').setAttribute('data-test-id', id);
    const modal = new bootstrap.Modal(document.getElementById('deleteLaboratoryTestModal'));
    modal.show();
}

async function confirmDeleteLaboratoryTest() {
    const id = document.getElementById('deleteLaboratoryTestModal').getAttribute('data-test-id');
    
    try {
        const data = await apiCall(`api/laboratory-tests/${id}`, { method: 'DELETE' });
        if (data.success) {
            showAlert('Laboratory test deleted successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteLaboratoryTestModal'));
            modal.hide();
            loadLaboratoryTests();
        }
    } catch (error) {
        console.error('Error deleting laboratory test:', error);
    }
}