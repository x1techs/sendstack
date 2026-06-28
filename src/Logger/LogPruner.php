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
 * retention period (stored under the sendstack_logs_config option key).
 *
 * @since 1.0.0
 */
class LogPruner {

	/** @var LogRepository */
	private $repository;

	/** @var string WP-Cron hook name. */
	public const CRON_HOOK = 'sendstack_prune_logs';

	/**
	 * @since 1.0.0
	 * @param LogRepository $repository Injected log repository.
	 */
	public function __construct( LogRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Attach the cron callback to the WP-Cron hook.
	 *
	 * Called during plugin boot so the cron callback is available when the
	 * WP-Cron runner triggers the event.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		add_action( self::CRON_HOOK, array( $this, 'run' ) );
	}

	/**
	 * Schedule the pruning event if it is not already scheduled.
	 *
	 * Idempotent — safe to call on every boot; WP-Cron deduplicates by hook name.
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
	 * Remove the scheduled event from WP-Cron.
	 *
	 * Called on plugin deactivation to avoid orphaned cron events.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function unschedule(): void {
		$timestamp = wp_next_scheduled( self::CRON_HOOK );
		if ( $timestamp ) {
			wp_unschedule_event( $timestamp, self::CRON_HOOK );
		}
	}

	/**
	 * Delete log rows that exceed the configured retention window.
	 *
	 * A retention of 0 or less means "keep forever" — the pruner returns early
	 * without touching the table.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run(): void {
		$config = (array) get_option( 'sendstack_logs_config', array() );
		$days   = (int) ( $config['retention_days'] ?? 30 );

		if ( $days <= 0 ) {
			return;
		}

		$deleted = $this->repository->purge_older_than( $days );

		do_action( 'sendstack_logs_pruned', $deleted, $days );
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * WHY DAILY CRON INSTEAD OF PRUNING ON EVERY PAGE LOAD
 * WordPress's WP-Cron is pseudo-cron — events fire on the next page visit after
 * the scheduled time. Using a daily schedule means the DELETE query runs at most
 * once every 24 hours. Running it on every page load or every send attempt would
 * add a DELETE query to every wp_mail() call, increasing write pressure on the
 * logs table unnecessarily. Daily is the standard interval for housekeeping jobs
 * in WordPress plugins (e.g. WP's own transient expiry cleanup).
 *
 * RETENTION = 0 MEANS "KEEP FOREVER"
 * Users who want to keep logs indefinitely set retention_days to 0 (or leave it
 * unset). The early-return guard prevents an accidental DELETE of all rows:
 * purge_older_than(0) would delete everything newer than today, not nothing.
 *
 * register() ADDS THE HOOK, schedule() ADDS THE EVENT
 * These are intentionally separate responsibilities:
 *   register() — adds the PHP callback so WP-Cron can call it.
 *   schedule() — adds the time-based event so WP-Cron knows when to fire.
 * The LoggerServiceProvider calls both on boot. A deactivation hook would call
 * unschedule() to remove the event while leaving the PHP hook intact (harmless).
 *
 * THE sendstack_logs_pruned ACTION
 * Fired after the DELETE so other subsystems can react — e.g. a future
 * diagnostic screen could log the prune count, or an admin notice could
 * report how many rows were cleaned up.
 */
