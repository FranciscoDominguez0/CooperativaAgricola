/**
 * Modular Search System for Agricultural Cooperative Management
 * Supports Production, Sales, and Payments modules
 */

class CooperativeSearch {
    constructor(config) {
        this.config = {
            module: config.module, // 'produccion', 'ventas', 'pagos'
            searchFields: config.searchFields,
            tableBodyId: config.tableBodyId,
            loadFunction: config.loadFunction,
            debounceTime: config.debounceTime || 300,
            minSearchLength: config.minSearchLength || 2,
            ...config
        };
        
        this.searchTimeout = null;
        this.currentSearchTerm = '';
        this.isSearching = false;
        
        this.init();
    }
    
    init() {
        this.bindEvents();
    }
    
    getSearchPlaceholder() {
        const placeholders = {
            'produccion': 'Buscar por cultivo, variedad, calidad o socio...',
            'ventas': 'Buscar por producto, cliente, estado o método de pago...',
            'pagos': 'Buscar por tipo, estado, método de pago o comprobante...'
        };
        return placeholders[this.config.module] || 'Buscar...';
    }
    
    createFilterButtons() {
        const filters = {
            'produccion': [
                { value: 'cultivo', label: 'Cultivo', icon: 'fas fa-seedling' },
                { value: 'variedad', label: 'Variedad', icon: 'fas fa-leaf' },
                { value: 'calidad', label: 'Calidad', icon: 'fas fa-star' },
                { value: 'socio', label: 'Socio', icon: 'fas fa-user' }
            ],
            'ventas': [
                { value: 'producto', label: 'Producto', icon: 'fas fa-box' },
                { value: 'cliente', label: 'Cliente', icon: 'fas fa-user-tie' },
                { value: 'estado', label: 'Estado', icon: 'fas fa-info-circle' },
                { value: 'metodo_pago', label: 'Método Pago', icon: 'fas fa-credit-card' }
            ],
            'pagos': [
                { value: 'tipo', label: 'Tipo', icon: 'fas fa-tag' },
                { value: 'estado', label: 'Estado', icon: 'fas fa-info-circle' },
                { value: 'metodo_pago', label: 'Método Pago', icon: 'fas fa-credit-card' },
                { value: 'comprobante', label: 'Comprobante', icon: 'fas fa-receipt' }
            ]
        };
        
        const moduleFilters = filters[this.config.module] || [];
        
        return moduleFilters.map(filter => `
            <button 
                class="filter-btn" 
                data-filter="${filter.value}"
                title="Filtrar por ${filter.label}"
            >
                <i class="${filter.icon}"></i>
                <span>${filter.label}</span>
            </button>
        `).join('');
    }
    
