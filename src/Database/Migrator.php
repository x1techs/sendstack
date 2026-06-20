<?php
/**
 * Version-gated migration runner.
 *
 * @package SendStack\Database
 * @since   1.0.0
 */

namespace SendStack\Database;

defined( 'ABSPATH' ) || exit;

use SendStack\Database\Migrations\Migration_1_0_0;

/**
 * Runs database migrations in version order, tracking progress in wp_options.
 *
 * Each migration class must expose up() and down() methods. Migrations are
 * executed only when the stored DB version is below the migration's target.
 *
 * @since 1.0.0
 */
class Migrator {

	/** @var string Option key that stores the currently applied DB version. */
	private const OPTION_KEY = 'sendstack_db_version';

	/**
	 * Ordered map of target version string => migration class name.
	 *
	 * @var array<string, class-string>
	 */
	private static $migrations = array(
		'1.0.0' => Migration_1_0_0::class,
	);

	/**
	 * Run all pending migrations in ascending version order.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function migrate(): void {
		$installed = self::current_version();

		foreach ( self::$migrations as $version => $class ) {
			if ( version_compare( $installed, $version, '>=' ) ) {
				continue;
			}

			( new $class() )->up();

			update_option( self::OPTION_KEY, $version );
		}
	}

	/**
	 * Return the currently applied DB schema version.
	 *
	 * @since  1.0.0
	 * @return string Semver string, or '0.0.0' when no migration has run.
	 */
	public static function current_version(): string {
		return (string) get_option( self::OPTION_KEY, '0.0.0' );
	}
}
