<?php
/**
 * Automatic failover to a backup mailer.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * When the primary mailer fails, FailoverHandler re-attempts delivery through
 * the configured backup driver (if failover is enabled in settings).
 *
 * @since 1.0.0
 */
class FailoverHandler {

	/** @var MailerManager */
	private $manager;

	/** @var string Option key for the backup provider slug. */
	private const OPTION_BACKUP = 'sendstack_backup_mailer';

	/** @var string Option key for the enabled flag. */
	private const OPTION_ENABLED = 'sendstack_failover_enabled';

	/**
	 * @param MailerManager $manager Active mailer manager.
	 */
	public function __construct( MailerManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Try the backup mailer after a primary failure.
	 *
	 * Returns the backup SendResult, or the original failure result when
	 * failover is disabled or no backup driver is configured.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Original message.
	 * @param  SendResult  $result  Primary mailer's failure result.
	 * @return SendResult
	 */
	public function handle_failure( MailPayload $payload, SendResult $result ): SendResult {
		if ( ! $this->is_enabled() ) {
			return $result;
		}

		$slug      = (string) get_option( self::OPTION_BACKUP, '' );
		$providers = $this->manager->available_providers();

		if ( ! isset( $providers[ $slug ] ) ) {
			return $result;
		}

		return $providers[ $slug ]->send( $payload );
	}

	/**
	 * Whether automatic failover is active.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function is_enabled(): bool {
		return (bool) get_option( self::OPTION_ENABLED, false );
	}
}
