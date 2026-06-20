<?php
/**
 * Plugin deactivation handler.
 *
 * @package SendStack\Core
 * @since   1.0.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs once when the plugin is deactivated from the Plugins screen.
 *
 * @since 1.0.0
 */
class Deactivator {

	/**
	 * Clean up transient state on deactivation.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}
}
