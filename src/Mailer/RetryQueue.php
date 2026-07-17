<?php
/**
 * WP-Cron–based retry queue for failed sends.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

use SendStack\Core\CronHooks;

/**
 * Serialises failed MailPayloads into a wp_options queue and processes them
 * via a scheduled WP-Cron event.
 *
 * @since 1.0.0
 */
class RetryQueue {

	/**
	 * Mailer registry used to retry queued messages.
	 *
	 * @var MailerManager
	 */
	private $manager;

	/**
	 * WordPress option key used to persist the retry queue.
	 *
	 * @var string
	 */
	private const OPTION_KEY = 'sendstack_retry_queue';

	/**
	 * Store the mailer manager dependency.
	 *
	 * @param MailerManager $manager Active mailer manager.
	 */
	public function __construct( MailerManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Add a failed message to the retry queue and ensure a cron event exists.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload The message to retry later.
	 * @return void
	 */
	public function schedule( MailPayload $payload ): void {
		$queue   = $this->load_queue();
		$queue[] = array(
			'to'          => $payload->to(),
			'subject'     => $payload->subject(),
			'body'        => $payload->body(),
			'headers'     => $payload->headers(),
			'attachments' => $payload->attachments(),
			'queued_at'   => time(),
		);

		update_option( self::OPTION_KEY, $queue, false );

		if ( ! wp_next_scheduled( CronHooks::PROCESS_RETRY_QUEUE ) ) {
			wp_schedule_single_event( time() + 300, CronHooks::PROCESS_RETRY_QUEUE );
		}
	}

	/**
	 * Drain the queue, re-attempting each message in turn.
	 *
	 * Hooked to the CRON_HOOK event by the service provider.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function process(): void {
		$queue = $this->load_queue();

		if ( empty( $queue ) ) {
			return;
		}

		// Clear before processing to avoid re-queuing on partial failure.
		$this->clear();

		foreach ( $queue as $entry ) {
			$payload = MailPayload::from_args(
				array(
					'to'          => $entry['to'] ?? array(),
					'subject'     => $entry['subject'] ?? '',
					'message'     => $entry['body'] ?? '',
					'headers'     => $entry['headers'] ?? array(),
					'attachments' => $entry['attachments'] ?? array(),
				)
			);

			$this->manager->handle_send( $payload );
		}
	}

	/**
	 * Remove all pending retry entries.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function clear(): void {
		delete_option( self::OPTION_KEY );

		$timestamp = wp_next_scheduled( CronHooks::PROCESS_RETRY_QUEUE );
		if ( false !== $timestamp ) {
			wp_unschedule_event( $timestamp, CronHooks::PROCESS_RETRY_QUEUE );
		}
	}

	/**
	 * Load the persisted retry queue.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function load_queue(): array {
		return (array) get_option( self::OPTION_KEY, array() );
	}
}
