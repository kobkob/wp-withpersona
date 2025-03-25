<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WpWithPersona_Settings {


	/**
	 * The single instance of WpWithPersona_Settings.
	 *
	 * @var     object
	 * @access  private
	 * @since     1.0.0
	 */
	private static $_instance = null;

	/**
	 * The main plugin object.
	 *
	 * @var     object
	 * @access  public
	 * @since     1.0.0
	 */
	public $parent = null;

	/**
	 * Prefix for plugin settings.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $base = '';

	/**
	 * Available settings for plugin.
	 *
	 * @var     array
	 * @access  public
	 * @since   1.0.0
	 */
	public $settings = array();

	/**
	 * Default settings tag.
	 *
	 * @var     string
	 * @access  public
	 * @since   1.0.0
	 */
	public $default_tab = 'account';

	public function __construct( $parent ) {
		$this->parent = $parent;

		$this->base = 'wpwithpersona_';

		// Initialise settings
		add_action( 'init', array( $this, 'init_settings' ), 11 );

		// Register plugin settings
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// Add settings page to menu
		add_action( 'admin_menu', array( $this, 'add_menu_item' ) );

		// Add settings link to plugins page
		add_filter( 'plugin_action_links_' . plugin_basename( $this->parent->file ), array( $this, 'add_settings_link' ) );
	}

	/**
	 * Initialise settings
	 *
	 * @return void
	 */
	public function init_settings() {
		$this->settings = $this->settings_fields();
	}

	/**
	 * Add settings page and dashboard to admin menu
	 *
	 * @return void
	 */
	public function add_menu_item() {
		$page = add_options_page( __( 'WP With Persona Settings', 'wp-withpersona' ), __( 'WP With Persona Settings', 'wp-withpersona' ), 'manage_options', $this->parent->_token . '_settings', array( $this, 'settings_page' ) );
		add_action( 'admin_print_styles-' . $page, array( $this, 'settings_assets' ) );
	}

	/**
	 * Load settings JS & CSS
	 *
	 * @return void
	 */
	public function settings_assets() {

		// We're including the farbtastic script & styles here because they're needed for the colour picker
		// If you're not including a colour picker field then you can leave these calls out as well as the farbtastic dependency for the wpt-admin-js script below
		wp_enqueue_style( 'farbtastic' );
		wp_enqueue_script( 'farbtastic' );

		// We're including the WP media scripts here because they're needed for the image upload field
		// If you're not including an image upload then you can leave this function call out
		wp_enqueue_media();

		wp_register_script( $this->parent->_token . '-settings-js', $this->parent->assets_url . 'js/settings' . $this->parent->script_suffix . '.js', array( 'farbtastic', 'jquery' ), '1.0.0' );
		wp_enqueue_script( $this->parent->_token . '-settings-js' );
	}

	/**
	 * Add settings link to plugin list table
	 *
	 * @param  array $links Existing links
	 * @return array         Modified links
	 */
	public function add_settings_link( $links ) {
		// $this->default_tab = $this->default_tab ? $this->default_tab : 'account';
		$settings_link = '<a href="options-general.php?page=' . $this->parent->_token . '_settings&tab=' . $this->$default_tab . '>' . __( 'Settings', 'wp-withpersona' ) . '</a>';
		array_push( $links, $settings_link );
		return $links;
	}

	/**
	 * Build settings fields
	 *
	 * @return array Fields to be displayed on settings page
	 */
	private function settings_fields() {
		// $this->default_tab = 'account';
		$settings['account'] = array(
			'title'       => __( 'Account', 'wp-withpersona' ),
			'description' => __( 'Your account and keys at With Persona.', 'wp-withpersona' ),
			'fields'      => array(
				array(
					'id'          => 'api_key',
					'label'       => __( 'API Key', 'wp-withpersona' ),
					'description' => __( 'Your API Key from Persona.', 'wp-withpersona' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( 'Your API Key', 'wp-withpersona' ),
				),
				array(
					'id'          => 'api_password',
					'label'       => __( 'API Password', 'wp-withpersona' ),
					'description' => __( 'The password for the API.', 'wp-withpersona' ),
					'type'        => 'text',
					'default'     => '',
					'placeholder' => __( '**************', 'wp-withpersona' ),
				),
				array(
					'id'          => 'limit_uers',
					'label'       => __( 'Users Limit', 'wp-withpersona' ),
					'description' => __( 'The maximum users per month.', 'wp-withpersona' ),
					'type'        => 'number',
					'default'     => 50,
				),

			),
		);
		$settings['pages'] = array(
			'title'       => __( 'Pages', 'wp-withpersona' ),
			'description' => __( 'Pages where WithPersona will load', 'wp-withpersona' ),
			'fields'      => array(
				array(
					'id'          => 'add_registration_page',
					'label'       => __( 'Registration Page.', 'wp-withpersona' ),
					'description' => __( 'Use WP With Persona on the default Registration Page.', 'wp-withpersona' ),
					'type'        => 'checkbox',
					'default'     => '',
				),
				array(
					'id'          => 'add_admin_page',
					'label'       => __( 'Administration Page.', 'wp-withpersona' ),
					'description' => __( 'Use WP With Persona on the WordPress administrative pages.', 'wp-withpersona' ),
					'type'        => 'checkbox',
					'default'     => '',
				),
				array(
					'id'          => 'add_page_btn',
					'label'       => __( 'Add a page', 'wp-withpersona' ),
					'description' => __( 'Add all pages where Persona tool will load.', 'wp-withpersona' ),
					'type'        => 'text_multi',
					'default'     => '',
					'placeholder' => __( 'https://', 'wp-withpersona' ),
				),
			),
		);
		$settings          = apply_filters( $this->parent->_token . '_settings_fields', $settings );

		return $settings;
	}

	/**
	 * Register plugin settings
	 *
	 * @return void
	 */
	public function register_settings() {
		if ( is_array( $this->settings ) ) {

			// Check posted/selected tab
			$current_section = '';
			if ( isset( $_POST['tab'] ) && $_POST['tab'] ) {
				$current_section = $_POST['tab'];
			} elseif ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
				$current_section = $_GET['tab'];
			}

			foreach ( $this->settings as $section => $data ) {
				if ( $current_section && $current_section != $section ) {
					continue;
				}

				// Add section to page
				add_settings_section( $section, $data['title'], array( $this, 'settings_section' ), $this->parent->_token . '_settings' );

				foreach ( $data['fields'] as $field ) {

					// Validation callback for field
					$validation = '';
					if ( isset( $field['callback'] ) ) {
						$validation = $field['callback'];
					}

					// Register field
					$option_name = $this->base . $field['id'];
					register_setting( $this->parent->_token . '_settings', $option_name, $validation );

					// Add field to page
					add_settings_field(
						$field['id'],
						$field['label'],
						array( $this->parent->admin, 'display_field' ),
						$this->parent->_token . '_settings',
						$section,
						array(
							'field'  => $field,
							'prefix' => $this->base,
						)
					);
				}

				if ( ! $current_section ) {
					break;
				}
			}
		}
	}

	public function settings_section( $section ) {
		$html = '<p> ' . $this->settings[ $section['id'] ]['description'] . '</p>' . "\n";
		echo $html;
	}

	/**
	 * Load settings page content
	 *
	 * @return void
	 */
	public function settings_page() {

		// Build page HTML
		$html  = '<div class="wrap" id="' . $this->parent->_token . '_settings">' . "\n";
		$html .= '<div class="wpp_header">';
		$html .= '<h2>' . __( 'WP With Persona Settings', 'wp-withpersona' ) . '</h2>' . "\n";
		$html .= '<div class="wpp-help-icon"><span class="dashicons dashicons-editor-help"></span></div>';
		$html .= '</div>';

		$tab = '';
		if ( isset( $_GET['tab'] ) && $_GET['tab'] ) {
			$tab .= $_GET['tab'];
		}

		// Show page tabs
		if ( is_array( $this->settings ) && 1 < count( $this->settings ) ) {
			$html .= '<h2 class="nav-tab-wrapper">' . "\n";

			$c = 0;
			foreach ( $this->settings as $section => $data ) {

				// Set tab class
				$class = 'nav-tab';
				if ( ! isset( $_GET['tab'] ) ) {
					if ( 0 == $c ) {
						$class .= ' nav-tab-active';
					}
				} elseif ( isset( $_GET['tab'] ) && $section == $_GET['tab'] ) {
					$class .= ' nav-tab-active';
				}

				// Set tab link
				$tab_link = add_query_arg( array( 'tab' => $section ) );
				if ( isset( $_GET['settings-updated'] ) ) {
					$tab_link = remove_query_arg( 'settings-updated', $tab_link );
				}

				// Output tab
				$html .= '<a href="' . $tab_link . '" class="' . esc_attr( $class ) . '">' . esc_html( $data['title'] ) . '</a>' . "\n";

				++$c;
			}

			$html .= '</h2>' . "\n";
		}

		$html .= '<form method="post" action="options.php" enctype="multipart/form-data">' . "\n";

		// Get settings fields
		ob_start();
		settings_fields( $this->parent->_token . '_settings' );
		do_settings_sections( $this->parent->_token . '_settings' );
		// do_settings_sections( 'wp_with_persona_settings' );
		$html .= ob_get_clean();

		$html .= '<p class="submit">' . "\n";
		$html .= '<input type="hidden" name="tab" value="' . esc_attr( $tab ) . '" />' . "\n";
		$html .= '<input name="Submit" type="submit" class="button-primary" value="' . esc_attr( __( 'Save Settings', 'wp-withpersona' ) ) . '" />' . "\n";
		$html .= '</p>' . "\n";
		$html .= '</form>' . "\n";
		$html .= '</div>' . "\n";

		echo $html;
	}

	/**
	 * Main WpWithPersona_Settings Instance
	 *
	 * Ensures only one instance of WpWithPersona_Settings is loaded or can be loaded.
	 *
	 * @since 1.0.0
	 * @static
	 * @see WpWithPersona()
	 * @return Main WpWithPersona_Settings instance
	 */
	public static function instance( $parent ) {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self( $parent );
		}
		return self::$_instance;
	} // End instance()

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-withpersona' ), $this->parent->_version );
	} // End __clone()

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup() {
		_doing_it_wrong( __FUNCTION__, __( 'Cheatin&#8217; huh?', 'wp-withpersona' ), $this->parent->_version );
	} // End __wakeup()
}
