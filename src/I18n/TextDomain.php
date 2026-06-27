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
	 * Hooked to `init` from Plugin::boot() so that WP's locale is fully
	 * determined before translation files are loaded.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function load(): void {
		// load_plugin_textdomain() resolves .mo files from the /languages directory
		// and also checks WP_LANG_DIR/plugins/ for community-supplied translations.
		load_plugin_textdomain(
			'sendstack',
			false,
			dirname( SENDSTACK_BASENAME ) . '/languages'
		);
	}
}
