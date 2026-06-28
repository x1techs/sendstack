<?php
/**
 * CRUD operations over the sendstack_logs table.
 *
 * @package SendStack\Logger
 * @since   1.0.0
 */

namespace SendStack\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Provides insert / update / find / delete / query / count / purge /
 * bulk_delete methods for the sendstack_logs database table.
 *
 * SECURITY CONTRACT: every query uses $wpdb->prepare(). Column names in ORDER BY
 * are whitelisted. LIKE values are escaped with $wpdb->esc_like(). IDs are
 * cast with absint(). No user input is ever interpolated directly into SQL.
 *
 * @since 1.0.0
 */
class LogRepository {

	/** @var \wpdb */
	private $db;

	/** @var string Fully-qualified table name. */
	private $table;

	/**
	 * Columns that callers are allowed to update via update().
	 * Prevents unexpected columns (e.g. from_email, body_mime) from being
	 * overwritten by untrusted input.
	 *
	 * @var string[]
	 */
	private const UPDATABLE_COLUMNS = array(
		'status',
		'message_id',
		'error_code',
		'error_message',
		'attempt_count',
		'updated_at',
	);

	/**
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = $wpdb->prefix . 'sendstack_logs';
	}

	// -------------------------------------------------------------------------
	// Write operations
	// -------------------------------------------------------------------------

	/**
	 * Insert a new log row and return the new row ID.
	 *
	 * @since  1.0.0
	 * @param  LogEntry $entry Entry to persist (id property is ignored).
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public function insert( LogEntry $entry ): int {
		$result = $this->db->insert( $this->table, $entry->to_array() );
		return false !== $result ? (int) $this->db->insert_id : 0;
	}

	/**
	 * Update whitelisted columns on an existing row.
	 *
	 * Always sets updated_at to the current site time. Silently drops any keys
	 * not in the UPDATABLE_COLUMNS whitelist.
	 *
	 * @since  1.0.0
	 * @param  int                  $id   Row primary key.
	 * @param  array<string, mixed> $data Column-value pairs to update.
	 * @return bool True on success, false when the query fails.
	 */
	public function update( int $id, array $data ): bool {
		$data = array_intersect_key( $data, array_flip( self::UPDATABLE_COLUMNS ) );

		if ( empty( $data ) ) {
			return false;
		}

		$data['updated_at'] = current_time( 'mysql' );

		$result = $this->db->update(
			$this->table,
			$data,
			array( 'id' => $id ),
			null,
			array( '%d' )
		);

		return false !== $result;
	}

	// -------------------------------------------------------------------------
	// Read operations
	// -------------------------------------------------------------------------

	/**
	 * Fetch a single log entry by primary key.
	 *
	 * @since  1.0.0
	 * @param  int $id Row primary key.
	 * @return LogEntry|null Null when no matching row exists.
	 */
	public function find( int $id ): ?LogEntry {
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM `{$this->table}` WHERE id = %d LIMIT 1", $id )
		);

