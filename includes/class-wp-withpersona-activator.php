<?php
/**
 * The file that defines the activator plugin class
 *
 * A class definition that includes activation functions.
 *
 * @link       http://kobkob.org
 * @since      1.0.1
 *
 * @package    WP_WithPersona
 * @subpackage WP_WithPersona/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.1
 * @package    WP_WithPersona
 * @subpackage WP_WithPersona/includes
 * @author     Monsenhor <filipo@kobkob.org>
 */
class WP_WithPersona_Activator {

	/**
	 * Activate the plugin.
	 * Check versions.
	 * Create the database.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {

		/* Check PHP and WP versions */
		global $wp_version;

		$php = '8.0';
		$wp  = '6.0';

		if ( version_compare( PHP_VERSION, $php, '<' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			$message = __( 'This plugin can not be activated because it requires a PHP version greater than "). $php .__("Your PHP version can be updated by your hosting company.', 'wp-withpersona' );
			wp_die(
				'<p>' . esc_html( $message ) . '</p> <a href="' . esc_url( admin_url( 'plugins.php' ) ) . '">' . esc_html( __( 'go back', 'wp-withpersona' ) ) . '</a>'
			);
		}

		if ( version_compare( $wp_version, $wp, '<' ) ) {
			deactivate_plugins( basename( __FILE__ ) );
			wp_die(
				esc_html(
					'<p>' .
					__( 'This plugin can not be activated because it requires a WordPress version greater than ', 'wp-withpersona' ) . $wp . __( 'Please go to Dashboard &#9656; Updates to gran the latest version of WordPress .', 'wp-withpersona' ) .
					'</p> <a href="' . admin_url( 'plugins.php' ) . '">' . __( 'go back', 'wp-withpersona' ) . '</a>'
				)
			);
		}

		/* Intalls the DB for user limit records */
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name      = $wpdb->prefix . 'wp_withpersona';

		$sql = "CREATE TABLE $table_name (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			user_count_limit mediumint(9) NULL,
			description VARCHAR(255) NULL,
			datebegin datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			dateend datetime DEFAULT CURRENT_TIMESTAMP NOT NULL,
			PRIMARY KEY  (id)
		) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta( $sql );
	}
}
