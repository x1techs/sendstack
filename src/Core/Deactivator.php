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
	 * Remove scheduled cron events and flush rewrite rules on deactivation.
	 *
	 * Does NOT delete options or DB tables — that is uninstall.php's responsibility.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function deactivate(): void {
		// Prevent orphaned WP-Cron events from firing after the plugin is disabled.
		wp_clear_scheduled_hook( 'sendstack_log_prune' );
		wp_clear_scheduled_hook( 'sendstack_retry_process' );

		// Remove any REST-API route registrations so they don't linger in the rewrite table.
		flush_rewrite_rules();
	}
}
