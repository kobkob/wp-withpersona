<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'traits/trait-persona-verification-common.php';

class WpWithPersona_Verification {

	use Persona_Verification_Common;

	/**
	 * The single instance of WpWithPersona_Verification.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $parent = null;

	/**
	 * Constructor
	 *
	 * @param object $parent The parent object
	 */
	public function __construct( $parent ) {
		$this->parent = $parent;

		// Add verification check on user login
		add_action( 'wp_login', array( $this, 'check_user_verification' ), 10, 2 );

		// Add verification check on page load for logged-in users
		add_action( 'init', array( $this, 'check_verification_on_page_load' ) );

		// Add verification status to user meta
		add_action( 'user_register', array( $this, 'add_verification_meta' ) );

		// Create verification page
		add_action( 'admin_init', array( $this, 'create_verification_page' ) );

		// Add verification link to user profile
		//add_action( 'show_user_profile', array( $this, 'add_verification_link_to_profile' ) );
		//add_action( 'edit_user_profile', array( $this, 'add_verification_link_to_profile' ) );

		// Register shortcode
		add_shortcode( 'persona_verification', array( $this, 'render_verification_content' ) );
		add_shortcode( 'wp_withpersona', array( $this, 'render_verification_content' ) );

		// Ensure shortcode is processed in the content
		add_filter( 'the_content', array( $this, 'process_verification_shortcode' ), 1 );

		// Add admin notice if page needs recreation
		add_action( 'admin_notices', array( $this, 'check_verification_page_status' ) );

		// Register AJAX handlers
		add_action( 'wp_ajax_update_user_verification_status', array( $this, 'handle_update_user_verification_status' ) );

		// Check if required settings are configured
		add_action( 'admin_notices', array( $this, 'check_required_settings_notice' ) );

		// Add admin notice for unverified administrators
		add_action( 'admin_notices', array( $this, 'check_admin_verification_notice' ) );
	}

	private function check_required_settings() {
		$template_id    = get_option( 'wpwithpersona_api_template_id' );
		$environment_id = get_option( 'wpwithpersona_api_environment_id' );
		$api_key        = get_option( 'wpwithpersona_api_key' );

		if ( empty( $template_id ) || empty( $environment_id ) || empty( $api_key ) ) {
			return false;
		}
		return true;
	}

	/**
	 * Check if required settings are configured
	 */
	public function check_required_settings_notice() {
		if ( ! $this->check_required_settings() ) {
			?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'Persona verification requires configuration. Please set up your Template ID, Environment ID, and API Key in the settings.', 'wp-withpersona' ); ?></p>
				<p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-withpersona-settings' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Configure Settings', 'wp-withpersona' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}

