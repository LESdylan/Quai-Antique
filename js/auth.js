document.addEventListener('DOMContentLoaded', function() {
    // Initialize default admin if not exists
    if (!localStorage.getItem('adminUser')) {
        const adminUser = {
            email: 'admin@quaiantique.fr',
            password: 'Admin123', // In a real app, this would be securely hashed
            firstName: 'Admin',
            lastName: 'Quai Antique',
            isAdmin: true
        };
        localStorage.setItem('adminUser', JSON.stringify(adminUser));
    }
    
    // Initialize users array if not exists
    if (!localStorage.getItem('users')) {
        localStorage.setItem('users', JSON.stringify([]));
    }
    
    // Login Form Handler
    const loginForm = document.getElementById('loginForm');
    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            
            // Check if admin
            const adminUser = JSON.parse(localStorage.getItem('adminUser'));
            if (email === adminUser.email && password === adminUser.password) {
                // Login as admin
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('userData', JSON.stringify(adminUser));
                window.location.href = 'admin/dashboard.html';
                return;
            }
            
            // Check if regular user
            const users = JSON.parse(localStorage.getItem('users'));
            const user = users.find(u => u.email === email && u.password === password);
            
            if (user) {
                // Login as user
                localStorage.setItem('isLoggedIn', 'true');
                localStorage.setItem('userData', JSON.stringify(user));
                window.location.href = '../index.html';
            } else {
                alert('Identifiants incorrects. Veuillez réessayer.');
            }
        });
    }
    
    // Registration Form Handler
    const registerForm = document.getElementById('registerForm');
    if (registerForm) {
        registerForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const firstName = document.getElementById('firstName').value;
            const lastName = document.getElementById('lastName').value;
            const email = document.getElementById('email').value;
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirmPassword').value;
            const defaultGuests = document.getElementById('defaultGuests').value;
            const allergies = document.getElementById('allergies').value;
            
            // Simple password validation
            if (password.length < 8) {
                alert('Le mot de passe doit contenir au moins 8 caractères.');
                return;
            }
            
            if (password !== confirmPassword) {
                alert('Les mots de passe ne correspondent pas.');
                return;
            }
            
            // Check if email already exists
            const users = JSON.parse(localStorage.getItem('users'));
            if (users.some(user => user.email === email)) {
                alert('Cet email est déjà utilisé. Veuillez en choisir un autre.');
                return;
            }
            
            // Create new user
            const newUser = {
                firstName,
                lastName,
                email,
                password, // In a real app, this would be securely hashed
                defaultGuests: parseInt(defaultGuests),
                allergies,
                isAdmin: false,
                reservations: []
            };
            
            // Add to users array
            users.push(newUser);
            localStorage.setItem('users', JSON.stringify(users));
            
            // Auto login
            localStorage.setItem('isLoggedIn', 'true');
            localStorage.setItem('userData', JSON.stringify(newUser));
            
            alert('Compte créé avec succès!');
            window.location.href = '../index.html';
        });
    }
    
    // Logout functionality
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('userData');
            window.location.href = '../index.html';
        });
    }
});
