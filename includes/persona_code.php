<?php
/**
 * Persona integration code
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once plugin_dir_path( __FILE__ ) . 'traits/trait-persona-verification-common.php';

class Persona_Code {
	use Persona_Verification_Common;

	public function render() {
		$reference_id        = $this->get_reference_id();
		$verification_status = $this->get_verification_status();
		$settings            = $this->get_persona_settings();

		if ( ! $this->are_settings_configured() ) {
			$this->render_configuration_error();
			return;
		}

		$this->render_persona_container( $verification_status );
		$this->render_persona_styles();
		$this->render_persona_scripts(
			$settings['template_id'],
			$settings['environment_id'],
			$reference_id,
			$verification_status
		);
	}
}

// Initialize and render
$persona_code = new Persona_Code();
$persona_code->render();
