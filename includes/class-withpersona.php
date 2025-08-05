<?php
/**
 * Main plugin class file.
 *
 * @package WpWithPersona/Includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Main plugin class.
 */
class WpWithPersona {


	/**
	 * The single instance of WpWithPersona.
	 *
	 * @var     object
	 * @access  private
	 * @since   1.0.0
	 */
	private static $_instance = null; //phpcs:ignore

	/**
	 * Local instance of WpWithPersona_Admin_API
	 *
	 * @var WpWithPersona_Admin_API|null
	 */
	public $admin = null;

	/**
	 * Settings class object
	 *
	 * @var     object
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = null;
	/**
	 * The version number.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_version; //phpcs:ignore

	/**
	 * The token.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $_token; //phpcs:ignore

	/**
	 * The main plugin file.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $file;

	/**
	 * The main plugin directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $dir;

	/**
	 * The plugin assets directory.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_dir;

	/**
	 * The plugin assets URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $assets_url;

	/**
	 * The ib admin URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $admin_url;

	/**
	 * The ib test URL.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $test_url;

	/**
	 * Suffix for JavaScripts.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $script_suffix;

	/**
	 * Constructor funtion.
	 *
	 * @param string $file File constructor.
	 * @param string $version Plugin version.
	 */
	public function __construct( $file = '', $version = '1.0.0' ) {
		$this->_version = $version;
		$this->_token   = 'wp_with_persona';

		// Register frontend assets
		add_action( 'wp_enqueue_scripts', array( $this, 'register_frontend_assets' ) );

		// Load plugin environment variables.
		$this->file       = $file;
		$this->dir        = dirname( $this->file );
		$this->assets_dir = trailingslashit( $this->dir ) . 'assets';
		$this->assets_url = esc_url( trailingslashit( plugins_url( '/assets/', $this->file ) ) );

		// $this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '.min';
		$this->script_suffix = defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ? '' : '';
		$this->admin_url     = 'wp-withpersona-dashboard';
		$this->test_url      = 'persona_test_page';

		register_activation_hook( $this->file, array( $this, 'install' ) );

		// Shortcode
		add_action( 'widgets_init', array( $this, 'shortcodes_init' ) );

		// Admin Stuff
		if ( is_admin() ) {
			// Load API for generic admin functions.
			$this->admin = new WpWithPersona_Admin_API();

			// Load admin JS & CSS.
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_scripts' ), 10, 1 );
			add_action( 'admin_enqueue_scripts', array( $this, 'admin_enqueue_styles' ), 10, 1 );
			// Add main menu
			add_action( 'admin_menu', array( $this, 'wp_withpersona_dashboard' ), 10, 1 );
		}

		// Handle localisation.
		$this->load_plugin_textdomain();
		add_action( 'init', array( $this, 'load_localisation' ), 0 );

		// AJAX handlers
		add_action( 'wp_ajax_save_persona_status', array( $this, 'save_persona_status' ) );
		add_action( 'wp_ajax_nopriv_save_persona_status', array( $this, 'save_persona_status' ) );
		add_action( 'wp_ajax_get_inquiry_details', array( $this, 'get_inquiry_details_ajax' ) );
		add_action( 'wp_ajax_nopriv_get_inquiry_details', array( $this, 'get_inquiry_details_ajax' ) );

		// Update Persona reference ID when user registers
		add_action( 'user_register', array( $this, 'update_persona_reference_id' ) );

		// Display inquiry data in admin area
		add_action( 'admin_notices', array( $this, 'display_inquiry_data_admin' ) );
	} // End __construct ()





	/**
	 * Initialize shortcodes of the site.
	 *
	 * @since    1.0.0
	 */
	public function shortcodes_init() {
		// Register primary shortcode
		add_shortcode( WPWITHPERSONASHORTCODE, array( $this, 'wp_withpersona_shortcode' ) );

		// Register alternative shortcode if it doesn't exist
		if ( ! shortcode_exists( WPWITHPERSONASHORTCODEOPT ) ) {
			add_shortcode( WPWITHPERSONASHORTCODEOPT, array( $this, 'wp_withpersona_shortcode' ) );
		}
	}

	/**
	 * Add dashboard menu
	 *
	 * @since    1.0.1
	 */
	public function wp_withpersona_dashboard() {
		add_menu_page( __( 'Persona Dashboard', 'wp-withpersona' ), __( 'Persona Dashboard', 'wp-withpersona' ), 'manage_options', 'wp-withpersona-dashboard', array( $this, 'wp_withpersona_dashboard_page' ), 'dashicons-id', 30 );
	}

	/**
	 * Dashboard page
	 *
	 * @since    1.0.1
	 */
	public function wp_withpersona_dashboard_page() {
		$file_name = plugin_dir_path( __DIR__ ) . 'admin/dashboard.php';

		if ( file_exists( $file_name ) ) {
			require_once $file_name;
		} else {
			echo "<h1>Error, $file_name not found</h1>";
		}
	}

