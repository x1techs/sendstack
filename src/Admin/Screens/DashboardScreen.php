<?php
/**
 * Dashboard admin screen.
 *
 * @package SendStack\Admin\Screens
 * @since   1.0.0
 */

namespace SendStack\Admin\Screens;

defined( 'ABSPATH' ) || exit;

use SendStack\Logger\LogRepository;
use SendStack\Stats\StatsRepository;

/**
 * Renders the SendStack overview dashboard: 7-day stat tiles, active provider,
 * and a recent-failures mini-table for quick triage.
 *
 * @since 1.0.0
 */
class DashboardScreen extends AbstractScreen {

	/** @var StatsRepository */
	private $stats_repo;

	/** @var LogRepository */
	private $log_repo;

	/**
	 * @param StatsRepository $stats_repo Stats repository for summary tiles.
	 * @param LogRepository   $log_repo   Log repository for the failures table.
	 */
	public function __construct( StatsRepository $stats_repo, LogRepository $log_repo ) {
		$this->stats_repo = $stats_repo;
		$this->log_repo   = $log_repo;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'sendstack';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Dashboard', 'sendstack' );
	}

	/**
	 * Output stats tiles and the recent-failures table.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function content(): void {
		$summary  = $this->stats_repo->summary( 7 );
		$total    = $summary['total_sent'] + $summary['total_failed'];
		$rate     = $total > 0 ? round( $summary['total_sent'] / $total * 100, 1 ) : 0.0;
		$settings = (array) get_option( 'sendstack_settings', array() );
		$provider = isset( $settings['provider'] ) && '' !== $settings['provider']
			? ucfirst( (string) $settings['provider'] )
			: __( 'Not configured', 'sendstack' );
		?>

		<div class="sstk-stats-tiles">
			<div class="sstk-stat-tile sstk-stat-tile--success">
				<div class="sstk-stat-tile__value"><?php echo esc_html( number_format_i18n( $summary['total_sent'] ) ); ?></div>
				<div class="sstk-stat-tile__label"><?php esc_html_e( 'Sent (7 days)', 'sendstack' ); ?></div>
			</div>
			<div class="sstk-stat-tile sstk-stat-tile--failure">
				<div class="sstk-stat-tile__value"><?php echo esc_html( number_format_i18n( $summary['total_failed'] ) ); ?></div>
				<div class="sstk-stat-tile__label"><?php esc_html_e( 'Failed (7 days)', 'sendstack' ); ?></div>
			</div>
			<div class="sstk-stat-tile">
				<div class="sstk-stat-tile__value"><?php echo esc_html( $rate . '%' ); ?></div>
				<div class="sstk-stat-tile__label"><?php esc_html_e( 'Success Rate', 'sendstack' ); ?></div>
			</div>
			<div class="sstk-stat-tile">
				<div class="sstk-stat-tile__value"><?php echo esc_html( $provider ); ?></div>
				<div class="sstk-stat-tile__label"><?php esc_html_e( 'Active Provider', 'sendstack' ); ?></div>
			</div>
		</div>

		<h2><?php esc_html_e( 'Recent Failures', 'sendstack' ); ?></h2>
		<?php
		$failures = $this->log_repo->query(
			array(
				'status'   => 'failed',
				'per_page' => 5,
				'orderby'  => 'created_at',
				'order'    => 'DESC',
			)
		);

		if ( empty( $failures ) ) {
			echo '<p>' . esc_html__( 'No recent failures.', 'sendstack' ) . '</p>';
			return;
		}
		?>
		<table class="wp-list-table widefat fixed striped">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Date', 'sendstack' ); ?></th>
					<th><?php esc_html_e( 'To', 'sendstack' ); ?></th>
					<th><?php esc_html_e( 'Subject', 'sendstack' ); ?></th>
					<th><?php esc_html_e( 'Error', 'sendstack' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $failures as $entry ) : ?>
				<tr>
					<td><?php echo esc_html( (string) wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $entry->created_at() ) ) ); ?></td>
					<td><?php echo esc_html( implode( ', ', $entry->to() ) ); ?></td>
					<td><?php echo esc_html( $entry->subject() ); ?></td>
					<td><?php echo esc_html( $entry->error_message() ); ?></td>
				</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		<?php
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * TWO REPOSITORIES IN ONE SCREEN
 * DashboardScreen receives both StatsRepository (for the tiles) and
 * LogRepository (for the failures table). Injecting both keeps the class
 * honest about what it actually reads — and makes it easy to swap either
 * in a test without affecting the other.
 *
 * TRANSIENT CACHING HAPPENS INSIDE StatsRepository::summary()
 * The dashboard doesn't cache anything itself. summary(7) internally checks a
 * 15-minute transient ('sendstack_stats_7d') before hitting the DB. The screen
 * is unaware of this — it just calls the method and renders what comes back.
 *
 * number_format_i18n() FOR STATS NUMBERS
 * Always format counts through number_format_i18n() rather than (string)
 * casting, so the thousands separator respects the site locale (e.g. French
 * sites expect 1 234 not 1,234). The rate is formatted manually since it needs
 * a % suffix and one decimal place.
 *
 * EARLY RETURN ON EMPTY FAILURES
 * If the failures query returns nothing, the method echoes a success message and
 * returns rather than rendering an empty <table>. This avoids a confusing empty
 * table header and keeps the no-data state readable.
 */
