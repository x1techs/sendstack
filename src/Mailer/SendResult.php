<?php
/**
 * Send attempt outcome value object.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object returned by every mailer's send() call.
 *
 * Use the named constructors:
 *   SendResult::success($message_id, $provider_response)
 *   SendResult::failure($error_message, $error_code, $provider_response)
 *
 * @since 1.0.0
 */
final class SendResult {

	/**
	 * Whether the send attempt succeeded.
	 *
	 * @since 1.0.0
	 * @var bool
	 */
	private $success;

	/**
	 * Provider-assigned message ID on success; empty string on failure.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $message_id;

	/**
	 * Human-readable failure description; empty string on success.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $error_message;

	/**
	 * Short machine-readable error key; empty string on success.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $error_code;

	/**
	 * Raw structured response from the provider (HTTP body decoded, SMTP array, etc.).
	 *
	 * @since 1.0.0
	 * @var array<mixed>
	 */
	private $provider_response;

	/**
	 * Private constructor — use the named constructors.
	 *
	 * @param bool         $success
	 * @param string       $message_id
	 * @param string       $error_message
	 * @param string       $error_code
	 * @param array<mixed> $provider_response
	 */
	private function __construct(
		bool $success,
		string $message_id,
		string $error_message,
		string $error_code,
		array $provider_response
	) {
		$this->success           = $success;
		$this->message_id        = $message_id;
		$this->error_message     = $error_message;
		$this->error_code        = $error_code;
		$this->provider_response = $provider_response;
	}

	// -------------------------------------------------------------------------
	// Named constructors
	// -------------------------------------------------------------------------

	/**
	 * Create a successful result.
	 *
	 * @since  1.0.0
	 * @param  string       $message_id        Provider-assigned message identifier.
	 * @param  array<mixed> $provider_response Optional raw provider response.
	 * @return self
	 */
	public static function success( string $message_id = '', array $provider_response = array() ): self {
		return new self( true, $message_id, '', '', $provider_response );
	}

	/**
	 * Create a failure result.
	 *
	 * @since  1.0.0
	 * @param  string       $error_message     Human-readable error description.
	 * @param  string       $error_code        Short machine-readable error key.
	 * @param  array<mixed> $provider_response Optional raw provider response.
	 * @return self
	 */
	public static function failure(
		string $error_message,
		string $error_code = '',
		array $provider_response = array()
	): self {
		return new self( false, '', $error_message, $error_code, $provider_response );
	}

	// -------------------------------------------------------------------------
	// Getters
	// -------------------------------------------------------------------------

	/**
	 * @since  1.0.0
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
	}

	/**
	 * Provider-assigned message identifier; empty string when not available.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function message_id(): string {
		return $this->message_id;
	}

	/**
	 * Human-readable error description; empty string on success.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function error_message(): string {
		return $this->error_message;
	}

	/**
	 * Short machine-readable error key; empty string on success.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function error_code(): string {
		return $this->error_code;
	}

	/**
	 * Raw structured response from the provider's transport layer.
	 *
	 * @since  1.0.0
	 * @return array<mixed>
	 */
	public function provider_response(): array {
		return $this->provider_response;
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * WHY NAMED CONSTRUCTORS (success/failure) INSTEAD OF new SendResult()?
 * Two distinct states (success, failure) need two distinct sets of meaningful
 * fields. Named constructors make the call-site intent obvious:
 *   SendResult::success($id)  vs  SendResult::failure($msg, $code)
 * A generic constructor would require callers to pass a bool flag + partially
 * meaningful parameters, which is error-prone and harder to read.
 *
 * WHY IS provider_response AN ARRAY, NOT A STRING?
 * Different providers surface response data differently:
 *   - HTTP API providers return decoded JSON (array)
 *   - SMTP returns an array of [code, message] pairs
 * Storing the decoded structure lets the Logger and Alerts systems access
 * individual fields without re-parsing. Providers that only have a raw string
 * can wrap it: ['body' => $raw_string].
 *
 * WHY IS THIS CLASS final?
 * SendResult is a pure data carrier with no extension points. Marking it final
 * prevents subclasses from introducing mutation or alternate interpretations of
 * is_success(), which would break the simple boolean contract relied on by
 * PhpMailerOverride and FailoverHandler.
 *
 * message_id FIELD
 * Providers like SendGrid and Mailgun return a unique message ID per accepted
 * send. Storing it here lets the Logger write it to the log table, enabling
 * delivery-event webhooks from the provider to be correlated back to a log row.
 */
