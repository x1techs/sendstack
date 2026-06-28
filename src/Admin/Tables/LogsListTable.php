<?php
/**
 * Logs list table.
 *
 * @package SendStack\Admin\Tables
 * @since   1.0.0
 */

namespace SendStack\Admin\Tables;

defined( 'ABSPATH' ) || exit;

use SendStack\Logger\LogEntry;
use SendStack\Logger\LogRepository;
use SendStack\Logger\LogStatus;

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
}

/**
 * Renders a paginated, sortable, filterable, and bulk-actionable table of
 * email log rows using the WordPress WP_List_Table API.
 *
 * Items are kept as LogEntry objects throughout so column renderers use typed
 * getters instead of fragile array-key access.
 *
 * @since 1.0.0
 */
class LogsListTable extends \WP_List_Table {

	/** @var LogRepository */
	private $repository;

	/**
	 * @param LogRepository $repository Injected log data access object.
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
	 * Return column header definitions.
	 *
	 * @since  1.0.0
	 * @return array<string, string>
	 */
	public function get_columns(): array {
		return apply_filters(
			'sendstack_logs_columns',
			array(
				'cb'         => '<input type="checkbox">',
				'status'     => __( 'Status', 'sendstack' ),
				'to'         => __( 'To', 'sendstack' ),
				'subject'    => __( 'Subject', 'sendstack' ),
				'provider'   => __( 'Provider', 'sendstack' ),
				'created_at' => __( 'Date', 'sendstack' ),
			)
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
			'status'     => array( 'status', false ),
			'subject'    => array( 'subject', false ),
			'created_at' => array( 'created_at', true ),
		);
	}

	/**
	 * Render the checkbox column for bulk selection.
	 *
	 * @since  1.0.0
	 * @param  LogEntry $item Log entry.
	 * @return string
	 */
	public function column_cb( $item ): string {
		return sprintf(
			'<input type="checkbox" name="log_ids[]" value="%d">',
			(int) $item->id()
		);
	}

	/**
	 * Default column renderer — dispatches to the appropriate output per column.
	 *
	 * @since  1.0.0
	 * @param  LogEntry $item        Log entry.
	 * @param  string   $column_name Column key.
	 * @return string
	 */
	public function column_default( $item, $column_name ): string {
		switch ( $column_name ) {
			case 'status':
				return sprintf(
					'<span class="sstk-status sstk-status--%s">%s</span>',
					esc_attr( $item->status() ),
					esc_html( $item->status() )
				);

			case 'to':
				return esc_html( implode( ', ', $item->to() ) );

			case 'subject':
				return $this->render_subject_column( $item );

			case 'provider':
				return esc_html( $item->provider() );

			case 'created_at':
				return esc_html(
					(string) wp_date(
						get_option( 'date_format' ) . ' ' . get_option( 'time_format' ),
						strtotime( $item->created_at() )
					)
				);

			default:
				return '';
		}
	}

	/**
	 * Render the subject cell with inline row actions.
	 *
	 * @since  1.0.0
	 * @param  LogEntry $item Log entry.
	 * @return string
	 */
	private function render_subject_column( LogEntry $item ): string {
		$delete_url = wp_nonce_url(
			add_query_arg(
				array(
					'page'      => 'sendstack-logs',
					'action'    => 'delete',
					'log_ids[]' => $item->id(),
				),
				admin_url( 'admin.php' )
			),
			'bulk-logs'
		);

		$row_actions = apply_filters(
			'sendstack_logs_row_actions',
			array(
				'delete' => sprintf(
					'<a href="%s" class="submitdelete" aria-label="%s">%s</a>',
					esc_url( $delete_url ),
					/* translators: %s: email subject */
					esc_attr( sprintf( __( 'Delete "%s"', 'sendstack' ), $item->subject() ) ),
					esc_html__( 'Delete', 'sendstack' )
				),
			),
			$item
		);

		return esc_html( $item->subject() ) . $this->row_actions( $row_actions );
	}

	/**
	 * Return available bulk actions.
	 *
	 * @since  1.0.0
	 * @return array<string, string>
	 */
	public function get_bulk_actions(): array {
		return array(
			'bulk_delete' => __( 'Delete', 'sendstack' ),
		);
	}

