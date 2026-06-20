<?php
/**
 * Rate-limits outbound alerts.
 *
 * @package SendStack\Alerts
 * @since   1.0.0
 */

namespace SendStack\Alerts;

defined( 'ABSPATH' ) || exit;

/**
 * Prevents the same alert type from flooding a channel by tracking the last
 * sent timestamp in a WordPress transient.
 *
 * @since 1.0.0
 */
class AlertThrottle {

	/** @var int Default cooldown between alerts of the same type per channel (seconds). */
	private const DEFAULT_TTL = 3600;

	/** @var string Transient key prefix. */
	private const PREFIX = 'sendstack_alert_throttle_';

	/** @var int TTL in seconds. */
	private $ttl;

	/**
	 * @param int $ttl Cooldown in seconds between repeated alerts (default 1 hour).
	 */
	public function __construct( int $ttl = self::DEFAULT_TTL ) {
		$this->ttl = $ttl;
	}

	/**
	 * Return true when enough time has passed since the last alert of this
	 * type was sent via this channel.
	 *
	 * @since  1.0.0
	 * @param  AlertInterface $channel Channel to check.
	 * @param  AlertEvent     $event   Event type to check.
	 * @return bool
	 */
	public function should_send( AlertInterface $channel, AlertEvent $event ): bool {
		return false === get_transient( $this->key( $channel, $event ) );
	}

	/**
	 * Record that an alert was just sent so the cooldown window starts.
	 *
	 * @since  1.0.0
	 * @param  AlertInterface $channel Channel that delivered the alert.
	 * @param  AlertEvent     $event   Event that was dispatched.
	 * @return void
	 */
	public function record( AlertInterface $channel, AlertEvent $event ): void {
		set_transient( $this->key( $channel, $event ), time(), $this->ttl );
	}

	/**
	 * Build the transient key for a channel + event type combination.
	 *
	 * @since  1.0.0
	 * @param  AlertInterface $channel Channel.
	 * @param  AlertEvent     $event   Event.
	 * @return string
	 */
	private function key( AlertInterface $channel, AlertEvent $event ): string {
		return self::PREFIX . $channel->slug() . '_' . $event->type();
	}
}
