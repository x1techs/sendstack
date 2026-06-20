<?php
/**
 * Wizard sender-identity step.
 *
 * @package SendStack\Admin\Wizard\Steps
 * @since   1.0.0
 */

namespace SendStack\Admin\Wizard\Steps;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Wizard\WizardStepInterface;
use SendStack\Support\Validator;

/**
 * Captures the From name and From email address used for all outbound mail.
 *
 * @since 1.0.0
 */
class SenderStep implements WizardStepInterface {

	/** @var string Option key for the From name. */
	private const OPTION_FROM_NAME = 'sendstack_from_name';

	/** @var string Option key for the From email. */
	private const OPTION_FROM_EMAIL = 'sendstack_from_email';

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function id(): string {
		return 'sender';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Sender Identity', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		$from_name  = get_option( self::OPTION_FROM_NAME, get_option( 'blogname', '' ) );
		$from_email = get_option( self::OPTION_FROM_EMAIL, get_option( 'admin_email', '' ) );
		// TODO: include templates/admin/wizard/sender.php.
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return bool True when both fields are present and the email is valid.
	 */
	public function validate( array $data ): bool {
		$name  = trim( $data['sendstack_from_name'] ?? '' );
		$email = trim( $data['sendstack_from_email'] ?? '' );

		return '' !== $name && Validator::email( $email );
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return void
	 */
	public function save( array $data ): void {
		update_option( self::OPTION_FROM_NAME, sanitize_text_field( $data['sendstack_from_name'] ?? '' ) );
		update_option( self::OPTION_FROM_EMAIL, sanitize_email( $data['sendstack_from_email'] ?? '' ) );
	}
}
