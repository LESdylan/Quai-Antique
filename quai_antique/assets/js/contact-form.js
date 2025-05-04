/**
 * Contact Form Handler
 * Fixed to properly handle form submission and errors
 */

document.addEventListener('DOMContentLoaded', function() {
    const contactForm = document.getElementById('contact-form');
    if (!contactForm) return;
    
    const submitButton = document.getElementById('contact-submit');
    const formStatus = document.getElementById('form-status');
    
    contactForm.addEventListener('submit', function(event) {
        event.preventDefault();
        
        // Show loading state
        if (submitButton) {
            submitButton.disabled = true;
            submitButton.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Envoi en cours...';
        }
        
        if (formStatus) {
            formStatus.innerHTML = '<div class="alert alert-info">Envoi en cours...</div>';
        }
        
        // Simple form data submission - no JSON or content type headers
        const formData = new FormData(contactForm);
        
        // Make a standard form submission - no JSON attempt
        fetch(contactForm.getAttribute('action') || '/contact', {
            method: 'POST',
            body: formData
        })
        .then(response => {
            // Handle any response status and get text first
            return response.text().then(text => {
                // Try to parse as JSON if possible
                let data;
                try {
                    data = JSON.parse(text);
                    if (!response.ok) {
                        throw new Error(data.message || `Error ${response.status}`);
                    }
                    return data;
                } catch (e) {
                    // Not JSON or invalid JSON
                    if (!response.ok) {
                        throw new Error(`Server error: ${response.status}`);
                    }
                    // If we got here, it's because the response is not JSON
                    // but the server responded OK - consider it a success
                    return { success: true, message: "Message envoyé avec succès!" };
                }
            });
        })
        .then(data => {
            // Handle success
            if (formStatus) {
                formStatus.innerHTML = `
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle me-2"></i> Message envoyé avec succès!
                    </div>
                `;
            }
            
            // Reset form
            contactForm.reset();
            
            // Redirect to success page or show success message
            setTimeout(() => {
                window.location.href = '/contact/success';
            }, 1500);
        })
        .catch(error => {
            console.error('Form submission error:', error);
            
            // Show error message
            if (formStatus) {
                formStatus.innerHTML = `
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Une erreur est survenue lors de l'envoi du message. Veuillez réessayer.
                    </div>
                `;
            }
        })
        .finally(() => {
            // Reset button state
            if (submitButton) {
                submitButton.disabled = false;
                submitButton.innerHTML = 'Envoyer';
            }
        });
    });
});
