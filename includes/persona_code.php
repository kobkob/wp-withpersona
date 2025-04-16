<?php
/**
 * Persona integration code
 */

// Generate or retrieve session-based reference ID
if (!isset($_SESSION['persona_reference_id'])) {
	$_SESSION['persona_reference_id'] = 'session_' . uniqid();
}

// Check if we have a user ID (for registered users)
$reference_id = is_user_logged_in() ? get_current_user_id() : $_SESSION['persona_reference_id'];

// Get verification status from session
$verification_status = isset($_SESSION['persona_verification_status']) ? $_SESSION['persona_verification_status'] : 'not_started';

// Output the Persona script
?>
<div id="persona-container">
	<button class="button button-primary" id="persona-button" <?php echo $verification_status === 'completed' ? 'disabled' : ''; ?>>
		<?php echo $verification_status === 'completed' ? 'Already Verified' : 'Verify with Persona'; ?>
	</button>
	<span>Verification status: <span id="persona-status"><?php echo ucfirst($verification_status); ?></span></span>
	<small>Powered by <b><a target="__blank" href="https://withpersona.com/">With Persona</b></a></small>
</div>
<style>
	#persona-container {
		background-color: #f0f0f0;
		padding: 20px;
		border-radius: 10px;
		display: flex;
		flex-direction: column;
		gap: 20px;
		margin: 10px 0px;
	}

	#persona-container span {
		font-size: 0.9rem;
		font-weight: bold;
	}

	#persona-container small {
		font-size: 0.9rem;
		font-weight: normal;
	}

	#persona-container button {
		background-color: #000;
		color: #fff;
		padding: 10px 20px;
		border-radius: 5px;
		cursor: pointer;
		width: 200px;
	}
</style>
<script>
	/**
	 *  Possible fields to collect for persona embedded workflow
	 *
	 *  nameFirst
	 *  nameLast
	 *  birthdate
	 *  addressStreet1
	 *  addressCity
	 *  addressSubdivision
	 *  addressPostalCode
	 *  addressCountryCode
	 *  phoneNumber
	 *  emailAddress
	 */
	// window.WP_WITH_PERSONA_FIELDS = {
	// 	nameFirst: "Jane",
	// 	nameLast: "Doe",
	// 	birthdate: "2000-12-31",
	// 	addressStreet1: "132 Second St.",
	// 	addressCity: "San Francisco",
	// 	addressSubdivision: "California",
	// 	addressPostalCode: "93441",
	// 	addressCountryCode: "US",
	// 	phoneNumber: "415-415-4154",
	// 	emailAddress: "janedoe@persona.com",
	// };
	window.WP_WITH_PERSONA_TEMPLATE_ID = 'itmpl_wQoG1XsLAZr7aNtwd3KwApWH';
	window.WP_WITH_PERSONA_ENVIRONMENT_ID = 'env_XDfTrQonZiwdRLyfiuHgyuQo';
	window.WP_WITH_PERSONA_REFERENCE_ID = '<?php echo $reference_id; ?>';
	window.WP_WITH_PERSONA_LANGUAGE = 'en';
	window.WP_WITH_PERSONA_VERIFICATION_STATUS = '<?php echo $verification_status; ?>';
	var personaContainer = document.getElementById('persona-container');
	var personaButton = document.getElementById('persona-button');
	personaButton.addEventListener('click', (e) => {
		e.preventDefault();
		jQuery(e.target).text('Opening...');
		// personaContainer.style.display = 'block';
		var client = new Persona.Client({
			language: window.WP_WITH_PERSONA_LANGUAGE,
			templateId: window.WP_WITH_PERSONA_TEMPLATE_ID,
			environmentId: window.WP_WITH_PERSONA_ENVIRONMENT_ID,
			referenceId: window.WP_WITH_PERSONA_REFERENCE_ID,
			onReady: () => {
				client.open()
				jQuery(e.target).text('Verify with Persona');
			},
			onCancel: () => {
				console.log('Canceled');
				jQuery('#persona-status').text('Canceled, please try again.');
			},
			onComplete: ({ inquiryId, status, fields }) => {
				console.log(`Completed inquiry ${inquiryId} with status ${status}`);
				jQuery('#persona-status').text(status);
				// Save status to session
				jQuery.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'save_persona_status',
						status: status,
						nonce: '<?php echo wp_create_nonce('save_persona_status'); ?>'
					},
					success: function (response) {
						console.log('Status saved:', response);
					}
				});
				// propagate the status using window variables
				window.WP_WITH_PERSONA_STATUS = status;
			},
			onError: (error) => {
				console.error('Error:', error);
				jQuery('#persona-status').text('Error, please try again.');
			},
			fields: window.WP_WITH_PERSONA_FIELDS,
		});
	});
</script>
<script src="https://cdn.withpersona.com/dist/persona-v5.1.2.js"
	integrity="sha384-nuMfOsYXMwp5L13VJicJkSs8tObai/UtHEOg3f7tQuFWU5j6LAewJbjbF5ZkfoDo"
	crossorigin="anonymous"></script>