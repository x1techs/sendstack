<?php
/**
 * Contract every mailer driver must satisfy.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Mailer driver interface.
 *
 * @since 1.0.0
 */
interface MailerInterface {

	/**
	 * Machine-readable identifier for this driver (e.g. "smtp", "sendgrid").
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string;

	/**
	 * Human-readable provider name shown in the settings UI.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string;

	/**
	 * Attempt to deliver a message and return the outcome.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload The message to send.
	 * @return SendResult
	 */
	public function send( MailPayload $payload ): SendResult;

	/**
	 * Ping the provider to confirm credentials are valid.
	 *
	 * @since  1.0.0
	 * @return bool True when the connection succeeds.
	 */
	public function verify_connection(): bool;

	/**
	 * Return a list of feature keys this driver supports.
	 *
	 * Examples: 'html', 'attachments', 'bulk', 'oauth'.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public function supports(): array;
}
