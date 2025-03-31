document.addEventListener('DOMContentLoaded', function() {
    console.log("Debug script loaded");
    
    // Check if we're on the reservation page
    const seatingMapContainer = document.getElementById('seatingMapContainer');
    if (!seatingMapContainer) {
        console.log("Not on reservation page or seatingMapContainer not found");
        return;
    }
    
    console.log("Found seatingMapContainer:", seatingMapContainer);
    
    // Verify all required elements exist
    const elementsToCheck = [
        'seatingMap',
        'restaurantLayout',
        'zoomIn',
        'zoomOut',
        'resetView',
        'optimizeTablesBtn',
        'tableSuggestion',
        'suggestionContent',
        'acceptSuggestion',
        'declineSuggestion',
        'guests',
        'tableNumber'
    ];
    
    elementsToCheck.forEach(id => {
        const element = document.getElementById(id);
        console.log(`Element #${id}: ${element ? 'Found' : 'MISSING'}`);
    });
    
    // Force initialize the seating map
    const seatingMap = document.getElementById('seatingMap');
    const restaurantLayout = document.getElementById('restaurantLayout');
    
    // Make sure elements are visible - set explicit styles
    if (seatingMapContainer) {
        seatingMapContainer.style.display = 'block';
        seatingMapContainer.style.visibility = 'visible';
        seatingMapContainer.style.height = 'auto';
        seatingMapContainer.style.overflow = 'visible';
    }
    
    if (seatingMap) {
        seatingMap.style.display = 'block';
        seatingMap.style.visibility = 'visible';
        seatingMap.style.height = '500px';
    }
    
    if (restaurantLayout) {
        restaurantLayout.style.display = 'block';
        restaurantLayout.style.visibility = 'visible';
        restaurantLayout.style.width = '100%';
        restaurantLayout.style.height = '100%';
    }
    
    // Add a single test table if no tables are created
    if (restaurantLayout && !restaurantLayout.querySelector('.table')) {
        console.log("No tables found, creating a test table");
        
        // Create a simple test table
        const testTable = document.createElement('div');
        testTable.className = 'table available round';
        testTable.setAttribute('data-id', '999');
        testTable.setAttribute('data-capacity', '2');
        testTable.style.left = '200px';
        testTable.style.top = '200px';
        testTable.style.width = '60px';
        testTable.style.height = '60px';
        
        // Add table number
        const tableNumber = document.createElement('div');
        tableNumber.className = 'table-number';
        tableNumber.textContent = '999';
        testTable.appendChild(tableNumber);
        
        // Add capacity indicator
        const tableCapacity = document.createElement('div');
        tableCapacity.className = 'table-capacity';
        tableCapacity.textContent = '2p';
        testTable.appendChild(tableCapacity);
        
        restaurantLayout.appendChild(testTable);
    }
    
    // Create link to admin dashboard with auto-login
    const adminLinkContainer = document.createElement('div');
    adminLinkContainer.style.marginTop = '20px';
    adminLinkContainer.style.textAlign = 'center';
    
    const adminAccessBtn = document.createElement('a');
    adminAccessBtn.href = 'admin/dashboard.html';
    adminAccessBtn.className = 'btn-secondary';
    adminAccessBtn.textContent = "Accès Admin";
    adminAccessBtn.style.display = 'inline-block';
    adminAccessBtn.style.padding = '10px 15px';
    adminAccessBtn.style.textDecoration = 'none';
    
    adminLinkContainer.appendChild(adminAccessBtn);
    seatingMapContainer.parentNode.insertBefore(adminLinkContainer, seatingMapContainer.nextSibling);
    
    console.log("Debug initialization complete");
});
