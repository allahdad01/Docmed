// Search and Filters Module
// Handles search, filtering, and pagination functionality

export class SearchFilterManager {
    constructor(options = {}) {
        this.options = {
            searchDelay: 300,
            minSearchLength: 2,
            pageSize: 10,
            ...options
        };
        
        this.currentFilters = {};
        this.currentPage = 1;
        this.totalPages = 1;
        this.totalRecords = 0;
        this.searchTimeout = null;
    }
    
    // Initialize search functionality
    initSearch(searchInputId, onSearch) {
        const searchInput = document.getElementById(searchInputId);
        if (!searchInput) return;
        
        searchInput.addEventListener('input', (e) => {
            const query = e.target.value.trim();
            this.debouncedSearch(query, onSearch);
        });
        
        // Add search icon and clear button
        this.addSearchControls(searchInput);
    }
    
    // Debounced search to avoid too many API calls
    debouncedSearch(query, onSearch) {
        clearTimeout(this.searchTimeout);
        
        this.searchTimeout = setTimeout(() => {
            if (query.length >= this.options.minSearchLength || query.length === 0) {
                this.currentFilters.search = query;
                this.currentPage = 1; // Reset to first page on search
                if (typeof onSearch === 'function') {
                    onSearch(this.currentFilters, this.currentPage);
                }
            }
        }, this.options.searchDelay);
    }
    
    // Add search controls (icon and clear button)
    addSearchControls(searchInput) {
        const wrapper = searchInput.parentElement;
        if (!wrapper) return;
        
        // Add search icon
        if (!wrapper.querySelector('.search-icon')) {
            const icon = document.createElement('i');
            icon.className = 'bi bi-search search-icon position-absolute';
            icon.style.left = '10px';
            icon.style.top = '50%';
            icon.style.transform = 'translateY(-50%)';
            icon.style.color = '#6c757d';
            wrapper.appendChild(icon);
            
            // Adjust input padding
            searchInput.style.paddingLeft = '35px';
        }
        
        // Add clear button
        if (!wrapper.querySelector('.search-clear')) {
            const clearBtn = document.createElement('button');
            clearBtn.type = 'button';
            clearBtn.className = 'btn btn-sm btn-outline-secondary search-clear position-absolute';
            clearBtn.innerHTML = '×';
            clearBtn.style.right = '5px';
            clearBtn.style.top = '50%';
            clearBtn.style.transform = 'translateY(-50%)';
            clearBtn.style.display = 'none';
            
            clearBtn.addEventListener('click', () => {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            });
            
            wrapper.appendChild(clearBtn);
            
            // Show/hide clear button based on input
            searchInput.addEventListener('input', () => {
                clearBtn.style.display = searchInput.value ? 'block' : 'none';
            });
        }
    }
    
    // Initialize filters
    initFilters(filterContainerId, onFilter) {
        const container = document.getElementById(filterContainerId);
        if (!container) return;
        
        // Add filter change listeners
        const filterInputs = container.querySelectorAll('select, input[type="date"], input[type="checkbox"]');
        filterInputs.forEach(input => {
            input.addEventListener('change', () => {
                this.updateFilters(container, onFilter);
            });
        });
        
        // Add filter reset button
        this.addFilterResetButton(container, onFilter);
    }
    
    // Update filters from form inputs
    updateFilters(container, onFilter) {
        const filterInputs = container.querySelectorAll('select, input[type="date"], input[type="checkbox"]');
        const filters = {};
        
        filterInputs.forEach(input => {
            if (input.type === 'checkbox') {
                if (input.checked) {
                    filters[input.name] = input.value || true;
                }
            } else if (input.value) {
                filters[input.name] = input.value;
            }
        });
        
        this.currentFilters = { ...this.currentFilters, ...filters };
        this.currentPage = 1; // Reset to first page on filter change
        
        if (typeof onFilter === 'function') {
            onFilter(this.currentFilters, this.currentPage);
        }
    }
    
    // Add filter reset button
    addFilterResetButton(container, onFilter) {
        if (container.querySelector('.filter-reset')) return;
        
        const resetBtn = document.createElement('button');
        resetBtn.type = 'button';
        resetBtn.className = 'btn btn-outline-secondary btn-sm filter-reset';
        resetBtn.innerHTML = '<i class="bi bi-arrow-clockwise"></i> Reset Filters';
        
        resetBtn.addEventListener('click', () => {
            this.resetFilters(container, onFilter);
        });
        
        container.appendChild(resetBtn);
    }
    
    // Reset all filters
    resetFilters(container, onFilter) {
        const filterInputs = container.querySelectorAll('select, input[type="date"], input[type="checkbox"]');
        filterInputs.forEach(input => {
            if (input.type === 'checkbox') {
                input.checked = false;
            } else {
                input.value = '';
            }
        });
        
        this.currentFilters = {};
        this.currentPage = 1;
        
        if (typeof onFilter === 'function') {
            onFilter(this.currentFilters, this.currentPage);
        }
    }
    
    // Initialize pagination
    initPagination(containerId, onPageChange) {
        const container = document.getElementById(containerId);
        if (!container) return;
        
        this.paginationContainer = container;
        this.onPageChange = onPageChange;
    }
    