	/**
	 * Check if administrator is verified and show notice if not
	 */
	public function check_admin_verification_notice() {
		if ( ! is_user_logged_in() || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( ! $this->check_required_settings() ) {
			return;
		}

		$user_id = get_current_user_id();
		if ( ! $this->is_user_verified( $user_id ) ) {
			$verification_url = $this->get_verification_page_url();
			?>
			<div class="notice notice-warning">
				<p>
					<strong><?php esc_html_e( 'Admin Account Verification Required', 'wp-withpersona' ); ?></strong>
				</p>
				<p><?php esc_html_e( 'Your administrator account is not currently verified with Persona. While you can still access the admin area, it is recommended to complete verification for security purposes.', 'wp-withpersona' ); ?></p>
				<?php if ( $verification_url ) : ?>
					<p>
						<a href="<?php echo esc_url( $verification_url ); ?>" class="button button-primary">
							<?php esc_html_e( 'Complete Verification Now', 'wp-withpersona' ); ?>
						</a>
					</p>
				<?php endif; ?>
			</div>
			<?php
		}
	}

	/**
	 * Check if a user is verified in Persona
	 *
	 * @param int $user_id The user ID to check
	 * @return bool Whether the user is verified
	 */
	public function is_user_verified( $user_id ) {
		$api_key     = get_option( 'wpwithpersona_api_key' );
		$template_id = get_option( 'wpwithpersona_api_template_id' );

		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			return false;
		}

		// Get verification status from user meta
		$verification_status = get_user_meta( $user_id, 'persona_verification_status', true );
		$last_checked        = get_user_meta( $user_id, 'persona_verification_last_checked', true );
		$inquiry_id          = get_user_meta( $user_id, 'persona_verification_inquiry_id', true );

		// Check for cache bust parameter
		$force_check = isset( $_GET['update_verification'] ) && $_GET['update_verification'] === '1';
		if ( $force_check ) {
			update_user_meta( $user_id, 'persona_verification_last_checked', 0 );
			$last_checked = 0;
		}

		// If we checked recently (within last hour), return cached status
		if ( $verification_status && $last_checked && ( time() - $last_checked ) < 3600 ) {
			return $verification_status === 'approved' || $verification_status === 'completed' || $verification_status === 'verified';
		}

		// If we have a specific inquiry ID, use the detailed inquiry endpoint
		if ( ! empty( $inquiry_id ) ) {
			$inquiry_data = $this->parent->get_inquiry_details( $inquiry_id );

			if ( ! is_wp_error( $inquiry_data ) && isset( $inquiry_data['attributes'] ) ) {
				$attributes  = $inquiry_data['attributes'];
				$status      = isset( $attributes['status'] ) ? $attributes['status'] : 'unknown';
				$is_verified = $status === 'approved' || $status === 'completed' || $status === 'verified';

				// Update user meta with verification status
				update_user_meta( $user_id, 'persona_verification_status', $status );
				update_user_meta( $user_id, 'persona_verification_last_checked', time() );
				if ( isset( $attributes['completed-at'] ) ) {
					update_user_meta( $user_id, 'persona_verification_completed_at', $attributes['completed-at'] );
				}

				// Process and store detailed inquiry data
				$attributes = $inquiry_data['attributes'] ?? array();
				$fields     = $attributes['fields'] ?? array();

				// Store basic inquiry information
				update_user_meta( $user_id, 'persona_verification_inquiry_id', $inquiry_data['id'] );
				update_user_meta( $user_id, 'persona_inquiry_status', $attributes['status'] ?? '' );
				update_user_meta( $user_id, 'persona_inquiry_created_at', $attributes['created-at'] ?? '' );
				update_user_meta( $user_id, 'persona_inquiry_completed_at', $attributes['completed-at'] ?? '' );

				// Store user fields if available
				if ( ! empty( $fields ) ) {
					$user_fields = array();
					foreach ( $fields as $field_name => $field_data ) {
						if ( isset( $field_data['value'] ) && ! empty( $field_data['value'] ) ) {
							$user_fields[ $field_name ] = $field_data['value'];
						}
					}
					update_user_meta( $user_id, 'persona_inquiry_fields', $user_fields );
				}

				// Store behavior data if available
				if ( isset( $attributes['behaviors'] ) ) {
					update_user_meta( $user_id, 'persona_inquiry_behaviors', $attributes['behaviors'] );
				}

				// Store relationships if available
				if ( isset( $inquiry_data['relationships'] ) ) {
					update_user_meta( $user_id, 'persona_inquiry_relationships', $inquiry_data['relationships'] );
				}

				return $is_verified;
			}
		}

		// Fallback to the original API call for users without specific inquiry ID
		$response = wp_remote_get(
			"https://withpersona.com/api/v1/inquiries?filter[reference-id]=$user_id&filter[inquiry-template-id]=$template_id",
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'Persona API error: ' . $response->get_error_message() );
			return false;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		// Check if we have valid data
		if ( empty( $body['data'] ) || ! is_array( $body['data'] ) ) {
			update_user_meta( $user_id, 'persona_verification_status', 'unverified' );
			update_user_meta( $user_id, 'persona_verification_last_checked', time() );
			return false;
		}

		$data = $body['data'];
		if ( empty( $data[0] ) ) {
			update_user_meta( $user_id, 'persona_verification_status', 'unverified' );
			update_user_meta( $user_id, 'persona_verification_last_checked', time() );
			return false;
		}

		$latest_inquiry = $data[0];
		if ( empty( $latest_inquiry['attributes'] ) ) {
			update_user_meta( $user_id, 'persona_verification_status', 'unverified' );
			update_user_meta( $user_id, 'persona_verification_last_checked', time() );
			return false;
		}

		$attributes  = $latest_inquiry['attributes'];
		$status      = isset( $attributes['status'] ) ? $attributes['status'] : 'unknown';
		$is_verified = $status === 'approved' || $status === 'completed' || $status === 'verified';

		// Update user meta with verification status
		update_user_meta( $user_id, 'persona_verification_status', $status );
		update_user_meta( $user_id, 'persona_verification_last_checked', time() );
		if ( isset( $attributes['completed-at'] ) ) {
			update_user_meta( $user_id, 'persona_verification_completed_at', $attributes['completed-at'] );
		}

		// Store the inquiry ID for future detailed checks
		if ( isset( $latest_inquiry['id'] ) ) {
			update_user_meta( $user_id, 'persona_verification_inquiry_id', $latest_inquiry['id'] );
		}

		return $is_verified;
	}

