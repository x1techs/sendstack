<?php
/**
 * Wizard provider-configuration step.
 *
 * @package SendStack\Admin\Wizard\Steps
 * @since   1.0.0
 */

namespace SendStack\Admin\Wizard\Steps;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Wizard\WizardStepInterface;
use SendStack\Mailer\MailerManager;

/**
 * Collects provider-specific credentials (API keys, SMTP host/port, etc.)
 * for the provider chosen in ProviderStep.
 *
 * @since 1.0.0
 */
class ConfigureStep implements WizardStepInterface {

	/** @var MailerManager */
	private $mailer_manager;

	/**
	 * @param MailerManager $mailer_manager Used to resolve the active provider.
	 */
	public function __construct( MailerManager $mailer_manager ) {
		$this->mailer_manager = $mailer_manager;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function id(): string {
		return 'configure';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Configure Provider', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		$provider = $this->mailer_manager->resolve_mailer();
		// TODO: include templates/admin/wizard/configure.php.
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return bool True when required provider credentials are present.
	 */
	public function validate( array $data ): bool {
		// TODO: delegate to the active provider's credential validator.
		return true;
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return void
	 */
	public function save( array $data ): void {
		// TODO: pass sanitized credentials to the active provider's settings store.
	}
}
