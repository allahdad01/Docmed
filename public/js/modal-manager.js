// Modal Manager Module
// Handles all modal operations and form management

export function openModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.add('show');
        modal.style.display = 'block';
        document.body.classList.add('modal-open');
        
        // Focus on first input
        const firstInput = modal.querySelector('input, select, textarea');
        if (firstInput) {
            firstInput.focus();
        }
    }
}

export function closeModal(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.classList.remove('show');
        modal.style.display = 'none';
        document.body.classList.remove('modal-open');
        
        // Reset form if exists
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
        }
    }
}

export function closeAllModals() {
    const modals = document.querySelectorAll('.modal');
    modals.forEach(modal => {
        if (modal.classList.contains('show')) {
            closeModal(modal.id);
        }
    });
}

export function resetModalForm(modalId) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const form = modal.querySelector('form');
        if (form) {
            form.reset();
            
            // Clear any hidden fields
            const hiddenFields = form.querySelectorAll('input[type="hidden"]');
            hiddenFields.forEach(field => {
                if (field.id !== 'csrf_token') { // Preserve CSRF token
                    field.value = '';
                }
            });
        }
    }
}

export function setModalTitle(modalId, title) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const titleElement = modal.querySelector('.modal-title');
        if (titleElement) {
            titleElement.textContent = title;
        }
    }
}

export function setModalMode(modalId, mode) {
    const modal = document.getElementById(modalId);
    if (modal) {
        // Update modal title based on mode
        const titleElement = modal.querySelector('.modal-title');
        if (titleElement) {
            titleElement.textContent = mode === 'add' ? 'Add New' : 'Edit';
        }
        
        // Update submit button text
        const submitBtn = modal.querySelector('button[type="submit"]');
        if (submitBtn) {
            submitBtn.textContent = mode === 'add' ? 'Add' : 'Update';
        }
        
        // Store mode in modal data
        modal.dataset.mode = mode;
    }
}

export function getModalMode(modalId) {
    const modal = document.getElementById(modalId);
    return modal ? modal.dataset.mode : null;
}

export function showModalLoading(modalId, show = true) {
    const modal = document.getElementById(modalId);
    if (modal) {
        const submitBtn = modal.querySelector('button[type="submit"]');
        const loadingSpinner = modal.querySelector('.loading-spinner');
        
        if (submitBtn) {
            if (show) {
                submitBtn.disabled = true;
                submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2"></span>Loading...';
            } else {
                submitBtn.disabled = false;
                submitBtn.innerHTML = getModalMode(modalId) === 'add' ? 'Add' : 'Update';
            }
        }
        
        if (loadingSpinner) {
            loadingSpinner.style.display = show ? 'block' : 'none';
        }
    }
}

export function validateModalForm(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return false;
    
    const form = modal.querySelector('form');
    if (!form) return true;
    
    // Check HTML5 validation
    if (!form.checkValidity()) {
        form.reportValidity();
        return false;
    }
    
    // Custom validation can be added here
    return true;
}

export function getModalFormData(modalId) {
    const modal = document.getElementById(modalId);
    if (!modal) return {};
    
    const form = modal.querySelector('form');
    if (!form) return {};
    
    const formData = new FormData(form);
    const data = {};
    
    for (let [key, value] of formData.entries()) {
        data[key] = value;
    }
    
    return data;
}

export function setModalFormData(modalId, data) {
    const modal = document.getElementById(modalId);
    if (!modal) return;
    
    const form = modal.querySelector('form');
    if (!form) return;
    
    Object.keys(data).forEach(key => {
        const field = form.querySelector(`[name="${key}"]`);
        if (field) {
            if (field.type === 'checkbox') {
                field.checked = Boolean(data[key]);
            } else if (field.type === 'radio') {
                const radio = form.querySelector(`[name="${key}"][value="${data[key]}"]`);
                if (radio) radio.checked = true;
            } else {
                field.value = data[key];
            }
        }
    });
}

export function addModalEventListener(modalId, event, callback) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.addEventListener(event, callback);
    }
}

export function removeModalEventListener(modalId, event, callback) {
    const modal = document.getElementById(modalId);
    if (modal) {
        modal.removeEventListener(event, callback);
    }
}

export function createModal(modalId, title, content, options = {}) {
    const modalHtml = `
        <div class="modal fade" id="${modalId}" tabindex="-1" aria-labelledby="${modalId}Label" aria-hidden="true">
            <div class="modal-dialog ${options.size || 'modal-lg'}">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="${modalId}Label">${title}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        ${content}
                    </div>
                    ${options.showFooter !== false ? `
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                            ${options.submitButton ? `<button type="submit" class="btn btn-primary">${options.submitButton}</button>` : ''}
                        </div>
                    ` : ''}
                </div>
            </div>
        </div>
    `;
    
    // Remove existing modal if it exists
    const existingModal = document.getElementById(modalId);
    if (existingModal) {
        existingModal.remove();
    }
    
    // Add modal to body
    document.body.insertAdjacentHTML('beforeend', modalHtml);
    
    // Initialize Bootstrap modal
    const modal = document.getElementById(modalId);
    if (modal && window.bootstrap) {
        new bootstrap.Modal(modal);
    }
}

export function showConfirmModal(title, message, onConfirm, onCancel) {
    const modalId = 'confirmModal';
    const content = `
        <div class="text-center">
            <i class="bi bi-question-circle text-warning" style="font-size: 3rem;"></i>
            <p class="mt-3">${message}</p>
        </div>
    `;
    
    createModal(modalId, title, content, {
        size: 'modal-sm',
        submitButton: 'Confirm',
        showFooter: true
    });
    
    const modal = document.getElementById(modalId);
    if (modal) {
        const confirmBtn = modal.querySelector('.btn-primary');
        const closeBtn = modal.querySelector('.btn-secondary');
        
        if (confirmBtn) {
            confirmBtn.addEventListener('click', () => {
                closeModal(modalId);
                if (typeof onConfirm === 'function') {
                    onConfirm();
                }
            });
        }
        
        if (closeBtn) {
            closeBtn.addEventListener('click', () => {
                closeModal(modalId);
                if (typeof onCancel === 'function') {
                    onCancel();
                }
            });
        }
        
        openModal(modalId);
    }
}

export function showAlertModal(title, message, type = 'info') {
    const modalId = 'alertModal';
    const iconClass = {
        'info': 'bi-info-circle text-info',
        'success': 'bi-check-circle text-success',
        'warning': 'bi-exclamation-triangle text-warning',
        'danger': 'bi-x-circle text-danger'
    }[type] || 'bi-info-circle text-info';
    
    const content = `
        <div class="text-center">
            <i class="bi ${iconClass}" style="font-size: 3rem;"></i>
            <p class="mt-3">${message}</p>
        </div>
    `;
    
    createModal(modalId, title, content, {
        size: 'modal-sm',
        showFooter: true
    });
    
    openModal(modalId);
}

// Auto-close modals when clicking outside
document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal') && e.target.classList.contains('show')) {
        closeModal(e.target.id);
    }
});

// Close modals on escape key
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') {
        closeAllModals();
    }
});