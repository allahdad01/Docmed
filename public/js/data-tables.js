// Data Tables Module
// Handles data table operations, sorting, and display

export class DataTable {
    constructor(tableId, options = {}) {
        this.table = document.getElementById(tableId);
        this.options = {
            pageSize: 10,
            sortable: true,
            searchable: true,
            pagination: true,
            selectable: false,
            multiSelect: false,
            responsive: true,
            ...options
        };
        
        this.data = [];
        this.filteredData = [];
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
        this.selectedRows = new Set();
        
        if (this.table) {
            this.init();
        }
    }
    
    // Initialize the data table
    init() {
        this.setupTable();
        this.setupEventListeners();
        
        if (this.options.responsive) {
            this.makeResponsive();
        }
    }
    
    // Setup table structure
    setupTable() {
        // Add table wrapper
        const wrapper = document.createElement('div');
        wrapper.className = 'table-responsive';
        this.table.parentNode.insertBefore(wrapper, this.table);
        wrapper.appendChild(this.table);
        
        // Add search bar if enabled
        if (this.options.searchable) {
            this.addSearchBar(wrapper);
        }
        
        // Add table controls
        this.addTableControls(wrapper);
        
        // Add pagination if enabled
        if (this.options.pagination) {
            this.addPagination(wrapper);
        }
    }
    
    // Add search bar
    addSearchBar(wrapper) {
        const searchContainer = document.createElement('div');
        searchContainer.className = 'mb-3';
        
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.className = 'form-control';
        searchInput.placeholder = 'Search...';
        searchInput.id = `${this.table.id}-search`;
        
        searchInput.addEventListener('input', (e) => {
            this.filterData(e.target.value);
        });
        
        searchContainer.appendChild(searchInput);
        wrapper.insertBefore(searchContainer, this.table);
    }
    
    // Add table controls
    addTableControls(wrapper) {
        const controlsContainer = document.createElement('div');
        controlsContainer.className = 'd-flex justify-content-between align-items-center mb-3';
        
        // Page size selector
        if (this.options.pagination) {
            const pageSizeSelect = document.createElement('select');
            pageSizeSelect.className = 'form-select form-select-sm';
            pageSizeSelect.style.width = 'auto';
            
            [10, 25, 50, 100].forEach(size => {
                const option = document.createElement('option');
                option.value = size;
                option.textContent = `${size} per page`;
                if (size === this.options.pageSize) {
                    option.selected = true;
                }
                pageSizeSelect.appendChild(option);
            });
            
            pageSizeSelect.addEventListener('change', (e) => {
                this.options.pageSize = parseInt(e.target.value);
                this.currentPage = 1;
                this.renderTable();
            });
            
            controlsContainer.appendChild(pageSizeSelect);
        }
        
        // Bulk actions if selectable
        if (this.options.selectable) {
            const bulkActions = document.createElement('div');
            bulkActions.className = 'btn-group';
            
            const selectAllBtn = document.createElement('button');
            selectAllBtn.className = 'btn btn-outline-secondary btn-sm';
            selectAllBtn.textContent = 'Select All';
            selectAllBtn.addEventListener('click', () => this.selectAll());
            
            const clearSelectionBtn = document.createElement('button');
            clearSelectionBtn.className = 'btn btn-outline-secondary btn-sm';
            clearSelectionBtn.textContent = 'Clear Selection';
            clearSelectionBtn.addEventListener('click', () => this.clearSelection());
            
            bulkActions.appendChild(selectAllBtn);
            bulkActions.appendChild(clearSelectionBtn);
            controlsContainer.appendChild(bulkActions);
        }
        
        wrapper.insertBefore(controlsContainer, this.table);
    }
    
    // Add pagination
    addPagination(wrapper) {
        const paginationContainer = document.createElement('div');
        paginationContainer.className = 'd-flex justify-content-between align-items-center mt-3';
        paginationContainer.id = `${this.table.id}-pagination`;
        
        wrapper.appendChild(paginationContainer);
    }
    
    // Setup event listeners
    setupEventListeners() {
        if (this.options.sortable) {
            this.setupSorting();
        }
        
        if (this.options.selectable) {
            this.setupSelection();
        }
    }
    
