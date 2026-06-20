<?php
/**
 * Persists send attempts to the log table.
 *
 * @package SendStack\Logger
 * @since   1.0.0
 */

namespace SendStack\Logger;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\MailPayload;
use SendStack\Mailer\SendResult;

/**
 * Subscribes to the sendstack_before_send and sendstack_after_send action hooks
 * and writes log rows via LogRepository.
 *
 * @since 1.0.0
 */
class LogWriter {

	/** @var LogRepository */
	private $repository;

	/**
	 * Transient storage for in-flight row IDs, keyed by a hash of the payload.
	 *
	 * @var array<string, int>
	 */
	private $pending = array();

	/**
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
		add_action( 'sendstack_before_send', array( $this, 'on_before_send' ) );
		add_action( 'sendstack_after_send', array( $this, 'on_after_send' ), 10, 3 );
	}

	/**
	 * Insert a QUEUED log row before delivery is attempted.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Message about to be sent.
	 * @return void
	 */
	public function on_before_send( MailPayload $payload ): void {
		$id = $this->repository->insert(
			array(
				'status'  => LogStatus::QUEUED,
				'to'      => implode( ', ', $payload->to() ),
				'subject' => $payload->subject(),
				'body'    => $payload->body(),
				'headers' => implode( "\n", $payload->headers() ),
			)
		);

		if ( $id > 0 ) {
			$this->pending[ $this->payload_key( $payload ) ] = $id;
		}
	}

	/**
	 * Update the log row with the delivery outcome after send completes.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload  Original message.
	 * @param  SendResult  $result   Delivery outcome.
	 * @param  string      $provider Provider slug that handled the send.
	 * @return void
	 */
	public function on_after_send( MailPayload $payload, SendResult $result, string $provider ): void {
		$key = $this->payload_key( $payload );
		$id  = $this->pending[ $key ] ?? 0;

		unset( $this->pending[ $key ] );

		$data = array(
			'status'            => $result->is_success() ? LogStatus::SENT : LogStatus::FAILED,
			'provider'          => $provider,
			'error_code'        => $result->error_code(),
			'error_message'     => $result->error_message(),
			'provider_response' => $result->provider_response(),
		);

		if ( $result->is_success() ) {
			$data['sent_at'] = current_time( 'mysql', true );
		}

		if ( $id > 0 ) {
			$this->repository->update( $id, $data );
		} else {
			// on_before_send was not called (e.g. direct invocation in tests).
			$data['to']      = implode( ', ', $payload->to() );
			$data['subject'] = $payload->subject();
			$this->repository->insert( $data );
		}
	}

	/**
	 * Stable key for correlating before/after events for the same payload.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Payload to hash.
	 * @return string
	 */
	private function payload_key( MailPayload $payload ): string {
		return md5( implode( '|', $payload->to() ) . $payload->subject() . $payload->body() );
	}
}
