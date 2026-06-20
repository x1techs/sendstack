<?php
/**
 * Table installer and uninstaller.
 *
 * @package SendStack\Database
 * @since   1.0.0
 */

namespace SendStack\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Creates or updates all plugin tables on activation and drops them on
 * uninstall using the DDL strings provided by Schema.
 *
 * @since 1.0.0
 */
class Installer {

	/**
	 * Create or upgrade all managed tables via dbDelta.
	 *
	 * Safe to call on every activation — dbDelta only alters tables when the
	 * schema has changed.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function install(): void {
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$wpdb->hide_errors();

		foreach ( Schema::tables() as $table ) {
			$ddl = Schema::for( $table );

			if ( '' !== $ddl ) {
				dbDelta( $ddl );
			}
		}
	}

	/**
	 * Drop all plugin tables.
	 *
	 * Called from uninstall.php only — irreversible data loss.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function uninstall(): void {
		global $wpdb;

		foreach ( array_reverse( Schema::tables() ) as $table ) {
			$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}{$table}`" ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}
	}
}
