<?php
/**
 * Table DDL definitions.
 *
 * @package SendStack\Database
 * @since   1.0.0
 */

namespace SendStack\Database;

defined( 'ABSPATH' ) || exit;

/**
 * Centralises all CREATE TABLE DDL strings so Installer and Migrator share
 * a single source of truth. Strings are formatted for dbDelta().
 *
 * @since 1.0.0
 */
class Schema {

	/**
	 * Return every managed table name (without prefix).
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public static function tables(): array {
		return array(
			'sendstack_logs',
			'sendstack_stats',
		);
	}

	/**
	 * Return the dbDelta-compatible CREATE TABLE DDL for a given table.
	 *
	 * @since  1.0.0
	 * @param  string $table Unprefixed table name (e.g. 'sendstack_logs').
	 * @return string CREATE TABLE statement, or empty string for unknown tables.
	 */
	public static function for( string $table ): string {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();
		$full_table      = $wpdb->prefix . $table;

		switch ( $table ) {
			case 'sendstack_logs':
				return "CREATE TABLE {$full_table} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  status varchar(20) NOT NULL DEFAULT 'queued',
  provider varchar(50) NOT NULL DEFAULT '',
  `to` text NOT NULL,
  subject varchar(255) NOT NULL DEFAULT '',
  body longtext NOT NULL,
  headers text NOT NULL,
  attachments text NOT NULL,
  error_code varchar(50) NOT NULL DEFAULT '',
  error_message text NOT NULL,
  provider_response text NOT NULL,
  sent_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status (status),
  KEY created_at (created_at)
) {$charset_collate};";

			case 'sendstack_stats':
				return "CREATE TABLE {$full_table} (
  id bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  metric varchar(50) NOT NULL,
  value bigint(20) UNSIGNED NOT NULL DEFAULT 0,
  stat_date date NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY metric_date (metric, stat_date)
) {$charset_collate};";

			default:
				return '';
		}
	}
}
