<?php
/**
 * Text-domain loader.
 *
 * @package SendStack\I18n
 * @since   1.0.0
 */

namespace SendStack\I18n;

defined( 'ABSPATH' ) || exit;

/**
 * Loads the plugin's translation files from the /languages directory.
 *
 * @since 1.0.0
 */
class TextDomain {

	/**
	 * Register the text domain with WordPress.
	 *
	 * Hooked to `init` or called from a service provider's boot() method.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function load(): void {
		load_plugin_textdomain(
			'sendstack',
			false,
			dirname( SENDSTACK_BASENAME ) . '/languages'
		);
	}
}
