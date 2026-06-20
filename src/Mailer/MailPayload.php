<?php
/**
 * Immutable email message value object.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Carries all data for a single outbound email.
 *
 * Create via MailPayload::from_args(); produce modified copies via with().
 *
 * @since 1.0.0
 */
final class MailPayload {

	/** @var string[] */
	private $to;

	/** @var string */
	private $subject;

	/** @var string */
	private $body;

	/** @var string[] */
	private $headers;

	/** @var string[] */
	private $attachments;

	private function __construct(
		array $to,
		string $subject,
		string $body,
		array $headers,
		array $attachments
	) {
		$this->to          = $to;
		$this->subject     = $subject;
		$this->body        = $body;
		$this->headers     = $headers;
		$this->attachments = $attachments;
	}

	/**
	 * Construct a payload from a wp_mail()-style argument map.
	 *
	 * Accepted keys: to, subject, message (body), headers, attachments.
	 *
	 * @since  1.0.0
	 * @param  array $args Argument map, keyed as wp_mail() expects.
	 * @return self
	 */
	public static function from_args( array $args ): self {
		$to          = isset( $args['to'] ) ? (array) $args['to'] : array();
		$subject     = isset( $args['subject'] ) ? (string) $args['subject'] : '';
		$body        = isset( $args['message'] ) ? (string) $args['message'] : '';
		$headers     = isset( $args['headers'] ) ? (array) $args['headers'] : array();
		$attachments = isset( $args['attachments'] ) ? (array) $args['attachments'] : array();

		return new self( $to, $subject, $body, $headers, $attachments );
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function to(): array {
		return $this->to;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function subject(): string {
		return $this->subject;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function body(): string {
		return $this->body;
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function headers(): array {
		return $this->headers;
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function attachments(): array {
		return $this->attachments;
	}

	/**
	 * Return a new instance with one or more properties replaced.
	 *
	 * Accepted keys: to, subject, body, headers, attachments.
	 *
	 * @since  1.0.0
	 * @param  array $overrides Map of property names to new values.
	 * @return self
	 */
	public function with( array $overrides ): self {
		$clone = clone $this;

		foreach ( array( 'to', 'subject', 'body', 'headers', 'attachments' ) as $prop ) {
			if ( array_key_exists( $prop, $overrides ) ) {
				$clone->$prop = $overrides[ $prop ];
			}
		}

		return $clone;
	}
}
