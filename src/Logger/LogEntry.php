<?php
/**
 * Log-row data-transfer object.
 *
 * @package SendStack\Logger
 * @since   1.0.0
 */

namespace SendStack\Logger;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\MailPayload;

/**
 * Immutable DTO wrapping a single row from the sendstack_logs table.
 *
 * Instantiate via the named constructors from_row() or from_payload().
 * All mutation returns a new instance (clone-based immutability).
 *
 * @since 1.0.0
 */
final class LogEntry {

	// -------------------------------------------------------------------------
	// Private properties (all DB-mapped unless noted)
	// -------------------------------------------------------------------------

	/** @var int Row primary key; 0 for unsaved entries. */
	private $id;

	/** @var string Provider-assigned message identifier; empty until delivered. */
	private $message_id;

	/** @var string One of LogStatus::* constants. */
	private $status;

	/** @var string Provider slug, e.g. 'smtp', 'sendgrid'. */
	private $provider;

	/** @var string Sender email address. */
	private $from_email;

	/** @var string[] Recipient addresses. */
	private $to;

	/** @var string Email subject line. */
	private $subject;

	/** @var string Email body (plain text or HTML). */
	private $body;

	/** @var string MIME content type, e.g. 'text/html'. Not a dedicated DB column — derived from headers. */
	private $content_type;

	/** @var array<string,string> Extra headers keyed by name. */
	private $headers;

	/** @var string Short machine-readable error key; empty on success. */
	private $error_code;

	/** @var string Human-readable error description; empty on success. */
	private $error_message;

	/** @var int Number of send attempts made. */
	private $attempt_count;

	/** @var string MySQL datetime of row creation. */
	private $created_at;

	/** @var string MySQL datetime of last update. */
	private $updated_at;

	// -------------------------------------------------------------------------
	// Private constructor — use named constructors
	// -------------------------------------------------------------------------

	/** @codeCoverageIgnore */
	private function __construct() {}

	// -------------------------------------------------------------------------
	// Named constructors
	// -------------------------------------------------------------------------

	/**
	 * Hydrate an entry from a $wpdb row object.
	 *
	 * JSON-decodes the to_json and headers_json columns gracefully, falling back
	 * to empty arrays when the stored value is malformed or absent.
	 *
	 * @since  1.0.0
	 * @param  object $row stdClass returned by $wpdb->get_row().
	 * @return self
	 */
	public static function from_row( object $row ): self {
		$data = (array) $row;

		$entry = new self();

		$to_decoded = json_decode( isset( $data['to_json'] ) ? (string) $data['to_json'] : '[]', true );
		$entry->to  = is_array( $to_decoded ) ? $to_decoded : array();

		$headers_decoded = json_decode( isset( $data['headers_json'] ) ? (string) $data['headers_json'] : '{}', true );
		$entry->headers  = is_array( $headers_decoded ) ? $headers_decoded : array();

		$entry->id            = isset( $data['id'] ) ? (int) $data['id'] : 0;
		$entry->message_id    = isset( $data['message_id'] ) ? (string) $data['message_id'] : '';
		$entry->status        = isset( $data['status'] ) ? (string) $data['status'] : LogStatus::QUEUED;
		$entry->provider      = isset( $data['provider'] ) ? (string) $data['provider'] : '';
		$entry->from_email    = isset( $data['from_email'] ) ? (string) $data['from_email'] : '';
		$entry->subject       = isset( $data['subject'] ) ? (string) $data['subject'] : '';
		$entry->body          = isset( $data['body_mime'] ) ? (string) $data['body_mime'] : '';
		$entry->error_code    = isset( $data['error_code'] ) ? (string) $data['error_code'] : '';
		$entry->error_message = isset( $data['error_message'] ) ? (string) $data['error_message'] : '';
		$entry->attempt_count = isset( $data['attempt_count'] ) ? (int) $data['attempt_count'] : 1;
		$entry->created_at    = isset( $data['created_at'] ) ? (string) $data['created_at'] : '';
		$entry->updated_at    = isset( $data['updated_at'] ) ? (string) $data['updated_at'] : '';

		// Derive content_type from stored headers; fall back to text/plain.
		$entry->content_type = isset( $entry->headers['Content-Type'] )
			? (string) $entry->headers['Content-Type']
			: 'text/plain';

		return $entry;
	}

