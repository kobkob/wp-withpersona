<?php
/**
 * Template for Persona verification script
 *
 * @package WP_WithPersona
 * @var string $template_id
 * @var string $environment_id
 * @var string $reference_id
 * @var string $verification_status
 * @var string $nonce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<script>
	const personaConfig = {
		templateId: <?php echo wp_json_encode( $template_id ); ?>,
		environmentId: <?php echo wp_json_encode( $environment_id ); ?>,
		referenceId: <?php echo wp_json_encode( $reference_id ); ?>,
		language: "en",
		verificationStatus: <?php echo wp_json_encode( $verification_status ); ?>,
		nonce: <?php echo wp_json_encode( $nonce ); ?>
	};

	window.WP_WITH_PERSONA_CONFIG = personaConfig;

	document.addEventListener("DOMContentLoaded", function() {
		const personaButton = document.getElementById("persona-button");

		if (personaButton) {
			personaButton.addEventListener("click", function(e) {
				e.preventDefault();
				jQuery(e.target).text("Opening...");

				const client = new Persona.Client({
					language: personaConfig.language,
					templateId: personaConfig.templateId,
					environmentId: personaConfig.environmentId,
					referenceId: personaConfig.referenceId,
					onReady: function() {
						client.open();
						jQuery(e.target).text("Verify with Persona");
					},
					onCreate: function(inquiryId) {
						console.log("Inquiry created with ID: " + inquiryId);
						
						// Save inquiry ID immediately when created
						jQuery.ajax({
							url: ajaxurl,
							type: "POST",
							data: {
								action: "save_persona_status",
								status: "created",
								inquiryId: inquiryId,
								nonce: personaConfig.nonce
							},
							success: function(response) {
								console.log("Inquiry ID saved:", response);
							}
						});
						
						window.WP_WITH_PERSONA_INQUIRY_ID = inquiryId;
					},
					onCancel: function() {
						console.log("Canceled");
						jQuery("#persona-status").text("Canceled, please try again.");
					},
					onComplete: function({
						inquiryId,
						status,
						fields
					}) {
						console.log("Completed inquiry " + inquiryId + " with status " + status);
						jQuery("#persona-status").text(status);

						// Update button text and state if status is completed
						if (status === 'completed') {
							jQuery(personaButton).text('Already Verified').prop('disabled', true);
						}

						// Save both inquiry ID and status immediately
						jQuery.ajax({
							url: ajaxurl,
							type: "POST",
							data: {
								action: "save_persona_status",
								status: status,
								inquiryId: inquiryId,
								nonce: personaConfig.nonce
							},
							success: function(response) {
								console.log("Status and inquiry ID saved:", response);
							}
						});

						window.WP_WITH_PERSONA_STATUS = status;
						if (inquiryId) {
							window.WP_WITH_PERSONA_INQUIRY_ID = inquiryId;
						}

						// Trigger custom event
						jQuery(document).trigger('personaVerification', [status, inquiryId]);
					},
					onError: function(error) {
						console.error("Error:", error);
						jQuery("#persona-status").text("Error, please try again.");
					}
				});
			});
		}
	});
</script>
