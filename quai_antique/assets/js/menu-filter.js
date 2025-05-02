/**
 * Menu Filter System
 * Allows filtering menu items by category, allergen, etc.
 */
document.addEventListener('DOMContentLoaded', () => {
    const menuContainer = document.querySelector('#menu-container');
    if (!menuContainer) return;
    
    // Category filters
    document.querySelectorAll('.menu-filter').forEach(filter => {
        filter.addEventListener('click', (event) => {
            event.preventDefault();
            
            // Remove active class from all filters
            document.querySelectorAll('.menu-filter').forEach(btn => {
                btn.classList.remove('active');
            });
            
            // Add active class to the clicked filter
            filter.classList.add('active');
            
            const category = filter.dataset.category;
            filterMenuItems(category);
        });
    });
    
    // Allergen filter
    const allergenSelect = document.querySelector('#allergen-filter');
    if (allergenSelect) {
        allergenSelect.addEventListener('change', () => {
            const allergen = allergenSelect.value;
            filterMenuItemsByAllergen(allergen);
        });
    }
    
    // Seasonal filter
    const seasonalCheckbox = document.querySelector('#seasonal-filter');
    if (seasonalCheckbox) {
        seasonalCheckbox.addEventListener('change', () => {
            filterMenuItems();
        });
    }
    
    function filterMenuItems(category = 'all') {
        const seasonal = seasonalCheckbox && seasonalCheckbox.checked;
        const allergen = allergenSelect ? allergenSelect.value : '';
        
        const url = new URL(window.location.origin + '/menu/filter');
        if (category && category !== 'all') {
            url.searchParams.append('category', category);
        }
        if (allergen) {
            url.searchParams.append('allergen', allergen);
        }
        if (seasonal) {
            url.searchParams.append('seasonal', '1');
        }
        
        fetch(url)
            .then(response => response.text())
            .then(html => {
                document.querySelector('#menu-items-container').innerHTML = html;
                initializeMenuItemEffects();
            })
            .catch(error => {
                console.error('Error fetching filtered menu items:', error);
            });
    }
    
    function filterMenuItemsByAllergen(allergen) {
        const activeCategory = document.querySelector('.menu-filter.active');
        const category = activeCategory ? activeCategory.dataset.category : 'all';
        
        filterMenuItems(category);
    }
    
    function initializeMenuItemEffects() {
        // Add animation or effects to menu items after they're loaded
        document.querySelectorAll('.menu-item').forEach(item => {
            item.classList.add('menu-item-visible');
        });
    }
    
    // Initialize menu effects on page load
    initializeMenuItemEffects();
});
