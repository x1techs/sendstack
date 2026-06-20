<?php
/**
 * Wizard finish step.
 *
 * @package SendStack\Admin\Wizard\Steps
 * @since   1.0.0
 */

namespace SendStack\Admin\Wizard\Steps;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Wizard\WizardStepInterface;

/**
 * Final wizard step — confirms setup is complete and links to the dashboard.
 *
 * @since 1.0.0
 */
class FinishStep implements WizardStepInterface {

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function id(): string {
		return 'finish';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'All Done!', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		// TODO: include templates/admin/wizard/finish.php.
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return bool Always true — the finish step has no form fields to validate.
	 */
	public function validate( array $data ): bool {
		return true;
	}

	/**
	 * Mark the wizard as complete so maybe_redirect() no longer fires.
	 *
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return void
	 */
	public function save( array $data ): void {
		update_option( 'sendstack_setup_complete', true );
	}
}
