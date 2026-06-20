<?php
/**
 * Logs list table.
 *
 * @package SendStack\Admin\Tables
 * @since   1.0.0
 */

namespace SendStack\Admin\Tables;

defined( 'ABSPATH' ) || exit;

use SendStack\Logger\LogRepository;
use SendStack\Logger\LogStatus;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Renders a paginated, sortable, and bulk-actionable table of email log rows.
 *
 * @since 1.0.0
 */
class LogsListTable extends \WP_List_Table {

	/** @var LogRepository */
	private $repository;

	/**
	 * @param LogRepository $repository Injected log repository.
	 */
	public function __construct( LogRepository $repository ) {
		parent::__construct(
			array(
				'singular' => 'log',
				'plural'   => 'logs',
				'ajax'     => false,
			)
		);

		$this->repository = $repository;
	}

	/**
	 * Fetch, filter, and paginate log rows into $this->items.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page     = $this->get_items_per_page( 'sendstack_logs_per_page', 25 );
		$current_page = $this->get_pagenum();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$args = array(
			'status'   => isset( $_GET['status'] ) ? sanitize_text_field( wp_unslash( $_GET['status'] ) ) : '',
			'provider' => isset( $_GET['provider'] ) ? sanitize_text_field( wp_unslash( $_GET['provider'] ) ) : '',
			'search'   => isset( $_GET['s'] ) ? sanitize_text_field( wp_unslash( $_GET['s'] ) ) : '',
			'per_page' => $per_page,
			'page'     => $current_page,
			'orderby'  => isset( $_GET['orderby'] ) ? sanitize_text_field( wp_unslash( $_GET['orderby'] ) ) : 'created_at',
			'order'    => isset( $_GET['order'] ) ? sanitize_text_field( wp_unslash( $_GET['order'] ) ) : 'DESC',
		);
		// phpcs:enable

		$count       = $this->repository->count( $args );
		$this->items = array_map(
			static function ( $entry ) {
				return $entry->to_array();
			},
			$this->repository->query( $args )
		);

		$this->set_pagination_args(
			array(
				'total_items' => $count,
				'per_page'    => $per_page,
				'total_pages' => (int) ceil( $count / $per_page ),
			)
		);

		$this->_column_headers = array(
			$this->get_columns(),
			array(),
			$this->get_sortable_columns(),
		);
	}

	/**
	 * Return column headers.
	 *
	 * @since  1.0.0
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return array(
			'cb'         => '<input type="checkbox">',
			'to'         => __( 'Recipient', 'sendstack' ),
			'subject'    => __( 'Subject', 'sendstack' ),
			'provider'   => __( 'Provider', 'sendstack' ),
			'status'     => __( 'Status', 'sendstack' ),
			'created_at' => __( 'Date', 'sendstack' ),
		);
	}

	/**
	 * Return sortable column definitions.
	 *
	 * @since  1.0.0
	 * @return array<string, array<int, mixed>>
	 */
	public function get_sortable_columns(): array {
		return array(
			'created_at' => array( 'created_at', true ),
			'status'     => array( 'status', false ),
			'provider'   => array( 'provider', false ),
		);
	}

	/**
	 * Render the checkbox column for bulk selection.
	 *
	 * @since  1.0.0
	 * @param  array $item Log row as associative array.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf( '<input type="checkbox" name="log_ids[]" value="%d">', (int) $item['id'] );
	}

	/**
	 * Default column renderer for any column without a dedicated method.
	 *
	 * @since  1.0.0
	 * @param  array  $item        Log row as associative array.
	 * @param  string $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'status':
				return sprintf(
					'<span class="sendstack-status sendstack-status--%s">%s</span>',
					esc_attr( $item['status'] ),
					esc_html( $item['status'] )
				);

			case 'created_at':
				return esc_html(
					wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['created_at'] ) )
				);

			default:
				return esc_html( (string) ( $item[ $column_name ] ?? '' ) );
		}
	}

	/**
	 * Return available bulk actions.
	 *
	 * @since  1.0.0
	 * @return array<string, string>
	 */
	public function get_bulk_actions(): array {
		return array(
			'resend' => __( 'Resend', 'sendstack' ),
			'delete' => __( 'Delete', 'sendstack' ),
		);
	}

	/**
	 * Process a submitted bulk action.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function process_bulk_action(): void {
		$action = $this->current_action();

		if ( ! $action ) {
			return;
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$nonce = isset( $_GET['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_GET['_wpnonce'] ) ) : '';
		// phpcs:enable

		if ( ! wp_verify_nonce( $nonce, 'bulk-logs' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$ids = isset( $_GET['log_ids'] ) ? array_map( 'intval', (array) $_GET['log_ids'] ) : array();

		if ( empty( $ids ) ) {
			return;
		}

		switch ( $action ) {
			case 'delete':
				foreach ( $ids as $id ) {
					$this->repository->delete( $id );
				}
				break;

			case 'resend':
				// TODO: dispatch resend jobs for each ID.
				break;
		}
	}
}
