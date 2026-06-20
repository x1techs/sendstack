<?php
/**
 * Wizard test-email step.
 *
 * @package SendStack\Admin\Wizard\Steps
 * @since   1.0.0
 */

namespace SendStack\Admin\Wizard\Steps;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Wizard\WizardStepInterface;
use SendStack\Mailer\MailerManager;
use SendStack\Mailer\MailPayload;
use SendStack\Support\Validator;

/**
 * Sends a test message through the configured provider so the user can
 * verify delivery before completing setup.
 *
 * @since 1.0.0
 */
class TestStep implements WizardStepInterface {

	/** @var MailerManager */
	private $mailer_manager;

	/**
	 * @param MailerManager $mailer_manager Used to dispatch the test message.
	 */
	public function __construct( MailerManager $mailer_manager ) {
		$this->mailer_manager = $mailer_manager;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function id(): string {
		return 'test';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Send Test Email', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		$default_email = get_option( 'admin_email', '' );
		// TODO: include templates/admin/wizard/test.php.
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return bool True when a valid recipient address is provided.
	 */
	public function validate( array $data ): bool {
		return Validator::email( $data['sendstack_test_recipient'] ?? '' );
	}

	/**
	 * Dispatch the test message; sets a transient with the send result.
	 *
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return void
	 */
	public function save( array $data ): void {
		$recipient = sanitize_email( $data['sendstack_test_recipient'] ?? '' );

		$payload = MailPayload::from_args(
			array(
				'to'      => array( $recipient ),
				'subject' => __( 'SendStack Test Email', 'sendstack' ),
				'message' => __( 'This is a test email from SendStack. If you received it, your email delivery is working correctly.', 'sendstack' ),
			)
		);

		$result = $this->mailer_manager->handle_send( $payload );

		set_transient( 'sendstack_wizard_test_result', $result->is_success() ? 'success' : $result->error_message(), 60 );
	}
}
