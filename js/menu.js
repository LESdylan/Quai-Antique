document.addEventListener('DOMContentLoaded', function() {
    // Filter functionality for menu items
    const filterButtons = document.querySelectorAll('.filter-btn');
    const menuItems = document.querySelectorAll('.menu-item, .menu-card');
    const menuCategories = document.querySelectorAll('.menu-category');
    
    filterButtons.forEach(button => {
        button.addEventListener('click', function() {
            // Update active button
            filterButtons.forEach(btn => btn.classList.remove('active'));
            this.classList.add('active');
            
            const filter = this.getAttribute('data-filter');
            
            // Show all categories first
            menuCategories.forEach(category => {
                category.style.display = 'block';
            });
            
            // If filter is 'all', show everything
            if (filter === 'all') {
                menuItems.forEach(item => {
                    item.style.display = 'block';
                    
                    // Re-trigger the reveal animation
                    setTimeout(() => {
                        item.classList.remove('revealed');
                        void item.offsetWidth; // Force reflow
                        item.classList.add('revealed');
                    }, 10);
                });
            } else {
                // Otherwise, filter items
                menuItems.forEach(item => {
                    const category = item.getAttribute('data-category');
                    
                    if (category === filter) {
                        item.style.display = 'block';
                        
                        // Re-trigger the reveal animation
                        setTimeout(() => {
                            item.classList.remove('revealed');
                            void item.offsetWidth; // Force reflow
                            item.classList.add('revealed');
                        }, 10);
                    } else {
                        item.style.display = 'none';
                    }
                });
                
                // Hide empty categories
                menuCategories.forEach(category => {
                    const categoryId = category.id;
                    
                    if (categoryId && categoryId !== filter) {
                        category.style.display = 'none';
                    }
                });
            }
        });
    });
    
    // Initialize steam effects for hot dishes
    const steamEffects = document.querySelectorAll('.steam-effect');
    if (steamEffects.length > 0) {
        steamEffects.forEach(effect => {
            setInterval(() => {
                const span = document.createElement('span');
                effect.appendChild(span);
                
                setTimeout(() => {
                    span.remove();
                }, 4000);
            }, 2000);
        });
    }
});
