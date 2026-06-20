<?php
/**
 * Abstract base for mailer drivers.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Provides shared logging behaviour; concrete drivers implement send() and the
 * remaining MailerInterface accessors (slug, label, verify_connection, supports).
 *
 * @since 1.0.0
 */
abstract class AbstractMailer implements MailerInterface {

	/**
	 * Attempt delivery. Must call log_attempt() before returning.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload The message to send.
	 * @return SendResult
	 */
	abstract public function send( MailPayload $payload ): SendResult;

	/**
	 * Persist a send attempt to the log table.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload The message that was sent (or attempted).
	 * @param  SendResult  $result  Outcome of the attempt.
	 * @return void
	 */
	protected function log_attempt( MailPayload $payload, SendResult $result ): void {
		// TODO: insert row into sendstack_log via the Log service.
	}
}
