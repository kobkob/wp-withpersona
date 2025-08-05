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

    // Function to fetch inquiry details from Persona API
    window.wpWithPersonaGetInquiryDetails = function(inquiryId, callback) {
        if (!inquiryId) {
            console.error('Inquiry ID is required');
            return;
        }

        $.ajax({
            url: wpWithPersona.ajaxurl,
            type: 'POST',
            data: {
                action: 'get_inquiry_details',
                inquiry_id: inquiryId,
                nonce: wpWithPersona.nonces.get_inquiry_details
            },
            success: function(response) {
                if (response.success && callback) {
                    callback(response.data);
                } else {
                    console.error('Failed to fetch inquiry details:', response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('AJAX error:', error);
            }
        });
    };

    // Example usage:
    // wpWithPersonaGetInquiryDetails('inq_123456', function(inquiryData) {
    //     console.log('Inquiry details:', inquiryData);
    //     // Process the inquiry data here
    // });

})(jQuery); 