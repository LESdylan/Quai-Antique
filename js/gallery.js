document.addEventListener('DOMContentLoaded', function() {
    // Gallery images data with high-quality food photos
    const galleryImages = [
        {
            src: 'https://images.unsplash.com/photo-1600891964092-4316c288032e?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            title: 'Tartare de bœuf de Savoie aux herbes fraîches'
        },
        {
            src: 'https://images.unsplash.com/photo-1414235077428-338989a2e8c0?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            title: 'Filet de truite du lac au beurre blanc'
        },
        {
            src: 'https://images.unsplash.com/photo-1533777857889-4be7c70b33f7?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            title: 'Velouté de potimarron aux châtaignes'
        },
        {
            src: 'https://images.unsplash.com/photo-1546069901-ba9599a7e63c?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            title: 'Salade fraîcheur du jardin alpin'
        },
        {
            src: 'https://images.unsplash.com/photo-1504674900247-0877df9cc836?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            title: 'Suprême de volaille de Bresse aux morilles'
        },
        {
            src: 'https://images.unsplash.com/photo-1551024601-bec78aea704b?ixlib=rb-1.2.1&auto=format&fit=crop&w=800&q=80',
            title: 'Dessert glacé à la chartreuse'
        }
    ];

    // Get gallery container
    const galleryContainer = document.getElementById('galleryContainer');
    
    if (galleryContainer) {
        // Create gallery items with animations and effects
        galleryImages.forEach((image, index) => {
            const galleryItem = document.createElement('div');
            galleryItem.className = 'gallery-item reveal-item';
            galleryItem.setAttribute('data-delay', (index * 0.1).toString());
            
            galleryItem.innerHTML = `
                <img src="${image.src}" alt="${image.title}" loading="lazy">
                <div class="title">${image.title}</div>
                <div class="overlay">
                    <div class="zoom-icon">
                        <i class="fas fa-search-plus"></i>
                    </div>
                </div>
            `;
            
            galleryContainer.appendChild(galleryItem);
            
            // Add click event to open image in lightbox
            galleryItem.addEventListener('click', function() {
                openLightbox(image);
            });
        });
        
        // Initialize reveal animation
        initRevealAnimation();
    }
    
    // Initialize reveal animation for all elements with reveal-item class
    function initRevealAnimation() {
        const revealItems = document.querySelectorAll('.reveal-item');
        
        const revealCallback = (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const delay = entry.target.getAttribute('data-delay') || 0;
                    setTimeout(() => {
                        entry.target.classList.add('revealed');
                    }, delay * 1000);
                    observer.unobserve(entry.target);
                }
            });
        };
        
        const observer = new IntersectionObserver(revealCallback, {
            threshold: 0.1
        });
        
        revealItems.forEach(item => {
            observer.observe(item);
        });
    }
    
    // Lightbox functionality
    function openLightbox(image) {
        // Create lightbox container if it doesn't exist
        let lightbox = document.getElementById('gallery-lightbox');
        
        if (!lightbox) {
            lightbox = document.createElement('div');
            lightbox.id = 'gallery-lightbox';
            lightbox.className = 'lightbox';
            lightbox.innerHTML = `
                <div class="lightbox-content">
                    <span class="close-lightbox">&times;</span>
                    <img id="lightbox-img" src="" alt="">
                    <h3 id="lightbox-title"></h3>
                </div>
            `;
            document.body.appendChild(lightbox);
            
            // Add close event
            document.querySelector('.close-lightbox').addEventListener('click', function() {
                lightbox.classList.remove('active');
                document.body.classList.remove('no-scroll');
            });
        }
        
        // Set image and title
        document.getElementById('lightbox-img').src = image.src;
        document.getElementById('lightbox-title').textContent = image.title;
        
        // Show lightbox
        lightbox.classList.add('active');
        document.body.classList.add('no-scroll');
    }
});
