<?php
/**
 * Counts send outcomes into the stats rollup table.
 *
 * @package SendStack\Stats
 * @since   1.0.0
 */

namespace SendStack\Stats;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\MailerInterface;
use SendStack\Mailer\MailPayload;
use SendStack\Mailer\SendResult;

/**
 * Subscribes to sendstack_after_send and increments the daily counter for the
 * sending provider via StatsRepository.
 *
 * @since 1.0.0
 */
class StatsAggregator {

	/** @var StatsRepository */
	private $repository;

	/**
	 * @since 1.0.0
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
		add_action( 'sendstack_after_send', array( $this, 'on_after_send' ), 10, 3 );
	}

	/**
	 * Increment the daily counter for the provider and outcome status.
	 *
	 * Clears the stats transient cache after every increment so the dashboard
	 * reflects the latest totals on the next page load.
	 *
	 * @since  1.0.0
	 * @param  MailPayload     $payload Delivered or failed message.
	 * @param  SendResult      $result  Send outcome.
	 * @param  MailerInterface $mailer  Provider that handled the send.
	 * @return void
	 */
	public function on_after_send( MailPayload $payload, SendResult $result, MailerInterface $mailer ): void {
		$status = $result->is_success() ? 'sent' : 'failed';

		$this->repository->increment( $mailer->slug(), $status );
		$this->repository->clear_cache();
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * SINGLE HOOK, SINGLE RESPONSIBILITY
 * The previous stub registered two hooks (sendstack_send_success and
 * sendstack_send_failed). AbstractMailer fires only sendstack_after_send with
 * the result object — callers determine success/failure via result->is_success().
 * A single hook keeps the registration simple and the logic colocated.
 *
 * WHY clear_cache() AFTER EVERY INCREMENT
 * StatsRepository caches summary() results in transients (15 min for 7-day,
 * 1 hour for 30-day). Clearing after every email ensures the dashboard shows
 * updated totals as soon as any send completes. The overhead is two
 * delete_transient() calls, which are cheap compared to the DB write that
 * just occurred. Sites that send thousands of emails per minute should implement
 * a debounced clear if transient churn becomes measurable.
 *
 * MailerInterface IN THE HOOK SIGNATURE
 * The third parameter of sendstack_after_send is the mailer object (MailerInterface),
 * not just the provider slug. Using the interface gives the aggregator access to
 * any other provider metadata (slug, supports()) without needing a separate
 * lookup, and keeps the hook payload consistent across all listeners.
 */
