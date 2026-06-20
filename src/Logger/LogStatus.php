<?php
/**
 * Log-row status constants.
 *
 * @package SendStack\Logger
 * @since   1.0.0
 */

namespace SendStack\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Typed constants representing every valid value for the `status` column of
 * the sendstack_logs table. Acts as an enum for PHP 7.4 compatibility.
 *
 * @since 1.0.0
 */
final class LogStatus {

	/** @var string Message is waiting to be sent. */
	public const QUEUED = 'queued';

	/** @var string Message was delivered successfully. */
	public const SENT = 'sent';

	/** @var string Delivery failed and no further retry is scheduled. */
	public const FAILED = 'failed';

	/** @var string Delivery failed; a retry is scheduled via the cron queue. */
	public const RETRYING = 'retrying';

	/**
	 * Return all valid status strings.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public static function all(): array {
		return array( self::QUEUED, self::SENT, self::FAILED, self::RETRYING );
	}

	/** Not instantiable. */
	private function __construct() {}
}
