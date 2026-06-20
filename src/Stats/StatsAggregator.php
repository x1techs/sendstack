<?php
/**
 * Counts send outcomes into the stats table.
 *
 * @package SendStack\Stats
 * @since   1.0.0
 */

namespace SendStack\Stats;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\MailPayload;
use SendStack\Mailer\SendResult;

/**
 * Subscribes to sendstack_after_send and increments daily counters in the
 * sendstack_stats table via StatsRepository.
 *
 * @since 1.0.0
 */
class StatsAggregator {

	/** @var StatsRepository */
	private $repository;

	/**
	 * @param StatsRepository $repository Injected stats repository.
	 */
	public function __construct( StatsRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Attach action callbacks for the mailer event hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'sendstack_send_success', array( $this, 'on_send_success' ), 10, 2 );
		add_action( 'sendstack_send_failed', array( $this, 'on_send_failed' ), 10, 2 );
	}

	/**
	 * Increment the 'sent' metric on successful delivery.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Delivered message.
	 * @param  SendResult  $result  Successful send result.
	 * @return void
	 */
	public function on_send_success( MailPayload $payload, SendResult $result ): void {
		$this->repository->increment( 'sent' );
		$this->repository->increment( 'total' );
	}

	/**
	 * Increment the 'failed' metric on delivery failure.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Failed message.
	 * @param  SendResult  $result  Failure result.
	 * @return void
	 */
	public function on_send_failed( MailPayload $payload, SendResult $result ): void {
		$this->repository->increment( 'failed' );
		$this->repository->increment( 'total' );
	}
}
