<?php
/**
 * Mailer registry and dispatch hub.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains the list of registered drivers, resolves the active one, and
 * dispatches outbound mail through it.
 *
 * @since 1.0.0
 */
class MailerManager {

	/** @var array<string, MailerInterface> Keyed by slug. */
	private $mailers = array();

	/** @var string Option key storing the active provider slug. */
	private const OPTION_KEY = 'sendstack_active_mailer';

	/**
	 * Register a mailer driver.
	 *
	 * @since  1.0.0
	 * @param  MailerInterface $mailer Driver instance to register.
	 * @return void
	 */
	public function register( MailerInterface $mailer ): void {
		$this->mailers[ $mailer->slug() ] = $mailer;
	}

	/**
	 * Send a message through the currently active mailer.
	 *
	 * Falls through to FailoverHandler when the primary send fails.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Message to deliver.
	 * @return SendResult
	 */
	public function handle_send( MailPayload $payload ): SendResult {
		$mailer = $this->resolve_mailer();

		return $mailer->send( $payload );
	}

	/**
	 * Return the active mailer driver.
	 *
	 * @since  1.0.0
	 * @throws \RuntimeException When no drivers are registered or the saved slug is unknown.
	 * @return MailerInterface
	 */
	public function resolve_mailer(): MailerInterface {
		$slug = (string) get_option( self::OPTION_KEY, '' );

		if ( isset( $this->mailers[ $slug ] ) ) {
			return $this->mailers[ $slug ];
		}

		// Fall back to the first registered driver.
		if ( ! empty( $this->mailers ) ) {
			return reset( $this->mailers );
		}

		throw new \RuntimeException( 'SendStack: no mailer drivers are registered.' );
	}

	/**
	 * Return all registered drivers indexed by slug.
	 *
	 * @since  1.0.0
	 * @return array<string, MailerInterface>
	 */
	public function available_providers(): array {
		return $this->mailers;
	}
}
