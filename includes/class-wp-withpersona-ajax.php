<?php

/**
 * AJAX handlers for WP WithPersona
 */

if (!defined('ABSPATH')) {
  exit;
}

class WP_WithPersona_Ajax
{
  /**
   * Initialize the AJAX handlers
   */
  public function __construct()
  {
    add_action('wp_ajax_wpp_reverify_user', array($this, 'handle_reverify_user'));
  }

  /**
   * Handle the re-verification request
   */
  public function handle_reverify_user()
  {
    // Log the request
    error_log('WP WithPersona: Re-verification request received');
    error_log('POST data: ' . print_r($_POST, true));

    // Verify nonce
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
    error_log('User ID: ' . $user_id);

    if (!$user_id) {
      error_log('WP WithPersona: Invalid user ID');
      wp_send_json_error('Invalid user ID');
      return;
    }

    if (!check_ajax_referer('wpp_reverify_user_' . $user_id, 'nonce', false)) {
      error_log('WP WithPersona: Invalid nonce');
      wp_send_json_error('Invalid security token');
      return;
    }

    // Check if user exists and current user has permission
    if (!current_user_can('manage_options')) {
      error_log('WP WithPersona: Insufficient permissions');
      wp_send_json_error('Insufficient permissions');
      return;
    }

    // Verify user exists
    $user = get_user_by('id', $user_id);
    if (!$user) {
      error_log('WP WithPersona: User not found');
      wp_send_json_error('User not found');
      return;
    }

    // Reset verification status
    delete_user_meta($user_id, 'persona_verification_status');
    delete_user_meta($user_id, 'persona_verification_last_checked');

    error_log('WP WithPersona: Successfully reset verification for user ' . $user_id);
    wp_send_json_success(array(
      'message' => 'Verification status reset successfully',
      'user_id' => $user_id
    ));
  }
}

// Initialize the AJAX handlers
new WP_WithPersona_Ajax();
