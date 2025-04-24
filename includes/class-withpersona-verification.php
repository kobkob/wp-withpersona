<?php

if (! defined('ABSPATH')) {
	exit;
}

require_once plugin_dir_path(__FILE__) . 'traits/trait-persona-verification-common.php';

class WpWithPersona_Verification
{
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
	public function __construct($parent)
	{
		$this->parent = $parent;

		// Add verification check on user login
		add_action('wp_login', array($this, 'check_user_verification'), 10, 2);

		// Add verification check on page load for logged-in users
		add_action('init', array($this, 'check_verification_on_page_load'));

		// Add verification status to user meta
		add_action('user_register', array($this, 'add_verification_meta'));

		// Create verification page
		add_action('admin_init', array($this, 'create_verification_page'));

		// Add verification link to user profile
		add_action('show_user_profile', array($this, 'add_verification_link_to_profile'));
		add_action('edit_user_profile', array($this, 'add_verification_link_to_profile'));

		// Register shortcode
		add_shortcode('persona_verification', array($this, 'render_verification_content'));
		add_shortcode('wp_withpersona', array($this, 'render_verification_content'));

		// Ensure shortcode is processed in the content
		add_filter('the_content', array($this, 'process_verification_shortcode'), 1);

		// Add admin notice if page needs recreation
		add_action('admin_notices', array($this, 'check_verification_page_status'));

		// Register AJAX handlers
		add_action('wp_ajax_save_persona_status', array($this, 'handle_save_persona_status'));
		add_action('wp_ajax_update_user_verification_status', array($this, 'handle_update_user_verification_status'));

		// Check if required settings are configured
		add_action('admin_notices', array($this, 'check_required_settings'));
	}

