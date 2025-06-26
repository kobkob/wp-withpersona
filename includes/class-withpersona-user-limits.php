<?php

/**
 * Handles user registration limits functionality
 *
 * @package WP_WithPersona
 */

if (!defined('ABSPATH')) {
  exit; // Exit if accessed directly
}

class WP_WithPersona_User_Limits
{
  /**
   * Initialize the class
   */
  public function __construct()
  {
    add_action('ps_fm_before_registration_form_save_action_frontend', array($this, 'check_daily_registration_limit'), 10, 4);
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
  public function check_daily_registration_limit($output, $save_id, $values, $user_id)
  {
    error_log('WP WithPersona: Registration form submission started');
    error_log('WP WithPersona: Form data: ' . print_r($_POST, true));
    error_log('WP WithPersona: User ID: ' . $user_id);
    error_log('WP WithPersona: Save ID: ' . $save_id);
    error_log('WP WithPersona: Values: ' . print_r($values, true));

    // Get today's registrations count
    $today = date('Y-m-d');
    $args = array(
      'role'        => 'freelancer',
      'date_query'  => array(
        array(
          'after'     => $today,
          'inclusive' => true,
        ),
      ),
      'count_total' => true,
    );

    $daily_registrations = count_users_by_date($args);
    error_log('WP WithPersona: Daily registrations count: ' . $daily_registrations);

    // Get the daily limit from settings
    $daily_limit = get_option('wp_withpersona_limit_users', 50);

    // Check if daily limit is reached
    if ($daily_registrations >= $daily_limit) {
      wp_delete_user($save_id); // Delete the just-created user
      echo json_encode(array(
        'success' => false,
        'title'   => 'Error',
        'message' => 'Daily registration limit reached. Please try again tomorrow.',
      ));
      exit;
    }
  }
}

// Initialize the class
new WP_WithPersona_User_Limits();
