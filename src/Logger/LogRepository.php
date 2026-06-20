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
 * Provides insert / update / find / delete / query / count / purge methods
 * for the sendstack_logs database table.
 *
 * @since 1.0.0
 */
class LogRepository {

	/** @var \wpdb */
	private $db;

	/** @var string Fully-qualified table name. */
	private $table;

	/**
	 * @param \wpdb $db WordPress database object.
	 */
	public function __construct( \wpdb $db ) {
		$this->db    = $db;
		$this->table = $db->prefix . 'sendstack_logs';
	}

	/**
	 * Insert a new log row and return the new row ID.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $data Column-value map.
	 * @return int Inserted row ID, or 0 on failure.
	 */
	public function insert( array $data ): int {
		$data['created_at'] = $data['created_at'] ?? current_time( 'mysql', true );

		$result = $this->db->insert( $this->table, $data );

		return false !== $result ? (int) $this->db->insert_id : 0;
	}

	/**
	 * Update columns on an existing row.
	 *
	 * @since  1.0.0
	 * @param  int                  $id   Row primary key.
	 * @param  array<string, mixed> $data Column-value map of fields to update.
	 * @return bool True on success, false when the query fails or no row matches.
	 */
	public function update( int $id, array $data ): bool {
		$result = $this->db->update( $this->table, $data, array( 'id' => $id ) );

		return false !== $result && $result > 0;
	}

	/**
	 * Fetch a single log entry by primary key.
	 *
	 * @since  1.0.0
	 * @param  int $id Row primary key.
	 * @return LogEntry|null Null when no matching row exists.
	 */
	public function find( int $id ): ?LogEntry {
		$row = $this->db->get_row(
			$this->db->prepare( "SELECT * FROM `{$this->table}` WHERE id = %d LIMIT 1", $id ),
			ARRAY_A
		);

		return is_array( $row ) ? LogEntry::from_row( $row ) : null;
	}

	/**
	 * Delete a single log row.
	 *
	 * @since  1.0.0
	 * @param  int $id Row primary key.
	 * @return bool
	 */
	public function delete( int $id ): bool {
		return (bool) $this->db->delete( $this->table, array( 'id' => $id ), array( '%d' ) );
	}

	/**
	 * Query the log table with optional filters.
	 *
	 * Recognised args: status, provider, search, date_from, date_to,
	 *                  per_page (default 25), page (default 1),
	 *                  orderby (default 'created_at'), order (default 'DESC').
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $args Query arguments.
	 * @return LogEntry[]
	 */
	public function query( array $args = array() ): array {
		$defaults = array(
			'status'    => '',
			'provider'  => '',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
			'per_page'  => 25,
			'page'      => 1,
			'orderby'   => 'created_at',
			'order'     => 'DESC',
		);

		$args    = array_merge( $defaults, $args );
		$where   = array( '1=1' );
		$prepare = array();

		if ( '' !== $args['status'] ) {
			$where[]   = 'status = %s';
			$prepare[] = $args['status'];
		}

		if ( '' !== $args['provider'] ) {
			$where[]   = 'provider = %s';
			$prepare[] = $args['provider'];
		}

		if ( '' !== $args['search'] ) {
			$where[]   = '(`to` LIKE %s OR subject LIKE %s)';
			$like      = '%' . $this->db->esc_like( $args['search'] ) . '%';
			$prepare[] = $like;
			$prepare[] = $like;
		}

		if ( '' !== $args['date_from'] ) {
			$where[]   = 'created_at >= %s';
			$prepare[] = $args['date_from'];
		}

		if ( '' !== $args['date_to'] ) {
			$where[]   = 'created_at <= %s';
			$prepare[] = $args['date_to'];
		}

		$allowed_order   = array( 'ASC', 'DESC' );
		$allowed_orderby = array( 'id', 'created_at', 'sent_at', 'status', 'provider' );
		$order           = in_array( strtoupper( $args['order'] ), $allowed_order, true ) ? strtoupper( $args['order'] ) : 'DESC';
		$orderby         = in_array( $args['orderby'], $allowed_orderby, true ) ? $args['orderby'] : 'created_at';

		$per_page = max( 1, (int) $args['per_page'] );
		$offset   = ( max( 1, (int) $args['page'] ) - 1 ) * $per_page;

		$sql  = "SELECT * FROM `{$this->table}` WHERE " . implode( ' AND ', $where );
		$sql .= " ORDER BY `{$orderby}` {$order} LIMIT %d OFFSET %d";

		$prepare[] = $per_page;
		$prepare[] = $offset;

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->db->get_results( $this->db->prepare( $sql, $prepare ), ARRAY_A );

		return array_map( array( LogEntry::class, 'from_row' ), (array) $rows );
	}

	/**
	 * Count rows matching the given filters.
	 *
	 * Accepts the same filter args as query() (status, provider, search,
	 * date_from, date_to); pagination args are ignored.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $args Filter arguments.
	 * @return int
	 */
	public function count( array $args = array() ): int {
		$defaults = array(
			'status'    => '',
			'provider'  => '',
			'search'    => '',
			'date_from' => '',
			'date_to'   => '',
		);

		$args    = array_merge( $defaults, $args );
		$where   = array( '1=1' );
		$prepare = array();

		if ( '' !== $args['status'] ) {
			$where[]   = 'status = %s';
			$prepare[] = $args['status'];
		}

		if ( '' !== $args['provider'] ) {
			$where[]   = 'provider = %s';
			$prepare[] = $args['provider'];
		}

		if ( '' !== $args['search'] ) {
			$where[]   = '(`to` LIKE %s OR subject LIKE %s)';
			$like      = '%' . $this->db->esc_like( $args['search'] ) . '%';
			$prepare[] = $like;
			$prepare[] = $like;
		}

		if ( '' !== $args['date_from'] ) {
			$where[]   = 'created_at >= %s';
			$prepare[] = $args['date_from'];
		}

		if ( '' !== $args['date_to'] ) {
			$where[]   = 'created_at <= %s';
			$prepare[] = $args['date_to'];
		}

		$sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE " . implode( ' AND ', $where );

		if ( ! empty( $prepare ) ) {
			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$sql = $this->db->prepare( $sql, $prepare );
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		return (int) $this->db->get_var( $sql );
	}

	/**
	 * Delete log rows older than the given number of days.
	 *
	 * @since  1.0.0
	 * @param  int $days Retain rows newer than this many days.
	 * @return int Number of rows deleted.
	 */
	public function purge_older_than( int $days ): int {
		$cutoff = gmdate( 'Y-m-d H:i:s', strtotime( "-{$days} days" ) );

		$result = $this->db->query(
			$this->db->prepare(
				"DELETE FROM `{$this->table}` WHERE created_at < %s",
				$cutoff
			)
		);

		return false !== $result ? (int) $result : 0;
	}
}
