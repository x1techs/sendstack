<?php
/**
 * Admin-AJAX endpoint handler.
 *
 * @package SendStack\Admin\Ajax
 * @since   1.0.0
 */

namespace SendStack\Admin\Ajax;

defined( 'ABSPATH' ) || exit;

use SendStack\Logger\LogRepository;
use SendStack\Mailer\MailerManager;
use SendStack\Mailer\MailPayload;

/**
 * Registers and handles the three admin-only AJAX endpoints:
 * test email, resend a logged message, and connection verification.
 *
 * All actions require the sendstack_admin nonce and manage_options capability.
 *
 * @since 1.0.0
 */
class AjaxHandler {

	/** @var MailerManager */
	private $mailer_manager;

	/** @var LogRepository */
	private $log_repository;

	/**
	 * @param MailerManager $mailer_manager Used to send and verify.
	 * @param LogRepository $log_repository Used to fetch rows for resend.
	 */
	public function __construct( MailerManager $mailer_manager, LogRepository $log_repository ) {
		$this->mailer_manager = $mailer_manager;
		$this->log_repository = $log_repository;
	}

	/**
	 * Register all wp_ajax_ action hooks.
	 *
	 * Hooks are registered in AdminBootstrap; this method is provided for
	 * cases where explicit registration ordering is needed.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'wp_ajax_sendstack_test_email', array( $this, 'handle_test_email' ) );
		add_action( 'wp_ajax_sendstack_resend', array( $this, 'handle_resend' ) );
		add_action( 'wp_ajax_sendstack_verify', array( $this, 'handle_verify' ) );
	}

	/**
	 * Send a one-off test email to the address supplied in the AJAX request.
	 *
	 * Expected POST fields: nonce, recipient.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_test_email(): void {
		$this->authenticate();

		$recipient = isset( $_POST['recipient'] ) ? sanitize_email( wp_unslash( $_POST['recipient'] ) ) : '';

		if ( ! is_email( $recipient ) ) {
			wp_send_json_error( array( 'message' => __( 'Invalid recipient email address.', 'sendstack' ) ) );
		}

		$payload = MailPayload::from_args(
			array(
				'to'      => array( $recipient ),
				'subject' => __( 'SendStack Test Email', 'sendstack' ),
				'message' => __( 'This is a test email sent from the SendStack plugin.', 'sendstack' ),
			)
		);

		$result = $this->mailer_manager->handle_send( $payload );

		if ( $result->is_success() ) {
			wp_send_json_success( array( 'message' => __( 'Test email sent successfully.', 'sendstack' ) ) );
		} else {
			wp_send_json_error( array( 'message' => $result->error_message() ) );
		}
	}

	/**
	 * Re-dispatch a previously logged message.
	 *
	 * Expected POST fields: nonce, log_id.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_resend(): void {
		$this->authenticate();

		$log_id = isset( $_POST['log_id'] ) ? (int) $_POST['log_id'] : 0;

		if ( $log_id <= 0 ) {
			wp_send_json_error( array( 'message' => __( 'Invalid log ID.', 'sendstack' ) ) );
		}

		$entry = $this->log_repository->find( $log_id );

		if ( null === $entry ) {
			wp_send_json_error( array( 'message' => __( 'Log entry not found.', 'sendstack' ) ) );
		}

		$payload = MailPayload::from_args(
			array(
				'to'      => array_filter( array_map( 'trim', explode( ',', $entry->to ) ) ),
				'subject' => $entry->subject,
				'message' => $entry->body,
				'headers' => explode( "\n", $entry->headers ),
			)
		);

		$result = $this->mailer_manager->handle_send( $payload );

		if ( $result->is_success() ) {
			wp_send_json_success( array( 'message' => __( 'Email resent successfully.', 'sendstack' ) ) );
		} else {
			wp_send_json_error( array( 'message' => $result->error_message() ) );
		}
	}

	/**
	 * Ping the active mailer to verify its connection/credentials.
	 *
	 * Expected POST fields: nonce.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_verify(): void {
		$this->authenticate();

		$mailer = $this->mailer_manager->resolve_mailer();
		$ok     = $mailer->verify_connection();

		if ( $ok ) {
			wp_send_json_success( array( 'message' => __( 'Connection verified successfully.', 'sendstack' ) ) );
		} else {
			wp_send_json_error( array( 'message' => __( 'Connection verification failed. Check your credentials.', 'sendstack' ) ) );
		}
	}

	/**
	 * Verify nonce and capability; terminate with 403 on failure.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	private function authenticate(): void {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'sendstack_admin' ) || ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( array( 'message' => __( 'Unauthorised.', 'sendstack' ) ), 403 );
		}
	}
}
