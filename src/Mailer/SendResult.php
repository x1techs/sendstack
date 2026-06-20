<?php
/**
 * Send attempt outcome.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Immutable value object returned by every mailer's send() call.
 *
 * Use the named constructors: SendResult::success() or SendResult::failure().
 *
 * @since 1.0.0
 */
final class SendResult {

	/** @var bool */
	private $success;

	/** @var string */
	private $error_code;

	/** @var string */
	private $error_message;

	/** @var string Raw body / status from the provider's API response. */
	private $provider_response;

	private function __construct(
		bool $success,
		string $error_code,
		string $error_message,
		string $provider_response
	) {
		$this->success           = $success;
		$this->error_code        = $error_code;
		$this->error_message     = $error_message;
		$this->provider_response = $provider_response;
	}

	/**
	 * Create a successful result.
	 *
	 * @since  1.0.0
	 * @param  string $provider_response Optional raw provider response body.
	 * @return self
	 */
	public static function success( string $provider_response = '' ): self {
		return new self( true, '', '', $provider_response );
	}

	/**
	 * Create a failure result.
	 *
	 * @since  1.0.0
	 * @param  string $error_code        Short machine-readable error code.
	 * @param  string $error_message     Human-readable description.
	 * @param  string $provider_response Optional raw provider response body.
	 * @return self
	 */
	public static function failure(
		string $error_code,
		string $error_message,
		string $provider_response = ''
	): self {
		return new self( false, $error_code, $error_message, $provider_response );
	}

	/**
	 * @since  1.0.0
	 * @return bool
	 */
	public function is_success(): bool {
		return $this->success;
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
	 * Human-readable error description; empty string on success.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function error_message(): string {
		return $this->error_message;
	}

	/**
	 * Raw body returned by the provider's transport layer.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function provider_response(): string {
		return $this->provider_response;
	}
}
