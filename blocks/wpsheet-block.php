<?php
/**
 * Functions to register client-side assets (scripts and stylesheets) for the
 * Gutenberg block.
 *
 * @package wpsheet
 */

/**
 * Registers all block assets so that they can be enqueued through Gutenberg in
 * the corresponding context.
 *
 * @see https://wordpress.org/gutenberg/handbook/designers-developers/developers/tutorials/block-tutorial/applying-styles-with-stylesheets/
 */
function wpsheet_block_block_init() {
	// Skip block registration if Gutenberg is not enabled/merged.
	if ( ! function_exists( 'register_block_type' ) ) {
		return;
	}
	$dir = dirname( __FILE__ );

	$index_js = 'wpsheet-block/index.js';
	wp_register_script(
		'wpsheet-block-block-editor',
		plugins_url( $index_js, __FILE__ ),
		[
			'wp-blocks',
			'wp-i18n',
			'wp-element',
		],
		filemtime( "{$dir}/{$index_js}" )
	);

	$editor_css = 'wpsheet-block/editor.css';
	wp_register_style(
		'wpsheet-block-block-editor',
		plugins_url( $editor_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$editor_css}" )
	);

	$style_css = 'wpsheet-block/style.css';
	wp_register_style(
		'wpsheet-block-block',
		plugins_url( $style_css, __FILE__ ),
		[],
		filemtime( "{$dir}/{$style_css}" )
	);

	register_block_type( 'wpsheet/wpsheet-block', [
		'editor_script' => 'wpsheet-block-block-editor',
		'editor_style'  => 'wpsheet-block-block-editor',
		'style'         => 'wpsheet-block-block',
	] );
}

add_action( 'init', 'wpsheet_block_block_init' );
