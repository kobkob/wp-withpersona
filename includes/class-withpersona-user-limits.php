<?php

/**
 * Handles user registration limits functionality
 *
 * @package WP_WithPersona
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class WP_WithPersona_User_Limits {

	/**
	 * Initialize the class
	 */
	public function __construct() {
		add_action( 'ps_fm_after_registration_form_save_action', array( $this, 'check_daily_registration_limit' ), 10, 4 );
	}

	/**
	 * Check if daily registration limit has been reached
	 *
	 * @param int   $output   The output value
	 * @param int   $save_id  The save ID
	 * @param array $values   The form values
	 * @param int   $user_id  The user ID
	 * @return void
	 */
	public function check_daily_registration_limit( $output, $save_id, $values, $user_id ) {
		error_log( 'WP WithPersona: Registration form submission started' );
		error_log( 'WP WithPersona: Form data: ' . print_r( $_POST, true ) );

		// Handle WP_Error object for user_id
		if ( is_wp_error( $user_id ) ) {
			error_log( 'WP WithPersona: User ID is WP_Error: ' . $user_id->get_error_message() );
			return; // Exit early if user_id is an error
		}

		error_log( 'WP WithPersona: User ID: ' . $user_id );

		// Handle WP_Error object for save_id
		if ( is_wp_error( $save_id ) ) {
			error_log( 'WP WithPersona: Save ID is WP_Error: ' . $save_id->get_error_message() );
			return; // Exit early if save_id is an error
		}

		error_log( 'WP WithPersona: Save ID: ' . $save_id );
		error_log( 'WP WithPersona: Values: ' . print_r( $values, true ) );

		// Get today's registrations count
		$today = date( 'Y-m-d' );
		$args  = array(
			'role'        => 'freelancer',
			'date_query'  => array(
				array(
					'after'     => $today,
					'inclusive' => true,
				),
			),
			'count_total' => true,
		);

		$daily_registrations = count_users_by_date( $args );

		// Handle potential error from count_users_by_date
		if ( is_wp_error( $daily_registrations ) ) {
			error_log( 'WP WithPersona: Error counting daily registrations: ' . $daily_registrations->get_error_message() );
			return; // Exit early if counting failed
		}

		error_log( 'WP WithPersona: Daily registrations count: ' . $daily_registrations );

		// Get the daily limit from settings
		$daily_limit = get_option( 'wp_withpersona_limit_users', 50 );

		// Check if daily limit is reached
		if ( $daily_registrations >= $daily_limit ) {
			// Only delete user if save_id is a valid integer
			if ( is_numeric( $save_id ) && $save_id > 0 ) {
				wp_delete_user( $save_id ); // Delete the just-created user
			}
			echo json_encode(
				array(
					'success' => false,
					'title'   => 'Error',
					'message' => 'Daily registration limit reached. Please try again tomorrow.',
				)
			);
			exit;
		}
	}
}

// Initialize the class
new WP_WithPersona_User_Limits();
