<?php
/**
 * Plugin Name:     WP WithPersona
 * Plugin URI:      https://wpwithpersona.com/
 * Description:     Integrates Woocommerce WordPress installation with Persona
 * Author:          Monsenhor
 * Author URI:      https://kobkob.org/
 * Text Domain:     wp-withpersona
 * Domain Path:     /languages
 * Version:         1.2
 *
 * @package         WP-WithPersona
 */

// Your code starts here.

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

// Global Shortcodes
define( 'WPWITHPERSONASHORTCODE', 'wpwpersona' );


// Load plugin classes
//
//
require_once 'includes/class-withpersona.php';
require_once 'includes/class-withpersona-settings.php';
require_once 'includes/class-withpersona-admin-api.php';

/**
 * Returns the main instance of WpWithPersona to prevent the need to use globals.
 *
 * @since  1.0.0
 * @return object WP-WITHPERSONA
 */
function wp_with_persona() {
        $instance = WpWithPersona::instance( __FILE__, '1.0.0' );

        if ( is_null( $instance->settings ) ) {
                $instance->settings = WpWithPersona_Settings::instance( $instance );
        }

        return $instance;
}

/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-wp-with-persona-activator.php
 */
function activate_wp_with_persona() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-wp-with-persona-activator.php';
	WP_With_Persona_Activator::activate();
}


wp_with_persona();

register_activation_hook( __FILE__, 'activate_wp_with_persona' );

/*! \mainpage WordPress With Persona
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
 * Custom plugin implementing a AQL server as a smart code 
 * It uses REST tools to integrate with a mojolicious webservice.
 *
 * \section install_sec Installation
 *
 * \subsection step1 Step 1: Install similarly as any custom plugin
 *

 */