	/**
	 * Process a submitted bulk action.
	 *
	 * WP_List_Table generates a nonce with action name 'bulk-{plural}' = 'bulk-logs'.
	 * check_admin_referer() dies with a 403 when the nonce is invalid or missing.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function process_bulk_action(): void {
		if ( 'bulk_delete' !== $this->current_action() ) {
			return;
		}

		check_admin_referer( 'bulk-logs' );

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$ids = isset( $_REQUEST['log_ids'] ) ? array_map( 'absint', (array) $_REQUEST['log_ids'] ) : array();
		$ids = array_filter( $ids );

		if ( empty( $ids ) ) {
			return;
		}

		$deleted = $this->repository->bulk_delete( $ids );

		add_settings_error(
			'sendstack',
			'logs-deleted',
			sprintf(
				/* translators: %d: number of deleted log entries */
				_n( '%d log entry deleted.', '%d log entries deleted.', $deleted, 'sendstack' ),
				$deleted
			),
			'success'
		);
	}

	/**
	 * Fetch, filter, and paginate log entries into $this->items.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function prepare_items(): void {
		$per_page     = $this->get_items_per_page( 'sendstack_logs_per_page', 25 );
		$current_page = $this->get_pagenum();

		// phpcs:disable WordPress.Security.NonceVerification.Recommended
		$allowed_orderby = array( 'id', 'status', 'provider', 'created_at', 'subject' );
		$allowed_order   = array( 'ASC', 'DESC' );

		$raw_orderby = isset( $_REQUEST['orderby'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['orderby'] ) ) : 'created_at';
		$raw_order   = isset( $_REQUEST['order'] ) ? strtoupper( sanitize_text_field( wp_unslash( $_REQUEST['order'] ) ) ) : 'DESC';

		$args = array(
			'status'   => isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '',
			'provider' => isset( $_REQUEST['provider'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['provider'] ) ) : '',
			'search'   => isset( $_REQUEST['s'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['s'] ) ) : '',
			'per_page' => $per_page,
			'page'     => $current_page,
			'orderby'  => in_array( $raw_orderby, $allowed_orderby, true ) ? $raw_orderby : 'created_at',
			'order'    => in_array( $raw_order, $allowed_order, true ) ? $raw_order : 'DESC',
		);
		// phpcs:enable

		$count       = $this->repository->count( $args );
		$this->items = $this->repository->query( $args );

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
	 * Return status-filter view links shown above the table.
	 *
	 * @since  1.0.0
	 * @return array<string, string>
	 */
	public function get_views(): array {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$current = isset( $_REQUEST['status'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['status'] ) ) : '';
		$base    = add_query_arg( 'page', 'sendstack-logs', admin_url( 'admin.php' ) );

		$statuses = array(
			''                  => __( 'All', 'sendstack' ),
			LogStatus::SENT     => __( 'Sent', 'sendstack' ),
			LogStatus::FAILED   => __( 'Failed', 'sendstack' ),
			LogStatus::QUEUED   => __( 'Queued', 'sendstack' ),
			LogStatus::RETRYING => __( 'Retrying', 'sendstack' ),
		);

		$views = array();

		foreach ( $statuses as $status => $label ) {
			$count  = $this->repository->count( '' !== $status ? array( 'status' => $status ) : array() );
			$url    = '' !== $status ? add_query_arg( 'status', $status, $base ) : $base;
			$class  = $current === $status ? 'current' : '';
			$key    = '' !== $status ? $status : 'all';

			$views[ $key ] = sprintf(
				'<a href="%s" class="%s">%s <span class="count">(%s)</span></a>',
				esc_url( $url ),
				esc_attr( $class ),
				esc_html( $label ),
				number_format_i18n( $count )
			);
		}

		return $views;
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * ITEMS ARE LogEntry OBJECTS, NOT ARRAYS
 * WP_List_Table only requires that $this->items is iterable. Keeping LogEntry
 * objects means column renderers use typed getters ($item->status()) instead of
 * array key access ($item['status']). This catches typos at the PHPStan level
 * and removes the need to decode to_json / headers_json after the fact.
 *
 * WHITELIST BEFORE INTERPOLATION IN prepare_items()
 * orderby and order are interpolated into a SQL ORDER BY clause inside
 * LogRepository::query(). The repository whitelists them again, but the table
 * adds a second layer: never trust a value from $_REQUEST without validating
 * it against a known-good list in the calling code too.
 *
 * bulk-logs NONCE NAME
 * WP_List_Table auto-generates a nonce field with action name
 * 'bulk-{$this->_args["plural"]}' = 'bulk-logs'. check_admin_referer('bulk-logs')
 * validates that field. This is a WP_List_Table convention — the nonce name must
 * match exactly or every bulk action submission will silently fail.
 *
 * process_bulk_action() USES bulk_delete() NOT A LOOP OF delete()
 * repository->bulk_delete() runs a single DELETE … WHERE id IN (…) query.
 * Calling delete() in a loop for 50 rows would fire 50 queries. The bulk
 * method is both faster and atomic — either all rows are deleted or none are.
 *
 * get_views() QUERIES THE DB PER STATUS
 * Each status count runs one COUNT(*) query. For up to five statuses this is
 * negligible (five tiny indexed reads). An alternative would be a single
 * GROUP BY query, but the added complexity isn't warranted at this scale.
 * If profiling ever shows this is slow, add a cached summary method to
 * LogRepository.
 *
 * sendstack_logs_columns / sendstack_logs_row_actions FILTERS
 * Both filters are part of the public extensibility API (blueprint §e). Wrapping
 * get_columns() and row_actions with apply_filters() lets a third-party plugin
 * add a "Attachments" column or a "Resend" row action without patching core.
 */