    // Setup sorting functionality
    setupSorting() {
        const headers = this.table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                this.sortByColumn(header.dataset.sortable);
            });
            
            // Add sort indicator
            const indicator = document.createElement('span');
            indicator.className = 'sort-indicator ms-1';
            indicator.innerHTML = '↕';
            header.appendChild(indicator);
        });
    }
    
    // Setup row selection
    setupSelection() {
        if (this.options.multiSelect) {
            // Add checkbox column header
            const headerRow = this.table.querySelector('thead tr');
            if (headerRow) {
                const checkboxHeader = document.createElement('th');
                checkboxHeader.innerHTML = '<input type="checkbox" class="form-check-input" id="select-all">';
                checkboxHeader.className = 'text-center';
                headerRow.insertBefore(checkboxHeader, headerRow.firstChild);
                
                // Select all functionality
                const selectAllCheckbox = checkboxHeader.querySelector('#select-all');
                selectAllCheckbox.addEventListener('change', (e) => {
                    if (e.target.checked) {
                        this.selectAll();
                    } else {
                        this.clearSelection();
                    }
                });
            }
        }
    }
    
    // Load data into the table
    loadData(data) {
        this.data = data;
        this.filteredData = [...data];
        this.renderTable();
    }
    
    // Filter data based on search term
    filterData(searchTerm) {
        if (!searchTerm) {
            this.filteredData = [...this.data];
        } else {
            this.filteredData = this.data.filter(row => {
                return Object.values(row).some(value => 
                    value && value.toString().toLowerCase().includes(searchTerm.toLowerCase())
                );
            });
        }
        
        this.currentPage = 1;
        this.renderTable();
    }
    
    // Sort data by column
    sortByColumn(columnName) {
        if (this.sortColumn === columnName) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = columnName;
            this.sortDirection = 'asc';
        }
        
        this.filteredData.sort((a, b) => {
            let aVal = a[columnName];
            let bVal = b[columnName];
            
            // Handle null/undefined values
            if (aVal === null || aVal === undefined) aVal = '';
            if (bVal === null || bVal === undefined) bVal = '';
            
            // Convert to string for comparison
            aVal = aVal.toString().toLowerCase();
            bVal = bVal.toString().toLowerCase();
            
            if (aVal < bVal) return this.sortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return this.sortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        
        this.updateSortIndicators();
        this.renderTable();
    }
    
    // Update sort indicators
    updateSortIndicators() {
        const headers = this.table.querySelectorAll('th[data-sortable]');
        headers.forEach(header => {
            const indicator = header.querySelector('.sort-indicator');
            if (header.dataset.sortable === this.sortColumn) {
                indicator.innerHTML = this.sortDirection === 'asc' ? '↑' : '↓';
                indicator.className = 'sort-indicator ms-1 text-primary';
            } else {
                indicator.innerHTML = '↕';
                indicator.className = 'sort-indicator ms-1 text-muted';
            }
        });
    }
    
    // Render the table
    renderTable() {
        const tbody = this.table.querySelector('tbody');
        if (!tbody) return;
        
        // Clear existing rows
        tbody.innerHTML = '';
        
        // Calculate pagination
        const startIndex = (this.currentPage - 1) * this.options.pageSize;
        const endIndex = startIndex + this.options.pageSize;
        const pageData = this.filteredData.slice(startIndex, endIndex);
        
        // Render rows
        pageData.forEach((row, index) => {
            const tr = this.createTableRow(row, startIndex + index);
            tbody.appendChild(tr);
        });
        
        // Update pagination
        if (this.options.pagination) {
            this.updatePagination();
        }
        
        // Update row count
        this.updateRowCount();
    }
    
    // Create a table row
    createTableRow(rowData, rowIndex) {
        const tr = document.createElement('tr');
        tr.dataset.rowIndex = rowIndex;
        
        // Add selection checkbox if enabled
        if (this.options.selectable) {
            const checkboxCell = document.createElement('td');
            checkboxCell.className = 'text-center';
            
            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.className = 'form-check-input row-checkbox';
            checkbox.checked = this.selectedRows.has(rowIndex);
            
            checkbox.addEventListener('change', (e) => {
                if (e.target.checked) {
                    this.selectedRows.add(rowIndex);
                } else {
                    this.selectedRows.delete(rowIndex);
                }
                this.updateSelectionUI();
            });
            
            checkboxCell.appendChild(checkbox);
            tr.appendChild(checkboxCell);
        }
        
        // Add data cells
        Object.values(rowData).forEach(value => {
            const td = document.createElement('td');
            td.textContent = value || '';
            tr.appendChild(td);
        });
        
        return tr;
    }
    
    // Update pagination display
    updatePagination() {
        const container = document.getElementById(`${this.table.id}-pagination`);
        if (!container) return;
        
        const totalPages = Math.ceil(this.filteredData.length / this.options.pageSize);
        
        container.innerHTML = '';
        
        if (totalPages <= 1) return;
        
        // Page info
        const pageInfo = document.createElement('div');
        pageInfo.className = 'text-muted small';
        const startRecord = (this.currentPage - 1) * this.options.pageSize + 1;
        const endRecord = Math.min(this.currentPage * this.options.pageSize, this.filteredData.length);
        pageInfo.textContent = `Showing ${startRecord} to ${endRecord} of ${this.filteredData.length} records`;
        container.appendChild(pageInfo);
        
        // Pagination controls
        const pagination = document.createElement('nav');
        const ul = document.createElement('ul');
        ul.className = 'pagination pagination-sm mb-0';
        
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
        const endPage = Math.min(totalPages, this.currentPage + 2);
        
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
        nextLi.className = `page-item ${this.currentPage === totalPages ? 'disabled' : ''}`;
        const nextBtn = document.createElement('a');
        nextBtn.className = 'page-link';
        nextBtn.href = '#';
        nextBtn.innerHTML = '&raquo;';
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            if (this.currentPage < totalPages) {
                this.goToPage(this.currentPage + 1);
            }
        });
        nextLi.appendChild(nextBtn);
        ul.appendChild(nextLi);
        
        pagination.appendChild(ul);
        container.appendChild(pagination);
    }
    
    // Go to specific page
    goToPage(page) {
        const totalPages = Math.ceil(this.filteredData.length / this.options.pageSize);
        if (page < 1 || page > totalPages) return;
        
        this.currentPage = page;
        this.renderTable();
    }
    
    // Update row count display
    updateRowCount() {
        // This can be customized to show row count in a specific location
        console.log(`Showing ${this.filteredData.length} records`);
    }
    
    // Selection methods
    selectAll() {
        const checkboxes = this.table.querySelectorAll('.row-checkbox');
        checkboxes.forEach((checkbox, index) => {
            checkbox.checked = true;
            this.selectedRows.add((this.currentPage - 1) * this.options.pageSize + index);
        });
        this.updateSelectionUI();
    }
    
    clearSelection() {
        const checkboxes = this.table.querySelectorAll('.row-checkbox');
        checkboxes.forEach(checkbox => {
            checkbox.checked = false;
        });
        this.selectedRows.clear();
        this.updateSelectionUI();
    }
    
    updateSelectionUI() {
        const selectAllCheckbox = this.table.querySelector('#select-all');
        if (selectAllCheckbox) {
            const totalRows = this.filteredData.length;
            const selectedRows = this.selectedRows.size;
            selectAllCheckbox.checked = selectedRows === totalRows;
            selectAllCheckbox.indeterminate = selectedRows > 0 && selectedRows < totalRows;
        }
    }
    
    // Get selected rows
    getSelectedRows() {
        return Array.from(this.selectedRows).map(index => this.filteredData[index]);
    }
    
    // Get selected row indices
    getSelectedIndices() {
        return Array.from(this.selectedRows);
    }
    
    // Make table responsive
    makeResponsive() {
        this.table.classList.add('table-responsive');
    }
    
    // Export table data
    exportToCSV(filename = 'table-data.csv') {
        const headers = Array.from(this.table.querySelectorAll('th'))
            .map(th => th.textContent.trim())
            .filter(text => text !== ''); // Remove empty headers
        
        const csvContent = [
            headers.join(','),
            ...this.filteredData.map(row => 
                headers.map(header => {
                    const value = row[header] || '';
                    return typeof value === 'string' && value.includes(',') ? `"${value}"` : value;
                }).join(',')
            )
        ].join('\n');
        
        const blob = new Blob([csvContent], { type: 'text/csv' });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        window.URL.revokeObjectURL(url);
    }
    
    // Refresh table
    refresh() {
        this.renderTable();
    }
    
    // Destroy table
    destroy() {
        // Remove event listeners and clean up
        this.table.remove();
    }
}

