<?php

/**
 * Trait containing common Persona verification functionality
 *
 * This trait provides shared methods used by the WpWithPersona_Verification class.
 * It contains the core rendering logic for Persona verification widgets including:
 * - Settings retrieval and validation
 * - Container, styles, and scripts rendering
 * - Error handling and configuration checks
 * - Reference ID and verification status management
 *
 * This trait centralizes the common functionality to avoid code duplication
 * and maintain consistency across the verification system.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait Persona_Verification_Common {

	/**
	 * Get the verification reference ID
	 */
	protected function get_reference_id() {
		if ( ! isset( $_SESSION['persona_reference_id'] ) ) {
			$_SESSION['persona_reference_id'] = 'session_' . uniqid();
		}
		return is_user_logged_in() ? get_current_user_id() : $_SESSION['persona_reference_id'];
	}

	/**
	 * Get the current inquiry ID from session
	 *
	 * @return string The inquiry ID or empty string
	 */
	public function get_current_inquiry_id() {
		return isset( $_SESSION['persona_verification_inquiry_id'] ) ? $_SESSION['persona_verification_inquiry_id'] : '';
	}

	/**
	 * Get the verification status
	 */
	protected function get_verification_status() {
		// Clear session data on registration pages
		$this->clear_session_on_registration_pages();

		// For logged-in users, check user meta first
		if ( is_user_logged_in() ) {
			$user_id             = get_current_user_id();
			$verification_status = get_user_meta( $user_id, 'persona_verification_status', true );

			error_log( 'Persona Debug - User ID: ' . $user_id . ', User Meta Status: ' . $verification_status );

			if ( ! empty( $verification_status ) ) {
				return $verification_status;
			}
		}

		// Fallback to session data
		$session_status = isset( $_SESSION['persona_verification_status'] ) ? $_SESSION['persona_verification_status'] : 'not_started';
		error_log( 'Persona Debug - Session Status: ' . $session_status );

		return $session_status;
	}

	/**
	 * Clear session data on registration pages
	 */
	private function clear_session_on_registration_pages() {
		// Check if we're on a registration page
		$is_registration_page = false;

		// Check URL for registration keywords
		$current_url = $_SERVER['REQUEST_URI'] ?? '';
		if ( strpos( $current_url, 'registration' ) !== false || strpos( $current_url, 'register' ) !== false ) {
			$is_registration_page = true;
			error_log( 'Persona Debug - Registration page detected via URL: ' . $current_url );
		}

		// Check page content for registration shortcodes
		if ( is_page() ) {
			$post = get_post();
			if ( $post ) {
				$content                             = $post->post_content;
				$has_registration_shortcode          = has_shortcode( $content, 'ps_fm_registration' );
				$has_customer_registration_shortcode = has_shortcode( $content, 'ps_fm_customer_registration' );

				if ( $has_registration_shortcode || $has_customer_registration_shortcode ) {
					$is_registration_page = true;
					error_log( 'Persona Debug - Registration page detected via shortcode' );
				}
			}
		}

		if ( $is_registration_page ) {
			// Start session if not already started
			if ( session_status() === PHP_SESSION_NONE ) {
				session_start();
			}

			// Clear Persona session data
			unset( $_SESSION['persona_verification_status'] );
			unset( $_SESSION['persona_verification_inquiry_id'] );
			unset( $_SESSION['persona_reference_id'] );

			// Also clear user meta for logged-in users
			if ( is_user_logged_in() ) {
				$user_id = get_current_user_id();
				delete_user_meta( $user_id, 'persona_verification_status' );
				delete_user_meta( $user_id, 'persona_verification_inquiry_id' );
				delete_user_meta( $user_id, 'persona_verification_last_checked' );
				error_log( 'Persona Debug - Cleared user meta for user ID: ' . $user_id );
			}

			error_log( 'Persona Debug - Cleared session data on registration page' );
		}
	}

	/**
	 * Get Persona settings
	 */
	protected function get_persona_settings() {
		return array(
			'template_id'    => get_option( 'wpwithpersona_api_template_id' ),
			'environment_id' => get_option( 'wpwithpersona_api_environment_id' ),
		);
	}

	/**
	 * Check if settings are configured
	 */
	protected function are_settings_configured() {
		$settings = $this->get_persona_settings();
		return ! empty( $settings['template_id'] ) && ! empty( $settings['environment_id'] );
	}

	/**
	 * Render configuration error message
	 */
	protected function render_configuration_error() {
		if ( current_user_can( 'manage_options' ) ) {
			?>
			<div class="notice notice-error" style="padding: 20px; border-left-width: 5px; background-color: #fff; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">
				<h2 style="margin-top: 0; color: #d63638;"><?php esc_html_e( 'Persona Verification Error:', 'wp-withpersona' ); ?></h2>
				<p style="font-size: 14px; margin-bottom: 15px;">
					<?php esc_html_e( 'Persona verification is not properly configured. Please set up your Template ID and Environment ID in the WordPress admin settings.', 'wp-withpersona' ); ?>
				</p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-withpersona-settings' ) ); ?>"
						class="button button-primary"
						style="background-color: var(--e-global-color-primary);
								color: #fff; 
								padding: 8px 16px; 
								text-decoration: none; 
								border-radius: 4px; 
								font-weight: 500;
								border: none;
								display: inline-block;
								transition: all 0.2s ease;">
						<?php esc_html_e( 'Configure Persona Settings', 'wp-withpersona' ); ?> →
					</a>
				</p>
			</div>
			<?php
		} else {
			$this->render_public_error_message();
		}
	}

	/**
	 * Render public error message
	 */
	protected function render_public_error_message() {
		?>
		<div class="persona-error-container" style="
			max-width: 800px;
			margin: 40px auto;
			padding: 40px;
			background-color: #fff;
			border-radius: 8px;
			box-shadow: 0 2px 4px rgba(0,0,0,0.05);
		">
			<h1 style="
				font-size: 32px;
				font-weight: 600;
				color: #1d2327;
				margin: 0 0 32px 0;
			"><?php esc_html_e( 'Identity Verification', 'wp-withpersona' ); ?></h1>
			<div class="persona-error-content" style="
				display: flex;
				flex-direction: column;
				gap: 16px;
			">
				<div class="persona-error-icon" style="
					font-size: 20px;
					color: #856404;
				">⚠️</div>
				<h2 style="
					margin: 0;
					color: #1d2327;
					font-size: 24px;
					font-weight: 600;
					line-height: 1.3;
				"><?php esc_html_e( 'Configuration Error', 'wp-withpersona' ); ?></h2>
				<p style="
					margin: 0;
					font-size: 16px;
					color: #50575e;
					line-height: 1.6;
				"><?php esc_html_e( 'Verification is temporarily unavailable. Please try again later or contact support.', 'wp-withpersona' ); ?></p>
				<div style="margin-top: 8px;">
					<a href="#" style="
						display: inline-block;
						color: #d63638;
						text-decoration: none;
						font-size: 15px;
						font-weight: 500;
					"><?php esc_html_e( 'Contact Support', 'wp-withpersona' ); ?> →</a>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Render the Persona container
	 */
	protected function render_persona_container( $verification_status, $inquiry_id ) {
		?>
		<script>
			window.WP_WITH_PERSONA_STATUS = '<?php echo $verification_status; ?>';
			window.WP_WITH_PERSONA_INQUIRY_ID = '<?php echo $inquiry_id; ?>';
		</script>
		<div id="persona-container">
			<button class="button button-primary" id="persona-button" <?php echo $verification_status === 'completed' ? 'disabled' : ''; ?> style="white-space: nowrap;"><?php echo $verification_status === 'completed' ? esc_html__( 'Already Verified', 'wp-withpersona' ) : esc_html__( 'Verify with Persona', 'wp-withpersona' ); ?></button>
			<span><?php esc_html_e( 'Verification status:', 'wp-withpersona' ); ?> <span id="persona-status"><?php echo ucfirst( $verification_status ); ?></span></span>
			<small><?php esc_html_e( 'Powered by', 'wp-withpersona' ); ?> <b><a target="__blank" href="https://withpersona.com/"><?php esc_html_e( 'With Persona', 'wp-withpersona' ); ?></b></a></small>
		</div>
		<?php
	}

	/**
	 * Render the Persona styles
	 */
	protected function render_persona_styles() {
		?>
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
				color: #427b08;
				padding: 10px 20px;
				border-radius: 5px;
				cursor: pointer;
				width: 200px;
			}
		</style>
		<?php
	}

	/**
	 * Render the Persona scripts
	 */
	protected function render_persona_scripts( $template_id, $environment_id, $reference_id, $verification_status ) {
		// Create a nonce for the AJAX call
		$nonce = wp_create_nonce( 'save_persona_status' );

		// Enqueue Persona script
		wp_enqueue_script( 'persona-verification', 'https://cdn.withpersona.com/dist/persona-v5.1.2.js', array(), '5.1.2', true );

		// Load the template
		$template_path = WP_WITH_PERSONA_PLUGIN_DIR . 'templates/persona-verification-script.php';

		if ( file_exists( $template_path ) ) {
			// Extract variables for the template
			extract(
				array(
					'template_id'         => $template_id,
					'environment_id'      => $environment_id,
					'reference_id'        => $reference_id,
					'verification_status' => $verification_status,
					'nonce'               => $nonce,
				)
			);

			// Start output buffering
			ob_start();
			include $template_path;
			$inline_script = ob_get_clean();

			// Add the inline script
			wp_add_inline_script( 'persona-verification', $inline_script, 'after' );
		}
	}
}
