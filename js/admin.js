document.addEventListener('DOMContentLoaded', function() {
    // Check if user is admin
    const isLoggedIn = localStorage.getItem('isLoggedIn');
    const userData = isLoggedIn ? JSON.parse(localStorage.getItem('userData')) : null;
    
    if (!isLoggedIn || !userData || !userData.isAdmin) {
        // Redirect non-admin users
        window.location.href = '../../index.html';
        return;
    }
    
    // Admin panel navigation
    const adminNavLinks = document.querySelectorAll('.admin-nav a');
    const adminPanels = document.querySelectorAll('.admin-panel');
    
    adminNavLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Remove active class from all links and panels
            adminNavLinks.forEach(l => l.parentElement.classList.remove('active'));
            adminPanels.forEach(panel => panel.classList.remove('active'));
            
            // Add active class to clicked link
            this.parentElement.classList.add('active');
            
            // Show the corresponding panel
            const panelId = this.getAttribute('data-target');
            document.getElementById(panelId).classList.add('active');
        });
    });
    
    // Load dashboard data
    loadDashboardData();
    
    // Load reservations data
    loadReservations();
    
    // Load settings data
    loadSettings();
    
    // Reservation filters
    const filterDateInput = document.getElementById('filterDate');
    const filterServiceSelect = document.getElementById('filterService');
    const filterStatusSelect = document.getElementById('filterStatus');
    const applyFilterBtn = document.getElementById('applyFilter');
    
    if (filterDateInput && applyFilterBtn) {
        // Set default date to today
        const today = new Date();
        filterDateInput.value = today.toISOString().split('T')[0];
        
        applyFilterBtn.addEventListener('click', function() {
            loadReservations(
                filterDateInput.value,
                filterServiceSelect.value,
                filterStatusSelect.value
            );
        });
    }
    
    // Save settings
    const saveSettingsBtn = document.getElementById('saveSettings');
    if (saveSettingsBtn) {
        saveSettingsBtn.addEventListener('click', saveSettings);
    }
    
    // Table optimization
    const optimizeAllTablesBtn = document.getElementById('optimizeAllTables');
    if (optimizeAllTablesBtn) {
        optimizeAllTablesBtn.addEventListener('click', optimizeAllTables);
    }
    
    // Logout button
    const logoutBtn = document.getElementById('logoutBtn');
    if (logoutBtn) {
        logoutBtn.addEventListener('click', function(e) {
            e.preventDefault();
            localStorage.removeItem('isLoggedIn');
            localStorage.removeItem('userData');
            window.location.href = '../../index.html';
        });
    }
    
    // Load dashboard data
    function loadDashboardData() {
        const reservations = JSON.parse(localStorage.getItem('reservations') || '[]');
        const today = new Date().toISOString().split('T')[0];
        
        // Count today's reservations
        const todayReservations = reservations.filter(reservation => 
            reservation.date === today
        ).length;
        
        // Count tonight's covers
        const tonightCovers = reservations.reduce((total, reservation) => {
            if (reservation.date === today && reservation.service === 'dinner') {
                return total + reservation.guests;
            }
            return total;
        }, 0);
        
        // Count this week's reservations
        const oneWeekFromToday = new Date();
        oneWeekFromToday.setDate(oneWeekFromToday.getDate() + 7);
        const oneWeekFromTodayStr = oneWeekFromToday.toISOString().split('T')[0];
        
        const weekReservations = reservations.filter(reservation => 
            reservation.date >= today && reservation.date <= oneWeekFromTodayStr
        ).length;
        
        // Update dashboard cards
        document.getElementById('todayReservations').textContent = todayReservations;
        document.getElementById('tonightCovers').textContent = tonightCovers;
        document.getElementById('weekReservations').textContent = weekReservations;
        
        // Load today's upcoming reservations
        const upcomingReservations = reservations.filter(reservation => 
            reservation.date === today
        ).sort((a, b) => {
            // Sort by time
            return a.time.localeCompare(b.time);
        });
        
        const upcomingTable = document.getElementById('upcomingReservationsTable');
        if (upcomingTable) {
            upcomingTable.innerHTML = '';
            
            if (upcomingReservations.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="6" style="text-align: center;">Aucune réservation pour aujourd'hui</td>
                `;
                upcomingTable.appendChild(emptyRow);
            } else {
                upcomingReservations.forEach(reservation => {
                    const row = document.createElement('tr');
                    
                    // Format service
                    const serviceText = reservation.service === 'lunch' ? 'Déjeuner' : 'Dîner';
                    
                    row.innerHTML = `
                        <td>${reservation.time} (${serviceText})</td>
                        <td>${reservation.name}</td>
                        <td>${reservation.guests}</td>
                        <td>${reservation.tableNumber || 'Non spécifiée'}</td>
                        <td>${reservation.phone}</td>
                        <td><span class="status-badge status-${reservation.status}">${formatStatus(reservation.status)}</span></td>
                    `;
                    
                    upcomingTable.appendChild(row);
                });
            }
        }
    }
    
    // Load reservations with optional filters
    function loadReservations(date = '', service = '', status = '') {
        const reservations = JSON.parse(localStorage.getItem('reservations') || '[]');
        
        // Apply filters
        let filteredReservations = [...reservations];
        
        if (date) {
            filteredReservations = filteredReservations.filter(reservation => 
                reservation.date === date
            );
        }
        
        if (service) {
            filteredReservations = filteredReservations.filter(reservation => 
                reservation.service === service
            );
        }
        
        if (status) {
            filteredReservations = filteredReservations.filter(reservation => 
                reservation.status === status
            );
        }
        
        // Sort by date and time
        filteredReservations.sort((a, b) => {
            if (a.date !== b.date) {
                return a.date.localeCompare(b.date);
            }
            return a.time.localeCompare(b.time);
        });
        
        const reservationsTable = document.getElementById('reservationsTable');
        if (reservationsTable) {
            reservationsTable.innerHTML = '';
            
            if (filteredReservations.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.innerHTML = `
                    <td colspan="8" style="text-align: center;">Aucune réservation trouvée</td>
                `;
                reservationsTable.appendChild(emptyRow);
            } else {
                filteredReservations.forEach(reservation => {
                    const row = document.createElement('tr');
                    
                    // Format date
                    const formattedDate = formatDate(reservation.date);
                    
                    // Format service
                    const serviceText = reservation.service === 'lunch' ? 'Déjeuner' : 'Dîner';
                    
                    row.innerHTML = `
                        <td>${formattedDate}</td>
                        <td>${reservation.time} (${serviceText})</td>
                        <td>${reservation.name}</td>
                        <td>${reservation.guests}</td>
                        <td>${reservation.tableNumber || 'Non spécifiée'}</td>
                        <td>${reservation.phone}</td>
                        <td><span class="status-badge status-${reservation.status}">${formatStatus(reservation.status)}</span></td>
                        <td class="action-btns">
                            <button class="action-btn view-btn" data-id="${reservation.id}" title="Voir les détails">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button class="action-btn edit-btn" data-id="${reservation.id}" title="Modifier">
                                <i class="fas fa-edit"></i>
                            </button>
                            <button class="action-btn delete-btn" data-id="${reservation.id}" title="Supprimer">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    `;
                    
                    reservationsTable.appendChild(row);
                });
                
                // Add event listeners to buttons
                addReservationActionListeners();
            }
        }
    }
    
    // Add event listeners to reservation action buttons
    function addReservationActionListeners() {
        // View button
        document.querySelectorAll('.view-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                viewReservation(reservationId);
            });
        });
        
        // Edit button
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                editReservation(reservationId);
            });
        });
        
        // Delete button
        document.querySelectorAll('.delete-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const reservationId = this.getAttribute('data-id');
                deleteReservation(reservationId);
            });
        });
    }
    
    // View reservation details
    function viewReservation(id) {
        const reservations = JSON.parse(localStorage.getItem('reservations') || '[]');
        const reservation = reservations.find(r => r.id === id);
        
        if (!reservation) {
            alert('Réservation non trouvée');
            return;
        }
        
        // Format date
        const formattedDate = formatDate(reservation.date);
        
        // Format service
        const serviceText = reservation.service === 'lunch' ? 'Déjeuner' : 'Dîner';
        
        // Build modal content
        const modalContent = `
            <h3>Détails de la réservation</h3>
            <div class="reservation-details">
                <p><strong>Client:</strong> ${reservation.name}</p>
                <p><strong>Date:</strong> ${formattedDate}</p>
                <p><strong>Service:</strong> ${serviceText}</p>
                <p><strong>Heure:</strong> ${reservation.time}</p>
                <p><strong>Personnes:</strong> ${reservation.guests}</p>
                <p><strong>Table:</strong> ${reservation.tableNumber || 'Non spécifiée'}</p>
                <p><strong>Téléphone:</strong> ${reservation.phone}</p>
                <p><strong>Email:</strong> ${reservation.email}</p>
                <p><strong>Allergies:</strong> ${reservation.allergies || 'Aucune'}</p>
                <p><strong>Notes:</strong> ${reservation.notes || 'Aucune'}</p>
                <p><strong>Statut:</strong> ${formatStatus(reservation.status)}</p>
                <p><strong>Créé le:</strong> ${formatDateTime(reservation.createdAt)}</p>
            </div>
        `;
        
        showModal(modalContent);
    }
    
    // Edit reservation
    function editReservation(id) {
        const reservations = JSON.parse(localStorage.getItem('reservations') || '[]');
        const reservation = reservations.find(r => r.id === id);
        
        if (!reservation) {
            alert('Réservation non trouvée');
            return;
        }
        
        // Build modal content with form
        const modalContent = `
            <h3>Modifier la réservation</h3>
            <form id="editReservationForm">
                <div class="form-group">
                    <label for="editDate">Date</label>
                    <input type="date" id="editDate" value="${reservation.date}" required>
                </div>
                
                <div class="form-group">
                    <label for="editService">Service</label>
                    <select id="editService" required>
                        <option value="lunch" ${reservation.service === 'lunch' ? 'selected' : ''}>Déjeuner</option>
                        <option value="dinner" ${reservation.service === 'dinner' ? 'selected' : ''}>Dîner</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="editTime">Heure</label>
                    <input type="text" id="editTime" value="${reservation.time}" required>
                </div>
                
                <div class="form-group">
                    <label for="editGuests">Nombre de personnes</label>
                    <input type="number" id="editGuests" value="${reservation.guests}" min="1" max="20" required>
                </div>
                
                <div class="form-group">
                    <label for="editTable">Table</label>
                    <input type="text" id="editTable" value="${reservation.tableNumber || ''}">
                </div>
                
                <div class="form-group">
                    <label for="editName">Nom</label>
                    <input type="text" id="editName" value="${reservation.name}" required>
                </div>
                
                <div class="form-group">
                    <label for="editPhone">Téléphone</label>
                    <input type="text" id="editPhone" value="${reservation.phone}" required>
                </div>
                
                <div class="form-group">
                    <label for="editEmail">Email</label>
                    <input type="email" id="editEmail" value="${reservation.email}" required>
                </div>
                
                <div class="form-group">
                    <label for="editAllergies">Allergies</label>
                    <textarea id="editAllergies">${reservation.allergies || ''}</textarea>
                </div>
                
                <div class="form-group">
                    <label for="editNotes">Notes</label>
                    <textarea id="editNotes">${reservation.notes || ''}</textarea>
                </div>
                
                <div class="form-group">
                    <label for="editStatus">Statut</label>
                    <select id="editStatus" required>
                        <option value="confirmed" ${reservation.status === 'confirmed' ? 'selected' : ''}>Confirmé</option>
                        <option value="pending" ${reservation.status === 'pending' ? 'selected' : ''}>En attente</option>
                        <option value="cancelled" ${reservation.status === 'cancelled' ? 'selected' : ''}>Annulé</option>
                    </select>
                </div>
                
                <div class="modal-buttons">
                    <button type="button" class="btn-secondary" onclick="closeModal()">Annuler</button>
                    <button type="submit" class="btn-primary">Enregistrer</button>
                </div>
            </form>
        `;
        
        showModal(modalContent);
        
        // Add submit event listener to the form
        document.getElementById('editReservationForm').addEventListener('submit', function(e) {
            e.preventDefault();
            
            // Update reservation data
            reservation.date = document.getElementById('editDate').value;
            reservation.service = document.getElementById('editService').value;
            reservation.time = document.getElementById('editTime').value;
            reservation.guests = parseInt(document.getElementById('editGuests').value);
            reservation.tableNumber = document.getElementById('editTable').value;
            reservation.name = document.getElementById('editName').value;
            reservation.phone = document.getElementById('editPhone').value;
            reservation.email = document.getElementById('editEmail').value;
            reservation.allergies = document.getElementById('editAllergies').value;
            reservation.notes = document.getElementById('editNotes').value;
            reservation.status = document.getElementById('editStatus').value;
            
            // Save updated reservations
            localStorage.setItem('reservations', JSON.stringify(reservations));
            
            // Close modal and reload reservations
            closeModal();
            loadReservations(
                document.getElementById('filterDate').value,
                document.getElementById('filterService').value,
                document.getElementById('filterStatus').value
            );
            loadDashboardData();
            
            // Show success message
            alert('Réservation mise à jour avec succès');
        });
    }
    
    // Delete reservation
    function deleteReservation(id) {
        if (confirm('Êtes-vous sûr de vouloir supprimer cette réservation ?')) {
            const reservations = JSON.parse(localStorage.getItem('reservations') || '[]');
            const updatedReservations = reservations.filter(r => r.id !== id);
            
            // Save updated reservations
            localStorage.setItem('reservations', JSON.stringify(updatedReservations));
            
            // Reload reservations
            loadReservations(
                document.getElementById('filterDate').value,
                document.getElementById('filterService').value,
                document.getElementById('filterStatus').value
            );
            loadDashboardData();
            
            // Show success message
            alert('Réservation supprimée avec succès');
        }
    }
    
    // Show modal
    function showModal(content) {
        // Create modal if it doesn't exist
        let modal = document.getElementById('adminModal');
        
        if (!modal) {
            modal = document.createElement('div');
            modal.id = 'adminModal';
            modal.className = 'admin-modal';
            
            modal.innerHTML = `
                <div class="modal-backdrop"></div>
                <div class="modal-content">
                    <span class="modal-close" onclick="closeModal()">&times;</span>
                    <div class="modal-body"></div>
                </div>
            `;
            
            document.body.appendChild(modal);
            
            // Add close modal function to window
            window.closeModal = function() {
                modal.style.display = 'none';
            };
            
            // Close modal when clicking on backdrop
            modal.querySelector('.modal-backdrop').addEventListener('click', window.closeModal);
        }
        
        // Set modal content
        modal.querySelector('.modal-body').innerHTML = content;
        
        // Show modal
        modal.style.display = 'block';
        
        // Add modal styles if not already added
        if (!document.getElementById('admin-modal-styles')) {
            const style = document.createElement('style');
            style.id = 'admin-modal-styles';
            style.textContent = `
                .admin-modal {
                    display: none;
                    position: fixed;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    z-index: 1000;
                }
                
                .modal-backdrop {
                    position: absolute;
                    top: 0;
                    left: 0;
                    width: 100%;
                    height: 100%;
                    background-color: rgba(0, 0, 0, 0.5);
                }
                
                .modal-content {
                    position: absolute;
                    top: 50%;
                    left: 50%;
                    transform: translate(-50%, -50%);
                    background-color: white;
                    padding: 30px;
                    border-radius: 5px;
                    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
                    max-width: 600px;
                    width: 90%;
                    max-height: 90vh;
                    overflow-y: auto;
                }
                
                .modal-close {
                    position: absolute;
                    top: 10px;
                    right: 15px;
                    font-size: 1.5rem;
                    cursor: pointer;
                    color: #999;
                    transition: color 0.3s ease;
                }
                
                .modal-close:hover {
                    color: var(--primary-color);
                }
                
                .modal-body h3 {
                    font-family: var(--font-subtitle);
                    font-size: 1.5rem;
                    margin-bottom: 20px;
                    color: var(--primary-color);
                }
                
                .reservation-details p {
                    margin-bottom: 10px;
                }
                
                .modal-buttons {
                    display: flex;
                    justify-content: flex-end;
                    gap: 10px;
                    margin-top: 20px;
                }
                
                .btn-secondary {
                    background-color: #e0e0e0;
                    color: #333;
                    border: none;
                    padding: 10px 15px;
                    cursor: pointer;
                    transition: background-color 0.3s ease;
                }
                
                .btn-secondary:hover {
                    background-color: #ccc;
                }
            `;
            
            document.head.appendChild(style);
        }
    }
    
    // Load settings
    function loadSettings() {
        // Load max capacity
        const maxCapacity = localStorage.getItem('maxGuests') || '50';
        
        const maxCapacityInput = document.getElementById('maxCapacity');
        if (maxCapacityInput) {
            maxCapacityInput.value = maxCapacity;
        }
        
        // Load restaurant hours
        const hours = JSON.parse(localStorage.getItem('restaurantHours') || '[]');
        
        // Set each day's hours
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        
        days.forEach((day, index) => {
            const dayData = hours[index];
            
            if (dayData) {
                const lunchSelect = document.getElementById(`${day}Lunch`);
                const dinnerSelect = document.getElementById(`${day}Dinner`);
                
                if (lunchSelect) {
                    lunchSelect.value = dayData.lunch;
                }
                
                if (dinnerSelect) {
                    dinnerSelect.value = dayData.dinner;
                }
            }
        });
    }
    
    // Save settings
    function saveSettings() {
        // Save max capacity
        const maxCapacity = document.getElementById('maxCapacity').value;
        localStorage.setItem('maxGuests', maxCapacity);
        
        // Save restaurant hours
        const days = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        const hours = [];
        
        days.forEach(day => {
            const lunchSelect = document.getElementById(`${day}Lunch`);
            const dinnerSelect = document.getElementById(`${day}Dinner`);
            
            const dayData = {
                day: day.charAt(0).toUpperCase() + day.slice(1),
                lunch: lunchSelect ? lunchSelect.value : 'closed',
                dinner: dinnerSelect ? dinnerSelect.value : 'closed'
            };
            
            hours.push(dayData);
        });
        
        localStorage.setItem('restaurantHours', JSON.stringify(hours));
        
        // Show success message
        alert('Paramètres enregistrés avec succès');
    }
    
    // Optimize all tables using AI
    function optimizeAllTables() {
        const reservations = JSON.parse(localStorage.getItem('reservations') || '[]');
        
        // Get future reservations that don't have a table assigned
        const futureReservations = reservations.filter(reservation => {
            const reservationDate = new Date(reservation.date);
            const today = new Date();
            today.setHours(0, 0, 0, 0);
            
            return reservationDate >= today && (!reservation.tableNumber || reservation.tableNumber === '');
        });
        
        if (futureReservations.length === 0) {
            alert('Aucune réservation future sans table assignée.');
            return;
        }
        
        // Sort by date and time
        futureReservations.sort((a, b) => {
            if (a.date !== b.date) {
                return a.date.localeCompare(b.date);
            }
            return a.time.localeCompare(b.time);
        });
        
        // Run the table optimization algorithm
        const optimizationResults = runOptimizationAlgorithm(futureReservations);
        
        // Display results
        const resultsElement = document.getElementById('optimizationResults');
        if (resultsElement) {
            let resultsHtml = '<h4>Résultats de l\'optimisation</h4>';
            
            if (optimizationResults.success) {
                resultsHtml += `
                    <p class="success-message"><i class="fas fa-check-circle"></i> ${optimizationResults.assignedCount} tables ont été assignées avec succès.</p>
                    <ul class="results-list">
                `;
                
                optimizationResults.assignments.forEach(assignment => {
                    resultsHtml += `
                        <li>
                            <strong>${formatDate(assignment.date)}, ${assignment.time}</strong>: 
                            ${assignment.name} (${assignment.guests} pers.) → Table ${assignment.tableNumber}
                        </li>
                    `;
                });
                
                resultsHtml += '</ul>';
                
                if (optimizationResults.unassignedCount > 0) {
                    resultsHtml += `
                        <p class="warning-message">
                            <i class="fas fa-exclamation-triangle"></i> 
                            ${optimizationResults.unassignedCount} réservations n'ont pas pu être assignées automatiquement.
                        </p>
                    `;
                }
                
                // Save the changes
                localStorage.setItem('reservations', JSON.stringify(reservations));
            } else {
                resultsHtml += `
                    <p class="error-message">
                        <i class="fas fa-times-circle"></i> 
                        L'optimisation a échoué: ${optimizationResults.message}
                    </p>
                `;
            }
            
            resultsElement.innerHTML = resultsHtml;
            resultsElement.classList.add('active');
            
            // Refresh reservations list
            loadReservations(
                document.getElementById('filterDate').value,
                document.getElementById('filterService').value,
                document.getElementById('filterStatus').value
            );
        }
    }
    
    // Run table optimization algorithm
    function runOptimizationAlgorithm(reservationsToAssign) {
        // Restaurant tables configuration (from seat-map.js)
        const restaurantTables = [
            { id: 1, capacity: 2 },
            { id: 2, capacity: 2 },
            { id: 3, capacity: 2 },
            { id: 4, capacity: 2 },
            { id: 5, capacity: 2 },
            { id: 6, capacity: 1 },
            { id: 7, capacity: 1 },
            { id: 8, capacity: 1 },
            { id: 9, capacity: 4 },
            { id: 10, capacity: 4 },
            { id: 11, capacity: 4 },
            { id: 12, capacity: 4 },
            { id: 13, capacity: 4 },
            { id: 14, capacity: 6 },
            { id: 15, capacity: 6 },
            { id: 16, capacity: 8 }
        ];
        
        // Group reservations by date and service
        const groupedReservations = {};
        
        reservationsToAssign.forEach(reservation => {
            const key = `${reservation.date}-${reservation.service}`;
            
            if (!groupedReservations[key]) {
                groupedReservations[key] = [];
            }
            
            groupedReservations[key].push(reservation);
        });
        
        // Get all reservations (including already assigned ones)
        const allReservations = JSON.parse(localStorage.getItem('reservations') || '[]');
        
        // Track successful assignments
        const assignments = [];
        let assignedCount = 0;
        let unassignedCount = 0;
        
        // Process each group
        for (const key in groupedReservations) {
            const timeSlotReservations = groupedReservations[key];
            const [date, service] = key.split('-');
            
            // Get already occupied tables for this time slot
            const occupiedTableIds = allReservations
                .filter(r => r.date === date && r.service === service && r.tableNumber)
                .map(r => parseInt(r.tableNumber));
            
            // Available tables
            const availableTables = restaurantTables.filter(table => !occupiedTableIds.includes(table.id));
            
            // Sort reservations by size (descending) to prioritize larger parties
            timeSlotReservations.sort((a, b) => b.guests - a.guests);
            
            // Assign tables
            timeSlotReservations.forEach(reservation => {
                // Skip reservations with more than 10 people
                if (reservation.guests > 10) {
                    unassignedCount++;
                    return;
                }
                
                // Find optimal table
                let bestTableIndex = -1;
                let minWaste = Infinity;
                
                availableTables.forEach((table, index) => {
                    if (table.capacity >= reservation.guests) {
                        const waste = table.capacity - reservation.guests;
                        if (waste < minWaste) {
                            minWaste = waste;
                            bestTableIndex = index;
                        }
                    }
                });
                
                if (bestTableIndex !== -1) {
                    const assignedTable = availableTables[bestTableIndex];
                    
                    // Assign table
                    reservation.tableNumber = assignedTable.id.toString();
                    
                    // Track assignment
                    assignments.push({
                        date: reservation.date,
                        time: reservation.time,
                        name: reservation.name,
                        guests: reservation.guests,
                        tableNumber: assignedTable.id
                    });
                    
                    assignedCount++;
                    
                    // Remove assigned table from available tables
                    availableTables.splice(bestTableIndex, 1);
                    
                    // Update the reservation in the original array
                    const reservationIndex = allReservations.findIndex(r => r.id === reservation.id);
                    if (reservationIndex !== -1) {
                        allReservations[reservationIndex].tableNumber = assignedTable.id.toString();
                    }
                } else {
                    unassignedCount++;
                }
            });
        }
        
        return {
            success: true,
            assignedCount,
            unassignedCount,
            assignments
        };
    }
    
    // Helper function to format date
    function formatDate(dateString) {
        const options = { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' };
        const date = new Date(dateString);
        return date.toLocaleDateString('fr-FR', options);
    }
    
    // Helper function to format date and time
    function formatDateTime(dateTimeString) {
        const date = new Date(dateTimeString);
        return date.toLocaleString('fr-FR');
    }
    
    // Helper function to format reservation status
    function formatStatus(status) {
        switch (status) {
            case 'confirmed':
                return 'Confirmée';
            case 'pending':
                return 'En attente';
            case 'cancelled':
                return 'Annulée';
            default:
                return status;
        }
    }
});