	/**
	 * Check user verification on login
	 *
	 * @param string  $user_login The user login
	 * @param WP_User $user The user object
	 */
	public function check_user_verification( $user_login, $user ) {
		if ( ! $this->is_user_verified( $user->ID ) ) {
			// Add notice for unverified users
			add_action(
				'admin_notices',
				function () {
					$verification_url = $this->get_verification_page_url();
					echo '<div class="notice notice-warning"><p>';
					esc_html_e( 'Your account is not verified with Persona. ', 'wp-withpersona' );
					if ( $verification_url ) {
						echo '<a href="' . esc_url( $verification_url ) . '">' . esc_html__( 'Complete verification now', 'wp-withpersona' ) . '</a>';
					}
					echo '</p></div>';
				}
			);
		}
	}

	/**
	 * Redirect unverified users to verification page
	 */
	private function redirect_unverified_user() {
		if ( ! is_user_logged_in() ) {
			return;
		}

		$user_id          = get_current_user_id();
		$verification_url = $this->get_verification_page_url();

		// Allow administrators to access wp-admin even if not verified
		if ( current_user_can( 'manage_options' ) ) {
			$current_url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			if ( strpos( $current_url, '/wp-admin/' ) !== false ) {
				return;
			}
		}

		$is_verified = $this->is_user_verified( $user_id );

		// If user is verified and trying to access verification page, redirect them
		$current_url                       = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$verification_page_id              = get_option( 'wpwithpersona_verification_page_id' );
		$verification_page_url             = get_permalink( $verification_page_id );
		$verification_path                 = parse_url( $verification_page_url, PHP_URL_PATH );
		$verification_path_with_cache_bust = $verification_path . '?update_verification=1';

		if ( $is_verified && ( $current_url === $verification_path || $current_url === $verification_path_with_cache_bust ) ) {
			wp_safe_redirect( home_url( '/wp-admin/' ) );
			exit;
		}

		// Don't redirect if user is verified
		if ( $is_verified ) {
			return;
		}

		// Redirect to verification page
		wp_safe_redirect( $verification_url );
		exit;
	}