    // Update pagination display
    updatePagination(totalRecords, currentPage, pageSize) {
        this.totalRecords = totalRecords;
        this.currentPage = currentPage;
        this.totalPages = Math.ceil(totalRecords / pageSize);
        
        if (this.paginationContainer) {
            this.renderPagination();
        }
    }
    
    // Render pagination controls
    renderPagination() {
        if (!this.paginationContainer) return;
        
        const container = this.paginationContainer;
        container.innerHTML = '';
        
        if (this.totalPages <= 1) return;
        
        const pagination = document.createElement('nav');
        pagination.setAttribute('aria-label', 'Page navigation');
        
        const ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm justify-content-center mb-0';
        
        // Previous button
        const prevLi = document.createElement('li');
        prevLi.className = `page-item ${this.currentPage === 1 ? 'disabled' : ''}`;
        const prevBtn = document.createElement('a');
        prevBtn.className = 'page-link';
        prevBtn.href = '#';
        prevBtn.innerHTML = '&laquo;';
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.currentPage > 1) {
                this.goToPage(this.currentPage - 1);
            }
        });
        prevLi.appendChild(prevBtn);
        ul.appendChild(prevLi);
        
        // Page numbers
        const startPage = Math.max(1, this.currentPage - 2);
        const endPage = Math.min(this.totalPages, this.currentPage + 2);
        
        for (let i = startPage; i <= endPage; i++) {
            const li = document.createElement('li');
            li.className = `page-item ${i === this.currentPage ? 'active' : ''}`;
            const a = document.createElement('a');
            a.className = 'page-link';
            a.href = '#';
            a.textContent = i;
            a.addEventListener('click', (e) => {
                e.preventDefault();
                this.goToPage(i);
            });
            li.appendChild(a);
            ul.appendChild(li);
        }
        
        // Next button
        const nextLi = document.createElement('li');
        nextLi.className = `page-item ${this.currentPage === this.totalPages ? 'disabled' : ''}`;
        const nextBtn = document.createElement('a');
        nextBtn.className = 'page-link';
        nextBtn.href = '#';
        nextBtn.innerHTML = '&raquo;';
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.currentPage < this.totalPages) {
                this.goToPage(this.currentPage + 1);
            }
        });
        nextLi.appendChild(nextBtn);
        ul.appendChild(nextLi);
        
        pagination.appendChild(ul);
        container.appendChild(pagination);
        
        // Add page info
        const pageInfo = document.createElement('div');
        pageInfo.className = 'text-center text-muted small mt-2';
        pageInfo.textContent = `Showing ${((this.currentPage - 1) * this.options.pageSize) + 1} to ${Math.min(this.currentPage * this.options.pageSize, this.totalRecords)} of ${this.totalRecords} records`;
        container.appendChild(pageInfo);
    }
    
    // Go to specific page
    goToPage(page) {
        if (page < 1 || page > this.totalPages) return;
        
        this.currentPage = page;
        
        if (typeof this.onPageChange === 'function') {
            this.onPageChange(this.currentFilters, this.currentPage);
        }
    }
    
    // Get current filters and page
    getCurrentState() {
        return {
            filters: this.currentFilters,
            page: this.currentPage,
            pageSize: this.options.pageSize
        };
    }
    
    // Set filters programmatically
    setFilters(filters) {
        this.currentFilters = { ...filters };
    }
    
    // Set current page
    setPage(page) {
        this.currentPage = page;
    }
    
    // Clear all state
    clear() {
        this.currentFilters = {};
        this.currentPage = 1;
        this.totalPages = 1;
        this.totalRecords = 0;
    }
}

// Utility functions for search and filtering
export function highlightSearchTerm(text, searchTerm) {
    if (!searchTerm || !text) return text;
    
    const regex = new RegExp(`(${searchTerm})`, 'gi');
    return text.toString().replace(regex, '<mark>$1</mark>');
}

export function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

export function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

export function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Advanced search with multiple fields
export function createAdvancedSearch(fields, onSearch) {
    const searchContainer = document.createElement('div');
    searchContainer.className = 'advanced-search mb-3';
    
    const row = document.createElement('div');
    row.className = 'row g-2';
    
    fields.forEach(field => {
        const col = document.createElement('div');
        col.className = `col-md-${field.width || 3}`;
        
        const input = document.createElement('input');
        input.type = field.type || 'text';
        input.className = 'form-control form-control-sm';
        input.placeholder = field.placeholder || field.label;
        input.name = field.name;
        
        if (field.type === 'select') {
            input.type = 'select';
            input.innerHTML = `<option value="">${field.placeholder || field.label}</option>`;
            if (field.options) {
                field.options.forEach(option => {
                    const opt = document.createElement('option');
                    opt.value = option.value;
                    opt.textContent = option.label;
                    input.appendChild(opt);
                });
            }
        }
        
        col.appendChild(input);
        row.appendChild(col);
    });
    
    searchContainer.appendChild(row);
    
    // Add search button
    const searchBtn = document.createElement('button');
    searchBtn.type = 'button';
    searchBtn.className = 'btn btn-primary btn-sm mt-2';
    searchBtn.innerHTML = '<i class="bi bi-search"></i> Search';
    searchBtn.addEventListener('click', () => {
        const searchData = {};
        searchContainer.querySelectorAll('input, select').forEach(input => {
            if (input.value) {
                searchData[input.name] = input.value;
            }
        });
        onSearch(searchData);
    });
    
    searchContainer.appendChild(searchBtn);
    
    return searchContainer;
}