    bindEvents() {
        const searchInput = document.getElementById(`searchInput_${this.config.module}`);
        const searchClear = document.getElementById(`searchClear_${this.config.module}`);
        const searchReset = document.getElementById(`searchReset_${this.config.module}`);
        const filterButtons = document.querySelectorAll(`#searchFilters_${this.config.module} .filter-btn`);
        
        // Search input events
        if (searchInput) {
            searchInput.addEventListener('input', (e) => {
                this.handleSearch(e.target.value);
            });
            
            searchInput.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    this.performSearch();
                }
            });
            
            searchInput.addEventListener('focus', () => {
                this.animateSearch();
            });
        }
        
        // Clear search button
        if (searchClear) {
            searchClear.addEventListener('click', () => {
                this.clearSearch();
            });
        }
        
        // Reset search button
        if (searchReset) {
            searchReset.addEventListener('click', () => {
                this.resetSearch();
            });
        }
        
        // Filter buttons
        filterButtons.forEach(btn => {
            btn.addEventListener('click', (e) => {
                const filter = e.currentTarget.dataset.filter;
                this.applyFilter(filter);
            });
        });
    }
    
    handleSearch(searchTerm) {
        // Clear previous timeout
        if (this.searchTimeout) {
            clearTimeout(this.searchTimeout);
        }
        
        // Show/hide clear button
        const searchClear = document.getElementById(`searchClear_${this.config.module}`);
        if (searchClear) {
            searchClear.style.display = searchTerm.length > 0 ? 'block' : 'none';
        }
        
        // Debounce search
        this.searchTimeout = setTimeout(() => {
            this.currentSearchTerm = searchTerm;
            this.performSearch();
        }, this.config.debounceTime);
    }
    
    async performSearch() {
        if (this.isSearching) return;
        
        const searchTerm = this.currentSearchTerm.trim();
        
        // Don't search if term is too short
        if (searchTerm.length > 0 && searchTerm.length < this.config.minSearchLength) {
            return;
        }
        
        this.isSearching = true;
        this.showSearchLoading();
        
        try {
            // Call the module's load function with search term
            if (this.config.loadFunction) {
                await this.config.loadFunction(1, searchTerm);
            }
            
            this.updateSearchResults(searchTerm);
        } catch (error) {
            console.error('Search error:', error);
            this.showSearchError();
        } finally {
            this.isSearching = false;
            this.hideSearchLoading();
        }
    }
    
    applyFilter(filter) {
        const searchInput = document.getElementById(`searchInput_${this.config.module}`);
        if (searchInput) {
            // Add filter hint to search input
            const placeholder = this.getSearchPlaceholder();
            searchInput.placeholder = `Filtrando por ${filter}...`;
            searchInput.focus();
            
            // Update filter button states
            document.querySelectorAll(`#searchFilters_${this.config.module} .filter-btn`).forEach(btn => {
                btn.classList.toggle('active', btn.dataset.filter === filter);
            });
        }
    }
    
    clearSearch() {
        const searchInput = document.getElementById(`searchInput_${this.config.module}`);
        if (searchInput) {
            searchInput.value = '';
            searchInput.placeholder = this.getSearchPlaceholder();
            this.currentSearchTerm = '';
            this.performSearch();
        }
    }
    
    resetSearch() {
        this.clearSearch();
        this.hideSearchResults();
        
        // Reset filter buttons
        document.querySelectorAll(`#searchFilters_${this.config.module} .filter-btn`).forEach(btn => {
            btn.classList.remove('active');
        });
    }
    
    updateSearchResults(searchTerm) {
        const resultsInfo = document.getElementById(`searchResults_${this.config.module}`);
        const searchCount = resultsInfo?.querySelector('.search-count');
        
        if (searchTerm && searchTerm.length >= this.config.minSearchLength) {
            if (resultsInfo) {
                resultsInfo.style.display = 'flex';
            }
            if (searchCount) {
                searchCount.textContent = `Buscando: "${searchTerm}"`;
            }
        } else {
            this.hideSearchResults();
        }
    }
    
    hideSearchResults() {
        const resultsInfo = document.getElementById(`searchResults_${this.config.module}`);
        if (resultsInfo) {
            resultsInfo.style.display = 'none';
        }
    }
    
    showSearchLoading() {
        const searchInput = document.getElementById(`searchInput_${this.config.module}`);
        if (searchInput) {
            searchInput.classList.add('searching');
        }
    }
    
    hideSearchLoading() {
        const searchInput = document.getElementById(`searchInput_${this.config.module}`);
        if (searchInput) {
            searchInput.classList.remove('searching');
        }
    }
    
    showSearchError() {
        // Show error toast
        if (typeof showToast === 'function') {
            showToast('Error en la búsqueda. Inténtalo de nuevo.', 'error');
        }
    }
    
    animateSearch() {
        const searchInput = document.getElementById(`searchInput_${this.config.module}`);
        if (searchInput) {
            searchInput.classList.add('search-focus');
            setTimeout(() => {
                searchInput.classList.remove('search-focus');
            }, 1000);
        }
    }
    
    // Public method to get current search term
    getCurrentSearch() {
        return this.currentSearchTerm;
    }
    
    // Public method to set search term programmatically
    setSearch(term) {
        const searchInput = document.getElementById(`searchInput_${this.config.module}`);
        if (searchInput) {
            searchInput.value = term;
            this.currentSearchTerm = term;
            this.performSearch();
        }
    }
}

// Utility functions for search functionality
const SearchUtils = {
    // Highlight search terms in text
    highlightText(text, searchTerm) {
        if (!searchTerm || searchTerm.length < 2) return text;
        
        const regex = new RegExp(`(${searchTerm})`, 'gi');
        return text.replace(regex, '<mark class="search-highlight">$1</mark>');
    },
    
    // Get search suggestions based on module
    getSearchSuggestions(module, data) {
        const suggestions = {
            'produccion': this.getProductionSuggestions(data),
            'ventas': this.getSalesSuggestions(data),
            'pagos': this.getPaymentsSuggestions(data)
        };
        return suggestions[module] || [];
    },
    
    getProductionSuggestions(data) {
        const suggestions = new Set();
        data.forEach(item => {
            if (item.cultivo) suggestions.add(item.cultivo);
            if (item.variedad) suggestions.add(item.variedad);
            if (item.calidad) suggestions.add(item.calidad);
        });
        return Array.from(suggestions);
    },
    
    getSalesSuggestions(data) {
        const suggestions = new Set();
        data.forEach(item => {
            if (item.producto) suggestions.add(item.producto);
            if (item.cliente) suggestions.add(item.cliente);
            if (item.estado) suggestions.add(item.estado);
            if (item.metodo_pago) suggestions.add(item.metodo_pago);
        });
        return Array.from(suggestions);
    },
    
    getPaymentsSuggestions(data) {
        const suggestions = new Set();
        data.forEach(item => {
            if (item.tipo) suggestions.add(item.tipo);
            if (item.estado) suggestions.add(item.estado);
            if (item.metodo_pago) suggestions.add(item.metodo_pago);
            if (item.numero_comprobante) suggestions.add(item.numero_comprobante);
        });
        return Array.from(suggestions);
    }
};

// Export for module use
window.CooperativeSearch = CooperativeSearch;
window.SearchUtils = SearchUtils;
