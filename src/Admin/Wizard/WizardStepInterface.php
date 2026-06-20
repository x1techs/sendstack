<?php
/**
 * Wizard step contract.
 *
 * @package SendStack\Admin\Wizard
 * @since   1.0.0
 */

namespace SendStack\Admin\Wizard;

defined( 'ABSPATH' ) || exit;

/**
 * Each setup wizard step must implement this interface.
 *
 * @since 1.0.0
 */
interface WizardStepInterface {

	/**
	 * Machine-readable step identifier (e.g. 'welcome', 'provider').
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function id(): string;

	/**
	 * Human-readable step title shown in the progress bar.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string;

	/**
	 * Output the step's form fields.
	 *
	 * Called inside the wizard page template. Must not output the wrapping
	 * form element — that is handled by SetupWizard.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void;

	/**
	 * Validate submitted form data for this step.
	 *
	 * @since  1.0.0
	 * @param  array $data Sanitized POST data.
	 * @return bool True when the data is valid and save() may proceed.
	 */
	public function validate( array $data ): bool;

	/**
	 * Persist validated step data.
	 *
	 * @since  1.0.0
	 * @param  array $data Sanitized, pre-validated POST data.
	 * @return void
	 */
	public function save( array $data ): void;
}
