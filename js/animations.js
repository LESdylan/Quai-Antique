document.addEventListener('DOMContentLoaded', function() {
    // Fade in page content after loading
    const pageLoader = document.querySelector('.page-loader');
    if (pageLoader) {
        setTimeout(() => {
            pageLoader.classList.add('fade-out');
            setTimeout(() => {
                pageLoader.style.display = 'none';
            }, 500);
        }, 800);
    }

    // Animate text elements
    const animateText = document.querySelectorAll('.animate-text');
    animateText.forEach((element, index) => {
        let delay = element.classList.contains('delay-1') ? 200 : 
                    element.classList.contains('delay-2') ? 400 : 0;
        
        setTimeout(() => {
            element.classList.add('animated');
        }, delay);
    });

    // Reveal sections on scroll
    const revealSections = document.querySelectorAll('.reveal-section');
    
    const revealSectionCallback = (entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('revealed');
                observer.unobserve(entry.target);
            }
        });
    };
    
    const sectionObserver = new IntersectionObserver(revealSectionCallback, {
        threshold: 0.1
    });
    
    revealSections.forEach(section => {
        sectionObserver.observe(section);
    });

    // Scroll indicator functionality
    const scrollIndicator = document.querySelector('.scroll-indicator');
    if (scrollIndicator) {
        scrollIndicator.addEventListener('click', () => {
            window.scrollTo({
                top: window.innerHeight,
                behavior: 'smooth'
            });
        });
    }

    // Parallax effect for background images
    const parallaxSections = document.querySelectorAll('.parallax-bg');
    
    window.addEventListener('scroll', () => {
        const scrollTop = window.pageYOffset;
        
        parallaxSections.forEach(section => {
            const speed = section.getAttribute('data-speed') || 0.5;
            section.style.backgroundPositionY = `${scrollTop * speed}px`;
        });
    });

    // Add steam animation to hot dishes
    const steamEffects = document.querySelectorAll('.steam-effect');
    steamEffects.forEach(effect => {
        setInterval(() => {
            const span = document.createElement('span');
            effect.appendChild(span);
            
            setTimeout(() => {
                span.remove();
            }, 4000);
        }, 2000);
    });

    // Make CTA buttons more appetizing with hover effect
    const ctaButtons = document.querySelectorAll('.btn-primary, .btn-primary-large');
    ctaButtons.forEach(button => {
        button.addEventListener('mouseenter', () => {
            if (!button.classList.contains('pulse-animation')) {
                button.classList.add('hover-effect');
            }
        });
        
        button.addEventListener('mouseleave', () => {
            button.classList.remove('hover-effect');
        });
    });

    // Header scroll effect
    const header = document.querySelector('header');
    window.addEventListener('scroll', () => {
        if (window.scrollY > 50) {
            header.classList.add('scrolled');
        } else {
            header.classList.remove('scrolled');
        }
    });

    // Decorative flourish animations
    const flourishes = document.querySelectorAll('.text-accent');
    flourishes.forEach(flourish => {
        const revealCallback = (entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    setTimeout(() => {
                        flourish.style.width = '80px';
                        flourish.style.opacity = '1';
                    }, 200);
                    observer.unobserve(flourish);
                }
            });
        };
        
        const observer = new IntersectionObserver(revealCallback, {
            threshold: 0.1
        });
        
        observer.observe(flourish);
        
        // Initial style
        flourish.style.width = '0';
        flourish.style.opacity = '0';
        flourish.style.transition = 'width 1s ease, opacity 1s ease';
    });

    // Animate handwritten text
    document.querySelectorAll('.handwritten').forEach(element => {
        // Apply the animation only if not already animated
        if (!element.classList.contains('animated')) {
            element.style.opacity = '0';
            element.style.transform = 'translateY(20px)';
            element.style.transition = 'opacity 1s ease, transform 1s ease';
            
            setTimeout(() => {
                element.style.opacity = '1';
                element.style.transform = 'translateY(0)';
                element.classList.add('animated');
            }, 300);
        }
    });

    // Delicious hover effect on menu items
    document.querySelectorAll('.menu-item').forEach(item => {
        item.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-10px)';
            this.style.boxShadow = '0 20px 40px rgba(0, 0, 0, 0.1)';
        });
        
        item.addEventListener('mouseleave', function() {
            this.style.transform = '';
            this.style.boxShadow = '';
        });
    });

    // Scroll to top button
    const scrollTopBtn = document.createElement('button');
    scrollTopBtn.className = 'scroll-top-btn';
    scrollTopBtn.innerHTML = '<i class="fas fa-chevron-up"></i>';
    document.body.appendChild(scrollTopBtn);
    
    scrollTopBtn.addEventListener('click', () => {
        window.scrollTo({
            top: 0,
            behavior: 'smooth'
        });
    });
    
    window.addEventListener('scroll', () => {
        if (window.scrollY > 500) {
            scrollTopBtn.classList.add('visible');
        } else {
            scrollTopBtn.classList.remove('visible');
        }
    });

    // Add CSS for scroll top button
    const style = document.createElement('style');
    style.textContent = `
        .scroll-top-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background-color: var(--secondary-color);
            color: white;
            border: none;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.2rem;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            opacity: 0;
            visibility: hidden;
            transition: opacity 0.3s ease, visibility 0.3s ease, transform 0.3s ease;
            z-index: 999;
        }
        
        .scroll-top-btn.visible {
            opacity: 1;
            visibility: visible;
        }
        
        .scroll-top-btn:hover {
            transform: translateY(-5px);
            background-color: var(--primary-color);
        }
    `;
    document.head.appendChild(style);
});