	/**
	 * Check if required settings are configured
	 */
	public function check_required_settings()
	{
		$template_id    = get_option('wpwithpersona_api_template_id');
		$environment_id = get_option('wpwithpersona_api_environment_id');

		if (empty($template_id) || empty($environment_id)) {
?>
			<div class="notice notice-warning">
				<p><?php esc_html_e('Persona verification requires configuration. Please set up your Template ID and Environment ID in the settings.', 'wp-withpersona'); ?></p>
				<p>
					<a href="<?php echo esc_url(admin_url('admin.php?page=wp-withpersona-settings')); ?>" class="button button-primary">
						<?php esc_html_e('Configure Settings', 'wp-withpersona'); ?>
					</a>
				</p>
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
	public function is_user_verified($user_id)
	{
		$api_key        = get_option('wpwithpersona_api_key');
		$template_id    = get_option('wpwithpersona_api_template_id');
		$environment_id = get_option('wpwithpersona_api_environment_id');

		if (empty($api_key) || empty($template_id) || empty($environment_id)) {
			return false;
		}

		$user = get_user_by('id', $user_id);
		if (! $user) {
			return false;
		}

		// Get verification status from user meta
		$verification_status = get_user_meta($user_id, 'persona_verification_status', true);
		$last_checked        = get_user_meta($user_id, 'persona_verification_last_checked', true);

		// If we checked recently (within last hour), return cached status
		if ($verification_status && $last_checked && (time() - $last_checked) < 3600) {
			return $verification_status === 'verified';
		}

		// Make API call to Persona to verify user
		$response = wp_remote_post(
			'https://withpersona.com/api/v1/verifications',
			array(
				'headers' => array(
					'Authorization' => 'Bearer ' . $api_key,
					'Content-Type'  => 'application/json',
				),
				'body'    => json_encode(
					array(
						'template_id'    => $template_id,
						'environment_id' => $environment_id,
						'email'          => $user->user_email,
					)
				),
			)
		);

		if (is_wp_error($response)) {
			return false;
		}

		$body        = json_decode(wp_remote_retrieve_body($response), true);
		$is_verified = isset($body['status']) && $body['status'] === 'verified';

		// Update user meta with verification status
		update_user_meta($user_id, 'persona_verification_status', $is_verified ? 'verified' : 'unverified');
		update_user_meta($user_id, 'persona_verification_last_checked', time());

		return $is_verified;
	}

	/**
	 * Check user verification on login
	 *
	 * @param string  $user_login The user login
	 * @param WP_User $user The user object
	 */
	public function check_user_verification($user_login, $user)
	{
		if (! $this->is_user_verified($user->ID)) {
			// Add notice for unverified users
			add_action(
				'admin_notices',
				function () {
					$verification_url = $this->get_verification_page_url();
					echo '<div class="notice notice-warning"><p>';
					esc_html_e('Your account is not verified with Persona. ', 'wp-withpersona');
					if ($verification_url) {
						echo '<a href="' . esc_url($verification_url) . '">' . esc_html__('Complete verification now', 'wp-withpersona') . '</a>';
					}
					echo '</p></div>';
				}
			);
		}
	}

	/**
	 * Check verification on page load for logged-in users
	 */
	public function check_verification_on_page_load()
	{
		if (is_user_logged_in()) {
			$user_id = get_current_user_id();
			if (! $this->is_user_verified($user_id)) {
				// Add notice for unverified users
				add_action(
					'admin_notices',
					function () {
						$verification_url = $this->get_verification_page_url();
						echo '<div class="notice notice-warning"><p>';
						esc_html_e('Your account is not verified with Persona. ', 'wp-withpersona');
						if ($verification_url) {
							echo '<a href="' . esc_url($verification_url) . '">' . esc_html__('Complete verification now', 'wp-withpersona') . '</a>';
						}
						echo '</p></div>';
					}
				);
			}
		}
	}

	/**
	 * Add verification meta to new users
	 *
	 * @param int $user_id The new user ID
	 */
	public function add_verification_meta($user_id)
	{
		add_user_meta($user_id, 'persona_verification_status', 'unverified');
		add_user_meta($user_id, 'persona_verification_last_checked', 0);
	}

	/**
	 * Get the verification page URL
	 *
	 * @return string The verification page URL
	 */
	public function get_verification_page_url()
	{
		$page_id = get_option('wpwithpersona_verification_page_id');
		if ($page_id) {
			return get_permalink($page_id);
		}
		return '';
	}

	/**
	 * Create verification page if it doesn't exist
	 */
	public function create_verification_page()
	{
		$page_id = get_option('wpwithpersona_verification_page_id');

		if (! $page_id) {
			$page_data = array(
				'post_title'     => __('Identity Verification', 'wp-withpersona'),
				'post_content'   => '<!-- wp:shortcode -->
[persona_verification]
<!-- /wp:shortcode -->',
				'post_status'    => 'publish',
				'post_type'      => 'page',
				'post_author'    => 1,
				'comment_status' => 'closed',
				'ping_status'    => 'closed',
			);

			$page_id = wp_insert_post($page_data);

			if ($page_id) {
				// Set the page template
				update_post_meta($page_id, '_wp_page_template', 'default');
				update_option('wpwithpersona_verification_page_id', $page_id);
			}
		} else {
			// Update existing page content
			$page_data = array(
				'ID'           => $page_id,
				'post_content' => '<!-- wp:shortcode -->
[persona_verification]
<!-- /wp:shortcode -->',
			);
			wp_update_post($page_data);
		}
	}

	/**
	 * Add verification link to user profile
	 */
	public function add_verification_link_to_profile($user)
	{
		if (! current_user_can('edit_user', $user->ID)) {
			return;
		}
		?>
		<h3><?php esc_html_e('Persona Verification', 'wp-withpersona'); ?></h3>
		<table class="form-table">
			<tr>
				<th><label for="persona_verification"><?php esc_html_e('Verification Status', 'wp-withpersona'); ?></label></th>
				<td>
					<?php
					$verification_status = get_user_meta($user->ID, 'persona_verification_status', true);
					if ($verification_status === 'verified') {
						echo '<span class="dashicons dashicons-yes-alt" style="color: #46b450;"></span> ' . esc_html__('Verified', 'wp-withpersona');
					} else {
						echo '<span class="dashicons dashicons-warning" style="color: #ffb900;"></span> ' . esc_html__('Not Verified', 'wp-withpersona');
						echo '<p class="description"><a href="' . esc_url($this->get_verification_page_url()) . '">' . esc_html__('Complete verification', 'wp-withpersona') . '</a></p>';
					}
					?>
				</td>
			</tr>
		</table>
		<?php
	}

	/**
	 * Render verification content via shortcode
	 */
	public function render_verification_content()
	{
		$reference_id        = $this->get_reference_id();
		$verification_status = $this->get_verification_status();
		$settings            = $this->get_persona_settings();

		if (! $this->are_settings_configured()) {
			$this->render_configuration_error();
			return;
		}

		ob_start();
		$this->render_persona_container($verification_status);
		$this->render_persona_styles();
		$this->render_persona_scripts(
			$settings['template_id'],
			$settings['environment_id'],
			$reference_id,
			$verification_status
		);
		return ob_get_clean();
	}

	/**
	 * Process the verification shortcode in the content
	 */
	public function process_verification_shortcode($content)
	{
		global $post;

		if (is_page() && $post && $post->ID == get_option('wpwithpersona_verification_page_id')) {
			return do_shortcode('[persona_verification]');
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
	public static function instance($parent)
	{
		if (is_null(self::$_instance)) {
			self::$_instance = new self($parent);
		}
		return self::$_instance;
	}

	/**
	 * Cloning is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __clone()
	{
		_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'wp-withpersona'), $this->parent->_version);
	}

	/**
	 * Unserializing instances of this class is forbidden.
	 *
	 * @since 1.0.0
	 */
	public function __wakeup()
	{
		_doing_it_wrong(__FUNCTION__, __('Cheatin&#8217; huh?', 'wp-withpersona'), $this->parent->_version);
	}

	/**
	 * Force recreation of the verification page
	 */
	public function recreate_verification_page()
	{
		delete_option('wpwithpersona_verification_page_id');
		$this->create_verification_page();
	}

	/**
	 * Check verification page status and show notice if needed
	 */
	public function check_verification_page_status()
	{
		$page_id = get_option('wpwithpersona_verification_page_id');
		if (! $page_id || ! get_post($page_id)) {
		?>
			<div class="notice notice-warning">
				<p><?php esc_html_e('The Persona verification page needs to be recreated.', 'wp-withpersona'); ?></p>
				<p>
					<a href="<?php echo esc_url(add_query_arg('recreate_verification_page', '1')); ?>" class="button button-primary">
						<?php esc_html_e('Recreate Page', 'wp-withpersona'); ?>
					</a>
				</p>
			</div>
<?php
		}
	}

	/**
	 * Handle AJAX request to save Persona verification status
	 */
	public function handle_save_persona_status()
	{
		// check_ajax_referer( 'save_persona_status', 'nonce' );

		if (isset($_POST['status'])) {
			$_SESSION['persona_verification_status'] = sanitize_text_field($_POST['status']);
			wp_send_json_success('Status saved');
		}

		wp_send_json_error('Invalid request');
	}

	/**
	 * Handle AJAX request to update user verification status
	 */
	public function handle_update_user_verification_status()
	{
		check_ajax_referer('update_user_verification_status', 'nonce');

		if (is_user_logged_in()) {
			$user_id     = get_current_user_id();
			$is_verified = isset($_POST['is_verified']) ? (bool) $_POST['is_verified'] : false;

			update_user_meta($user_id, 'persona_verification_status', $is_verified ? 'verified' : 'unverified');
			update_user_meta($user_id, 'persona_verification_last_checked', time());

			wp_send_json_success('User verification status updated');
		}

		wp_send_json_error('User not logged in');
	}
}
