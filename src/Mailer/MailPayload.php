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
 * Every public property is read-only — mutation returns a new instance.
 *
 * @since 1.0.0
 */
final class MailPayload {

	/**
	 * Recipient addresses.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	private $to;

	/**
	 * Email subject line.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $subject;

	/**
	 * Email body (plain text or HTML).
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $body;

	/**
	 * Extra headers not promoted to dedicated properties (e.g. X-Mailer).
	 *
	 * @since 1.0.0
	 * @var array<string,string>
	 */
	private $headers;

	/**
	 * Absolute paths to files to attach.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	private $attachments;

	/**
	 * MIME content type extracted from the Content-Type header.
	 *
	 * Defaults to "text/plain" when no Content-Type header is present.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $content_type;

	/**
	 * Sender email address extracted from the From header.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $from_email;

	/**
	 * Sender display name extracted from the From header.
	 *
	 * @since 1.0.0
	 * @var string
	 */
	private $from_name;

	/**
	 * CC recipient addresses.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	private $cc;

	/**
	 * BCC recipient addresses.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	private $bcc;

	/**
	 * Reply-To addresses.
	 *
	 * @since 1.0.0
	 * @var string[]
	 */
	private $reply_to;

	/**
	 * Private constructor — use from_args().
	 *
	 * @param string[]             $to
	 * @param string               $subject
	 * @param string               $body
	 * @param array<string,string> $headers
	 * @param string[]             $attachments
	 * @param string               $content_type
	 * @param string               $from_email
	 * @param string               $from_name
	 * @param string[]             $cc
	 * @param string[]             $bcc
	 * @param string[]             $reply_to
	 */
	private function __construct(
		array $to,
		string $subject,
		string $body,
		array $headers,
		array $attachments,
		string $content_type,
		string $from_email,
		string $from_name,
		array $cc,
		array $bcc,
		array $reply_to
	) {
		$this->to           = $to;
		$this->subject      = $subject;
		$this->body         = $body;
		$this->headers      = $headers;
		$this->attachments  = $attachments;
		$this->content_type = $content_type;
		$this->from_email   = $from_email;
		$this->from_name    = $from_name;
		$this->cc           = $cc;
		$this->bcc          = $bcc;
		$this->reply_to     = $reply_to;
	}

	// -------------------------------------------------------------------------
	// Named constructor
	// -------------------------------------------------------------------------

	/**
	 * Build a payload from a wp_mail()-style argument map.
	 *
	 * Normalises to/attachments to arrays, promotes well-known header fields
	 * (From, Content-Type, Cc, Bcc, Reply-To) into typed properties, then
	 * exposes the finished payload through the sendstack_mail_payload filter.
	 *
	 * @since  1.0.0
	 * @param  array $args wp_mail() argument map.
	 *                     Keys: to, subject, message, headers, attachments.
	 * @return self
	 */
	public static function from_args( array $args ): self {
		$to          = array_values( array_filter( (array) ( isset( $args['to'] ) ? $args['to'] : array() ) ) );
		$subject     = isset( $args['subject'] ) ? (string) $args['subject'] : '';
		$body        = isset( $args['message'] ) ? (string) $args['message'] : '';
		$attachments = array_values( array_filter( (array) ( isset( $args['attachments'] ) ? $args['attachments'] : array() ) ) );

		$parsed = self::parse_headers( isset( $args['headers'] ) ? $args['headers'] : array() );

		$payload = new self(
			$to,
			$subject,
			$body,
			$parsed['headers'],
			$attachments,
			$parsed['content_type'],
			$parsed['from_email'],
			$parsed['from_name'],
			$parsed['cc'],
			$parsed['bcc'],
			$parsed['reply_to']
		);

		/** @var self $payload */
		$payload = apply_filters( 'sendstack_mail_payload', $payload );

		return $payload;
	}

