// WithPersona Frontend Scripts
(function($) {
    'use strict';

    // Initialize any frontend functionality
    $(document).ready(function() {
        // Most functionality is handled inline in the verification template
        // This file is for any additional frontend interactions
        
        // Initialize any custom event listeners
        $(document).on('persona_verification_complete', function(e, data) {
            if (data && data.status === 'completed') {
                // Refresh the page after successful verification
                window.location.reload();
            }
        });
    });

})(jQuery); 