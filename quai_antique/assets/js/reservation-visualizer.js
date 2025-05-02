/**
 * Restaurant Floor Plan Visualizer
 * Allows users to see and select available tables during reservation
 */
class ReservationVisualizer {
    constructor(element, options = {}) {
        this.element = document.querySelector(element);
        this.tables = options.tables || [];
        this.selectedDate = options.initialDate || new Date();
        this.selectedTime = options.initialTime || '19:00';
        this.maxGuests = options.maxGuests || 8;
        
        // Initialize the visualizer
        this.init();
    }
    
    init() {
        this.renderFloorPlan();
        this.bindEvents();
        this.fetchAvailability();
    }
    
    renderFloorPlan() {
        // Create SVG or Canvas element for floor plan
        const floorPlan = document.createElement('div');
        floorPlan.classList.add('floor-plan');
        
        // For each table, create a representation
        this.tables.forEach(table => {
            const tableElement = document.createElement('div');
            tableElement.classList.add('table');
            tableElement.dataset.tableId = table.id;
            tableElement.dataset.seats = table.seats;
            tableElement.style.left = `${table.x}%`;
            tableElement.style.top = `${table.y}%`;
            
            // Add visual indicator for table size
            tableElement.innerHTML = `
                <svg viewBox="0 0 100 100" class="table-shape ${table.shape}">
                    <circle cx="50" cy="50" r="45" />
                </svg>
                <span class="table-number">${table.number}</span>
                <span class="table-seats">${table.seats} seats</span>
            `;
            
            floorPlan.appendChild(tableElement);
        });
        
        this.element.appendChild(floorPlan);
    }
    
    bindEvents() {
        // Handle table selection
        this.element.addEventListener('click', (e) => {
            const tableElement = e.target.closest('.table');
            if (!tableElement) return;
            
            // Don't allow selecting unavailable tables
            if (tableElement.classList.contains('unavailable')) {
                this.showMessage('This table is not available at the selected time');
                return;
            }
            
            // Toggle selection
            this.element.querySelectorAll('.table.selected').forEach(el => {
                el.classList.remove('selected');
            });
            tableElement.classList.add('selected');
            
            // Update reservation form
            const tableId = tableElement.dataset.tableId;
            const seats = tableElement.dataset.seats;
            this.updateReservationForm(tableId, seats);
        });
    }
    
    fetchAvailability() {
        // Simulated API call to get table availability
        const date = this.formatDate(this.selectedDate);
        const time = this.selectedTime;
        
        // In real implementation, this would be an API call
        fetch(`/api/availability?date=${date}&time=${time}`)
            .then(response => response.json())
            .then(data => this.updateAvailability(data))
            .catch(error => console.error('Error fetching availability:', error));
    }
    
    updateAvailability(data) {
        // Update each table's availability status
        data.tables.forEach(table => {
            const tableElement = this.element.querySelector(`.table[data-table-id="${table.id}"]`);
            if (!tableElement) return;
            
            if (table.available) {
                tableElement.classList.remove('unavailable');
                tableElement.classList.add('available');
            } else {
                tableElement.classList.remove('available', 'selected');
                tableElement.classList.add('unavailable');
            }
        });
    }
    
    updateReservationForm(tableId, seats) {
        // Update hidden form fields with selected table info
        const tableInput = document.querySelector('#reservation_table_id');
        const seatsInput = document.querySelector('#reservation_seats');
        
        if (tableInput) tableInput.value = tableId;
        if (seatsInput) seatsInput.value = seats;
        
        // Dispatch an event that the reservation form can listen for
        const event = new CustomEvent('tableSelected', {
            detail: { tableId, seats }
        });
        document.dispatchEvent(event);
    }
    
    setDateTime(date, time) {
        this.selectedDate = date;
        this.selectedTime = time;
        this.fetchAvailability();
    }
    
    formatDate(date) {
        return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }
    
    showMessage(message) {
        // Create or update tooltip message
        let tooltip = this.element.querySelector('.tooltip-message');
        if (!tooltip) {
            tooltip = document.createElement('div');
            tooltip.classList.add('tooltip-message');
            this.element.appendChild(tooltip);
        }
        
        tooltip.textContent = message;
        tooltip.classList.add('visible');
        
        // Hide message after delay
        setTimeout(() => {
            tooltip.classList.remove('visible');
        }, 3000);
    }
}
