<?php
/**
 * Plugin-level debug logger.
 *
 * @package SendStack\Support
 * @since   1.0.0
 */

namespace SendStack\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Writes structured log lines to the PHP error log when WP_DEBUG is enabled.
 *
 * All output is gated on WP_DEBUG so nothing reaches production unless the
 * site owner has explicitly enabled debug mode.
 *
 * @since 1.0.0
 */
class DebugLogger {

	/** @var string Prefix prepended to every log line. */
	private const PREFIX = '[SendStack]';

	/**
	 * Log a debug-level message (verbose tracing).
	 *
	 * @since  1.0.0
	 * @param  string               $message Human-readable description.
	 * @param  array<string, mixed> $context Optional structured key-value context.
	 * @return void
	 */
	public function debug( string $message, array $context = array() ): void {
		$this->write( 'DEBUG', $message, $context );
	}

	/**
	 * Log an informational message (normal operational events).
	 *
	 * @since  1.0.0
	 * @param  string               $message Human-readable description.
	 * @param  array<string, mixed> $context Optional structured key-value context.
	 * @return void
	 */
	public function info( string $message, array $context = array() ): void {
		$this->write( 'INFO', $message, $context );
	}

	/**
	 * Log an error-level message (unexpected failures).
	 *
	 * Unlike debug/info, error lines are written even when WP_DEBUG_LOG is off,
	 * provided WP_DEBUG itself is true.
	 *
	 * @since  1.0.0
	 * @param  string               $message Human-readable description.
	 * @param  array<string, mixed> $context Optional structured key-value context.
	 * @return void
	 */
	public function error( string $message, array $context = array() ): void {
		$this->write( 'ERROR', $message, $context );
	}

	/**
	 * Format and emit a log line when WP_DEBUG is active.
	 *
	 * @since  1.0.0
	 * @param  string               $level   Log level label.
	 * @param  string               $message Message.
	 * @param  array<string, mixed> $context Context.
	 * @return void
	 */
	private function write( string $level, string $message, array $context ): void {
		if ( ! defined( 'WP_DEBUG' ) || ! WP_DEBUG ) {
			return;
		}

		$line = sprintf( '%s[%s] %s', self::PREFIX, $level, $message );

		if ( ! empty( $context ) ) {
			$line .= ' ' . wp_json_encode( $context );
		}

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( $line );
	}
}
