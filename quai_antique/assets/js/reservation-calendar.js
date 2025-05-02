/**
 * Reservation Calendar Handler
 * Interactive date/time picker for restaurant reservations
 */
class ReservationCalendar {
    constructor(options) {
        this.options = Object.assign({
            dateSelector: '#reservation_date',
            timeSlotsContainer: '#time-slots-container',
            timeSlotInput: '#reservation_timeSlot',
            guestCountInput: '#reservation_guestCount',
            availabilityUrl: '/reservation/time-slots',
            checkAvailabilityUrl: '/reservation/check-availability',
        }, options);
        
        this.dateInput = document.querySelector(this.options.dateSelector);
        this.timeSlotsContainer = document.querySelector(this.options.timeSlotsContainer);
        this.timeSlotInput = document.querySelector(this.options.timeSlotInput);
        this.guestCountInput = document.querySelector(this.options.guestCountInput);
        
        this.init();
    }
    
    init() {
        if (!this.dateInput || !this.timeSlotsContainer || !this.timeSlotInput) {
            console.error('Required elements not found');
            return;
        }
        
        // Set min date to today
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        this.dateInput.setAttribute('min', formattedDate);
        
        // Bind events
        this.dateInput.addEventListener('change', this.handleDateChange.bind(this));
        this.guestCountInput.addEventListener('change', this.handleGuestCountChange.bind(this));
    }
    
    handleDateChange() {
        const selectedDate = this.dateInput.value;
        if (!selectedDate) return;
        
        // Clear currently selected time slot
        this.timeSlotInput.value = '';
        
        // Fetch available time slots for the selected date
        this.fetchTimeSlots(selectedDate);
    }
    
    handleGuestCountChange() {
        const selectedDate = this.dateInput.value;
        const selectedTimeSlot = this.timeSlotInput.value;
        
        if (selectedDate && selectedTimeSlot) {
            this.checkAvailability(selectedDate, selectedTimeSlot, this.guestCountInput.value);
        }
    }
    
    async fetchTimeSlots(date) {
        this.timeSlotsContainer.innerHTML = '<div class="text-center"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
        
        try {
            const response = await fetch(`${this.options.availabilityUrl}?date=${date}`);
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Failed to fetch time slots');
            }
            
            if (!data.timeSlots || data.timeSlots.length === 0) {
                this.timeSlotsContainer.innerHTML = '<div class="alert alert-info">Aucun créneau disponible pour cette date.</div>';
                return;
            }
            
            this.renderTimeSlots(data.timeSlots, date);
        } catch (error) {
            console.error('Error fetching time slots:', error);
            this.timeSlotsContainer.innerHTML = `<div class="alert alert-danger">Erreur lors de la récupération des créneaux disponibles: ${error.message}</div>`;
        }
    }
    
    renderTimeSlots(timeSlots, date) {
        const timeSlotButtons = document.createElement('div');
        timeSlotButtons.className = 'time-slots-grid';
        
        timeSlots.forEach(timeSlot => {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-outline-primary time-slot-btn';
            button.textContent = timeSlot;
            button.setAttribute('data-time-slot', timeSlot);
            
            button.addEventListener('click', () => {
                // Remove active class from all buttons
                document.querySelectorAll('.time-slot-btn').forEach(btn => {
                    btn.classList.remove('active');
                });
                
                // Mark this button as active
                button.classList.add('active');
                
                // Set the hidden input value
                this.timeSlotInput.value = timeSlot;
                
                // Check availability with the current guest count
                this.checkAvailability(date, timeSlot, this.guestCountInput.value);
            });
            
            timeSlotButtons.appendChild(button);
        });
        
        // Clear container and add new buttons
        this.timeSlotsContainer.innerHTML = '';
        this.timeSlotsContainer.appendChild(timeSlotButtons);
    }
    
    async checkAvailability(date, timeSlot, guestCount) {
        const availabilityMessage = document.createElement('div');
        availabilityMessage.id = 'availability-message';
        availabilityMessage.className = 'mt-3 text-center';
        availabilityMessage.innerHTML = '<div class="spinner-border spinner-border-sm text-primary" role="status"><span class="visually-hidden">Loading...</span></div> Vérification de la disponibilité...';
        
        // Remove any existing availability message
        const existingMessage = document.getElementById('availability-message');
        if (existingMessage) {
            existingMessage.remove();
        }
        
        // Add the new message
        this.timeSlotsContainer.appendChild(availabilityMessage);
        
        try {
            const response = await fetch(this.options.checkAvailabilityUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    date,
                    timeSlot,
                    guestCount,
                }),
            });
            
            const data = await response.json();
            
            if (!response.ok) {
                throw new Error(data.error || 'Failed to check availability');
            }
            
            if (data.available) {
                availabilityMessage.className = 'mt-3 text-center text-success';
                availabilityMessage.innerHTML = '<i class="fas fa-check-circle"></i> Créneau disponible';
            } else {
                availabilityMessage.className = 'mt-3 text-center text-danger';
                availabilityMessage.innerHTML = '<i class="fas fa-times-circle"></i> Créneau complet';
            }
        } catch (error) {
            console.error('Error checking availability:', error);
            availabilityMessage.className = 'mt-3 text-center text-danger';
            availabilityMessage.innerHTML = `<i class="fas fa-exclamation-triangle"></i> Erreur lors de la vérification: ${error.message}`;
        }
    }
}

// Initialize when the DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    const reservationForm = document.querySelector('form[name="reservation"]');
    if (reservationForm) {
        new ReservationCalendar();
    }
});
