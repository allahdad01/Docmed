// Products Management Module
// Handles all product-related functionality

export function showProducts() {
    hideAllSections();
    document.getElementById('products-section').style.display = 'block';
    document.querySelectorAll('.nav-link').forEach(link => link.classList.remove('active'));
    event.target.classList.add('active');
    loadProducts();
}

export async function loadProducts() {
    try {
        const products = await apiCall('api/products');
        if (products && products.data) {
            displayProducts(products.data);
            if (products.pagination) {
                displayPagination(products.pagination, 'productsPagination', loadProducts);
            }
        }
    } catch (error) {
        console.error('Error loading products:', error);
        showAlert('Failed to load products', 'danger');
    }
}

export function displayProducts(products) {
    const container = document.getElementById('productsTableBody');
    if (!container) return;
    
    container.innerHTML = '';
    
    products.forEach(product => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${product.name}</td>
            <td>${product.category_name || 'N/A'}</td>
            <td>${product.sku || 'N/A'}</td>
            <td>$${product.price || '0.00'}</td>
            <td>${product.stock_quantity || '0'}</td>
            <td>
                <span class="badge ${product.status === 'active' ? 'bg-success' : 'bg-secondary'}">
                    ${product.status || 'inactive'}
                </span>
            </td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editProduct(${product.id})">
                    <i class="bi bi-pencil"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteProduct(${product.id})">
                    <i class="bi bi-trash"></i>
                </button>
            </td>
        `;
        container.appendChild(row);
    });
}

export function addProduct() {
    // Reset form
    document.getElementById('productForm').reset();
    document.getElementById('productId').value = '';
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

export function editProduct(id) {
    // Load product data and populate form
    loadProductForEdit(id);
    
    // Show modal
    const modal = new bootstrap.Modal(document.getElementById('productModal'));
    modal.show();
}

export async function loadProductForEdit(id) {
    try {
        const product = await apiCall(`api/products/${id}`);
        if (product) {
            document.getElementById('productId').value = product.id;
            document.getElementById('productName').value = product.name;
            document.getElementById('productCategory').value = product.category_id || '';
            document.getElementById('productSku').value = product.sku || '';
            document.getElementById('productPrice').value = product.price || '';
            document.getElementById('productDescription').value = product.description || '';
            document.getElementById('productStatus').value = product.status || 'active';
        }
    } catch (error) {
        console.error('Error loading product for edit:', error);
        showAlert('Failed to load product details', 'danger');
    }
}

export async function saveProduct() {
    const formData = new FormData(document.getElementById('productForm'));
    const productId = document.getElementById('productId').value;
    
    const productData = {
        name: formData.get('name'),
        category_id: formData.get('category_id'),
        sku: formData.get('sku'),
        price: parseFloat(formData.get('price')),
        description: formData.get('description'),
        status: formData.get('status')
    };
    
    try {
        let response;
        if (productId) {
            // Update existing product
            response = await apiCall(`api/products/${productId}`, {
                method: 'PUT',
                body: JSON.stringify(productData)
            });
        } else {
            // Create new product
            response = await apiCall('api/products', {
                method: 'POST',
                body: JSON.stringify(productData)
            });
        }
        
        if (response) {
            showAlert(productId ? 'Product updated successfully' : 'Product created successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('productModal'));
            modal.hide();
            loadProducts();
        }
    } catch (error) {
        console.error('Error saving product:', error);
        showAlert('Failed to save product', 'danger');
    }
}

export function deleteProduct(id) {
    if (confirm('Are you sure you want to delete this product?')) {
        confirmDeleteProduct(id);
    }
}

export async function confirmDeleteProduct(id) {
    try {
        const response = await apiCall(`api/products/${id}`, {
            method: 'DELETE'
        });
        
        if (response) {
            showAlert('Product deleted successfully', 'success');
            loadProducts();
        }
    } catch (error) {
        console.error('Error deleting product:', error);
        showAlert('Failed to delete product', 'danger');
    }
}