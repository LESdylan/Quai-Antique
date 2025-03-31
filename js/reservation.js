document.addEventListener('DOMContentLoaded', function() {
    // Set min date to today
    const dateInput = document.getElementById('date');
    if (dateInput) {
        const today = new Date();
        const formattedDate = today.toISOString().split('T')[0];
        dateInput.min = formattedDate;
        dateInput.value = formattedDate;
    }
    
    // Service and time selection
    const serviceSelect = document.getElementById('service');
    const timeSelect = document.getElementById('time');
    
    if (serviceSelect && timeSelect) {
        serviceSelect.addEventListener('change', function() {
            timeSelect.disabled = false;
            timeSelect.innerHTML = '<option value="" disabled selected>Sélectionnez une heure</option>';
            
            const lunchTimes = ['12:00', '12:15', '12:30', '12:45', '13:00', '13:15', '13:30', '13:45'];
            const dinnerTimes = ['19:00', '19:15', '19:30', '19:45', '20:00', '20:15', '20:30', '20:45'];
            
            const times = this.value === 'lunch' ? lunchTimes : dinnerTimes;
            
            times.forEach(time => {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = time;
                timeSelect.appendChild(option);
            });
            
            // Trigger change to update availability
            checkAvailability();
        });
    }
    
    // Guest counter
    const decreaseBtn = document.getElementById('decreaseGuests');
    const increaseBtn = document.getElementById('increaseGuests');
    const guestsInput = document.getElementById('guests');
    
    if (decreaseBtn && increaseBtn && guestsInput) {
        decreaseBtn.addEventListener('click', function() {
            let value = parseInt(guestsInput.value);
            if (value > 1) {
                guestsInput.value = value - 1;
                checkAvailability();
            }
        });
        
        increaseBtn.addEventListener('click', function() {
            let value = parseInt(guestsInput.value);
            if (value < 20) {
                guestsInput.value = value + 1;
                checkAvailability();
            }
        });
        
        guestsInput.addEventListener('change', function() {
            let value = parseInt(this.value);
            if (value < 1) this.value = 1;
            if (value > 20) this.value = 20;
            checkAvailability();
        });
    }
    
    // Availability checker
    function checkAvailability() {
        const date = document.getElementById('date').value;
        const service = document.getElementById('service').value;
        const time = document.getElementById('time').value;
        const guests = parseInt(document.getElementById('guests').value);
        const availabilityIndicator = document.getElementById('seatsAvailability');
        
        if (!date || !service || !time || !guests) return;
        
        // This is a mock check (in a real app this would be an API call)
        const maxGuests = parseInt(localStorage.getItem('maxGuests') || '50');
        
        // Mock reservations data for demonstration
        // In a real app, this would come from the database
        const existingReservations = JSON.parse(localStorage.getItem('reservations') || '[]');
        
        // Calculate current total for the selected time slot
        let currentTotal = 0;
        existingReservations.forEach(reservation => {
            if (reservation.date === date && 
                reservation.service === service && 
                reservation.time === time) {
                currentTotal += reservation.guests;
            }
        });
        
        const remainingSeats = maxGuests - currentTotal;
        const isAvailable = guests <= remainingSeats;
        
        if (isAvailable) {
            availabilityIndicator.innerHTML = `<i class="fas fa-check-circle"></i> Places disponibles (${remainingSeats} restantes)`;
            availabilityIndicator.className = 'seats-availability available';
        } else {
            availabilityIndicator.innerHTML = `<i class="fas fa-times-circle"></i> Complet pour cette tranche horaire`;
            availabilityIndicator.className = 'seats-availability not-available';
        }
    }
    
    // Pre-fill form for logged-in users
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    if (isLoggedIn) {
        const userData = JSON.parse(localStorage.getItem('userData'));
        const nameInput = document.getElementById('name');
        const emailInput = document.getElementById('email');
        const allergiesInput = document.getElementById('allergies');
        const loginPrompt = document.getElementById('loginPrompt');
        
        if (nameInput && userData) {
            nameInput.value = `${userData.firstName} ${userData.lastName}`;
        }
        
        if (emailInput && userData) {
            emailInput.value = userData.email;
        }
        
        if (allergiesInput && userData && userData.allergies) {
            allergiesInput.value = userData.allergies;
        }
        
        if (guestsInput && userData && userData.defaultGuests) {
            guestsInput.value = userData.defaultGuests;
        }
        
        if (loginPrompt) {
            loginPrompt.style.display = 'none';
        }
    }
    
    // Form submission
    const reservationForm = document.getElementById('reservationForm');
    
    if (reservationForm) {
        reservationForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Check if user is logged in
            if (!localStorage.getItem('isLoggedIn')) {
                alert('Veuillez vous connecter pour effectuer une réservation.');
                window.location.href = 'login.html';
                return;
            }
            
            // Get form data
            const date = document.getElementById('date').value;
            const service = document.getElementById('service').value;
            const time = document.getElementById('time').value;
            const guests = parseInt(document.getElementById('guests').value);
            const name = document.getElementById('name').value;
            const email = document.getElementById('email').value;
            const phone = document.getElementById('phone').value;
            const allergies = document.getElementById('allergies').value;
            const notes = document.getElementById('notes').value;
            const tableNumber = document.getElementById('tableNumber').value;
            
            // Validate form data
            if (!date || !service || !time || !guests || !name || !email || !phone) {
                alert('Veuillez remplir tous les champs obligatoires.');
                return;
            }
            
            // For large groups, don't require table selection
            if (guests <= 10 && !tableNumber) {
                alert('Veuillez sélectionner une table sur le plan de salle.');
                return;
            }
            
            // Check availability one more time
            const maxGuests = parseInt(localStorage.getItem('maxGuests') || '50');
            const existingReservations = JSON.parse(localStorage.getItem('reservations') || '[]');
            
            let currentTotal = 0;
            existingReservations.forEach(reservation => {
                if (reservation.date === date && 
                    reservation.service === service && 
                    reservation.time === time) {
                    currentTotal += reservation.guests;
                }
            });
            
            const remainingSeats = maxGuests - currentTotal;
            const isAvailable = guests <= remainingSeats;
            
            if (!isAvailable) {
                alert('Désolé, il n\'y a plus de places disponibles pour cette tranche horaire.');
                return;
            }
            
            // Create reservation object
            const reservation = {
                id: Date.now().toString(), // Use a timestamp as a simple ID
                date,
                service,
                time,
                guests,
                name,
                email,
                phone,
                allergies,
                notes,
                tableNumber,
                status: 'confirmed',
                createdAt: new Date().toISOString()
            };
            
            // Add to local reservations
            existingReservations.push(reservation);
            localStorage.setItem('reservations', JSON.stringify(existingReservations));
            
            // Add to user's reservations
            const userData = JSON.parse(localStorage.getItem('userData'));
            if (!userData.reservations) userData.reservations = [];
            userData.reservations.push(reservation.id);
            localStorage.setItem('userData', JSON.stringify(userData));
            
            // Success message and redirect
            alert('Réservation confirmée ! Nous avons hâte de vous accueillir.');
            window.location.href = '../index.html';
        });
    }
    
    // Initialize availability check
    if (dateInput && serviceSelect && timeSelect && guestsInput) {
        dateInput.addEventListener('change', checkAvailability);
        timeSelect.addEventListener('change', checkAvailability);
    }
    
    // Display max capacity
    const maxCapacityElement = document.getElementById('maxCapacity');
    if (maxCapacityElement) {
        const maxGuests = localStorage.getItem('maxGuests') || '50';
        maxCapacityElement.textContent = maxGuests;
    }
    
    // Show/hide testimonials animation
    const testimonials = document.querySelectorAll('.testimonial');
    testimonials.forEach((testimonial, index) => {
        testimonial.style.opacity = '0';
        testimonial.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            testimonial.style.transition = 'opacity 0.8s ease, transform 0.8s ease';
            testimonial.style.opacity = '1';
            testimonial.style.transform = 'translateY(0)';
        }, 500 + (index * 200));
    });

    // Add elegant hover effect to form elements
    const formElements = document.querySelectorAll('.input-wrapper input, .input-wrapper select, .input-wrapper textarea');
    formElements.forEach(element => {
        element.addEventListener('focus', function() {
            this.parentElement.style.transition = 'transform 0.3s ease';
            this.parentElement.style.transform = 'translateY(-3px)';
        });
        
        element.addEventListener('blur', function() {
            this.parentElement.style.transform = 'translateY(0)';
        });
    });
});