	/**
	 * Check verification on page load for logged-in users
	 */
	public function check_verification_on_page_load() {
		// Don't check on logout-related URLs
		$current_url = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';

		// Skip verification check on logout, login, and admin-ajax URLs
		if ( strpos( $current_url, 'wp-login.php?action=logout' ) !== false ||
			strpos( $current_url, 'wp-login.php' ) !== false ||
			strpos( $current_url, 'wp-admin/admin-ajax.php' ) !== false ) {
			return;
		}

		// Don't check on the verification page itself
		$verification_page_id  = get_option( 'wpwithpersona_verification_page_id' );
		$verification_page_url = get_permalink( $verification_page_id );
		$verification_path     = parse_url( $verification_page_url, PHP_URL_PATH );

		if ( $current_url === $verification_path ) {
			return;
		}

		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			if ( ! $this->check_required_settings() ) {
				return;
			}

			// Always check verification status and redirect if needed
			$this->redirect_unverified_user();
		}
	}

	/**
	 * Get the verification page URL for redirect after completion
	 *
	 * @return string The verification page URL
	 */
	protected function get_verification_redirect_url() {
		$verification_url = $this->get_verification_page_url();
		if ( $verification_url ) {
			return $verification_url . '?update_verification=1';
		}
		return '';
	}

	/**
	 * Get the inquiry ID for the current user
	 *
	 * @return string The inquiry ID or empty string
	 */
	protected function get_inquiry_id() {
		if ( is_user_logged_in() ) {
			$inquiry_id = get_user_meta( get_current_user_id(), 'persona_verification_inquiry_id', true );
			if ( ! empty( $inquiry_id ) ) {
				return $inquiry_id;
			}
		}
		return $this->get_current_inquiry_id();
	}

	/**
	 * Add verification meta to new users
	 *
	 * @param int $user_id The new user ID
	 */
	public function add_verification_meta( $user_id ) {
		update_user_meta( $user_id, 'persona_verification_status', 'unverified' );
		update_user_meta( $user_id, 'persona_verification_last_checked', 0 );
		update_user_meta( $user_id, 'persona_verification_timestamp', 0 );
		update_user_meta( $user_id, 'persona_verification_inquiry_id', '' );
	}

	/**
	 * Get the verification page URL
	 *
	 * @return string The verification page URL
	 */
	public function get_verification_page_url() {
		$page_id = get_option( 'wpwithpersona_verification_page_id' );
		// error_log('Verification page ID: ' . $page_id);

		if ( $page_id ) {
			$url = get_permalink( $page_id );
			// error_log('Generated verification URL: ' . $url);
			return $url;
		}
		error_log( 'No verification page ID found' );
		return '';
	}

	/**
	 * Create verification page if it doesn't exist
	 */
	public function create_verification_page() {
		$page_id = get_option( 'wpwithpersona_verification_page_id' );

		if ( ! $page_id ) {
			$page_data = array(
				'post_title'     => __( 'Identity Verification', 'wp-withpersona' ),
				'post_content'   => '<!-- wp:shortcode -->
[persona_verification]
<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_author'    => 1,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			);

			$page_id = wp_insert_post( $page_data );

			if ( $page_id ) {
				// Set the page template
				update_post_meta( $page_id, '_wp_page_template', 'default' );
				update_option( 'wpwithpersona_verification_page_id', $page_id );
			}
		} else {
			// Update existing page content
			$page_data = array(
				'ID'           => $page_id,
				'post_content' => '<!-- wp:shortcode -->
[persona_verification]
<!-- /wp:shortcode -->',
			);
			wp_update_post( $page_data );
		}
	}

	/**
	 * Add verification link to user profile
	 */
	public function add_verification_link_to_profile( $user ) {
		if ( ! current_user_can( 'edit_user', $user->ID ) ) {
			return;
		}
		?>
		<h3><?php esc_html_e( 'Persona Verification', 'wp-withpersona' ); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="persona_verification"><?php esc_html_e( 'Verification Status', 'wp-withpersona' ); ?></label></th>
				<td>
					<?php
					$verification_status = get_user_meta( $user->ID, 'persona_verification_status', true );
					$verification_time   = get_user_meta( $user->ID, 'persona_verification_timestamp', true );
					if ( $verification_status === 'verified' ) {
						echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . esc_html__( 'Verified', 'wp-withpersona' );
						if ( $verification_time ) {
							echo '<p class="description">' . esc_html__( 'Verified on: ', 'wp-withpersona' ) . date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $verification_time ) . '</p>';
						}
					} else {
						echo '<span class="dashicons dashicons-warning" style="color: #ffb900;"></span> ' . esc_html__( 'Not Verified', 'wp-withpersona' );
						echo '<p class="description"><a href="' . esc_url( $this->get_verification_page_url() ) . '">' . esc_html__( 'Complete verification', 'wp-withpersona' ) . '</a></p>';
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render verification content via shortcode
	 *
	 * This method is the single source of truth for Persona verification rendering.
	 * It returns buffered content and includes JavaScript for handling verification
	 * completion events and page redirects after successful verification.
	 *
	 * Features:
	 * - Returns buffered content (ob_start/ob_get_clean)
	 * - Includes JavaScript event handling for verification completion
	 * - Processes shortcode context and user verification status
	 * - Handles page redirects after verification completion
	 * - Complete WordPress integration with proper hooks and filters
	 *
	 * Usage: Called automatically via [persona_verification] and [wp_withpersona] shortcodes
	 *
	 * @return string The rendered verification HTML content
	 */
	public function render_verification_content() {
		$reference_id        = $this->get_reference_id();
		$verification_status = $this->get_verification_status();
		$settings            = $this->get_persona_settings();
		$inquiry_id          = $this->get_inquiry_id();

		if ( ! $this->are_settings_configured() ) {
			$this->render_configuration_error();
			return;
		}

		ob_start();
		$this->render_persona_container( $verification_status, $inquiry_id );
		$this->render_persona_styles();
		$this->render_persona_scripts(
			$settings['template_id'],
			$settings['environment_id'],
			$reference_id,
			$verification_status
		);

		// Add JavaScript to handle verification completion
		?>
		<script>
			document.addEventListener('DOMContentLoaded', function() {
				// Get verification redirect URL from backend
				var verificationRedirectUrl = '<?php echo esc_js( $this->get_verification_redirect_url() ); ?>';
				
				// Check if we're on the verification page
				var isOnVerificationPage = window.location.pathname === '<?php echo esc_js( parse_url( $this->get_verification_page_url(), PHP_URL_PATH ) ); ?>';
				
				// Listen for Persona completion event
				jQuery(document).on('personaVerification', function(event, status, inquiryId) {
					console.log('personaVerification event triggered!');
					console.log('Status:', status);
					console.log('Inquiry ID:', inquiryId);
					
					// Only redirect if we're on the verification page
					if (isOnVerificationPage) {
						if (verificationRedirectUrl) {
							window.location.href = verificationRedirectUrl;
						} else {
							// Fallback to current page if no verification URL is set
							window.location.href = window.location.pathname + '?update_verification=1';
						}
					} else {
						// If not on verification page, just update the status without redirecting
						console.log('Persona verification completed, but not on verification page - no redirect needed');
					}
				});
			});
		</script>
		<?php

		return ob_get_clean();
	}

	/**
	 * Process the verification shortcode in the content
	 */
	public function process_verification_shortcode( $content ) {
		global $post;

		if ( is_page() && $post && $post->ID == get_option( 'wpwithpersona_verification_page_id' ) ) {
			return do_shortcode( '[persona_verification]' );
		}

		return $content;
	}

	/**
	 * Main WpWithPersona_Verification Instance
	 *
	 * Ensures only one instance of WpWithPersona_Verification is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WpWithPersona()
	 * @return Main WpWithPersona_Verification instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-withpersona' ), $this->parent->_version );
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-withpersona' ), $this->parent->_version );
	}

	/**
	 * Force recreation of the verification page
	 */
	public function recreate_verification_page() {
		delete_option( 'wpwithpersona_verification_page_id' );
		$this->create_verification_page();
	}

	/**
	 * Check verification page status and show notice if needed
	 */
	public function check_verification_page_status() {
		$page_id = get_option( 'wpwithpersona_verification_page_id' );
		if ( ! $page_id || ! get_post( $page_id ) ) {
			?>
			<div class="notice notice-warning">
				<p><?php esc_html_e( 'The Persona verification page needs to be recreated.', 'wp-withpersona' ); ?></p>
				<p>
					<a href="<?php echo esc_url( add_query_arg( 'recreate_verification_page', '1' ) ); ?>" class="button button-primary">
						<?php esc_html_e( 'Recreate Page', 'wp-withpersona' ); ?>
					</a>
				</p>
			</div>
			<?php
		}
	}



	/**
	 * Handle AJAX request to update user verification status
	 */
	public function handle_update_user_verification_status() {
		check_ajax_referer( 'update_user_verification_status', 'nonce' );

		if ( is_user_logged_in() ) {
			$user_id     = get_current_user_id();
			$is_verified = isset( $_POST['is_verified'] ) ? (bool) $_POST['is_verified'] : false;

			// Preserve existing inquiry ID
			$existing_inquiry_id = get_user_meta( $user_id, 'persona_verification_inquiry_id', true );

			update_user_meta( $user_id, 'persona_verification_status', $is_verified ? 'verified' : 'unverified' );
			update_user_meta( $user_id, 'persona_verification_last_checked', time() );

			// Restore inquiry ID if it was lost
			if ( $existing_inquiry_id && ! get_user_meta( $user_id, 'persona_verification_inquiry_id', true ) ) {
				update_user_meta( $user_id, 'persona_verification_inquiry_id', $existing_inquiry_id );
			}

			wp_send_json_success( 'User verification status updated' );
		}

		wp_send_json_error( 'User not logged in' );
	}
}
