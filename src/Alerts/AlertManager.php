<?php
/**
 * Alert dispatch hub.
 *
 * @package SendStack\Alerts
 * @since   1.0.0
 */

namespace SendStack\Alerts;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains the registry of alert channels and routes AlertEvent objects to
 * every enabled channel, gated through AlertThrottle.
 *
 * @since 1.0.0
 */
class AlertManager {

	/** @var array<string, AlertInterface> Keyed by slug. */
	private $channels = array();

	/** @var AlertThrottle */
	private $throttle;

	/**
	 * @param AlertThrottle $throttle Rate-limiter for alert delivery.
	 */
	public function __construct( AlertThrottle $throttle ) {
		$this->throttle = $throttle;
	}

	/**
	 * Register an alert channel.
	 *
	 * @since  1.0.0
	 * @param  AlertInterface $channel Channel instance to register.
	 * @return void
	 */
	public function register( AlertInterface $channel ): void {
		$this->channels[ $channel->slug() ] = $channel;
	}

	/**
	 * Dispatch an event to all registered, enabled, non-throttled channels.
	 *
	 * @since  1.0.0
	 * @param  AlertEvent $event Alert to send.
	 * @return void
	 */
	public function dispatch( AlertEvent $event ): void {
		foreach ( $this->channels as $channel ) {
			if ( ! $this->is_channel_enabled( $channel ) ) {
				continue;
			}

			if ( ! $this->throttle->should_send( $channel, $event ) ) {
				continue;
			}

			$sent = $channel->send( $event );

			if ( $sent ) {
				$this->throttle->record( $channel, $event );
			}
		}
	}

	/**
	 * Return all registered channels indexed by slug.
	 *
	 * @since  1.0.0
	 * @return array<string, AlertInterface>
	 */
	public function channels(): array {
		return $this->channels;
	}

	/**
	 * Check the per-channel enabled flag stored in wp_options.
	 *
	 * Option key: sendstack_alert_{slug}_enabled
	 *
	 * @since  1.0.0
	 * @param  AlertInterface $channel Channel to check.
	 * @return bool
	 */
	private function is_channel_enabled( AlertInterface $channel ): bool {
		return (bool) get_option( 'sendstack_alert_' . $channel->slug() . '_enabled', false );
	}
}