	/**
	 * Return the form short code from template.
	 *
	 * @since    1.0.0
	 */
	public function wp_withpersona_shortcode() {
		// Use the more complete verification system instead of the simple Persona_Code
		// This provides better integration with WordPress and proper event handling
		$verification = WpWithPersona_Verification::instance( $this );
		return $verification->render_verification_content();
	}



	/**
	 * Admin enqueue style.
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return void
	 */
	public function admin_enqueue_styles( $hook = '' ) {
		wp_register_style( $this->_token . '-admin', esc_url( $this->assets_url ) . 'css/admin.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-admin' );
		// wp_enqueue_style( 'datetime-picker', esc_url( $this->assets_url ) .'css/jquery.datetimepicker.css' );
		// wp_enqueue_style( 'jquery-wp-css', esc_url( $this->assets_url ) .'css/jquery-ui-1.13.2.custom/jquery-ui.min.css' );
	} // End admin_enqueue_styles ()

	/**
	 * Load admin Javascript.
	 *
	 * @access  public
	 *
	 * @param string $hook Hook parameter.
	 *
	 * @return  void
	 * @since   1.0.0
	 */
	public function admin_enqueue_scripts( $hook = '' ) {
		wp_register_script( $this->_token . '-admin', esc_url( $this->assets_url ) . 'js/admin' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		wp_enqueue_script( 'jquery-ui-tabs' );
		wp_enqueue_script( 'jquery-ui-button' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'jquery-ui-selectmenu' );
		wp_enqueue_script( 'jquery-ui-autocomplete' );
		wp_enqueue_script( $this->_token . '-admin' );
		// wp_enqueue_script( 'datetime-picker', esc_url( $this->assets_url ) .'js/jquery.datetimepicker.js', array( 'jquery' ) );

		// Load panel js script if it is the admin page
		$screen       = get_current_screen();
		$current_slug = $screen->base;
		// echo "<h1>".$current_slug."</h1>";
		if ( $current_slug === 'toplevel_page_' . $this->admin_url ) {
			wp_enqueue_script( 'panel', esc_url( $this->assets_url ) . 'js/admin/panel.js', array( 'wp-api' ) );
		}
		if ( $current_slug === 'wp_withpersona_page_wp-withpersona-settings' ) {
			wp_enqueue_script( 'settings', esc_url( $this->assets_url ) . 'js/admin/settings.js', array( 'wp-api' ) );
		}
	} // End admin_enqueue_scripts ()

	/**
	 * Load plugin localisation
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_localisation() {
		load_plugin_textdomain( 'languages', false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_localisation ()

	/**
	 * Load plugin textdomain
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function load_plugin_textdomain() {
		$domain = 'languages';

		$locale = apply_filters( 'plugin_locale', get_locale(), $domain );

		load_textdomain( $domain, WP_LANG_DIR . '/' . $domain . '/' . $domain . '-' . $locale . '.mo' );
		load_plugin_textdomain( $domain, false, dirname( plugin_basename( $this->file ) ) . '/lang/' );
	} // End load_plugin_textdomain ()

	/**
	 * Main WpWithPersona Instance
	 *
	 * Ensures only one instance of WpWithPersona is loaded or can be loaded.
	 *
	 * @param string $file File instance.
	 * @param string $version Version parameter.
	 *
	 * @return Object WpWithPersona instance
	 * @see WpWithPersona()
	 * @since 1.0.0
	 * @static
	 */
	public static function instance( $file = '', $version = '1.0.0' ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $file, $version );
		}