// Utility functions for data tables
export function createSimpleTable(containerId, data, columns, options = {}) {
    const container = document.getElementById(containerId);
    if (!container) return null;
    
    const table = document.createElement('table');
    table.className = 'table table-striped table-hover';
    table.id = `table-${Date.now()}`;
    
    // Create header
    const thead = document.createElement('thead');
    const headerRow = document.createElement('tr');
    
    columns.forEach(column => {
        const th = document.createElement('th');
        th.textContent = column.title;
        if (column.sortable) {
            th.setAttribute('data-sortable', column.key);
        }
        headerRow.appendChild(th);
    });
    
    thead.appendChild(headerRow);
    table.appendChild(thead);
    
    // Create body
    const tbody = document.createElement('tbody');
    table.appendChild(tbody);
    
    container.appendChild(table);
    
    // Initialize DataTable
    const dataTable = new DataTable(table.id, options);
    dataTable.loadData(data);
    
    return dataTable;
}

export function formatTableData(data, formatters = {}) {
    return data.map(row => {
        const formattedRow = {};
        Object.keys(row).forEach(key => {
            if (formatters[key]) {
                formattedRow[key] = formatters[key](row[key]);
            } else {
                formattedRow[key] = row[key];
            }
        });
        return formattedRow;
    });
}