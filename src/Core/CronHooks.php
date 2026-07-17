<?php
/**
 * Canonical WordPress cron hook names.
 *
 * @package SendStack\Core
 * @since   0.1.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Provides one source of truth for every SendStack cron hook name.
 *
 * Schedulers, callbacks, and lifecycle handlers must reference these constants
 * instead of repeating hook-name strings in multiple files.
 *
 * @since 0.1.0
 */
final class CronHooks {

	/** Daily log-retention job. */
	public const PRUNE_LOGS = 'sendstack_prune_logs';

	/** One-time retry-queue processing job. */
	public const PROCESS_RETRY_QUEUE = 'sendstack_process_retry_queue';

	/**
	 * This class is a constants-only namespace and cannot be instantiated.
	 *
	 * @codeCoverageIgnore
	 */
	private function __construct() {}
}
