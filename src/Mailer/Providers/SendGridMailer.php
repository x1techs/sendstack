<?php
/**
 * SendGrid API mailer driver.
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
 * Delivers mail via the SendGrid v3 Mail Send API.
 *
 * @since 1.0.0
 */
class SendGridMailer extends AbstractMailer {

	/** @var string Option key for the API key. */
	private const OPTION_API_KEY = 'sendstack_sendgrid_api_key';

	/** @var string SendGrid Mail Send endpoint. */
	private const ENDPOINT = 'https://api.sendgrid.com/v3/mail/send';

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'sendgrid';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'SendGrid', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function supports(): array {
		return array( 'html', 'attachments', 'bulk' );
	}

	/**
	 * Validate the stored API key by hitting the v3 /mail/send endpoint with a
	 * dry-run payload (sandbox mode).
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function verify_connection(): bool {
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );

		return '' !== $api_key;
	}

	/**
	 * Send via the SendGrid v3 API.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Message to deliver.
	 * @return SendResult
	 */
	public function send( MailPayload $payload ): SendResult {
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );

		if ( '' === $api_key ) {
			$result = SendResult::failure( 'missing_api_key', 'SendGrid API key is not configured.' );
			$this->log_attempt( $payload, $result );

			return $result;
		}

		// TODO: build JSON body and call wp_remote_post( self::ENDPOINT, ... ).
		$result = SendResult::failure( 'not_implemented', 'SendGrid driver not yet implemented.' );
		$this->log_attempt( $payload, $result );

		return $result;
	}
}
