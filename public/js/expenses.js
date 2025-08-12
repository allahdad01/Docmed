// ===== EXPENSES FUNCTIONS =====

async function loadExpenses(page = 1) {
    try {
        const searchTerm = document.getElementById('expenseSearch').value;
        const categoryFilter = document.getElementById('expenseCategoryFilter').value;
        const dateFrom = document.getElementById('expenseDateFrom').value;
        const dateTo = document.getElementById('expenseDateTo').value;
        
        const params = new URLSearchParams({
            page: page,
            search: searchTerm,
            category: categoryFilter,
            date_from: dateFrom,
            date_to: dateTo
        });
        
        const data = await apiCall(`api/expenses?${params}`);
        if (data.success) {
            displayExpenses(data.data);
            displayPagination(data.pagination, 'expensesPagination', loadExpenses);
        } else {
            showAlert('Error loading expenses', 'danger');
        }
    } catch (error) {
        console.error('Error loading expenses:', error);
    }
}

function displayExpenses(expenses) {
    const tbody = document.getElementById('expensesTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = expenses.map(expense => `
        <tr>
            <td>${expense.date || 'N/A'}</td>
            <td>${expense.description || 'N/A'}</td>
            <td>${expense.category || 'N/A'}</td>
            <td>$${expense.amount || '0.00'}</td>
            <td>${expense.payment_method || 'N/A'}</td>
            <td>${expense.receipt_number || 'N/A'}</td>
            <td>${expense.notes || 'N/A'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editExpense(${expense.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deleteExpense(${expense.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

async function loadExpenseSummary() {
    try {
        const data = await apiCall('api/expenses/summary');
        if (data.success) {
            const summary = data.data;
            document.getElementById('totalExpenses').textContent = `$${summary.total_expenses || '0.00'}`;
            document.getElementById('monthlyExpenses').textContent = `$${summary.monthly_expenses || '0.00'}`;
        }
    } catch (error) {
        console.error('Error loading expense summary:', error);
    }
}

function addExpense() {
    document.getElementById('expenseModalTitle').textContent = 'Add Expense';
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseId').value = '';
    
    const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
    modal.show();
}

function editExpense(id) {
    document.getElementById('expenseModalTitle').textContent = 'Edit Expense';
    
    apiCall(`api/expenses/${id}`).then(data => {
        if (data.success) {
            const expense = data.data;
            document.getElementById('expenseId').value = expense.id;
            document.getElementById('expenseDate').value = expense.date;
            document.getElementById('expenseDescription').value = expense.description;
            document.getElementById('expenseCategory').value = expense.category;
            document.getElementById('expenseAmount').value = expense.amount;
            document.getElementById('expensePaymentMethod').value = expense.payment_method;
            document.getElementById('expenseReceiptNumber').value = expense.receipt_number;
            document.getElementById('expenseNotes').value = expense.notes;
            
            const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
            modal.show();
        }
    });
}

async function saveExpense() {
    const form = document.getElementById('expenseForm');
    const formData = new FormData(form);
    
    const expenseData = {
        date: formData.get('date'),
        description: formData.get('description'),
        category: formData.get('category'),
        amount: formData.get('amount'),
        payment_method: formData.get('payment_method'),
        receipt_number: formData.get('receipt_number'),
        notes: formData.get('notes')
    };
    
    const id = document.getElementById('expenseId').value;
    const endpoint = id ? `api/expenses/${id}` : 'api/expenses';
    const method = id ? 'PUT' : 'POST';
    
    try {
        const data = await apiCall(endpoint, {
            method: method,
            body: expenseData
        });
        
        if (data.success) {
            showAlert(`Expense ${id ? 'updated' : 'added'} successfully`, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('expenseModal'));
            modal.hide();
            loadExpenses();
            loadExpenseSummary();
        }
    } catch (error) {
        console.error('Error saving expense:', error);
    }
}

function deleteExpense(id) {
    document.getElementById('deleteExpenseModal').setAttribute('data-expense-id', id);
    const modal = new bootstrap.Modal(document.getElementById('deleteExpenseModal'));
    modal.show();
}

async function confirmDeleteExpense() {
    const id = document.getElementById('deleteExpenseModal').getAttribute('data-expense-id');
    
    try {
        const data = await apiCall(`api/expenses/${id}`, { method: 'DELETE' });
        if (data.success) {
            showAlert('Expense deleted successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deleteExpenseModal'));
            modal.hide();
            loadExpenses();
            loadExpenseSummary();
        }
    } catch (error) {
        console.error('Error deleting expense:', error);
    }
}