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

	/** @var string wp_options key that stores the currently installed schema version. */
	private const OPTION_KEY = 'sendstack_db_version';

	/**
	 * Run pending migrations if the installed schema version is behind the target.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function maybe_upgrade(): void {
		$installed = (string) get_option( self::OPTION_KEY, '0.0.0' );

		if ( version_compare( $installed, SENDSTACK_DB_VERSION, '>=' ) ) {
			return;
		}

		// Individual migration steps will be added here as the schema evolves.

		update_option( self::OPTION_KEY, SENDSTACK_DB_VERSION );
	}
}
