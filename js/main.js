document.addEventListener('DOMContentLoaded', function() {
    // Mobile Navigation Toggle
    const burgerMenu = document.querySelector('.burger-menu');
    const nav = document.querySelector('nav');
    
    if (burgerMenu) {
        burgerMenu.addEventListener('click', function() {
            nav.classList.toggle('active');
        });
    }
    
    // Check if user is logged in
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const loginBtn = document.getElementById('loginBtn');
    
    if (isLoggedIn && loginBtn) {
        // Get user data to check if admin
        const userData = JSON.parse(localStorage.getItem('userData') || '{}');
        
        // Change login button to profile or logout
        loginBtn.textContent = 'Mon Compte';
        loginBtn.href = 'profile.html';
        
        // Add admin link if user is admin
        if (userData.isAdmin) {
            const navList = document.querySelector('nav ul');
            if (navList) {
                // Create admin nav item before the login item
                const adminLi = document.createElement('li');
                const adminLink = document.createElement('a');
                adminLink.href = 'admin/dashboard.html';
                adminLink.textContent = 'Admin';
                adminLi.appendChild(adminLink);
                
                // Add it before the login menu item
                const loginLi = loginBtn.parentElement;
                navList.insertBefore(adminLi, loginLi);
            }
        }
    }
    
    // Restaurant Hours Data (In a real app, this would come from the backend)
    const defaultHours = [
        { day: 'Lundi', lunch: 'Fermé', dinner: 'Fermé' },
        { day: 'Mardi', lunch: '12:00 - 14:00', dinner: '19:00 - 21:00' },
        { day: 'Mercredi', lunch: '12:00 - 14:00', dinner: '19:00 - 21:00' },
        { day: 'Jeudi', lunch: '12:00 - 14:00', dinner: '19:00 - 21:00' },
        { day: 'Vendredi', lunch: '12:00 - 14:00', dinner: '19:00 - 21:00' },
        { day: 'Samedi', lunch: '12:00 - 14:00', dinner: '19:00 - 21:00' },
        { day: 'Dimanche', lunch: '12:00 - 14:00', dinner: '19:00 - 21:00' }
    ];
    
    // Initialize restaurant hours in localStorage if not set
    if (!localStorage.getItem('restaurantHours')) {
        localStorage.setItem('restaurantHours', JSON.stringify(defaultHours));
    }
    
    // Initialize max guests if not set
    if (!localStorage.getItem('maxGuests')) {
        localStorage.setItem('maxGuests', '50');
    }
    
    // Display restaurant hours if on homepage
    const hoursContainer = document.getElementById('hoursContainer');
    if (hoursContainer) {
        displayRestaurantHours();
    }
    
    // Function to display restaurant hours
    function displayRestaurantHours() {
        const hours = JSON.parse(localStorage.getItem('restaurantHours'));
        hoursContainer.innerHTML = '';
        
        hours.forEach(dayInfo => {
            const dayElement = document.createElement('div');
            dayElement.className = 'hours-day';
            dayElement.innerHTML = `
                <h3>${dayInfo.day}</h3>
                <p>Déjeuner: ${dayInfo.lunch}</p>
                <p>Dîner: ${dayInfo.dinner}</p>
            `;
            hoursContainer.appendChild(dayElement);
        });
    }
});
