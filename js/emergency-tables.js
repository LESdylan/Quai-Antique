/**
 * Emergency script to ensure tables are visible in the reservation page
 * This script creates visual tables directly regardless of other scripts
 */
(function() {
    // Run immediately and again after window load
    createEmergencyTables();
    
    window.addEventListener('load', function() {
        // Run multiple times to make sure it catches
        setTimeout(createEmergencyTables, 500);
        setTimeout(createEmergencyTables, 1000);
        setTimeout(createEmergencyTables, 2000);
    });
    
    function createEmergencyTables() {
        console.log("🚨 Executing emergency tables script");
        
        // Force container visible first
        const container = document.getElementById('seatingMapContainer');
        if (!container) {
            console.log("❌ Container not found!");
            return;
        }
        
        // Add a visible indicator that the map is being processed
        if (!document.getElementById('seatingMapIndicator')) {
            const indicator = document.createElement('div');
            indicator.id = 'seatingMapIndicator';
            indicator.className = 'seating-map-indicator';
            indicator.innerHTML = 'Plan de salle en cours de chargement... <i class="fas fa-sync fa-spin"></i>';
            container.insertBefore(indicator, container.firstChild);
        }
            
        // Ensure the layout exists and is visible
        let seatingMap = document.getElementById('seatingMap');
        let layout = document.getElementById('restaurantLayout');
        
        // Force styles
        container.style.display = 'block';
        container.style.visibility = 'visible';
        container.style.minHeight = '550px';
        container.style.border = '2px solid #e5e0d9';
        container.style.padding = '20px';
        container.style.backgroundColor = '#f9f7f4';
        
        if (!seatingMap) {
            console.log("❌ Seating map not found, creating one");
            seatingMap = document.createElement('div');
            seatingMap.id = 'seatingMap';
            seatingMap.className = 'seating-map';
            container.appendChild(seatingMap);
        }
        
        seatingMap.style.display = 'block';
        seatingMap.style.visibility = 'visible';
        seatingMap.style.height = '500px';
        seatingMap.style.backgroundColor = '#fff';
        seatingMap.style.border = '1px solid #ddd';
        
        if (!layout) {
            console.log("❌ Restaurant layout not found, creating one");
            layout = document.createElement('div');
            layout.id = 'restaurantLayout';
            layout.className = 'restaurant-layout';
            seatingMap.appendChild(layout);
        }
        
        layout.style.display = 'block';
        layout.style.visibility = 'visible';
        layout.style.position = 'relative';
        layout.style.width = '100%';
        layout.style.height = '100%';
        
        // Check if we already have tables
        const existingTables = layout.querySelectorAll('.table');
        if (existingTables.length > 0) {
            console.log(`✓ ${existingTables.length} tables already exist, making them visible`);
            existingTables.forEach(table => {
                table.style.display = 'flex';
                table.style.visibility = 'visible';
                table.style.opacity = '1';
            });
            
            // Update indicator
            const indicator = document.getElementById('seatingMapIndicator');
            if (indicator) {
                indicator.innerHTML = `✓ ${existingTables.length} tables disponibles pour la réservation`;
                indicator.style.backgroundColor = '#d4edda';
                indicator.style.color = '#155724';
                indicator.style.border = '1px solid #c3e6cb';
            }
            
            return;
        }
        
        // Define simple table configurations
        const tableConfigs = [
            { id: 1, x: 50, y: 50, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 2, x: 50, y: 150, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 3, x: 50, y: 250, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 4, x: 150, y: 50, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 5, x: 150, y: 150, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 6, x: 150, y: 250, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 9, x: 250, y: 50, width: 80, height: 80, capacity: 4, shape: 'square' },
            { id: 10, x: 250, y: 180, width: 80, height: 80, capacity: 4, shape: 'square' },
            { id: 14, x: 400, y: 100, width: 120, height: 80, capacity: 6, shape: 'rectangle' },
            { id: 16, x: 400, y: 230, width: 140, height: 80, capacity: 8, shape: 'rectangle' }
        ];
        
        // Create tables using direct DOM manipulation
        tableConfigs.forEach(config => {
            const tableEl = document.createElement('div');
            tableEl.className = `table available ${config.shape}`;
            tableEl.setAttribute('data-id', config.id);
            tableEl.setAttribute('data-capacity', config.capacity);
            tableEl.style.left = `${config.x}px`;
            tableEl.style.top = `${config.y}px`;
            tableEl.style.width = `${config.width}px`;
            tableEl.style.height = `${config.height}px`;
            tableEl.style.backgroundColor = 'rgba(200, 169, 126, 0.7)';
            tableEl.style.border = '2px solid rgba(200, 169, 126, 0.9)';
            tableEl.style.display = 'flex';
            tableEl.style.justifyContent = 'center';
            tableEl.style.alignItems = 'center';
            tableEl.style.position = 'absolute';
            tableEl.style.cursor = 'pointer';
            
            // Make round tables actually round
            if (config.shape === 'round') {
                tableEl.style.borderRadius = '50%';
            }
            
            // Add table number
            const tableNumber = document.createElement('div');
            tableNumber.className = 'table-number';
            tableNumber.textContent = config.id;
            tableNumber.style.fontWeight = 'bold';
            tableNumber.style.color = '#333';
            tableNumber.style.textShadow = '0 0 2px #fff';
            tableEl.appendChild(tableNumber);
            
            // Add capacity indicator as a small badge
            const tableCapacity = document.createElement('div');
            tableCapacity.className = 'table-capacity';
            tableCapacity.textContent = `${config.capacity}p`;
            tableCapacity.style.position = 'absolute';
            tableCapacity.style.bottom = '2px';
            tableCapacity.style.right = '2px';
            tableCapacity.style.fontSize = '10px';
            tableCapacity.style.padding = '2px 4px';
            tableCapacity.style.backgroundColor = 'rgba(255,255,255,0.7)';
            tableCapacity.style.borderRadius = '3px';
            tableEl.appendChild(tableCapacity);
            
            // Add click handler to select table
            tableEl.addEventListener('click', function() {
                // Remove selected class from all tables
                layout.querySelectorAll('.table.selected').forEach(t => {
                    t.classList.remove('selected');
                    t.style.backgroundColor = 'rgba(200, 169, 126, 0.7)';
                    t.style.border = '2px solid rgba(200, 169, 126, 0.9)';
                });
                
                // Add selected class to this table
                this.classList.add('selected');
                this.style.backgroundColor = 'var(--secondary-color)';
                this.style.border = '2px solid var(--primary-color)';
                
                // Update hidden input
                const tableInput = document.getElementById('tableNumber');
                if (tableInput) {
                    tableInput.value = this.getAttribute('data-id');
                }
            });
            
            layout.appendChild(tableEl);
        });
        
        // Update indicator
        const indicator = document.getElementById('seatingMapIndicator');
        if (indicator) {
            indicator.innerHTML = `✓ ${tableConfigs.length} tables disponibles pour la réservation`;
            indicator.style.backgroundColor = '#d4edda';
            indicator.style.color = '#155724';
            indicator.style.border = '1px solid #c3e6cb';
        }
        
        console.log(`✓ Created ${tableConfigs.length} emergency tables`);
    }
})();
