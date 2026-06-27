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
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  message_id VARCHAR(64) DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT 'queued',
  provider VARCHAR(32) DEFAULT '',
  from_email VARCHAR(190) DEFAULT '',
  to_json LONGTEXT,
  cc_json LONGTEXT,
  bcc_json LONGTEXT,
  subject VARCHAR(255) DEFAULT '',
  body_mime LONGTEXT,
  headers_json LONGTEXT,
  attachments_json LONGTEXT,
  error_code VARCHAR(50) DEFAULT '',
  error_message TEXT,
  attempt_count TINYINT(3) UNSIGNED NOT NULL DEFAULT 1,
  is_retry TINYINT(1) UNSIGNED NOT NULL DEFAULT 0,
  source VARCHAR(50) DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY idx_status_created (status, created_at),
  KEY idx_created (created_at),
  KEY idx_from (from_email)
) {$charset_collate};";

			case 'sendstack_stats':
				return "CREATE TABLE {$full_table} (
  id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  stat_date DATE NOT NULL,
  provider VARCHAR(32) NOT NULL DEFAULT '',
  status VARCHAR(20) NOT NULL DEFAULT '',
  count BIGINT(20) UNSIGNED NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY idx_date_provider_status (stat_date, provider, status)
) {$charset_collate};";

			default:
				return '';
		}
	}
}

/*
 * Concepts in this file
 * =====================
 *
 * Why dbDelta and not raw CREATE TABLE:
 *   dbDelta() compares the desired DDL against the live table structure and
 *   emits only the ALTER TABLE statements that are needed. On fresh installs it
 *   runs a CREATE TABLE; on upgrades it adds missing columns and indexes without
 *   touching existing rows, preventing data loss across plugin updates.
 *
 * Why two spaces before PRIMARY KEY:
 *   dbDelta parses DDL line-by-line with a regex that requires exactly two
 *   spaces between "PRIMARY KEY" and the opening parenthesis — `PRIMARY KEY  (id)`.
 *   One space, three spaces, or a tab causes dbDelta to silently mis-parse the
 *   definition and skip the primary key on creation.
 *
 * Why custom tables instead of post meta:
 *   - High-volume writes: every outgoing email writes a row; wp_postmeta row-level
 *     locking does not scale to that write rate on busy sites.
 *   - Indexed queries: compound indexes on (status, created_at) make log list
 *     queries fast; post meta has no multi-column indexes.
 *   - Retention/pruning: DELETE WHERE created_at < X on a dedicated table is a
 *     single efficient statement; purging post meta requires JOIN-heavy queries
 *     against wp_posts.
 *   - No post relationship: log entries are transactional records, not content —
 *     there is no reason for them to live inside the post hierarchy.
 *
 * Why Migrator stores the DB version after each migration, not at the end:
 *   If a run contains three migrations and the second one fails, the first has
 *   already been applied successfully. Storing the version immediately after
 *   each successful migration means a subsequent re-run skips the completed
 *   migration and resumes from the failure point, rather than re-running
 *   everything and risking duplicate-column errors or data corruption.
 */
