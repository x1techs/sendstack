<?php
/**
 * Log-row data-transfer object.
 *
 * @package SendStack\Logger
 * @since   1.0.0
 */

namespace SendStack\Logger;

defined( 'ABSPATH' ) || exit;

/**
 * Wraps a single row from the sendstack_logs table.
 *
 * Create via LogEntry::from_row(); produce a masked copy via redacted().
 *
 * @since 1.0.0
 */
final class LogEntry {

	/** @var int|null */
	public $id;

	/** @var string One of LogStatus::* */
	public $status;

	/** @var string Provider slug (e.g. 'smtp', 'sendgrid'). */
	public $provider;

	/** @var string Comma-separated recipient list. */
	public $to;

	/** @var string */
	public $subject;

	/** @var string Raw message body. */
	public $body;

	/** @var string Serialized headers. */
	public $headers;

	/** @var string Serialized attachment paths. */
	public $attachments;

	/** @var string */
	public $error_code;

	/** @var string */
	public $error_message;

	/** @var string Raw provider API response. */
	public $provider_response;

	/** @var string|null MySQL datetime of actual delivery. */
	public $sent_at;

	/** @var string MySQL datetime of row creation. */
	public $created_at;

	/**
	 * Build a LogEntry from a raw database row.
	 *
	 * @since  1.0.0
	 * @param  array $row Associative row from wpdb->get_row( …, ARRAY_A ).
	 * @return self
	 */
	public static function from_row( array $row ): self {
		$entry = new self();

		$entry->id                = isset( $row['id'] ) ? (int) $row['id'] : null;
		$entry->status            = (string) ( $row['status'] ?? LogStatus::QUEUED );
		$entry->provider          = (string) ( $row['provider'] ?? '' );
		$entry->to                = (string) ( $row['to'] ?? '' );
		$entry->subject           = (string) ( $row['subject'] ?? '' );
		$entry->body              = (string) ( $row['body'] ?? '' );
		$entry->headers           = (string) ( $row['headers'] ?? '' );
		$entry->attachments       = (string) ( $row['attachments'] ?? '' );
		$entry->error_code        = (string) ( $row['error_code'] ?? '' );
		$entry->error_message     = (string) ( $row['error_message'] ?? '' );
		$entry->provider_response = (string) ( $row['provider_response'] ?? '' );
		$entry->sent_at           = isset( $row['sent_at'] ) ? (string) $row['sent_at'] : null;
		$entry->created_at        = (string) ( $row['created_at'] ?? '' );

		return $entry;
	}

	/**
	 * Serialise the entry to a plain associative array.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return array(
			'id'                => $this->id,
			'status'            => $this->status,
			'provider'          => $this->provider,
			'to'                => $this->to,
			'subject'           => $this->subject,
			'body'              => $this->body,
			'headers'           => $this->headers,
			'attachments'       => $this->attachments,
			'error_code'        => $this->error_code,
			'error_message'     => $this->error_message,
			'provider_response' => $this->provider_response,
			'sent_at'           => $this->sent_at,
			'created_at'        => $this->created_at,
		);
	}

	/**
	 * Return a copy with sensitive fields replaced by a placeholder.
	 *
	 * Suitable for safe display in the admin UI or export.
	 *
	 * @since  1.0.0
	 * @return self
	 */
	public function redacted(): self {
		$clone                    = clone $this;
		$clone->body              = '[redacted]';
		$clone->headers           = '[redacted]';
		$clone->provider_response = '[redacted]';

		return $clone;
	}
}
