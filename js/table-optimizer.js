/**
 * Advanced AI Table Optimizer for Restaurant Seating
 * This system applies a constraint satisfaction algorithm to optimize table assignments
 * based on party size, table capacity, and other constraints.
 */
class TableOptimizer {
    constructor() {
        // Restaurant layout (table configurations)
        this.tables = [
            { id: 1, capacity: 2, shape: 'round', x: 50, y: 100, zone: 'window' },
            { id: 2, capacity: 2, shape: 'round', x: 50, y: 200, zone: 'window' },
            { id: 3, capacity: 2, shape: 'round', x: 130, y: 100, zone: 'center' },
            { id: 4, capacity: 2, shape: 'round', x: 130, y: 200, zone: 'center' },
            { id: 5, capacity: 2, shape: 'round', x: 130, y: 300, zone: 'center' },
            
            { id: 6, capacity: 1, shape: 'round', x: 600, y: 70, zone: 'bar' },
            { id: 7, capacity: 1, shape: 'round', x: 650, y: 70, zone: 'bar' },
            { id: 8, capacity: 1, shape: 'round', x: 700, y: 70, zone: 'bar' },
            
            { id: 9, capacity: 4, shape: 'square', x: 250, y: 100, zone: 'center' },
            { id: 10, capacity: 4, shape: 'square', x: 250, y: 200, zone: 'center' },
            { id: 11, capacity: 4, shape: 'square', x: 250, y: 300, zone: 'window' },
            { id: 12, capacity: 4, shape: 'square', x: 400, y: 150, zone: 'center' },
            { id: 13, capacity: 4, shape: 'square', x: 400, y: 250, zone: 'center' },
            
            { id: 14, capacity: 6, shape: 'rectangle', x: 520, y: 150, zone: 'quiet' },
            { id: 15, capacity: 6, shape: 'rectangle', x: 520, y: 250, zone: 'quiet' },
            
            { id: 16, capacity: 8, shape: 'rectangle', x: 350, y: 380, zone: 'large' }
        ];
        
        // Tables that can be combined (adjacent tables)
        this.combinableTables = [
            [1, 2], // Tables 1 and 2 can be combined
            [3, 4], // Tables 3 and 4 can be combined
            [9, 10], // Tables 9 and 10 can be combined
            [12, 13], // Tables 12 and 13 can be combined
            [14, 15] // Tables 14 and 15 can be combined
        ];
        
        // Distance matrix between tables (for proximity constraint)
        this.tableDistances = this.calculateTableDistances();
    }
    
    /**
     * Calculate distances between all tables
     * @returns {Object} A map of table distances
     */
    calculateTableDistances() {
        const distances = {};
        
        for (let i = 0; i < this.tables.length; i++) {
            const t1 = this.tables[i];
            distances[t1.id] = {};
            
            for (let j = 0; j < this.tables.length; j++) {
                const t2 = this.tables[j];
                
                // Euclidean distance
                const distance = Math.sqrt(
                    Math.pow(t1.x - t2.x, 2) + 
                    Math.pow(t1.y - t2.y, 2)
                );
                
                distances[t1.id][t2.id] = distance;
            }
        }
        
        return distances;
    }
    
    /**
     * Find optimal table assignments for multiple reservations
     * This uses a greedy algorithm with constraint satisfaction
     * 
     * @param {Array} reservations Array of reservation objects
     * @param {Array} occupiedTableIds Array of already occupied table IDs
     * @returns {Object} Optimization results
     */
    optimizeTableAssignments(reservations, occupiedTableIds = []) {
        // Sort reservations by party size (largest first)
        // This helps allocate large parties first which is often more constrained
        const sortedReservations = [...reservations].sort((a, b) => b.guests - a.guests);
        
        // Available tables (not occupied)
        const availableTables = this.tables.filter(table => !occupiedTableIds.includes(table.id));
        
        // Results tracking
        const assignments = [];
        const tableCombinations = [];
        let assignedCount = 0;
        let unassignedCount = 0;
        
        // Process each reservation
        sortedReservations.forEach(reservation => {
            // Special case for large groups (more than 10)
            if (reservation.guests > 10) {
                unassignedCount++;
                return;
            }
            
            // Try to find the best single table first
            const bestTable = this.findBestFitTable(reservation, availableTables);
            
            if (bestTable) {
                // Assign a single table
                assignment = this.assignSingleTable(reservation, bestTable, availableTables, assignments);
                assignedCount++;
            } else {
                // Try to find combinable tables
                const tableCombination = this.findBestTableCombination(reservation, availableTables);
                
                if (tableCombination) {
                    // Assign a table combination
                    this.assignCombinedTables(reservation, tableCombination, availableTables, assignments, tableCombinations);
                    assignedCount++;
                } else {
                    // No suitable table found
                    unassignedCount++;
                }
            }
        });
        
        return {
            success: true,
            assignments,
            tableCombinations,
            assignedCount,
            unassignedCount
        };
    }
    
