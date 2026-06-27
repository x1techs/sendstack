<?php
/**
 * Database migration runner.
 *
 * @package SendStack\Core
 * @since   1.0.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Compares the installed DB schema version against the target and runs any pending migrations.
 *
 * @since 1.0.0
 */
class Upgrader {

	/** @var string wp_options key that stores the currently installed plugin version. */
	private const OPTION_KEY = 'sendstack_version';

	/**
	 * Run pending migrations if the installed plugin version is behind the current one.
	 *
	 * Hooked to admin_init — only runs in wp-admin, never on the frontend request path.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		// Guard: never slow the frontend. is_admin() is true for AJAX calls too,
		// which is intentional — admin-AJAX runs in the admin context.
		if ( ! is_admin() ) {
			return;
		}

		$installed = (string) get_option( self::OPTION_KEY, '0.0.0' );

		// version_compare is O(1); this check is safe on every admin page load.
		if ( version_compare( $installed, SENDSTACK_VERSION, '>=' ) ) {
			return;
		}

		// Delegate schema changes to the Migrator, which tracks its own DB version.
		\SendStack\Database\Migrator::migrate();

		// Update the stored version so this block only executes once per release.
		update_option( self::OPTION_KEY, SENDSTACK_VERSION );
	}
}
