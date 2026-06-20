<?php
/**
 * Email logs admin screen.
 *
 * @package SendStack\Admin\Screens
 * @since   1.0.0
 */

namespace SendStack\Admin\Screens;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Tables\LogsListTable;

/**
 * Renders the paginated email log viewer, backed by LogsListTable.
 *
 * @since 1.0.0
 */
class LogsScreen extends AbstractScreen {

	/** @var LogsListTable */
	private $table;

	/**
	 * @param LogsListTable $table List table for log rows.
	 */
	public function __construct( LogsListTable $table ) {
		$this->table = $table;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'sendstack-logs';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Email Logs', 'sendstack' );
	}

	/**
	 * Render the logs list table.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'sendstack' ) );
		}

		$this->table->process_bulk_action();
		$this->table->prepare_items();

		ob_start();
		?>
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( $this->slug() ); ?>">
			<?php
			$this->table->search_box( __( 'Search Logs', 'sendstack' ), 'sendstack-log-search' );
			$this->table->display();
			?>
		</form>
		<?php
		$content = (string) ob_get_clean();

		$this->wrap( $content );
	}
}
