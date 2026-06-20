<?php
/**
 * CRUD operations over the sendstack_stats table.
 *
 * @package SendStack\Stats
 * @since   1.0.0
 */

namespace SendStack\Stats;

defined( 'ABSPATH' ) || exit;

/**
 * Provides increment / for_range / summary methods for the sendstack_stats
 * aggregate table.
 *
 * The table schema is expected to be:
 *   id, metric VARCHAR, value INT, stat_date DATE, created_at DATETIME
 *
 * @since 1.0.0
 */
class StatsRepository {

	/** @var \wpdb */
	private $db;

	/** @var string Fully-qualified table name. */
	private $table;

	/**
	 * @param \wpdb $db WordPress database object.
	 */
	public function __construct( \wpdb $db ) {
		$this->db    = $db;
		$this->table = $db->prefix . 'sendstack_stats';
	}

	/**
	 * Increment (or create) a metric counter for a given date.
	 *
	 * @since  1.0.0
	 * @param  string $metric Metric key (e.g. 'sent', 'failed', 'retried').
	 * @param  int    $value  Amount to add (default 1).
	 * @param  string $date   ISO date string (YYYY-MM-DD); defaults to today (UTC).
	 * @return void
	 */
	public function increment( string $metric, int $value = 1, string $date = '' ): void {
		if ( '' === $date ) {
			$date = gmdate( 'Y-m-d' );
		}

		// Use INSERT … ON DUPLICATE KEY UPDATE to avoid a SELECT + INSERT race.
		$this->db->query(
			$this->db->prepare(
				"INSERT INTO `{$this->table}` (metric, value, stat_date, created_at)
				 VALUES (%s, %d, %s, %s)
				 ON DUPLICATE KEY UPDATE value = value + %d",
				$metric,
				$value,
				$date,
				current_time( 'mysql', true ),
				$value
			)
		);
	}

	/**
	 * Return daily metric totals for a given date range.
	 *
	 * @since  1.0.0
	 * @param  string $start ISO date string (inclusive lower bound).
	 * @param  string $end   ISO date string (inclusive upper bound).
	 * @return array<string, array<string, int>> Indexed as [metric][date] => total.
	 */
	public function for_range( string $start, string $end ): array {
		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT metric, stat_date, SUM(value) AS total
				 FROM `{$this->table}`
				 WHERE stat_date BETWEEN %s AND %s
				 GROUP BY metric, stat_date
				 ORDER BY stat_date ASC",
				$start,
				$end
			),
			ARRAY_A
		);

		$result = array();

		foreach ( (array) $rows as $row ) {
			$result[ $row['metric'] ][ $row['stat_date'] ] = (int) $row['total'];
		}

		return $result;
	}

	/**
	 * Return aggregated metric totals for the last N days.
	 *
	 * @since  1.0.0
	 * @param  int $days Number of days to include (default 30).
	 * @return array<string, int> Metric key => total count.
	 */
	public function summary( int $days = 30 ): array {
		$since = gmdate( 'Y-m-d', strtotime( "-{$days} days" ) );

		$rows = $this->db->get_results(
			$this->db->prepare(
				"SELECT metric, SUM(value) AS total
				 FROM `{$this->table}`
				 WHERE stat_date >= %s
				 GROUP BY metric",
				$since
			),
			ARRAY_A
		);

		$result = array();

		foreach ( (array) $rows as $row ) {
			$result[ $row['metric'] ] = (int) $row['total'];
		}

		return $result;
	}
}
