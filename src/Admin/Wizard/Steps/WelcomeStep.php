<?php
/**
 * Wizard welcome step.
 *
 * @package SendStack\Admin\Wizard\Steps
 * @since   1.0.0
 */

namespace SendStack\Admin\Wizard\Steps;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Wizard\WizardStepInterface;

/**
 * Introductory wizard step — explains what SendStack does and asks the user
 * to confirm they are ready to begin setup.
 *
 * @since 1.0.0
 */
class WelcomeStep implements WizardStepInterface {

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function id(): string {
		return 'welcome';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Welcome', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		// TODO: include templates/admin/wizard/welcome.php.
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return bool Always true — no user input to validate on the welcome step.
	 */
	public function validate( array $data ): bool {
		return true;
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return void
	 */
	public function save( array $data ): void {
		// Nothing to persist on the welcome step.
	}
}
