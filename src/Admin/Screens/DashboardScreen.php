<?php
/**
 * Dashboard admin screen.
 *
 * @package SendStack\Admin\Screens
 * @since   1.0.0
 */

namespace SendStack\Admin\Screens;

defined( 'ABSPATH' ) || exit;

use SendStack\Stats\StatsRepository;

/**
 * Renders the SendStack overview dashboard: recent stats, active provider
 * status, and quick links to other screens.
 *
 * @since 1.0.0
 */
class DashboardScreen extends AbstractScreen {

	/** @var StatsRepository */
	private $stats;

	/**
	 * @param StatsRepository $stats Stats repository for summary data.
	 */
	public function __construct( StatsRepository $stats ) {
		$this->stats = $stats;
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
		return __( 'SendStack Dashboard', 'sendstack' );
	}

	/**
	 * Render the dashboard overview.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'sendstack' ) );
		}

		$summary = $this->stats->summary( 30 );

		ob_start();
		// TODO: include template file from templates/admin/dashboard.php.
		$content = (string) ob_get_clean();

		$this->wrap( $content );
	}
}
