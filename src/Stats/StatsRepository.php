<?php
/**
 * CRUD operations over the sendstack_stats rollup table.
 *
 * @package SendStack\Stats
 * @since   1.0.0
 */

namespace SendStack\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Provides increment / for_range / summary / clear_cache methods for the
 * sendstack_stats daily rollup table.
 *
 * The table schema is:
 *   id, stat_date DATE, provider VARCHAR, status VARCHAR, count BIGINT
 *   UNIQUE KEY on (stat_date, provider, status)
 *
 * @since 1.0.0
 */
class StatsRepository {

	/** @var \wpdb */
	private $db;

	/** @var string Fully-qualified table name. */
	private $table;

	/**
	 * @since 1.0.0
	 */
	public function __construct() {
		global $wpdb;
		$this->db    = $wpdb;
		$this->table = $wpdb->prefix . 'sendstack_stats';
	}

	// -------------------------------------------------------------------------
	// Write operations
	// -------------------------------------------------------------------------

	/**
	 * Atomically increment the counter for today + provider + status.
	 *
	 * Uses INSERT … ON DUPLICATE KEY UPDATE to avoid a SELECT-then-UPDATE race
	 * condition. The UNIQUE constraint on (stat_date, provider, status) ensures
	 * exactly one row per combination per day.
	 *
	 * @since  1.0.0
	 * @param  string $provider Provider slug (e.g. 'smtp', 'sendgrid').
	 * @param  string $status   Outcome status ('sent' or 'failed').
	 * @return void
	 */
	public function increment( string $provider, string $status ): void {
		$this->db->query(
			$this->db->prepare(
				"INSERT INTO `{$this->table}` (stat_date, provider, status, count)
				 VALUES (%s, %s, %s, 1)
				 ON DUPLICATE KEY UPDATE count = count + 1",
				current_time( 'Y-m-d' ),
				$provider,
				$status
			)
		);
	}

	/**
	 * Delete both stats transient caches.
	 *
	 * Called immediately after every increment() so the dashboard reflects the
	 * latest counts on the next page load.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function clear_cache(): void {
		delete_transient( 'sendstack_stats_7d' );
		delete_transient( 'sendstack_stats_30d' );
	}

	// -------------------------------------------------------------------------
	// Read operations
	// -------------------------------------------------------------------------

	/**
	 * Return daily sent/failed totals for a date range, optionally filtered
	 * by provider.
	 *
	 * Missing dates within the range are filled with zeros to give the charting
	 * layer continuous data points without needing to handle gaps.
	 *
	 * Result format:
	 *   [ 'YYYY-MM-DD' => [ 'sent' => int, 'failed' => int ], … ]
	 *
	 * @since  1.0.0
	 * @param  string      $start_date ISO date string (inclusive lower bound).
	 * @param  string      $end_date   ISO date string (inclusive upper bound).
	 * @param  string|null $provider   Filter by provider slug; null = all providers.
	 * @return array<string, array<string, int>>
	 */
	public function for_range( string $start_date, string $end_date, ?string $provider = null ): array {
		$sql  = "SELECT stat_date, status, SUM(count) AS total
				 FROM `{$this->table}`
				 WHERE stat_date BETWEEN %s AND %s";
		$args = array( $start_date, $end_date );

		if ( null !== $provider ) {
			$sql    .= ' AND provider = %s';
			$args[]  = $provider;
		}

		$sql .= ' GROUP BY stat_date, status ORDER BY stat_date ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		$rows = $this->db->get_results( $this->db->prepare( $sql, $args ), ARRAY_A );

		/** @var array<string, array<string, int>> $raw */
		$raw = array();

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$date   = isset( $row['stat_date'] ) ? (string) $row['stat_date'] : '';
			$status = isset( $row['status'] ) ? (string) $row['status'] : '';
			$count  = isset( $row['total'] ) ? (int) $row['total'] : 0;

			if ( '' !== $date && '' !== $status ) {
				if ( ! isset( $raw[ $date ] ) ) {
					$raw[ $date ] = array();
				}
				$raw[ $date ][ $status ] = $count;
			}
		}

		// Fill every date in the range, even those with no DB rows.
		$result     = array();
		$current_dt = new \DateTime( $start_date );
		$end_dt     = new \DateTime( $end_date );
		$one_day    = new \DateInterval( 'P1D' );

		while ( $current_dt <= $end_dt ) {
			$ds              = $current_dt->format( 'Y-m-d' );
			$row_data        = isset( $raw[ $ds ] ) ? $raw[ $ds ] : array();
			$result[ $ds ]   = array(
				'sent'   => isset( $row_data['sent'] ) ? (int) $row_data['sent'] : 0,
				'failed' => isset( $row_data['failed'] ) ? (int) $row_data['failed'] : 0,
			);
			$current_dt->add( $one_day );
		}

