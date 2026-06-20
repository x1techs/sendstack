<?php
/**
 * Gmail OAuth 2.0 mailer driver.
 *
 * @package SendStack\Mailer\Providers
 * @since   1.0.0
 */

namespace SendStack\Mailer\Providers;

defined( 'ABSPATH' ) || exit;

use SendStack\Auth\GoogleOAuthClient;
use SendStack\Auth\CredentialStore;
use SendStack\Mailer\AbstractMailer;
use SendStack\Mailer\MailPayload;
use SendStack\Mailer\SendResult;

/**
 * Sends mail through the Gmail API using an OAuth 2.0 access token.
 *
 * @since 1.0.0
 */
class GmailMailer extends AbstractMailer {

	/** @var GoogleOAuthClient */
	private $oauth_client;

	/** @var CredentialStore */
	private $credentials;

	/**
	 * @param GoogleOAuthClient $oauth_client OAuth client for token management.
	 * @param CredentialStore   $credentials  Encrypted credential storage.
	 */
	public function __construct( GoogleOAuthClient $oauth_client, CredentialStore $credentials ) {
		$this->oauth_client = $oauth_client;
		$this->credentials  = $credentials;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'gmail';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Gmail (OAuth 2.0)', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function supports(): array {
		return array( 'html', 'attachments', 'oauth' );
	}

	/**
	 * Confirm a valid, non-expired access token is stored.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public function verify_connection(): bool {
		$token = $this->credentials->get( 'gmail_access_token' );

		return ! empty( $token );
	}

	/**
	 * Send via the Gmail API.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Message to deliver.
	 * @return SendResult
	 */
	public function send( MailPayload $payload ): SendResult {
		// TODO: build RFC 2822 message, base64url-encode it, POST to Gmail API.
		$result = SendResult::failure( 'not_implemented', 'Gmail driver not yet implemented.' );
		$this->log_attempt( $payload, $result );

		return $result;
	}
}
