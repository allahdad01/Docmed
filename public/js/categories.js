// Categories Management Module
// Handles all category-related functionality

export function showCategories() {
    hideAllSections();
    document.getElementById('categories-section').style.display = 'block';
    loadCategories();
}

export async function loadCategories() {
    try {
        const response = await apiCall('GET', 'api/categories');
        if (response.success) {
            displayCategories(response.data);
        } else {
            showAlert('Error loading categories: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading categories:', error);
        showAlert('Error loading categories', 'danger');
    }
}

export function displayCategories(categories) {
    const tbody = document.querySelector('#categories-table tbody');
    tbody.innerHTML = '';

    categories.forEach(category => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${category.name}</td>
            <td>${category.description || '-'}</td>
            <td>${category.product_count || 0}</td>
            <td>${category.created_at}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editCategory(${category.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteCategory(${category.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        tbody.appendChild(row);
    });
}

export function addCategory() {
    document.getElementById('categoryModalLabel').textContent = 'Add Category';
    document.getElementById('categoryForm').reset();
    document.getElementById('categoryId').value = '';
    document.getElementById('categoryModal').classList.add('show');
    document.getElementById('categoryModal').style.display = 'block';
    document.body.classList.add('modal-open');
}

export function editCategory(id) {
    loadCategoryForEdit(id);
}

export async function loadCategoryForEdit(id) {
    try {
        const response = await apiCall('GET', `api/categories/${id}`);
        if (response.success) {
            const category = response.data;
            document.getElementById('categoryModalLabel').textContent = 'Edit Category';
            document.getElementById('categoryId').value = category.id;
            document.getElementById('categoryName').value = category.name;
            document.getElementById('categoryDescription').value = category.description || '';
            
            document.getElementById('categoryModal').classList.add('show');
            document.getElementById('categoryModal').style.display = 'block';
            document.body.classList.add('modal-open');
        } else {
            showAlert('Error loading category: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error loading category:', error);
        showAlert('Error loading category', 'danger');
    }
}

export async function saveCategory() {
    const id = document.getElementById('categoryId').value;
    const name = document.getElementById('categoryName').value.trim();
    const description = document.getElementById('categoryDescription').value.trim();

    if (!name) {
        showAlert('Please enter a category name', 'warning');
        return;
    }

    try {
        const data = {
            name: name,
            description: description
        };

        let response;
        if (id) {
            response = await apiCall('PUT', `api/categories/${id}`, data);
        } else {
            response = await apiCall('POST', 'api/categories', data);
        }

        if (response.success) {
            showAlert(id ? 'Category updated successfully' : 'Category added successfully', 'success');
            document.getElementById('categoryModal').classList.remove('show');
            document.getElementById('categoryModal').style.display = 'none';
            document.body.classList.remove('modal-open');
            loadCategories();
        } else {
            showAlert('Error saving category: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error saving category:', error);
        showAlert('Error saving category', 'danger');
    }
}

export function deleteCategory(id) {
    if (confirm('Are you sure you want to delete this category? This will also remove all products in this category.')) {
        confirmDeleteCategory(id);
    }
}

export async function confirmDeleteCategory(id) {
    try {
        const response = await apiCall('DELETE', `api/categories/${id}`);
        if (response.success) {
            showAlert('Category deleted successfully', 'success');
            loadCategories();
        } else {
            showAlert('Error deleting category: ' + response.message, 'danger');
        }
    } catch (error) {
        console.error('Error deleting category:', error);
        showAlert('Error deleting category', 'danger');
    }
}

export function closeCategoryModal() {
    document.getElementById('categoryModal').classList.remove('show');
    document.getElementById('categoryModal').style.display = 'none';
    document.body.classList.remove('modal-open');
}

export async function loadCategoriesForDropdown() {
    try {
        const response = await apiCall('GET', 'api/categories');
        if (response.success) {
            const select = document.getElementById('productCategory');
            if (select) {
                select.innerHTML = '<option value="">Select Category</option>';
                response.data.forEach(category => {
                    const option = document.createElement('option');
                    option.value = category.id;
                    option.textContent = category.name;
                    select.appendChild(option);
                });
            }
        }
    } catch (error) {
        console.error('Error loading categories for dropdown:', error);
    }
}