		return $result;
	}

	/**
	 * Return aggregate totals for the last N days, with per-provider breakdown.
	 *
	 * Caches results in a transient:
	 *   $days = 7  → transient 'sendstack_stats_7d',  TTL 15 min (900 s)
	 *   $days = 30 → transient 'sendstack_stats_30d', TTL 1 hour (3600 s)
	 *   other      → no caching
	 *
	 * Applies the 'sendstack_stats_summary' filter before returning so third-party
	 * code can augment or replace the summary without a DB query.
	 *
	 * @since  1.0.0
	 * @param  int $days Number of days to look back (default 7).
	 * @return array{total_sent: int, total_failed: int, providers: array<string, array<string, int>>}
	 */
	public function summary( int $days = 7 ): array {
		$transient_key = null;
		$ttl           = 0;

		if ( 7 === $days ) {
			$transient_key = 'sendstack_stats_7d';
			$ttl           = 900;
		} elseif ( 30 === $days ) {
			$transient_key = 'sendstack_stats_30d';
			$ttl           = 3600;
		}

		if ( null !== $transient_key ) {
			$cached = get_transient( $transient_key );
			if ( false !== $cached && is_array( $cached ) ) {
				/** @var array{total_sent: int, total_failed: int, providers: array<string, array<string, int>>} $cached */
				return $cached;
			}
		}

		// Calculate the start date in site timezone for consistency with stored stat_dates.
		$since_ts   = strtotime( current_time( 'mysql' ) ) - ( $days * DAY_IN_SECONDS );
		$since_date = date( 'Y-m-d', (int) $since_ts );

		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT provider, status, SUM(count) AS total
				 FROM `{$this->table}`
				 WHERE stat_date >= %s
				 GROUP BY provider, status",
				$since_date
			),
			ARRAY_A
		);

		$summary = array(
			'total_sent'   => 0,
			'total_failed' => 0,
			'providers'    => array(),
		);

		foreach ( (array) $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}
			$provider = isset( $row['provider'] ) ? (string) $row['provider'] : '';
			$status   = isset( $row['status'] ) ? (string) $row['status'] : '';
			$count    = isset( $row['total'] ) ? (int) $row['total'] : 0;

			if ( ! isset( $summary['providers'][ $provider ] ) ) {
				$summary['providers'][ $provider ] = array( 'sent' => 0, 'failed' => 0 );
			}

			if ( 'sent' === $status ) {
				$summary['total_sent']                      += $count;
				$summary['providers'][ $provider ]['sent']  += $count;
			} elseif ( 'failed' === $status ) {
				$summary['total_failed']                      += $count;
				$summary['providers'][ $provider ]['failed']  += $count;
			}
		}

		/** @var array{total_sent: int, total_failed: int, providers: array<string, array<string, int>>} $summary */
		$summary = apply_filters( 'sendstack_stats_summary', $summary, $days );

		if ( null !== $transient_key ) {
			set_transient( $transient_key, $summary, $ttl );
		}

		return $summary;
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * INSERT ON DUPLICATE KEY UPDATE — ATOMIC COUNTERS
 * A naive "check if row exists → INSERT or UPDATE" pattern has a race condition:
 * two simultaneous wp_mail() calls can both read "row not found" and both
 * attempt INSERT, causing one to fail with a duplicate-key error. INSERT … ON
 * DUPLICATE KEY UPDATE is a single atomic statement — MySQL handles the lock
 * internally. The UNIQUE KEY on (stat_date, provider, status) is what triggers
 * the ON DUPLICATE KEY branch. No application-level locking is needed.
 *
 * TRANSIENT CACHING STRATEGY
 * The dashboard reads summary() on every page load. Without caching, every
 * admin visit runs a GROUP BY query over the entire stats table. A 15-minute
 * TTL for the 7-day tile and 1-hour TTL for the 30-day chart keeps the UI
 * fast without showing stale data for long. clear_cache() is called by
 * StatsAggregator on every successful send/failure so the cache is invalidated
 * the moment new data arrives — the next dashboard load rebuilds it fresh.
 *
 * WHY DAY_IN_SECONDS INSTEAD OF strtotime("-N days")
 * strtotime() parses a relative date string each call. DAY_IN_SECONDS is a WP
 * constant (86400) and arithmetic on Unix timestamps is fast and unambiguous.
 * Both approaches respect the same calendar math for small N; the difference is
 * negligible, but the constant form is more readable in code review.
 *
 * for_range() FILLS MISSING DATES
 * A charting library expects one data point per day. If no emails were sent on
 * a given day, the DB returns no row for it. Rather than forcing every chart
 * implementation to handle gaps, the repository fills missing dates with zeros
 * so callers get a dense, continuous array. The DateTime loop is O(range_days)
 * and the range is typically ≤ 30, so performance is not a concern.
 *
 * current_time('Y-m-d') FOR stat_date
 * stat_dates are stored using the WordPress site timezone (current_time uses
 * the WP timezone setting). summary() uses the same timezone for the since_date
 * calculation to ensure consistency — comparing site-timezone dates avoids
 * off-by-one errors around midnight when the WP timezone differs from UTC.
 */
