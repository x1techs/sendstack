<?php
/**
 * Generic SMTP mailer driver.
 *
 * @package SendStack\Mailer\Providers
 * @since   1.0.0
 */

namespace SendStack\Mailer\Providers;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\AbstractMailer;
use SendStack\Mailer\MailPayload;
use SendStack\Mailer\SendResult;

/**
 * Sends mail via a user-configured SMTP server using WordPress's PHPMailer.
 *
 * @since 1.0.0
 */
class SmtpMailer extends AbstractMailer {

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'smtp';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'SMTP', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function supports(): array {
		return array( 'html', 'attachments' );
	}

	/**
	 * Verify that a connection to the configured SMTP host can be established.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function verify_connection(): bool {
		// TODO: open a test socket to the configured host:port.
		return false;
	}

	/**
	 * Send via SMTP.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Message to deliver.
	 * @return SendResult
	 */
	public function send( MailPayload $payload ): SendResult {
		// TODO: configure PHPMailer via phpmailer_init and call wp_mail().
		$result = SendResult::failure( 'not_implemented', 'SMTP driver not yet implemented.' );
		$this->log_attempt( $payload, $result );

		return $result;
	}
}
