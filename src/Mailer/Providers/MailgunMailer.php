<?php
/**
 * Mailgun API mailer driver.
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
 * Delivers mail via the Mailgun Messages API.
 *
 * @since 1.0.0
 */
class MailgunMailer extends AbstractMailer {

	/** @var string Option key for the API key. */
	private const OPTION_API_KEY = 'sendstack_mailgun_api_key';

	/** @var string Option key for the sending domain. */
	private const OPTION_DOMAIN = 'sendstack_mailgun_domain';

	/** @var string Option key for the API region ('us' or 'eu'). */
	private const OPTION_REGION = 'sendstack_mailgun_region';

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'mailgun';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Mailgun', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function supports(): array {
		return array( 'html', 'attachments', 'bulk' );
	}

	/**
	 * Confirm API key and domain are present.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function verify_connection(): bool {
		$api_key = (string) get_option( self::OPTION_API_KEY, '' );
		$domain  = (string) get_option( self::OPTION_DOMAIN, '' );

		return '' !== $api_key && '' !== $domain;
	}

	/**
	 * Send via the Mailgun Messages API.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Message to deliver.
	 * @return SendResult
	 */
	public function send( MailPayload $payload ): SendResult {
		if ( ! $this->verify_connection() ) {
			$result = SendResult::failure( 'missing_credentials', 'Mailgun API key or domain is not configured.' );
			$this->log_attempt( $payload, $result );

			return $result;
		}

		// TODO: build form body and call wp_remote_post to the region-specific endpoint.
		$result = SendResult::failure( 'not_implemented', 'Mailgun driver not yet implemented.' );
		$this->log_attempt( $payload, $result );

		return $result;
	}

	/**
	 * Return the API base URL for the configured region.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	private function api_base(): string {
		$region = (string) get_option( self::OPTION_REGION, 'us' );

		return 'eu' === $region
			? 'https://api.eu.mailgun.net/v3'
			: 'https://api.mailgun.net/v3';
	}
}
