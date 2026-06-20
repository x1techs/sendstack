<?php
/**
 * Alert event value object.
 *
 * @package SendStack\Alerts
 * @since   1.0.0
 */

namespace SendStack\Alerts;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable carrier for a single alert occurrence.
 *
 * Use the named constructors: AlertEvent::send_failed(), ::failover_triggered(),
 * or ::quota_warning().
 *
 * @since 1.0.0
 */
final class AlertEvent {

	/** @var string */
	private $type;

	/** @var string Short human-readable title for the alert. */
	private $title;

	/** @var string Detailed description of what happened. */
	private $message;

	/** @var array<string, mixed> Additional structured context. */
	private $context;

	/** @var int Unix timestamp of the event. */
	private $occurred_at;

	private function __construct(
		string $type,
		string $title,
		string $message,
		array $context
	) {
		$this->type        = $type;
		$this->title       = $title;
		$this->message     = $message;
		$this->context     = $context;
		$this->occurred_at = time();
	}

	/**
	 * Create an event representing a delivery failure.
	 *
	 * @since  1.0.0
	 * @param  string $provider      Provider slug that failed.
	 * @param  string $error_message Human-readable error description.
	 * @param  array  $context       Optional extra context (e.g. recipient, subject).
	 * @return self
	 */
	public static function send_failed( string $provider, string $error_message, array $context = array() ): self {
		return new self(
			'send_failed',
			/* translators: %s: provider name */
			sprintf( __( 'Email delivery failed via %s', 'sendstack' ), $provider ),
			$error_message,
			array_merge( array( 'provider' => $provider ), $context )
		);
	}

	/**
	 * Create an event representing automatic failover to a backup provider.
	 *
	 * @since  1.0.0
	 * @param  string $from_provider Primary provider slug.
	 * @param  string $to_provider   Backup provider slug.
	 * @param  array  $context       Optional extra context.
	 * @return self
	 */
	public static function failover_triggered( string $from_provider, string $to_provider, array $context = array() ): self {
		return new self(
			'failover_triggered',
			__( 'Failover triggered', 'sendstack' ),
			/* translators: 1: primary provider, 2: backup provider */
			sprintf( __( 'Switched from %1$s to %2$s after delivery failure.', 'sendstack' ), $from_provider, $to_provider ),
			array_merge(
				array(
					'from' => $from_provider,
					'to'   => $to_provider,
				),
				$context
			)
		);
	}

	/**
	 * Create an event representing a sending-quota warning.
	 *
	 * @since  1.0.0
	 * @param  string $provider  Provider slug approaching its quota.
	 * @param  float  $usage_pct Percentage of the quota consumed (0–100).
	 * @param  array  $context   Optional extra context.
	 * @return self
	 */
	public static function quota_warning( string $provider, float $usage_pct, array $context = array() ): self {
		return new self(
			'quota_warning',
			/* translators: %s: provider name */
			sprintf( __( 'Quota warning for %s', 'sendstack' ), $provider ),
			/* translators: 1: provider name, 2: usage percentage */
			sprintf( __( '%1$s has used %.1f%% of its sending quota.', 'sendstack' ), $provider, $usage_pct ),
			array_merge(
				array(
					'provider'  => $provider,
					'usage_pct' => $usage_pct,
				),
				$context
			)
		);
	}

	/** @since 1.0.0 */
	public function type(): string {
		return $this->type;
	}

	/** @since 1.0.0 */
	public function title(): string {
		return $this->title;
	}

	/** @since 1.0.0 */
	public function message(): string {
		return $this->message;
	}

	/**
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function context(): array {
		return $this->context;
	}

	/** @since 1.0.0 */
	public function occurred_at(): int {
		return $this->occurred_at;
	}
}
