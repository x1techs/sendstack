<?php
/**
 * Short-circuits WordPress's default PHPMailer delivery.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into the pre_wp_mail filter to intercept outbound messages before
 * PHPMailer touches them, routing them through MailerManager instead.
 *
 * @since 1.0.0
 */
class PhpMailerOverride {

	/** @var MailerManager */
	private $manager;

	/**
	 * @param MailerManager $manager Active mailer manager.
	 */
	public function __construct( MailerManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register the pre_wp_mail intercept filter.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function apply(): void {
		add_filter( 'pre_wp_mail', array( $this, 'intercept' ), 10, 2 );
	}

	/**
	 * Remove the intercept filter, restoring default PHPMailer behaviour.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function reset(): void {
		remove_filter( 'pre_wp_mail', array( $this, 'intercept' ), 10 );
	}

	/**
	 * Intercept a wp_mail() call and dispatch via MailerManager.
	 *
	 * Returns true to signal WordPress that delivery is already handled,
	 * or false to allow the failure to propagate.
	 *
	 * @since  1.0.0
	 * @param  null|bool $short_circuit Existing filter value (null by default).
	 * @param  array     $atts          wp_mail() argument map.
	 * @return bool
	 */
	public function intercept( $short_circuit, array $atts ): bool {
		$payload = MailPayload::from_args( $atts );
		$result  = $this->manager->handle_send( $payload );

		return $result->is_success();
	}
}
