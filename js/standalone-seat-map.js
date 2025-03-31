/**
 * Standalone Seat Map - This is a simplified version that will run after all other scripts
 * and ensure the seat map is visible regardless of any other issues
 */
window.addEventListener('load', function() {
    console.log("Standalone seat map initializing...");
    
    setTimeout(function() {
        try {
            // Make sure this only runs on the reservation page
            const container = document.getElementById('seatingMapContainer');
            if (!container) return;
            
            console.log("Applying standalone seat map...");
            
            // Force visibility
            container.style.display = 'block';
            container.style.visibility = 'visible';
            
            const seatingMap = document.getElementById('seatingMap');
            if (seatingMap) {
                seatingMap.style.display = 'block';
                seatingMap.style.visibility = 'visible';
                seatingMap.style.height = '500px';
            }
            
            const restaurantLayout = document.getElementById('restaurantLayout');
            if (restaurantLayout) {
                restaurantLayout.style.display = 'block';
                restaurantLayout.style.visibility = 'visible';
                restaurantLayout.style.height = '100%';
                restaurantLayout.style.width = '100%';
            }
            
            // Check if we have tables already
            if (restaurantLayout && !restaurantLayout.querySelector('.table')) {
                createBasicTables(restaurantLayout);
            }
            
            // Hide loading indicator
            const loadingStatus = document.getElementById('seatMapLoading');
            if (loadingStatus) {
                loadingStatus.style.display = 'none';
            }
            
            // Make sure optimize button is visible
            const optimizeBtn = document.getElementById('optimizeTablesBtn');
            if (optimizeBtn) {
                optimizeBtn.style.display = 'block';
                optimizeBtn.style.width = '100%';
                optimizeBtn.style.marginTop = '15px';
            }
            
            console.log("Standalone seat map successfully applied");
        } catch (err) {
            console.error("Error in standalone seat map:", err);
        }
    }, 1500); // Delay execution to let other scripts finish
    
    // Create some basic tables as a fallback
    function createBasicTables(layout) {
        console.log("Creating basic table layout...");
        
        // Create a few basic tables
        const tableConfigs = [
            { id: 1, x: 50, y: 100, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 2, x: 50, y: 200, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 9, x: 250, y: 100, width: 80, height: 80, capacity: 4, shape: 'square' },
            { id: 14, x: 520, y: 150, width: 120, height: 80, capacity: 6, shape: 'rectangle' },
            { id: 16, x: 350, y: 380, width: 140, height: 80, capacity: 8, shape: 'rectangle' }
        ];
        
        tableConfigs.forEach(config => {
            try {
                const tableEl = document.createElement('div');
                tableEl.className = `table available ${config.shape}`;
                tableEl.setAttribute('data-id', config.id);
                tableEl.setAttribute('data-capacity', config.capacity);
                tableEl.style.left = `${config.x}px`;
                tableEl.style.top = `${config.y}px`;
                tableEl.style.width = `${config.width}px`;
                tableEl.style.height = `${config.height}px`;
                
                // Add table number
                const tableNumber = document.createElement('div');
                tableNumber.className = 'table-number';
                tableNumber.textContent = config.id;
                tableEl.appendChild(tableNumber);
                
                // Add capacity indicator
                const tableCapacity = document.createElement('div');
                tableCapacity.className = 'table-capacity';
                tableCapacity.textContent = `${config.capacity}p`;
                tableEl.appendChild(tableCapacity);
                
                // Add simple click handler
                tableEl.addEventListener('click', function() {
                    // Remove selected class from all tables
                    document.querySelectorAll('.table.selected').forEach(t => {
                        t.classList.remove('selected');
                    });
                    
                    // Add selected class to this table
                    this.classList.add('selected');
                    
                    // Update hidden input
                    const tableInput = document.getElementById('tableNumber');
                    if (tableInput) {
                        tableInput.value = this.getAttribute('data-id');
                    }
                });
                
                layout.appendChild(tableEl);
            } catch (err) {
                console.error("Error creating basic table:", err);
            }
        });
    }
});
