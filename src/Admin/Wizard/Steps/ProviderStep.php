<?php
/**
 * Wizard provider-selection step.
 *
 * @package SendStack\Admin\Wizard\Steps
 * @since   1.0.0
 */

namespace SendStack\Admin\Wizard\Steps;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Wizard\WizardStepInterface;
use SendStack\Mailer\MailerManager;

/**
 * Lets the user choose which email provider to use as their primary mailer.
 *
 * @since 1.0.0
 */
class ProviderStep implements WizardStepInterface {

	/** @var MailerManager */
	private $mailer_manager;

	/**
	 * @param MailerManager $mailer_manager Used to list available providers.
	 */
	public function __construct( MailerManager $mailer_manager ) {
		$this->mailer_manager = $mailer_manager;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function id(): string {
		return 'provider';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Choose Provider', 'sendstack' );
	}

	/**
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		$providers = $this->mailer_manager->available_providers();
		// TODO: include templates/admin/wizard/provider.php.
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return bool True when a known provider slug is selected.
	 */
	public function validate( array $data ): bool {
		$slug      = $data['sendstack_provider'] ?? '';
		$providers = $this->mailer_manager->available_providers();

		return '' !== $slug && isset( $providers[ $slug ] );
	}

	/**
	 * @since  1.0.0
	 * @param  array $data Submitted POST data.
	 * @return void
	 */
	public function save( array $data ): void {
		$slug = $data['sendstack_provider'] ?? '';

		if ( '' !== $slug ) {
			update_option( 'sendstack_active_mailer', $slug );
		}
	}
}
