/**
 * Media Library JS
 * Handles AJAX loading and interactions for the media library
 */

class MediaLibrary {
    constructor(options = {}) {
        this.apiEndpoint = options.apiEndpoint || '/admin/api/media';
        this.csrfToken = options.csrfToken || '';
        this.onSelectCallback = options.onSelect || null;
        this.selectedItems = [];
        this.isMultiSelect = options.isMultiSelect || false;
        this.items = [];
        this.isLoading = false;
        this.isInitialized = false;
    }
    
    /**
     * Initialize the library - only if needed
     */
    init() {
        if (this.isInitialized) {
            return Promise.resolve(this.items);
        }
        
        return this.loadItems();
    }
    
    /**
     * Load media items from API
     */
    async loadItems(params = {}) {
        // Prevent reloading if already in progress
        if (this.isLoading) {
            return Promise.reject(new Error('Loading already in progress'));
        }
        
        this.isLoading = true;
        
        try {
            // Build query string from params
            const queryString = Object.keys(params)
                .filter(key => params[key] !== null && params[key] !== undefined)
                .map(key => `${encodeURIComponent(key)}=${encodeURIComponent(params[key])}`)
                .join('&');
                
            const url = `${this.apiEndpoint}${queryString ? '?' + queryString : ''}`;
            
            const response = await fetch(url, {
                method: 'GET',
                headers: {
                    'Accept': 'application/json',
                }
            });
            
            if (!response.ok) {
                throw new Error(`HTTP error ${response.status}`);
            }
            
            const data = await response.json();
            this.items = data;
            this.isLoading = false;
            this.isInitialized = true;
            return data;
        } catch (error) {
            console.error('Error loading media items:', error);
            this.isLoading = false;
            throw error;
        }
    }
    
    /**
     * Select an item
     */
    selectItem(id) {
        if (this.isMultiSelect) {
            // Toggle selection for multi-select
            const index = this.selectedItems.indexOf(id);
            if (index === -1) {
                this.selectedItems.push(id);
            } else {
                this.selectedItems.splice(index, 1);
            }
        } else {
            // Single select
            this.selectedItems = [id];
        }
        
        // Call callback if provided
        if (this.onSelectCallback) {
            const selectedItems = this.items.filter(item => this.selectedItems.includes(item.id));
            this.onSelectCallback(this.isMultiSelect ? selectedItems : selectedItems[0]);
        }
        
        return this.selectedItems;
    }
    
    /**
     * Clear selection
     */
    clearSelection() {
        this.selectedItems = [];
    }
    
    /**
     * Get selected items
     */
    getSelectedItems() {
        return this.items.filter(item => this.selectedItems.includes(item.id));
    }
}

// Make available globally
window.MediaLibrary = MediaLibrary;
