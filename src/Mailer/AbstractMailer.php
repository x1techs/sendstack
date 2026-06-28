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
 * Implements the send pipeline (hooks, logging trigger) via the Template Method
 * pattern. Concrete drivers extend this class and implement slug(), label(), and
 * do_send(). Everything else is handled here.
 *
 * @since 1.0.0
 */
abstract class AbstractMailer implements MailerInterface {

	/**
	 * Provider-specific settings loaded from the connection config.
	 *
	 * Populated by the constructor. Concrete drivers read via get_option().
	 *
	 * @since 1.0.0
	 * @var array<string,mixed>
	 */
	protected $options = array();

	/**
	 * Store provider options for later retrieval via get_option().
	 *
	 * @since 1.0.0
	 * @param array<string,mixed> $options Connection-specific settings.
	 */
	public function __construct( array $options = array() ) {
		$this->options = $options;
	}

	// -------------------------------------------------------------------------
	// Template Method — the send pipeline
	// -------------------------------------------------------------------------

	/**
	 * Deliver a message through the full send pipeline.
	 *
	 * Fires hooks before and after the underlying transport call, then
	 * triggers the logging action. Concrete drivers implement do_send().
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload The message to send.
	 * @return SendResult
	 */
	public function send( MailPayload $payload ): SendResult {
		do_action( 'sendstack_before_send', $payload, $this );

		$result = $this->do_send( $payload );

		$this->log_attempt( $payload, $result );

		do_action( 'sendstack_after_send', $payload, $result, $this );

		return $result;
	}

	/**
	 * Perform the actual delivery via the provider's transport mechanism.
	 *
	 * Implementations must not fire hooks — the base send() method handles
	 * that. Only the raw send attempt belongs here.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload The message to send.
	 * @return SendResult
	 */
	abstract protected function do_send( MailPayload $payload ): SendResult;

	// -------------------------------------------------------------------------
	// Default interface implementations (providers override as needed)
	// -------------------------------------------------------------------------

	/**
	 * Ping the provider to verify credentials are valid.
	 *
	 * Returns a failure result by default; concrete drivers that support
	 * connection verification should override this.
	 *
	 * @since  1.0.0
	 * @return SendResult
	 */
	public function verify_connection(): SendResult {
		return SendResult::failure(
			__( 'Connection verification is not implemented for this provider.', 'sendstack' )
		);
	}

	/**
	 * Return the set of features this driver supports.
	 *
	 * Defaults to plain-text and HTML bodies. Providers that support
	 * additional capabilities (attachments, custom headers, OAuth) should
	 * override and extend this list.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public function supports(): array {
		return array( 'html', 'plain' );
	}

	// -------------------------------------------------------------------------
	// Protected helpers available to all drivers
	// -------------------------------------------------------------------------

	/**
	 * Fire the logging action so the Logger service can write a log entry.
	 *
	 * The Logger (Feature 5) hooks into sendstack_email_sent to persist the
	 * attempt. This method only fires the action; no direct DB writes happen
	 * here, keeping the mailer layer decoupled from storage.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload The message that was attempted.
	 * @param  SendResult  $result  Outcome of the attempt.
	 * @return void
	 */
	protected function log_attempt( MailPayload $payload, SendResult $result ): void {
		do_action( 'sendstack_email_sent', $payload, $result, $this );
	}

	/**
	 * Read a value from the provider's option bag.
	 *
	 * @since  1.0.0
	 * @param  string $key     Option key.
	 * @param  mixed  $default Value to return when the key is absent.
	 * @return mixed
	 */
	protected function get_option( string $key, $default = null ) {
		return isset( $this->options[ $key ] ) ? $this->options[ $key ] : $default;
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * TEMPLATE METHOD PATTERN
 * send() is the stable algorithm: fire before-hook → transport → log → fire
 * after-hook. do_send() is the step that varies per provider. By making
 * do_send() abstract and keeping send() concrete in the base class, we
 * guarantee hooks and logging always run — even if a provider developer forgets.
 * Without this pattern every driver would have to duplicate hook calls, and one
 * missed call would produce a silent logging gap.
 *
 * WHY log_attempt() FIRES A HOOK INSTEAD OF WRITING DIRECTLY
 * The mailer layer must not depend on the Logger layer — that would create a
 * circular dependency if Logger ever needs to send email (e.g. alert emails).
 * The action 'sendstack_email_sent' is a stable event bus: whoever cares
 * (LogWriter in Feature 5, StatsAggregator in Feature 6) registers a listener.
 * No listener = no write. The mailer doesn't know or care.
 *
 * HOOK ORDER AND NAMES
 * sendstack_before_send  — before the transport call (good for rate-limit checks)
 * sendstack_email_sent   — fired inside log_attempt(), after do_send()
 * sendstack_after_send   — fired after log_attempt(), includes the result
 * The log hook fires before sendstack_after_send so that listeners on
 * sendstack_after_send can read a log entry that already exists.
 *
 * get_option() AND $this->options
 * Options are injected via the constructor rather than read inside do_send().
 * This makes providers testable (pass a fixture options array in a unit test)
 * and lazy (options are already in memory, no extra get_option() calls per
 * email). The MailerServiceProvider populates options from the DB once on boot.
 *
 * verify_connection() DEFAULT
 * Not all providers support a lightweight connection ping. The default returns
 * a failure result with a friendly message rather than throwing, so the admin
 * "verify" button works without crashing for providers that haven't implemented
 * it yet.
 */
