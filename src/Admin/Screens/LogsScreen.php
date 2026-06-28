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
use SendStack\Logger\LogRepository;

/**
 * Wraps LogsListTable inside the standard admin screen chrome.
 *
 * Receives the repository rather than the table so the table can be
 * instantiated at render time (after WP_List_Table is available).
 *
 * @since 1.0.0
 */
class LogsScreen extends AbstractScreen {

	/** @var LogRepository */
	private $repository;

	/**
	 * @param LogRepository $repository Log data access object.
	 */
	public function __construct( LogRepository $repository ) {
		$this->repository = $repository;
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
		return __( 'Email Log', 'sendstack' );
	}

	/**
	 * Render the paginated, filterable log list.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function content(): void {
		$list_table = new LogsListTable( $this->repository );
		$list_table->process_bulk_action();
		$list_table->prepare_items();
		?>
		<form method="get">
			<input type="hidden" name="page" value="<?php echo esc_attr( $this->slug() ); ?>">
			<?php
			$list_table->search_box( __( 'Search Emails', 'sendstack' ), 'sendstack-search' );
			$list_table->display();
			?>
		</form>
		<?php
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * WHY INJECT LogRepository RATHER THAN LogsListTable
 * LogsListTable extends WP_List_Table which calls get_columns() and other
 * setup during construction — that code needs wp-admin/includes/class-wp-list-table.php
 * to already be loaded. Loading order during service-provider registration
 * is unreliable. Injecting the lightweight repository and instantiating the
 * table inside content() (which runs on admin_menu callbacks, well after WP
 * has loaded all admin includes) is safer and requires no require_once guard.
 *
 * process_bulk_action() BEFORE prepare_items()
 * Bulk deletes must complete before we query for the current page, otherwise
 * the deleted rows would appear on screen for one more request. The list table
 * handles this ordering internally once both calls are made in the right sequence.
 *
 * FORM METHOD="GET" FOR THE LIST TABLE
 * WP_List_Table search and column-sort links append query args to the URL.
 * Using method="get" keeps all filter state in the URL so the page is
 * bookmarkable and Back-button friendly. The hidden 'page' input preserves
 * the menu slug so WordPress routes the submission correctly.
 */