	// -------------------------------------------------------------------------
	// Getters
	// -------------------------------------------------------------------------

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
	 * Extra headers not extracted into a dedicated property.
	 *
	 * Keyed by header name as-written in the source (e.g. "X-Mailer").
	 *
	 * @since  1.0.0
	 * @return array<string,string>
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
	 * MIME type string, e.g. "text/html" or "text/plain".
	 *
	 * Defaults to "text/plain" when no Content-Type header was present.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function content_type(): string {
		return $this->content_type;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function from_email(): string {
		return $this->from_email;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function from_name(): string {
		return $this->from_name;
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function cc(): array {
		return $this->cc;
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function bcc(): array {
		return $this->bcc;
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function reply_to(): array {
		return $this->reply_to;
	}

	// -------------------------------------------------------------------------
	// Immutable mutation
	// -------------------------------------------------------------------------

	/**
	 * Return a new instance with a single property replaced.
	 *
	 * Supported properties: to, subject, body, headers, attachments.
	 * An unrecognised property name is silently ignored and the original
	 * instance is returned unchanged.
	 *
	 * @since  1.0.0
	 * @param  string $property Property name to change.
	 * @param  mixed  $value    Replacement value.
	 * @return self
	 */
	public function with( string $property, $value ): self {
		$clone = clone $this;

		switch ( $property ) {
			case 'to':
				$clone->to = (array) $value;
				break;
			case 'subject':
				$clone->subject = (string) $value;
				break;
			case 'body':
				$clone->body = (string) $value;
				break;
			case 'headers':
				$clone->headers = (array) $value;
				break;
			case 'attachments':
				$clone->attachments = (array) $value;
				break;
			default:
				return $this;
		}

		return $clone;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Normalise a raw headers value into a structured result array.
	 *
	 * Accepts either a MIME-style string ("Header: value\r\nHeader2: value2")
	 * or an array of individual header strings ("Header: value").
	 * Promotes From, Content-Type, Cc, Bcc, and Reply-To into dedicated keys;
	 * everything else goes into the 'headers' sub-array.
	 *
	 * @since  1.0.0
	 * @param  string|array $raw_headers Raw headers from wp_mail() argument map.
	 * @return array<string,mixed>
	 */
	private static function parse_headers( $raw_headers ): array {
		$result = array(
			'headers'      => array(),
			'content_type' => 'text/plain',
			'from_email'   => '',
			'from_name'    => '',
			'cc'           => array(),
			'bcc'          => array(),
			'reply_to'     => array(),
		);

		if ( is_string( $raw_headers ) ) {
			$raw_headers = explode( "\n", str_replace( "\r\n", "\n", $raw_headers ) );
		}

		foreach ( (array) $raw_headers as $header ) {
			$header = trim( (string) $header );

			if ( '' === $header || false === strpos( $header, ':' ) ) {
				continue;
			}

			$parts = explode( ':', $header, 2 );
			$name  = trim( $parts[0] );
			$value = isset( $parts[1] ) ? trim( $parts[1] ) : '';

			switch ( strtolower( $name ) ) {
				case 'content-type':
					$mime_parts             = explode( ';', $value );
					$result['content_type'] = trim( $mime_parts[0] );
					break;

				case 'from':
					self::extract_from_header( $value, $result );
					break;

				case 'cc':
					$result['cc'][] = $value;
					break;

				case 'bcc':
					$result['bcc'][] = $value;
					break;

				case 'reply-to':
					$result['reply_to'][] = $value;
					break;

				default:
					/** @var array<string,string> $result['headers'] */
					$result['headers'][ $name ] = $value;
					break;
			}
		}

		return $result;
	}

	/**
	 * Parse a From header value into from_name and from_email entries.
	 *
	 * Handles three formats:
	 *   "Display Name <email@example.com>"
	 *   "<email@example.com>"
	 *   "email@example.com"
	 *
	 * @since  1.0.0
	 * @param  string              $value  Trimmed header value after "From:".
	 * @param  array<string,mixed> $result Parse result array (passed by reference).
	 * @return void
	 */
	private static function extract_from_header( string $value, array &$result ): void {
		if ( preg_match( '/^"?([^"<]*)"?\s*<([^>]+)>\s*$/', $value, $matches ) ) {
			$result['from_name']  = trim( $matches[1] );
			$result['from_email'] = trim( $matches[2] );
		} else {
			$result['from_email'] = $value;
		}
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * WHY IMMUTABLE?
 * MailPayload travels through several layers (PhpMailerOverride → MailerManager
 * → AbstractMailer → provider). Making it immutable means a hook running at
 * any layer cannot silently corrupt data seen by another layer. A filter that
 * needs to change the recipient list must call with('to', $new) and return
 * the new instance — the intent is explicit and auditable.
 *
 * WHY from_args() AND NOT new MailPayload()?
 * WordPress's wp_mail() uses an untyped array as its argument format. A named
 * constructor enforces a single, documented parsing step. All raw-array
 * normalisation (string→array, header extraction) happens here so every other
 * part of the system works with typed properties.
 *
 * HEADER PARSING
 * wp_mail() accepts headers as either a "\r\n"-separated string or a flat
 * array of "Header: value" strings. Promoting the five well-known headers
 * (Content-Type, From, Cc, Bcc, Reply-To) into dedicated typed properties
 * means each provider reads structured data rather than re-parsing strings.
 * Unknown headers are preserved in $headers for providers that forward them.
 *
 * THE sendstack_mail_payload FILTER
 * Applied at the end of from_args() before returning. Third-party code that
 * wants to mutate outgoing mail (change sender, add BCC, force HTML) has a
 * single interception point. Because MailPayload is final, filter callbacks
 * must return a MailPayload (via with()) — they cannot swap in a subclass with
 * different semantics.
 *
 * with() AND THE switch STATEMENT
 * Dynamic property access ($clone->$property) would confuse static analysis.
 * An explicit switch makes each branch type-safe and lets PHPStan verify that
 * the cast matches the declared property type.
 *
 * SECURITY
 * MailPayload does NOT sanitize. Sanitization is the caller's responsibility
 * (via wp_mail() norms and provider-level escaping). This keeps the class
 * focused on representation — the boundary where input validation belongs is
 * the REST controller or form handler, not the value object.
 */