	/**
	 * Build a new (unsaved) entry from a MailPayload before it is sent.
	 *
	 * Adds Content-Type to the headers array so it survives the DB round-trip.
	 * Defaults status to QUEUED; override via the second argument for retries.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload  Message about to be delivered.
	 * @param  string      $provider Provider slug (e.g. 'smtp').
	 * @param  string      $status   Initial status; defaults to LogStatus::QUEUED.
	 * @return self
	 */
	public static function from_payload(
		MailPayload $payload,
		string $provider,
		string $status = LogStatus::QUEUED
	): self {
		$entry = new self();

		$headers = $payload->headers();
		// Preserve content_type in headers_json so it survives the DB round-trip.
		$headers['Content-Type'] = $payload->content_type();

		$entry->id            = 0;
		$entry->message_id    = '';
		$entry->status        = $status;
		$entry->provider      = $provider;
		$entry->from_email    = $payload->from_email();
		$entry->to            = $payload->to();
		$entry->subject       = $payload->subject();
		$entry->body          = $payload->body();
		$entry->content_type  = $payload->content_type();
		$entry->headers       = $headers;
		$entry->error_code    = '';
		$entry->error_message = '';
		$entry->attempt_count = 1;
		$entry->created_at    = current_time( 'mysql' );
		$entry->updated_at    = current_time( 'mysql' );

		return $entry;
	}

	// -------------------------------------------------------------------------
	// Serialisation
	// -------------------------------------------------------------------------

	/**
	 * Return all DB-writable properties as a column-value map.
	 *
	 * Suitable for passing directly to $wpdb->insert(). Does not include `id`
	 * (auto-increment). JSON-encodes `to` and `headers`; maps `body` to `body_mime`.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'message_id'   => $this->message_id,
			'status'       => $this->status,
			'provider'     => $this->provider,
			'from_email'   => $this->from_email,
			'to_json'      => wp_json_encode( $this->to ) ?: '[]',
			'subject'      => $this->subject,
			'body_mime'    => $this->body,
			'headers_json' => wp_json_encode( $this->headers ) ?: '{}',
			'error_code'   => $this->error_code,
			'error_message' => $this->error_message,
			'attempt_count' => $this->attempt_count,
			'created_at'   => $this->created_at,
			'updated_at'   => $this->updated_at,
		);
	}

	// -------------------------------------------------------------------------
	// Redaction
	// -------------------------------------------------------------------------

	/**
	 * Return a clone with specified headers stripped and, optionally, body redacted.
	 *
	 * Body is redacted when the `sendstack_logs_config` option has `store_body`
	 * set to a falsy value. Intended for both pre-insert redaction in LogWriter
	 * and safe display in the admin UI.
	 *
	 * @since  1.0.0
	 * @param  string[] $redacted_headers Header names to remove from the headers array.
	 * @return self
	 */
	public function redacted( array $redacted_headers = array() ): self {
		$clone = clone $this;

		foreach ( $redacted_headers as $header_name ) {
			unset( $clone->headers[ $header_name ] );
		}

		$config = (array) get_option( 'sendstack_logs_config', array() );
		if ( isset( $config['store_body'] ) && ! (bool) $config['store_body'] ) {
			$clone->body = '[redacted]';
		}

		return $clone;
	}

	// -------------------------------------------------------------------------
	// Immutable mutation helpers
	// -------------------------------------------------------------------------

	/**
	 * Clone with an updated status.
	 *
	 * @since  1.0.0
	 * @param  string $status One of LogStatus::*.
	 * @return self
	 */
	public function with_status( string $status ): self {
		$clone         = clone $this;
		$clone->status = $status;
		return $clone;
	}

	/**
	 * Clone with error fields set.
	 *
	 * @since  1.0.0
	 * @param  string $code    Short machine-readable error key.
	 * @param  string $message Human-readable error description.
	 * @return self
	 */
	public function with_error( string $code, string $message ): self {
		$clone                = clone $this;
		$clone->error_code    = $code;
		$clone->error_message = $message;
		return $clone;
	}

