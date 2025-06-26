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

if (! defined('ABSPATH')) {
	exit;
}
?>
<script>
	const personaConfig = {
		templateId: <?php echo wp_json_encode($template_id); ?>,
		environmentId: <?php echo wp_json_encode($environment_id); ?>,
		referenceId: <?php echo wp_json_encode($reference_id); ?>,
		language: "en",
		verificationStatus: <?php echo wp_json_encode($verification_status); ?>,
		nonce: <?php echo wp_json_encode($nonce); ?>
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

						jQuery.ajax({
							url: ajaxurl,
							type: "POST",
							data: {
								action: "save_persona_status",
								status: status,
								nonce: personaConfig.nonce
							},
							success: function(response) {
								// console.log("Status saved:", response);
							}
						});

						window.WP_WITH_PERSONA_STATUS = status;

						// Trigger custom event
						jQuery(document).trigger('personaVerification', [status]);
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