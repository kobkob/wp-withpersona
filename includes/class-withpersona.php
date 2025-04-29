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
		$this->admin_url     = 'persona_admin_page';
		$this->test_url      = 'persona_test_page';

		register_activation_hook( $this->file, array( $this, 'install' ) );
		// Load frontend JS & CSS.
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ), 10 );
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ), 10 );

		// Persona workflow
		add_action( 'register_form', array( $this, 'wp_withpersona_new_user' ), 0, 1 );
		add_action( 'register_post', array( $this, 'wp_withpersona_register_user_post' ), 10, 1 );
		// add_action( 'user_register', array( $this, 'wp_withpersona_user_register' ), 10, 1 );
		add_action( 'register_new_user', array( $this, 'wp_withpersona_new_user_register' ), 10, 1 );

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

		// Update Persona reference ID when user registers
		add_action( 'user_register', array( $this, 'update_persona_reference_id' ) );
	} // End __construct ()

	/**
	 * Register user in Persona after creation.
	 * Limit number of registrations per month
	 *
	 * @since    1.0.0
	 */
	public function wp_withpersona_new_user( $user_id ) {
		$persona_code = plugin_dir_path( __DIR__ ) . 'includes/persona_code.php';
		if ( file_exists( $persona_code ) ) {
			require_once $persona_code;
			// echo "<h1>Fake Persona</h1>";
		} else {
			echo "<h1>Error, $persona_code not found</h1>";
		}

		if ( isset( $_POST['persona_id'] ) ) {
			update_user_meta( $user_id, 'persona_id', $_POST['persona_id'] );
		}
	}

	/**
	 * Register user post after creation.
	 * Limit number of registrations per month
	 *
	 * @since    1.2
	 */
	public function wp_withpersona_register_user_post( $user_id ) {
		$persona_code = plugin_dir_path( __DIR__ ) . 'public/register_user_post.php';
		if ( file_exists( $persona_code ) ) {
			require_once $persona_code;
		} else {
			echo "<h1>Error, $persona_code not found</h1>";
		}
		return $user_id;
	}

	/**
	 * Register user after post creation.
	 * Limit number of registrations per month
	 *
	 * @since    1.0.0
	 */
	// public function wp_withpersona_user_register( $user_id ) {
	// 	$persona_code = plugin_dir_path( __DIR__ ) . 'public/user_register.php';
	// 	if ( file_exists( $persona_code ) ) {
	// 		require_once $persona_code;
	// 	} else {
	// 		echo "<h1>Error, $persona_code not found</h1>";
	// 		die( 'Error' );
	// 	}
	// 	return $user_id;
	// }

	/**
	 * Register new user after creation.
	 * Handles both regular and AJAX registration flows
	 *
	 * @param int $user_id The user ID of the newly registered user.
	 * @return int|WP_Error|void The user ID on success, WP_Error on failure, or void for AJAX responses
	 * @since    1.0.0
	 */
	public function wp_withpersona_new_user_register( $user_id ) {
		// Check if this is an AJAX request
		if ( wp_doing_ajax() ) {
			$this->handle_persona_registration( $user_id );
			return;
		}

		// For non-AJAX requests, proceed with normal flow
		$this->handle_persona_registration( $user_id );
		return $user_id;
	}

	/**
	 * Handle Persona registration process
	 *
	 * @param int $user_id The user ID to process
	 * @return void
	 */
	private function handle_persona_registration( $user_id ) {
		// Get user data
		$user = get_user_by( 'id', $user_id );
		if ( ! $user ) {
			if ( wp_doing_ajax() ) {
				wp_send_json_error( array( 'message' => 'User not found' ) );
			}
			return;
		}

		// Get Persona ID if it exists
		$persona_id = get_user_meta( $user_id, 'persona_id', true );

		// If we have a Persona ID, process it
		if ( $persona_id ) {
			// Here you would add your Persona-specific logic
			// For example, making API calls to Persona, storing additional data, etc.

			if ( wp_doing_ajax() ) {
				wp_send_json_success(
					array(
						'user_id'      => $user_id,
						'persona_id'   => $persona_id,
						'redirect_url' => home_url( '/persona-verification/' ), // Adjust this URL as needed
					)
				);
			}
		} elseif ( wp_doing_ajax() ) {
			wp_send_json_success(
				array(
					'user_id'      => $user_id,
					'redirect_url' => home_url( '/registration-complete/' ), // Adjust this URL as needed
				)
			);
		}
	}

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
		add_menu_page( __( 'Persona Dashboard', 'wp-withpersona' ), __( 'Persona Dashboard', 'wp-withpersona' ), 'manage_options', 'the-wp_withpersona-dashboard', array( $this, 'wp_withpersona_dashboard_page' ), 'dashicons-id', 0 );
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
		// get the AQL result via webservice
		// $homepage = file_get_contents( 'http://54.210.194.36:3000/table' );
		ob_start();
		// include plugin_dir_path( __DIR__ ) . 'templates/wp_withpersona-display.php';
		include plugin_dir_path( __DIR__ ) . 'includes/persona_code.php';
		$o = ob_get_clean();
		return $o;
	}

	/**
	 * Load frontend CSS.
	 *
	 * @access  public
	 * @return void
	 * @since   1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( 'wp-jquery-ui-dialog' );
		wp_register_style( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'css/frontend.css', array(), $this->_version );
		wp_enqueue_style( $this->_token . '-frontend' );
	} // End enqueue_styles ()

	/**
	 * Load frontend Javascript.
	 *
	 * @access  public
	 * @return  void
	 * @since   1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( 'wp-jquery-ui-dialog' );
		wp_register_script( $this->_token . '-frontend', esc_url( $this->assets_url ) . 'js/frontend' . $this->script_suffix . '.js', array( 'jquery' ), $this->_version, true );
		wp_enqueue_script( $this->_token . '-frontend' );
	} // End enqueue_scripts ()

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

		// Start session if not already started
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}

		// Save status to session
		$_SESSION['persona_verification_status'] = sanitize_text_field( $_POST['status'] );

		// If user is logged in, also save to user meta
		if ( is_user_logged_in() ) {
			update_user_meta( get_current_user_id(), 'persona_verification_status', sanitize_text_field( $_POST['status'] ) );
		}

		wp_send_json_success( 'Status saved' );
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

			// Save verification status to user meta
			update_user_meta( $user_id, 'persona_verification_status', $verification_status );

			// Clear session data
			unset( $_SESSION['persona_reference_id'] );
			unset( $_SESSION['persona_verification_status'] );
		}
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
	}
}