	/**
	 * Clone with the provider-assigned message ID set.
	 *
	 * @since  1.0.0
	 * @param  string $message_id Provider-assigned identifier.
	 * @return self
	 */
	public function with_message_id( string $message_id ): self {
		$clone             = clone $this;
		$clone->message_id = $message_id;
		return $clone;
	}

	/**
	 * Clone with an updated attempt count.
	 *
	 * @since  1.0.0
	 * @param  int $count New attempt count.
	 * @return self
	 */
	public function with_attempt_count( int $count ): self {
		$clone                = clone $this;
		$clone->attempt_count = $count;
		return $clone;
	}

	// -------------------------------------------------------------------------
	// Getters
	// -------------------------------------------------------------------------

	/** @since 1.0.0 */
	public function id(): int {
		return $this->id;
	}

	/** @since 1.0.0 */
	public function message_id(): string {
		return $this->message_id;
	}

	/** @since 1.0.0 */
	public function status(): string {
		return $this->status;
	}

	/** @since 1.0.0 */
	public function provider(): string {
		return $this->provider;
	}

	/** @since 1.0.0 */
	public function from_email(): string {
		return $this->from_email;
	}

	/**
	 * @since  1.0.0
	 * @return string[]
	 */
	public function to(): array {
		return $this->to;
	}

	/** @since 1.0.0 */
	public function subject(): string {
		return $this->subject;
	}

	/** @since 1.0.0 */
	public function body(): string {
		return $this->body;
	}

	/** @since 1.0.0 */
	public function content_type(): string {
		return $this->content_type;
	}

	/**
	 * @since  1.0.0
	 * @return array<string,string>
	 */
	public function headers(): array {
		return $this->headers;
	}

	/** @since 1.0.0 */
	public function error_code(): string {
		return $this->error_code;
	}

	/** @since 1.0.0 */
	public function error_message(): string {
		return $this->error_message;
	}

	/** @since 1.0.0 */
	public function attempt_count(): int {
		return $this->attempt_count;
	}

	/** @since 1.0.0 */
	public function created_at(): string {
		return $this->created_at;
	}

	/** @since 1.0.0 */
	public function updated_at(): string {
		return $this->updated_at;
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * PRIVATE CONSTRUCTOR + NAMED CONSTRUCTORS
 * LogEntry can never be `new LogEntry()` from outside. This enforces that every
 * entry is created through one of two well-defined paths:
 *   from_row()    — reading from the database (fields are already typed/decoded)
 *   from_payload() — capturing an in-flight message before it is sent
 * Each named constructor has a clear contract about what defaults it sets, which
 * prevents "half-built" objects with zero-value or null fields from leaking into
 * the system.
 *
 * WHY (array) $row INSTEAD OF $row->property ACCESS
 * PHPStan types object $row as the generic "object" type and cannot know which
 * properties exist. Casting to array first makes every access array-safe and
 * avoids PHPStan level-5 "Cannot access property on object" errors — without
 * resorting to suppression annotations.
 *
 * content_type IS STORED INSIDE headers_json, NOT A DEDICATED COLUMN
 * The DB schema has no content_type column. When from_payload() creates an
 * entry, it adds 'Content-Type' to the headers array before serialisation.
 * from_row() reads it back from that same key. This round-trip is invisible to
 * callers, who access it via content_type().
 *
 * CLONE-BASED IMMUTABILITY
 * with_status(), with_error(), with_message_id(), with_attempt_count() each
 * return a new instance. This prevents mutations made after a send attempt from
 * silently affecting code that still holds a reference to the original entry —
 * the same reason MailPayload is immutable.
 *
 * REDACTION READS THE OPTION DIRECTLY
 * redacted() calls get_option('sendstack_logs_config') because it is used in
 * two distinct contexts: at write time (LogWriter redacts before insert) and at
 * read time (admin UI displays already-stored data safely). Reading the option
 * directly keeps both callers simple — they pass the header list and get back a
 * safe copy.
 *
 * BODY COLUMN IS body_mime, NOT body
 * The DB column is named body_mime to make the content type (HTML/plain)
 * explicit to anyone reading the schema directly. Internally the LogEntry
 * property is named $body for readability; to_array() performs the mapping.
 */
