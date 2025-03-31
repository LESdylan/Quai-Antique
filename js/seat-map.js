document.addEventListener('DOMContentLoaded', function() {
    console.log("Seat map script starting...");
    
    // Initialize variables
    let scale = 1;
    let selectedTable = null;
    let restaurantLayout = null;
    let seatingMap = null;
    let tables = [];
    let occupiedTables = [];
    
    // Restaurant layout configuration - moved outside the try block so it's accessible to all functions
    const restaurantConfig = {
        tables: [
            // Tables for 2 people
            { id: 1, x: 50, y: 100, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 2, x: 50, y: 200, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 3, x: 130, y: 100, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 4, x: 130, y: 200, width: 60, height: 60, capacity: 2, shape: 'round' },
            { id: 5, x: 130, y: 300, width: 60, height: 60, capacity: 2, shape: 'round' },
            
            // Tables for 1 person (bar seats)
            { id: 6, x: 600, y: 70, width: 30, height: 30, capacity: 1, shape: 'round' },
            { id: 7, x: 650, y: 70, width: 30, height: 30, capacity: 1, shape: 'round' },
            { id: 8, x: 700, y: 70, width: 30, height: 30, capacity: 1, shape: 'round' },
            
            // Tables for 4 people
            { id: 9, x: 250, y: 100, width: 80, height: 80, capacity: 4, shape: 'square' },
            { id: 10, x: 250, y: 200, width: 80, height: 80, capacity: 4, shape: 'square' },
            { id: 11, x: 250, y: 300, width: 80, height: 80, capacity: 4, shape: 'square' },
            { id: 12, x: 400, y: 150, width: 80, height: 80, capacity: 4, shape: 'square' },
            { id: 13, x: 400, y: 250, width: 80, height: 80, capacity: 4, shape: 'square' },
            
            // Tables for 6 people
            { id: 14, x: 520, y: 150, width: 120, height: 80, capacity: 6, shape: 'rectangle' },
            { id: 15, x: 520, y: 250, width: 120, height: 80, capacity: 6, shape: 'rectangle' },
            
            // Table for 8 people
            { id: 16, x: 350, y: 380, width: 140, height: 80, capacity: 8, shape: 'rectangle' }
        ],
        features: [
            // Kitchen, bar, walls, etc.
            { type: 'kitchen', x: 670, y: 300, width: 120, height: 180, label: 'Cuisine' },
            { type: 'bar', x: 600, y: 100, width: 150, height: 50, label: 'Bar' },
            { type: 'wall', x: 0, y: 0, width: 10, height: 500 },
            { type: 'wall', x: 0, y: 0, width: 800, height: 10 },
            { type: 'wall', x: 790, y: 0, width: 10, height: 500 },
            { type: 'wall', x: 0, y: 490, width: 800, height: 10 },
            { type: 'entrance', x: 400, y: 0, width: 100, height: 30, label: 'Entrée' }
        ]
    };
    
    try {
        // Get all necessary elements
        const mapContainer = document.getElementById('seatingMapContainer');
        
        // Exit if we're not on the reservation page
        if (!mapContainer) {
            console.log("Seat map container not found, exiting");
            return;
        }
        
        console.log("Seat map container found, initializing");
        
        // Force visible styles on the container
        mapContainer.style.display = 'block';
        mapContainer.style.visibility = 'visible';
        
        seatingMap = document.getElementById('seatingMap');
        if (!seatingMap) {
            console.error("Seating map element not found");
            return;
        }
        
        // Force visible styles
        seatingMap.style.display = 'block';
        seatingMap.style.visibility = 'visible';
        
        restaurantLayout = document.getElementById('restaurantLayout');
        if (!restaurantLayout) {
            console.error("Restaurant layout element not found");
            return;
        }
        
        // Force visible styles
        restaurantLayout.style.display = 'block';
        restaurantLayout.style.visibility = 'visible';
        
        const zoomInBtn = document.getElementById('zoomIn');
        const zoomOutBtn = document.getElementById('zoomOut');
        const resetBtn = document.getElementById('resetView');
        const guestsInput = document.getElementById('guests');
        const tableInput = document.getElementById('tableNumber');
        const largeGroupWarning = document.getElementById('largeGroupWarning');
        
        // Make the initializeSeatingMap call async with a short delay
        // This helps ensure all the DOM is fully prepared
        setTimeout(function() {
            try {
                initializeSeatingMap();
                console.log("Seat map initialized successfully");
                
                // Set up event listeners for service changes
                const serviceSelect = document.getElementById('service');
                const dateInput = document.getElementById('date');
                const timeSelect = document.getElementById('time');
                
                if (serviceSelect) {
                    serviceSelect.addEventListener('change', loadOccupiedTables);
                }
                
                if (dateInput) {
                    dateInput.addEventListener('change', loadOccupiedTables);
                }
                
                // Initialize table optimizer
                const optimizeBtn = document.getElementById('optimizeTablesBtn');
                if (optimizeBtn) {
                    optimizeBtn.addEventListener('click', suggestTableArrangement);
                }
                
                // Setup guests input event 
                if (guestsInput) {
                    guestsInput.addEventListener('change', function() {
                        const guests = parseInt(this.value);
                        
                        // Show warning for large groups
                        if (guests > 10) {
                            if (largeGroupWarning) {
                                largeGroupWarning.classList.add('visible');
                            }
                            // Disable table selection for large groups
                            tables.forEach(table => {
                                table.classList.add('occupied');
                            });
                            
                            // Clear selected table
                            if (selectedTable) {
                                selectedTable.classList.remove('selected');
                                selectedTable = null;
                                if (tableInput) {
                                    tableInput.value = '';
                                }
                            }
                        } else {
                            if (largeGroupWarning) {
                                largeGroupWarning.classList.remove('visible');
                            }
                            updateTables();
                        }
                    });
                }
                
                // Add event listeners for zoom controls
                if (zoomInBtn) {
                    zoomInBtn.addEventListener('click', function() {
                        scale = Math.min(2, scale + 0.1);
                        restaurantLayout.style.transform = `scale(${scale})`;
                    });
                }
                
                if (zoomOutBtn) {
                    zoomOutBtn.addEventListener('click', function() {
                        scale = Math.max(0.5, scale - 0.1);
                        restaurantLayout.style.transform = `scale(${scale})`;
                    });
                }
                
                if (resetBtn) {
                    resetBtn.addEventListener('click', function() {
                        scale = 1;
                        restaurantLayout.style.transform = `scale(${scale})`;
                    });
                }
            } catch (err) {
                console.error("Error initializing seat map:", err);
            }
        }, 200); // Short delay to ensure DOM is ready
        
    } catch (err) {
        console.error("Critical error in seat map initialization:", err);
    }
    
    // Check for existing reservations to mark occupied tables
    function loadOccupiedTables() {
        try {
            const date = document.getElementById('date').value;
            const service = document.getElementById('service').value;
            
            // Simulate occupied tables based on date and service
            const existingReservations = JSON.parse(localStorage.getItem('reservations') || '[]');
            
            const selectedDate = new Date(date);
            const day = selectedDate.getDay(); // 0 = Sunday, 1 = Monday, etc.
            
            // Reset occupied tables
            occupiedTables = [];
            
            if (service === 'lunch') {
                if (day === 0 || day === 6) { // Saturday or Sunday
                    occupiedTables = [2, 5, 9, 13, 15];
                } else {
                    occupiedTables = [3, 10];
                }
            } else if (service === 'dinner') {
                if (day === 5 || day === 6) { // Friday or Saturday
                    occupiedTables = [1, 4, 7, 9, 11, 14, 16];
                } else {
                    occupiedTables = [2, 6, 12];
                }
            }
            
            existingReservations.forEach(reservation => {
                if (reservation.date === date && reservation.service === service && reservation.tableNumber) {
                    occupiedTables.push(parseInt(reservation.tableNumber));
                }
            });
            
            occupiedTables = [...new Set(occupiedTables)];
            
            updateTables();
        } catch (err) {
            console.error("Error loading occupied tables:", err);
        }
    }
    
    // Initialize the seating map
    function initializeSeatingMap() {
        console.log("Creating restaurant features...");
        
        // Access restaurantConfig directly - it's now in the correct scope
        restaurantConfig.features.forEach(feature => {
            try {
                const featureEl = document.createElement('div');
                featureEl.className = `feature ${feature.type}`;
                featureEl.style.left = `${feature.x}px`;
                featureEl.style.top = `${feature.y}px`;
                featureEl.style.width = `${feature.width}px`;
                featureEl.style.height = `${feature.height}px`;
                
                if (feature.label) {
                    featureEl.textContent = feature.label;
                }
                
                restaurantLayout.appendChild(featureEl);
            } catch (err) {
                console.error("Error creating feature:", err);
            }
        });
        
        console.log("Creating tables...");
        
        restaurantConfig.tables.forEach(table => {
            try {
                const tableEl = document.createElement('div');
                tableEl.className = `table available ${table.shape}`;
                tableEl.setAttribute('data-id', table.id);
                tableEl.setAttribute('data-capacity', table.capacity);
                tableEl.style.left = `${table.x}px`;
                tableEl.style.top = `${table.y}px`;
                tableEl.style.width = `${table.width}px`;
                tableEl.style.height = `${table.height}px`;
                
                const tableNumber = document.createElement('div');
                tableNumber.className = 'table-number';
                tableNumber.textContent = table.id;
                tableEl.appendChild(tableNumber);
                
                const tableCapacity = document.createElement('div');
                tableCapacity.className = 'table-capacity';
                tableCapacity.textContent = `${table.capacity}p`;
                tableEl.appendChild(tableCapacity);
                
                addChairsToTable(tableEl, table);
                
                tableEl.addEventListener('click', function() {
                    if (this.classList.contains('occupied')) return;
                    
                    if (!guestsInput) {
                        console.error("Guests input not found");
                        return;
                    }
                    
                    const guests = parseInt(guestsInput.value);
                    const tableCapacity = parseInt(this.getAttribute('data-capacity'));
                    
                    if (guests > tableCapacity) {
                        alert(`Cette table peut accueillir au maximum ${tableCapacity} personnes. Veuillez choisir une table plus grande ou plusieurs tables.`);
                        return;
                    }
                    
                    if (selectedTable) {
                        selectedTable.classList.remove('selected');
                    }
                    
                    this.classList.add('selected');
                    selectedTable = this;
                    
                    if (tableInput) {
                        tableInput.value = this.getAttribute('data-id');
                    }
                });
                
                tables.push(tableEl);
                restaurantLayout.appendChild(tableEl);
            } catch (err) {
                console.error("Error creating table:", err);
            }
        });
        
        console.log(`Created ${tables.length} tables successfully`);
    }
    
    function addChairsToTable(tableEl, tableConfig) {
        const width = tableConfig.width;
        const height = tableConfig.height;
        
        if (tableConfig.shape === 'round') {
            const radius = width / 2;
            const chairPositions = [];
            
            for (let i = 0; i < tableConfig.capacity; i++) {
                const angle = (i / tableConfig.capacity) * 2 * Math.PI;
                chairPositions.push({
                    x: radius + Math.cos(angle) * (radius + 10),
                    y: radius + Math.sin(angle) * (radius + 10)
                });
            }
            
            chairPositions.forEach(pos => {
                const chair = document.createElement('div');
                chair.className = 'chair';
                chair.style.left = `${pos.x - 10}px`;
                chair.style.top = `${pos.y - 10}px`;
                tableEl.appendChild(chair);
            });
        } else if (tableConfig.shape === 'square') {
            for (let i = 0; i < Math.min(2, tableConfig.capacity); i++) {
                const chair = document.createElement('div');
                chair.className = 'chair';
                chair.style.left = `${width / 3 * (i + 1) - 10}px`;
                chair.style.top = '-15px';
                tableEl.appendChild(chair);
            }
            
            if (tableConfig.capacity > 2) {
                const chair = document.createElement('div');
                chair.className = 'chair';
                chair.style.left = `${width - 5}px`;
                chair.style.top = `${height / 2 - 10}px`;
                tableEl.appendChild(chair);
            }
            
            for (let i = 0; i < Math.min(2, Math.max(0, tableConfig.capacity - 3)); i++) {
                const chair = document.createElement('div');
                chair.className = 'chair';
                chair.style.left = `${width / 3 * (i + 1) - 10}px`;
                chair.style.top = `${height - 5}px`;
                tableEl.appendChild(chair);
            }
            
            if (tableConfig.capacity > 3) {
                const chair = document.createElement('div');
                chair.className = 'chair';
                chair.style.left = '-15px';
                chair.style.top = `${height / 2 - 10}px`;
                tableEl.appendChild(chair);
            }
        } else if (tableConfig.shape === 'rectangle') {
            const chairsPerLongSide = Math.ceil(tableConfig.capacity / 2);
            const spacing = width / (chairsPerLongSide + 1);
            
            for (let i = 0; i < chairsPerLongSide; i++) {
                const chair = document.createElement('div');
                chair.className = 'chair';
                chair.style.left = `${spacing * (i + 1) - 10}px`;
                chair.style.top = '-15px';
                tableEl.appendChild(chair);
            }
            
            for (let i = 0; i < (tableConfig.capacity - chairsPerLongSide); i++) {
                const chair = document.createElement('div');
                chair.className = 'chair';
                chair.style.left = `${spacing * (i + 1) - 10}px`;
                chair.style.top = `${height - 5}px`;
                tableEl.appendChild(chair);
            }
        }
    }
    
    function updateTables() {
        const guests = parseInt(guestsInput.value);
        
        tables.forEach(table => {
            const tableId = parseInt(table.getAttribute('data-id'));
            const tableCapacity = parseInt(table.getAttribute('data-capacity'));
            
            table.className = `table ${table.getAttribute('data-shape') || ''}`;
            
            if (occupiedTables.includes(tableId)) {
                table.classList.add('occupied');
            } else {
                table.classList.add('available');
            }
            
            if (tableCapacity < guests) {
                table.style.opacity = '0.5';
            } else {
                table.style.opacity = '1';
            }
        });
        
        if (selectedTable) {
            const tableId = parseInt(selectedTable.getAttribute('data-id'));
            const tableCapacity = parseInt(selectedTable.getAttribute('data-capacity'));
            
            if (occupiedTables.includes(tableId) || tableCapacity < guests) {
                selectedTable.classList.remove('selected');
                selectedTable = null;
                tableInput.value = '';
            } else {
                selectedTable.classList.add('selected');
            }
        }
    }
    
    function suggestTableArrangement() {
        // Get number of guests
        const guests = parseInt(document.getElementById('guests').value);
        if (isNaN(guests) || guests <= 0) {
            alert("Veuillez spécifier un nombre de personnes valide.");
            return;
        }
        
        // Get available tables (not occupied and with sufficient capacity)
        const availableTables = tables.filter(table => {
            const tableId = parseInt(table.getAttribute('data-id'));
            const tableCapacity = parseInt(table.getAttribute('data-capacity'));
            return !occupiedTables.includes(tableId) && tableCapacity >= guests;
        });
        
        if (availableTables.length === 0) {
            alert("Désolé, aucune table disponible ne correspond à votre demande. Veuillez changer le nombre de personnes ou contacter le restaurant par téléphone.");
            return;
        }
        
        // Find the table with the optimal capacity (minimal waste)
        availableTables.sort((a, b) => {
            const capacityA = parseInt(a.getAttribute('data-capacity'));
            const capacityB = parseInt(b.getAttribute('data-capacity'));
            return (capacityA - guests) - (capacityB - guests);
        });
        
        const suggestedTable = availableTables[0];
        const tableId = suggestedTable.getAttribute('data-id');
        const tableCapacity = suggestedTable.getAttribute('data-capacity');
        
        // Display suggestion
        const suggestionContent = document.getElementById('suggestionContent');
        const tableSuggestion = document.getElementById('tableSuggestion');
        
        if (suggestionContent && tableSuggestion) {
            suggestionContent.innerHTML = `
                <p>Pour ${guests} personne${guests > 1 ? 's' : ''}, nous vous suggérons la <strong>table ${tableId}</strong> qui peut accueillir jusqu'à ${tableCapacity} personnes.</p>
                <p>Cette table offre le meilleur confort pour votre groupe.</p>
            `;
            
            tableSuggestion.style.display = 'block';
            
            // Setup accept button
            const acceptButton = document.getElementById('acceptSuggestion');
            if (acceptButton) {
                acceptButton.onclick = function() {
                    // Deselect current table if any
                    if (selectedTable) {
                        selectedTable.classList.remove('selected');
                    }
                    
                    // Select suggested table
                    suggestedTable.classList.add('selected');
                    selectedTable = suggestedTable;
                    
                    // Update hidden input
                    const tableInput = document.getElementById('tableNumber');
                    if (tableInput) {
                        tableInput.value = tableId;
                    }
                    
                    // Hide suggestion
                    tableSuggestion.style.display = 'none';
                };
            }
            
            // Setup decline button
            const declineButton = document.getElementById('declineSuggestion');
            if (declineButton) {
                declineButton.onclick = function() {
                    tableSuggestion.style.display = 'none';
                };
            }
        }
    }
});
