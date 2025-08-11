// ===== PAYMENTS FUNCTIONS =====

async function loadPayments(page = 1) {
    try {
        const searchTerm = document.getElementById('paymentSearch').value;
        const typeFilter = document.getElementById('paymentTypeFilter').value;
        const dateFrom = document.getElementById('paymentDateFrom').value;
        const dateTo = document.getElementById('paymentDateTo').value;
        
        const params = new URLSearchParams({
            page: page,
            search: searchTerm,
            type: typeFilter,
            date_from: dateFrom,
            date_to: dateTo
        });
        
        const data = await apiCall(`api/payments?${params}`);
        if (data.success) {
            displayPayments(data.data);
            displayPagination(data.pagination, 'paymentsPagination', loadPayments);
        } else {
            showAlert('Error loading payments', 'danger');
        }
    } catch (error) {
        console.error('Error loading payments:', error);
    }
}

function displayPayments(payments) {
    const tbody = document.getElementById('paymentsTableBody');
    if (!tbody) return;
    
    tbody.innerHTML = payments.map(payment => `
        <tr>
            <td>${payment.date || 'N/A'}</td>
            <td>${payment.patient_name || 'N/A'}</td>
            <td>${payment.type || 'N/A'}</td>
            <td>$${payment.amount || '0.00'}</td>
            <td>${payment.payment_method || 'N/A'}</td>
            <td>
                <span class="badge bg-${getStatusBadgeColor(payment.status)}">
                    ${payment.status || 'N/A'}
                </span>
            </td>
            <td>${payment.receipt_number || 'N/A'}</td>
            <td>
                <button class="btn btn-sm btn-primary" onclick="editPayment(${payment.id})">
                    <i class="fas fa-edit"></i>
                </button>
                <button class="btn btn-sm btn-danger" onclick="deletePayment(${payment.id})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>
    `).join('');
}

function getStatusBadgeColor(status) {
    const colors = {
        'pending': 'warning',
        'partial': 'info',
        'completed': 'success',
        'cancelled': 'danger',
        'refunded': 'secondary'
    };
    return colors[status] || 'secondary';
}

async function loadPaymentSummary() {
    try {
        const data = await apiCall('api/payments/summary');
        if (data.success) {
            const summary = data.data;
            document.getElementById('totalReceived').textContent = `$${summary.total_received || '0.00'}`;
            document.getElementById('pendingPayments').textContent = `$${summary.pending_payments || '0.00'}`;
        }
    } catch (error) {
        console.error('Error loading payment summary:', error);
    }
}

function addPayment() {
    document.getElementById('paymentModalTitle').textContent = 'Add Payment';
    document.getElementById('paymentForm').reset();
    document.getElementById('paymentId').value = '';
    
    // Load patients and prescriptions
    loadPatientsForPayment();
    loadPrescriptionsForPayment();
    
    const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
    modal.show();
}

function editPayment(id) {
    document.getElementById('paymentModalTitle').textContent = 'Edit Payment';
    
    apiCall(`api/payments/${id}`).then(data => {
        if (data.success) {
            const payment = data.data;
            document.getElementById('paymentId').value = payment.id;
            document.getElementById('paymentDate').value = payment.date;
            document.getElementById('paymentPatientSelect').value = payment.patient_id;
            document.getElementById('paymentType').value = payment.type;
            document.getElementById('paymentAmount').value = payment.amount;
            document.getElementById('paymentMethod').value = payment.payment_method;
            document.getElementById('paymentStatus').value = payment.status;
            document.getElementById('paymentReceiptNumber').value = payment.receipt_number;
            document.getElementById('paymentNotes').value = payment.notes;
            
            const modal = new bootstrap.Modal(document.getElementById('paymentModal'));
            modal.show();
        }
    });
}

async function savePayment() {
    const form = document.getElementById('paymentForm');
    const formData = new FormData(form);
    
    const paymentData = {
        date: formData.get('date'),
        patient_id: formData.get('patient_id'),
        type: formData.get('type'),
        amount: formData.get('amount'),
        payment_method: formData.get('payment_method'),
        status: formData.get('status'),
        receipt_number: formData.get('receipt_number'),
        notes: formData.get('notes')
    };
    
    const id = document.getElementById('paymentId').value;
    const endpoint = id ? `api/payments/${id}` : 'api/payments';
    const method = id ? 'PUT' : 'POST';
    
    try {
        const data = await apiCall(endpoint, {
            method: method,
            body: paymentData
        });
        
        if (data.success) {
            showAlert(`Payment ${id ? 'updated' : 'added'} successfully`, 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('paymentModal'));
            modal.hide();
            loadPayments();
            loadPaymentSummary();
        }
    } catch (error) {
        console.error('Error saving payment:', error);
    }
}

function deletePayment(id) {
    document.getElementById('deletePaymentModal').setAttribute('data-payment-id', id);
    const modal = new bootstrap.Modal(document.getElementById('deletePaymentModal'));
    modal.show();
}

async function confirmDeletePayment() {
    const id = document.getElementById('deletePaymentModal').getAttribute('data-payment-id');
    
    try {
        const data = await apiCall(`api/payments/${id}`, { method: 'DELETE' });
        if (data.success) {
            showAlert('Payment deleted successfully', 'success');
            const modal = bootstrap.Modal.getInstance(document.getElementById('deletePaymentModal'));
            modal.hide();
            loadPayments();
            loadPaymentSummary();
        }
    } catch (error) {
        console.error('Error deleting payment:', error);
    }
}

async function loadPatientsForPayment() {
    try {
        const patients = await apiCall('api/patients');
        if (patients && patients.data) {
            const select = document.getElementById('paymentPatientSelect');
            select.innerHTML = '<option value="">Select Patient</option>';
            patients.data.forEach(patient => {
                select.innerHTML += `<option value="${patient.id}">${patient.name || 'N/A'} - ${patient.phone || 'N/A'}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading patients for payment:', error);
    }
}

async function loadPrescriptionsForPayment() {
    try {
        const prescriptions = await apiCall('api/prescriptions');
        if (prescriptions && prescriptions.data) {
            const select = document.getElementById('paymentPrescriptionSelect');
            select.innerHTML = '<option value="">Select Prescription</option>';
            prescriptions.data.forEach(prescription => {
                select.innerHTML += `<option value="${prescription.id}">Prescription #${prescription.id} - ${prescription.patient_name || 'N/A'}</option>`;
            });
        }
    } catch (error) {
        console.error('Error loading prescriptions for payment:', error);
    }
}