    /**
     * Find the best fitting single table for a reservation
     * 
     * @param {Object} reservation The reservation to seat
     * @param {Array} availableTables List of available tables
     * @returns {Object|null} The best table or null
     */
    findBestFitTable(reservation, availableTables) {
        // Tables with sufficient capacity
        const suitableTables = availableTables.filter(table => table.capacity >= reservation.guests);
        
        if (suitableTables.length === 0) {
            return null;
        }
        
        // First, try to find tables that match preferences (if any)
        if (reservation.preferences) {
            const preferredTables = suitableTables.filter(table => {
                return (
                    (!reservation.preferences.zone || table.zone === reservation.preferences.zone) &&
                    (!reservation.preferences.shape || table.shape === reservation.preferences.shape)
                );
            });
            
            if (preferredTables.length > 0) {
                // Sort by waste (capacity - guests) to find optimal fit
                return preferredTables.sort((a, b) => (a.capacity - reservation.guests) - (b.capacity - reservation.guests))[0];
            }
        }
        
        // If no preferred tables, sort all suitable tables by waste
        return suitableTables.sort((a, b) => (a.capacity - reservation.guests) - (b.capacity - reservation.guests))[0];
    }
    
    /**
     * Assign a single table to a reservation
     * 
     * @param {Object} reservation The reservation
     * @param {Object} table The selected table
     * @param {Array} availableTables List of available tables
     * @param {Array} assignments List of assignments to update
     */
    assignSingleTable(reservation, table, availableTables, assignments) {
        // Update reservation with assigned table
        reservation.tableNumber = table.id.toString();
        
        // Track assignment
        assignments.push({
            date: reservation.date,
            time: reservation.time,
            name: reservation.name,
            guests: reservation.guests,
            tableNumber: table.id,
            type: 'single'
        });
        
        // Remove from available tables
        const tableIndex = availableTables.findIndex(t => t.id === table.id);
        if (tableIndex !== -1) {
            availableTables.splice(tableIndex, 1);
        }
    }
    
    /**
     * Find the best combination of tables for a reservation
     * 
     * @param {Object} reservation Reservation to seat
     * @param {Array} availableTables List of available tables
     * @returns {Array|null} Array of tables to combine or null
     */
    findBestTableCombination(reservation, availableTables) {
        let bestCombination = null;
        let bestWaste = Infinity;
        
        // Check all possible combinations of combinable tables
        this.combinableTables.forEach(combination => {
            // Get the actual table objects for this combination
            const tables = combination.map(id => availableTables.find(t => t.id === id)).filter(t => t !== undefined);
            
            // Must have all tables in the combination available
            if (tables.length === combination.length) {
                // Calculate total capacity
                const totalCapacity = tables.reduce((sum, table) => sum + table.capacity, 0);
                
                // Check if capacity is sufficient and better than previous best
                if (totalCapacity >= reservation.guests) {
                    const waste = totalCapacity - reservation.guests;
                    
                    if (waste < bestWaste) {
                        bestWaste = waste;
                        bestCombination = tables;
                    }
                }
            }
        });
        
        return bestCombination;
    }
    
    /**
     * Assign a combination of tables to a reservation
     * 
     * @param {Object} reservation The reservation
     * @param {Array} tables Tables to combine
     * @param {Array} availableTables List of available tables
     * @param {Array} assignments List of assignments to update
     * @param {Array} tableCombinations List of combinations to update
     */
    assignCombinedTables(reservation, tables, availableTables, assignments, tableCombinations) {
        // Sort tables by ID for consistency
        const sortedTables = [...tables].sort((a, b) => a.id - b.id);
        
        // Use the first table as the main table
        const mainTable = sortedTables[0];
        reservation.tableNumber = mainTable.id.toString();
        
        // Track table IDs used in combination
        const tableIds = sortedTables.map(t => t.id);
        
        // Track assignment
        assignments.push({
            date: reservation.date,
            time: reservation.time,
            name: reservation.name,
            guests: reservation.guests,
            tableNumber: mainTable.id,
            type: 'combined',
            combinedWith: tableIds.filter(id => id !== mainTable.id)
        });
        
        // Track combination
        tableCombinations.push({
            date: reservation.date,
            time: reservation.time,
            service: reservation.service,
            tables: tableIds
        });
        
        // Add notes about combined tables
        if (reservation.notes) {
            reservation.notes += `\nCombined tables: ${tableIds.join(', ')}`;
        } else {
            reservation.notes = `Combined tables: ${tableIds.join(', ')}`;
        }
        
        // Remove all used tables from available tables
        sortedTables.forEach(table => {
            const tableIndex = availableTables.findIndex(t => t.id === table.id);
            if (tableIndex !== -1) {
                availableTables.splice(tableIndex, 1);
            }
        });
    }
    
    /**
     * Get suggested table assignments for a specific date and service
     * 
     * @param {string} date Date in ISO format
     * @param {string} service Service type ('lunch' or 'dinner')
     * @param {Array} allReservations All reservations
     * @returns {Object} Suggestions for the time slot
     */
    getSuggestedAssignments(date, service, allReservations) {
        // Get reservations for this date and service
        const timeSlotReservations = allReservations.filter(r => 
            r.date === date && r.service === service && !r.tableNumber
        );
        
        // Get already occupied tables
        const occupiedTableIds = allReservations
            .filter(r => r.date === date && r.service === service && r.tableNumber)
            .map(r => parseInt(r.tableNumber));
        
        // Run the optimization
        const result = this.optimizeTableAssignments(timeSlotReservations, occupiedTableIds);
        
        return {
            date,
            service,
            ...result
        };
    }
}

// Export the optimizer
window.TableOptimizer = TableOptimizer;