		return ( $row instanceof \stdClass ) ? LogEntry::from_row( $row ) : null;
	}

	/**
	 * Query the log table with optional filters and pagination.
	 *
	 * Supported args:
	 *   status    (string)  — filter by status column
	 *   provider  (string)  — filter by provider column
	 *   search    (string)  — LIKE search across subject, from_email, to_json
	 *   date_from (string)  — created_at >= date (ISO format)
	 *   date_to   (string)  — created_at <= date (ISO format)
	 *   orderby   (string)  — column to sort by; whitelist: id, status, provider, created_at, subject
	 *   order     (string)  — ASC or DESC
	 *   per_page  (int)     — rows per page, max 100, default 20
	 *   page      (int)     — 1-based page number, default 1
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $args Query arguments.
	 * @return LogEntry[]
	 */
	public function query( array $args = array() ): array {
		$args = array_merge(
			array(
				'status'    => '',
				'provider'  => '',
				'search'    => '',
				'date_from' => '',
				'date_to'   => '',
				'orderby'   => 'created_at',
				'order'     => 'DESC',
				'per_page'  => 20,
				'page'      => 1,
			),
			$args
		);

		list( $where, $prepare ) = $this->build_where( $args );

		$allowed_orderby = array( 'id', 'status', 'provider', 'created_at', 'subject' );
		$allowed_order   = array( 'ASC', 'DESC' );
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';
		$order           = in_array( strtoupper( (string) $args['order'] ), $allowed_order, true )
			? strtoupper( (string) $args['order'] )
			: 'DESC';

		$per_page  = min( 100, max( 1, (int) $args['per_page'] ) );
		$page      = max( 1, (int) $args['page'] );
		$offset    = ( $page - 1 ) * $per_page;

		$sql       = "SELECT * FROM `{$this->table}` WHERE " . implode( ' AND ', $where );
		$sql      .= " ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d";

		$prepare[] = $per_page;
		$prepare[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->db->get_results( $this->db->prepare( $sql, $prepare ), OBJECT );

		$entries = array();
		foreach ( (array) $rows as $row ) {
			if ( $row instanceof \stdClass ) {
				$entries[] = LogEntry::from_row( $row );
			}
		}

		return $entries;
	}

	/**
	 * Count rows matching the given filters.
	 *
	 * Accepts the same filter args as query() (status, provider, search,
	 * date_from, date_to); pagination and ordering args are ignored.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $args Filter arguments.
	 * @return int
	 */
	public function count( array $args = array() ): int {
		$args = array_merge(
			array(
				'status'    => '',
				'provider'  => '',
				'search'    => '',
				'date_from' => '',
				'date_to'   => '',
			),
			$args
		);

		list( $where, $prepare ) = $this->build_where( $args );

		$sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE " . implode( ' AND ', $where );

		if ( ! empty( $prepare ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $this->db->prepare( $sql, $prepare );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->db->get_var( $sql );
	}

	// -------------------------------------------------------------------------
	// Delete operations
	// -------------------------------------------------------------------------

	/**
	 * Delete a single log row.
	 *
	 * @since  1.0.0
	 * @param  int $id Row primary key.
	 * @return bool True on success.
	 */
	public function delete( int $id ): bool {
		return (bool) $this->db->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Delete multiple log rows by ID.
	 *
	 * Each ID is passed through absint() before use. Returns the number of
	 * rows actually deleted.
	 *
	 * @since  1.0.0
	 * @param  int[] $ids Row IDs to delete.
	 * @return int Number of rows deleted.
	 */
	public function bulk_delete( array $ids ): int {
		$ids = array_filter( array_map( 'absint', $ids ) );

		if ( empty( $ids ) ) {
			return 0;
		}

		$placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );

		$result = $this->db->query(
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$this->db->prepare(
				// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				"DELETE FROM `{$this->table}` WHERE id IN ({$placeholders})",
				$ids
			)
		);

		return false !== $result ? (int) $result : 0;
	}

	/**
	 * Delete log rows older than the given number of days.
	 *
	 * @since  1.0.0
	 * @param  int $days Retain rows newer than this many days; must be > 0.
	 * @return int Number of rows deleted.
	 */
	public function purge_older_than( int $days ): int {
		$result = $this->db->query(
			$this->db->prepare(
				"DELETE FROM `{$this->table}` WHERE created_at < DATE_SUB(NOW(), INTERVAL %d DAY)",
				$days
			)
		);

		return false !== $result ? (int) $result : 0;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Build the shared WHERE clause and prepare values from a filter args array.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $args Filter arguments.
	 * @return array{0: string[], 1: mixed[]} Tuple of [conditions, prepare_values].
	 */
	private function build_where( array $args ): array {
		$where   = array( '1=1' );
		$prepare = array();

		if ( '' !== (string) $args['status'] ) {
			$where[]   = 'status = %s';
			$prepare[] = sanitize_text_field( (string) $args['status'] );
		}

		if ( '' !== (string) $args['provider'] ) {
			$where[]   = 'provider = %s';
			$prepare[] = sanitize_text_field( (string) $args['provider'] );
		}

		if ( '' !== (string) $args['search'] ) {
			$like      = '%' . $this->db->esc_like( sanitize_text_field( (string) $args['search'] ) ) . '%';
			$where[]   = '(subject LIKE %s OR from_email LIKE %s OR to_json LIKE %s)';
			$prepare[] = $like;
			$prepare[] = $like;
			$prepare[] = $like;
		}

		if ( '' !== (string) $args['date_from'] ) {
			$where[]   = 'created_at >= %s';
			$prepare[] = sanitize_text_field( (string) $args['date_from'] );
		}

		if ( '' !== (string) $args['date_to'] ) {
			$where[]   = 'created_at <= %s';
			$prepare[] = sanitize_text_field( (string) $args['date_to'] );
		}

		return array( $where, $prepare );
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * SEPARATION OF CONCERNS: REPOSITORY VS WRITER
 * LogRepository is pure data access — it knows nothing about WordPress hooks,
 * plugin settings, or the email-send lifecycle. LogWriter owns all of that
 * logic and calls the repository at the right moment. This separation means
 * the repository can be injected into the REST API (Feature 7) or unit tests
 * without dragging in hook infrastructure.
 *
 * SECURITY IN EVERY QUERY
 * - absint() on every ID prevents a negative integer from being used as a
 *   WHERE key and accidentally matching rows (MySQL treats -1 as BIGINT).
 * - esc_like() on search terms prevents the % and _ LIKE wildcards from being
 *   used to craft a broader-than-intended pattern (a LIKE injection variant).
 * - The UPDATABLE_COLUMNS whitelist stops a caller from passing e.g.
 *   'from_email' => 'attacker@example.com' and overwriting fields that should
 *   only ever be set at insert time.
 * - ORDER BY columns are whitelisted; never interpolated from user input.
 *   Without a whitelist, an attacker with manage_options access could pass an
 *   arbitrary expression and trigger a MySQL error or information disclosure.
 * - $wpdb->prepare() handles SQL escaping for all parameterised values.
 *
 * build_where() DEDUPLICATION
 * query() and count() share identical filter logic. Extracting it into a
 * private method ensures they can't diverge — a bug fix in one automatically
 * applies to the other.
 *
 * purge_older_than() USES DATE_SUB(NOW(), ...)
 * DATE_SUB(NOW(), INTERVAL N DAY) is evaluated server-side and respects the
 * MySQL server clock. This avoids a class of timezone-related bugs where a PHP
 * gmdate() and a MySQL DATETIME column use different time references.
 *
 * update() ALWAYS SETS updated_at
 * The repository owns this invariant so callers don't have to remember it.
 * Any code that updates a log row — LogWriter, a future REST endpoint, a retry
 * handler — gets updated_at maintained automatically.
 */
