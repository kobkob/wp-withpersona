<?php

/**
 * Plugin Name:     WP WithPersona
 * Plugin URI:      https://wpwithpersona.com/
 * Description:     Integrates Woocommerce WordPress installation with Persona
 * Author:          Monsenhor
 * Author URI:      https://kobkob.org/
 * Text Domain:     wp-withpersona
 * Domain Path:     /languages
 * Version: 1.2.8
 *
 * @package         wp-withpersona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define plugin directory path.
define( 'WP_WITH_PERSONA_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );

// Global Shortcodes.
define( 'WPWITHPERSONASHORTCODE', 'wp_withpersona' );
define( 'WPWITHPERSONASHORTCODEOPT', 'persona_verification' );

// Load plugin classes.
require_once 'includes/class-withpersona.php';
require_once 'includes/class-withpersona-settings.php';
require_once 'includes/class-withpersona-admin-api.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-withpersona-verification.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-withpersona-user-limits.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-withpersona-ajax.php';

/**
 * Returns the main instance of WpWithPersona to prevent the need to use globals.
 *
 * @since  1.2.2
 * @return object WP-WITHPERSONA
 */
function wp_withpersona() {
	$instance = WpWithPersona::instance( __FILE__, '1.2.2' );

	if ( is_null( $instance->settings ) ) {
		$instance->settings = WpWithPersona_Settings::instance( $instance );
	}

	// Initialize verification.
	$verification = WpWithPersona_Verification::instance( $instance );

	return $instance;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-withpersona-activator.php
 */
function activate_wp_withpersona() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-withpersona-activator.php';
	WP_WithPersona_Activator::activate();
}

// Initialize the plugin.
wp_withpersona();

// Register activation hook.
register_activation_hook( __FILE__, 'activate_wp_withpersona' );

/*
! \mainpage WP - With Persona 1.2.2
 *
 * - by Monsenhor
 *
 * ## Features
 *
 * ## Public UI
 *
 * ## Admininstration UI
 *
 * \section intro_sec Introduction
 *
 * Custom plugin implementing a simple integration for With Persona API.
 *
 * \section install_sec Installation
 *
 * \subsection step1 Step 1: Install similarly as any custom plugin
 *

 */
