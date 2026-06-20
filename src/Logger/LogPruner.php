<?php
/**
 * WP-Cron–based log retention enforcer.
 *
 * @package SendStack\Logger
 * @since   1.0.0
 */

namespace SendStack\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Schedules and runs periodic log pruning based on the site's configured
 * retention period (stored in the sendstack_log_retention_days option).
 *
 * @since 1.0.0
 */
class LogPruner {

	/** @var LogRepository */
	private $repository;

	/** @var string WP-Cron hook name. */
	public const CRON_HOOK = 'sendstack_prune_logs';

	/** @var string Option key for the retention window in days. */
	private const OPTION_RETENTION = 'sendstack_log_retention_days';

	/** @var int Default retention window. */
	private const DEFAULT_RETENTION_DAYS = 30;

	/**
	 * @param LogRepository $repository Injected log repository.
	 */
	public function __construct( LogRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register the cron event if it is not already scheduled.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function schedule(): void {
		if ( ! wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time(), 'daily', self::CRON_HOOK );
		}
	}

	/**
	 * Purge log rows that exceed the configured retention window.
	 *
	 * Hooked to CRON_HOOK by the Logger service provider.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run(): void {
		$days = (int) get_option( self::OPTION_RETENTION, self::DEFAULT_RETENTION_DAYS );

		if ( $days <= 0 ) {
			return;
		}

		$this->repository->purge_older_than( $days );
	}
}