		return self::$_instance;
	} // End instance ()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Cloning of WpWithPersona is forbidden', 'wp-withpersona' ) ), esc_attr( $this->_version ) );
	} // End __clone ()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, esc_html( __( 'Unserializing instances of WpWithPersona is forbidden', 'wp-withpersona' ) ), esc_attr( $this->_version ) );
	} // End __wakeup ()

	/**
	 * Installation. Runs on activation.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function install() {
		$this->_log_version_number();
	} // End install ()

	/**
	 * Log the plugin version number.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	private function _log_version_number()
	{ //phpcs:ignore
		update_option( $this->_token . '_version', $this->_version );
	} // End _log_version_number ()



	/**
	 * Save Persona verification status
	 */
	public function save_persona_status() {
		check_ajax_referer( 'save_persona_status', 'nonce' );

		if ( ! isset( $_POST['status'] ) ) {
			wp_send_json_error( 'Status not provided' );
		}

		$status     = sanitize_text_field( $_POST['status'] );
		$inquiry_id = isset( $_POST['inquiryId'] ) ? sanitize_text_field( $_POST['inquiryId'] ) : '';

		// Validate status
		$valid_statuses = array( 'created', 'pending', 'completed', 'expired', 'failed', 'needs_review', 'approved', 'declined' );
		if ( ! in_array( $status, $valid_statuses ) ) {
			wp_send_json_error( 'Invalid status' );
		}

		// Start session if not already started
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		// Save status to session
		$_SESSION['persona_verification_status'] = $status;
		if ( $inquiry_id ) {
			$_SESSION['persona_verification_inquiry_id'] = $inquiry_id;
		}

		// Log the save operation
		error_log( 'WP WithPersona: Saving verification status - Status: ' . $status . ', Inquiry ID: ' . $inquiry_id . ', Session ID: ' . session_id() );

		// If user is logged in, also save to user meta
		if ( is_user_logged_in() ) {
			$user_id = get_current_user_id();
			update_user_meta( $user_id, 'persona_verification_status', $status );
			if ( $inquiry_id ) {
				update_user_meta( $user_id, 'persona_verification_inquiry_id', $inquiry_id );
			}
			update_user_meta( $user_id, 'persona_verification_last_checked', time() );
			
			error_log( 'WP WithPersona: Saved to user meta for user ID: ' . $user_id );
		} else {
			error_log( 'WP WithPersona: User not logged in, saved to session only' );
		}

		wp_send_json_success( array( 'status' => $status, 'inquiry_id' => $inquiry_id ) );
	}

	/**
	 * Update Persona reference ID when user registers
	 */
	public function update_persona_reference_id( $user_id ) {
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		// If we have a session-based reference ID, update it to the user ID
		if ( isset( $_SESSION['persona_reference_id'] ) ) {
			// Get the verification status from session
			$verification_status = isset( $_SESSION['persona_verification_status'] ) ? $_SESSION['persona_verification_status'] : 'not_started';
			$inquiry_id          = isset( $_SESSION['persona_verification_inquiry_id'] ) ? $_SESSION['persona_verification_inquiry_id'] : '';

			// Save verification status to user meta
			update_user_meta( $user_id, 'persona_verification_status', $verification_status );

			// Save inquiry ID to user meta if it exists
			if ( $inquiry_id ) {
				update_user_meta( $user_id, 'persona_verification_inquiry_id', $inquiry_id );
			}

			// Clear session data
			unset( $_SESSION['persona_reference_id'] );
			unset( $_SESSION['persona_verification_status'] );
			unset( $_SESSION['persona_verification_inquiry_id'] );
		}
	}

	/**
	 * Localize script data for frontend
	 */
	public function localize_script_data() {
		wp_localize_script(
			'wp-withpersona-frontend',
			'wpWithPersona',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonces'  => array(
					'save_persona_status' => wp_create_nonce( 'save_persona_status' ),
					'get_inquiry_details' => wp_create_nonce( 'get_inquiry_details' ),
				),
			)
		);
	}

	/**
	 * Register frontend scripts and styles
	 */
	public function register_frontend_assets() {
		// Register and enqueue frontend CSS
		wp_register_style(
			'wp-withpersona-frontend',
			plugins_url( '/assets/css/frontend.css', $this->file ),
			array(),
			$this->_version
		);
		wp_enqueue_style( 'wp-withpersona-frontend' );

		// Register and enqueue frontend JS
		wp_register_script(
			'wp-withpersona-frontend',
			plugins_url( '/assets/js/frontend.js', $this->file ),
			array( 'jquery' ),
			$this->_version,
			true
		);
		wp_enqueue_script( 'wp-withpersona-frontend' );

		// Localize script data
		$this->localize_script_data();
	}

	/**
	 * Retrieve inquiry details from Persona API
	 *
	 * @param string $inquiry_id The inquiry ID to retrieve
	 * @return array|WP_Error The inquiry data or WP_Error on failure
	 */
	public function get_inquiry_details( $inquiry_id ) {
		$api_key = get_option( 'wpwithpersona_api_key' );

		if ( empty( $api_key ) ) {
			return new WP_Error( 'no_api_key', 'Persona API key not configured' );
		}

		if ( empty( $inquiry_id ) ) {
			return new WP_Error( 'no_inquiry_id', 'Inquiry ID is required' );
		}

		$api_url = 'https://api.withpersona.com/api/v1/inquiries/' . urlencode( $inquiry_id );

		$response = wp_remote_get(
			$api_url,
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'timeout' => 30,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( $response_code !== 200 ) {
			$error_message = 'API request failed with status ' . $response_code;
			if ( ! empty( $response_body ) ) {
				$error_data = json_decode( $response_body, true );
				if ( $error_data && isset( $error_data['errors'] ) ) {
					$error_message .= ': ' . implode( ', ', array_column( $error_data['errors'], 'title' ) );
				}
			}
			return new WP_Error( 'api_error', $error_message );
		}

		$data = json_decode( $response_body, true );

		if ( ! $data || ! isset( $data['data'] ) ) {
			return new WP_Error( 'invalid_response', 'Invalid response from Persona API' );
		}

		return $data['data'];
	}

	/**
	 * Get stored inquiry data for a user
	 *
	 * @param int $user_id The user ID to get data for
	 * @return array The inquiry data
	 */
	public function get_user_inquiry_data( $user_id = null ) {
		if ( ! $user_id ) {
			$user_id = get_current_user_id();
		}

		if ( ! $user_id ) {
			return array();
		}

		$inquiry_data = array(
			'inquiry_id'          => get_user_meta( $user_id, 'persona_verification_inquiry_id', true ),
			'status'              => get_user_meta( $user_id, 'persona_inquiry_status', true ),
			'created_at'          => get_user_meta( $user_id, 'persona_inquiry_created_at', true ),
			'completed_at'        => get_user_meta( $user_id, 'persona_inquiry_completed_at', true ),
			'fields'              => get_user_meta( $user_id, 'persona_inquiry_fields', true ),
			'behaviors'           => get_user_meta( $user_id, 'persona_inquiry_behaviors', true ),
			'relationships'       => get_user_meta( $user_id, 'persona_inquiry_relationships', true ),
			'verification_status' => get_user_meta( $user_id, 'persona_verification_status', true ),
		);

		// Convert stored data back to arrays if they were serialized
		if ( is_string( $inquiry_data['fields'] ) ) {
			$inquiry_data['fields'] = maybe_unserialize( $inquiry_data['fields'] );
		}
		if ( is_string( $inquiry_data['behaviors'] ) ) {
			$inquiry_data['behaviors'] = maybe_unserialize( $inquiry_data['behaviors'] );
		}
		if ( is_string( $inquiry_data['relationships'] ) ) {
			$inquiry_data['relationships'] = maybe_unserialize( $inquiry_data['relationships'] );
		}

		return $inquiry_data;
	}

	/**
	 * Display inquiry data in admin area
	 */
	public function display_inquiry_data_admin() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$user_id      = get_current_user_id();
		$inquiry_data = $this->get_user_inquiry_data( $user_id );

		if ( empty( $inquiry_data['inquiry_id'] ) ) {
			return;
		}

		?>
		<div class="notice notice-info">
			<h3><?php esc_html_e( 'Persona Inquiry Data', 'wp-withpersona' ); ?></h3>
			<table class="form-table">
				<tr>
					<th><?php esc_html_e( 'Inquiry ID', 'wp-withpersona' ); ?></th>
					<td><?php echo esc_html( $inquiry_data['inquiry_id'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Status', 'wp-withpersona' ); ?></th>
					<td><?php echo esc_html( $inquiry_data['status'] ); ?></td>
				</tr>
				<tr>
					<th><?php esc_html_e( 'Verification Status', 'wp-withpersona' ); ?></th>
					<td><?php echo esc_html( $inquiry_data['verification_status'] ); ?></td>
				</tr>
				<?php if ( ! empty( $inquiry_data['fields'] ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Collected Fields', 'wp-withpersona' ); ?></th>
					<td>
						<ul>
						<?php foreach ( $inquiry_data['fields'] as $field_name => $field_value ) : ?>
							<li><strong><?php echo esc_html( $field_name ); ?>:</strong> <?php echo esc_html( $field_value ); ?></li>
						<?php endforeach; ?>
						</ul>
					</td>
				</tr>
				<?php endif; ?>
				<?php if ( ! empty( $inquiry_data['behaviors'] ) ) : ?>
				<tr>
					<th><?php esc_html_e( 'Behavior Data', 'wp-withpersona' ); ?></th>
					<td>
						<details>
							<summary><?php esc_html_e( 'View Behavior Data', 'wp-withpersona' ); ?></summary>
							<pre><?php echo esc_html( print_r( $inquiry_data['behaviors'], true ) ); ?></pre>
						</details>
					</td>
				</tr>
				<?php endif; ?>
			</table>
		</div>
		<?php
	}

	/**
	 * AJAX handler to retrieve inquiry details
	 */
	public function get_inquiry_details_ajax() {
		check_ajax_referer( 'get_inquiry_details', 'nonce' );

		if ( ! isset( $_POST['inquiry_id'] ) ) {
			wp_send_json_error( 'Inquiry ID not provided' );
		}

		$inquiry_id   = sanitize_text_field( $_POST['inquiry_id'] );
		$inquiry_data = $this->get_inquiry_details( $inquiry_id );

		if ( is_wp_error( $inquiry_data ) ) {
			wp_send_json_error( $inquiry_data->get_error_message() );
		}

		wp_send_json_success( $inquiry_data );
	}
}
