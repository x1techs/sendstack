<?php
/**
 * Persists send attempts to the log table.
 *
 * @package SendStack\Logger
 * @since   1.0.0
 */

namespace SendStack\Logger;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\MailerInterface;
use SendStack\Mailer\MailPayload;
use SendStack\Mailer\SendResult;

/**
 * Subscribes to sendstack_before_send and sendstack_after_send and persists
 * log entries via LogRepository.
 *
 * On before_send a QUEUED row is inserted. On after_send the row is updated to
 * SENT or FAILED. The two hook calls are correlated via a static array keyed by
 * spl_object_id($payload) — see Concepts block below for rationale.
 *
 * @since 1.0.0
 */
class LogWriter {

	/** @var LogRepository */
	private $repository;

	/**
	 * In-flight log IDs keyed by spl_object_id() of the originating MailPayload.
	 * Static so the correlation survives even if the container re-instantiates
	 * the writer mid-request (unlikely, but defensive).
	 *
	 * @var array<int, int>
	 */
	private static $pending_ids = array();

	/**
	 * @since 1.0.0
	 * @param LogRepository $repository Injected log repository.
	 */
	public function __construct( LogRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Attach action callbacks for the mailer event hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'sendstack_before_send', array( $this, 'on_before_send' ), 10, 2 );
		add_action( 'sendstack_after_send', array( $this, 'on_after_send' ), 10, 3 );
	}

	/**
	 * Insert a QUEUED log entry before delivery is attempted.
	 *
	 * Bails silently when logging is disabled in the plugin settings.
	 * Applies body/header redaction per the sendstack_logs_config option.
	 *
	 * @since  1.0.0
	 * @param  MailPayload     $payload Message about to be sent.
	 * @param  MailerInterface $mailer  The provider that will handle delivery.
	 * @return void
	 */
	public function on_before_send( MailPayload $payload, MailerInterface $mailer ): void {
		$settings = (array) get_option( 'sendstack_settings', array() );
		if ( empty( $settings['log_enabled'] ) ) {
			return;
		}

		$entry = LogEntry::from_payload( $payload, $mailer->slug() );

		// Apply configured redaction before the row is written to disk.
		$config           = (array) get_option( 'sendstack_logs_config', array() );
		$redacted_headers = isset( $config['redacted_headers'] ) ? (array) $config['redacted_headers'] : array();
		$entry            = $entry->redacted( $redacted_headers );

		$id = $this->repository->insert( $entry );

		if ( $id > 0 ) {
			self::$pending_ids[ spl_object_id( $payload ) ] = $id;
		}
	}

	/**
	 * Update the log entry with the delivery outcome after the send completes.
	 *
	 * Retrieves the row ID stored in on_before_send via the payload's object ID.
	 * Returns early if no matching ID is found (logging was disabled or insert failed).
	 *
	 * @since  1.0.0
	 * @param  MailPayload     $payload Message that was attempted.
	 * @param  SendResult      $result  Delivery outcome.
	 * @param  MailerInterface $mailer  Provider that handled the send.
	 * @return void
	 */
	public function on_after_send( MailPayload $payload, SendResult $result, MailerInterface $mailer ): void {
		$object_id = spl_object_id( $payload );

		if ( ! isset( self::$pending_ids[ $object_id ] ) ) {
			return;
		}

		$id = self::$pending_ids[ $object_id ];
		unset( self::$pending_ids[ $object_id ] );

		if ( $result->is_success() ) {
			$this->repository->update(
				$id,
				array(
					'status'     => LogStatus::SENT,
					'message_id' => $result->message_id(),
				)
			);
		} else {
			$this->repository->update(
				$id,
				array(
					'status'        => LogStatus::FAILED,
					'error_code'    => $result->error_code(),
					'error_message' => $result->error_message(),
				)
			);
		}

		do_action( 'sendstack_log_written', $id, $result );
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * THE spl_object_id() TRACKING PATTERN
 * LogWriter must correlate two separate hook calls — on_before_send and
 * on_after_send — for the same email. The natural solution would be to stash
 * the log row ID somewhere on the MailPayload object, but MailPayload is
 * intentionally immutable (final class, all-private properties, with() returns
 * a new instance). We cannot add properties to it, and we cannot use a WeakMap
 * in PHP 7.4 (WeakMap requires PHP 8.0).
 *
 * The solution is a static class-level array indexed by spl_object_id($payload).
 * spl_object_id() returns a unique integer for each live object — the same
 * integer that would be returned by spl_object_hash() but faster and usable as
 * an array key. Since before_send and after_send fire synchronously around the
 * same send() call (see AbstractMailer::send()), the MailPayload object is
 * guaranteed to still be alive when after_send fires, so the object ID is
 * stable for the duration of the correlation window.
 *
 * Using static instead of instance avoids the edge case where the container
 * somehow provides two LogWriter instances — though with singletons that should
 * never happen, being defensive here costs nothing.
 *
 * LOGGING IS OPT-IN
 * on_before_send bails immediately when log_enabled is falsy. This means logging
 * adds zero DB writes when disabled — no row is inserted, no object-ID key is
 * stored. on_after_send then bails too (no matching pending ID). The overhead is
 * two get_option() calls and an isset() check per send attempt.
 *
 * SEPARATION FROM LogRepository
 * LogWriter knows about the email-send lifecycle (hooks, settings, payload shape).
 * LogRepository only knows about the database table. This means REST controllers
 * (Feature 7) can use LogRepository directly for reads/updates without pulling in
 * hook logic, and unit tests can inject a mock repository.
 *
 * THE sendstack_log_written ACTION
 * Fired after the row is updated so downstream listeners (future alert integration,
 * dashboard counters) receive a log_id that is guaranteed to exist and be in its
 * final state (SENT or FAILED, never QUEUED).
 */
