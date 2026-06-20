<?php
/**
 * First-run setup wizard.
 *
 * @package SendStack\Admin\Wizard
 * @since   1.0.0
 */

namespace SendStack\Admin\Wizard;

defined( 'ABSPATH' ) || exit;

/**
 * Orchestrates the multi-step first-run onboarding wizard.
 *
 * SetupWizard checks whether setup has been completed, redirects new
 * installs to the wizard page, and delegates step rendering and saving to
 * the registered WizardStepInterface implementations.
 *
 * @since 1.0.0
 */
class SetupWizard {

	/** @var string Option key flagging whether setup is complete. */
	private const OPTION_COMPLETE = 'sendstack_setup_complete';

	/** @var string Page slug for the wizard screen. */
	public const PAGE_SLUG = 'sendstack-setup';

	/** @var WizardStepInterface[] Ordered step list. */
	private $steps;

	/**
	 * @param WizardStepInterface[] $steps Ordered list of wizard steps.
	 */
	public function __construct( array $steps ) {
		$this->steps = $steps;
	}

	/**
	 * Redirect a new install to the wizard page if setup is not yet complete.
	 *
	 * Hooked to admin_init.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function maybe_redirect(): void {
		if ( get_option( self::OPTION_COMPLETE ) ) {
			return;
		}

		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Avoid redirect loops on the wizard page itself.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['page'] ) && self::PAGE_SLUG === sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
			return;
		}

		wp_safe_redirect( add_query_arg( 'page', self::PAGE_SLUG, admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Output the full wizard page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'You do not have permission to access this page.', 'sendstack' ) );
		}

		$current_step = $this->current_step();

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$this->handle_step();
		}

		// TODO: include template from templates/admin/wizard.php.
	}

	/**
	 * Validate and save the current step, then advance to the next.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_step(): void {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $nonce, 'sendstack_wizard_step' ) ) {
			return;
		}

		$step = $this->current_step();

		if ( null === $step ) {
			return;
		}

		$data = array_map( 'sanitize_text_field', wp_unslash( (array) $_POST ) );

		if ( ! $step->validate( $data ) ) {
			return;
		}

		$step->save( $data );

		$next = $this->next_step( $step );

		if ( null === $next ) {
			update_option( self::OPTION_COMPLETE, true );
			wp_safe_redirect( admin_url( 'admin.php?page=sendstack' ) );
		} else {
			wp_safe_redirect(
				add_query_arg(
					array(
						'page' => self::PAGE_SLUG,
						'step' => $next->id(),
					),
					admin_url( 'admin.php' )
				)
			);
		}

		exit;
	}

	/**
	 * Return the step instance currently being viewed.
	 *
	 * @since  1.0.0
	 * @return WizardStepInterface|null Null when the step list is empty.
	 */
	private function current_step(): ?WizardStepInterface {
		if ( empty( $this->steps ) ) {
			return null;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$id = isset( $_GET['step'] ) ? sanitize_text_field( wp_unslash( $_GET['step'] ) ) : '';

		foreach ( $this->steps as $step ) {
			if ( $step->id() === $id ) {
				return $step;
			}
		}

		return $this->steps[0];
	}

	/**
	 * Return the step that follows $current, or null at the end.
	 *
	 * @since  1.0.0
	 * @param  WizardStepInterface $current Currently active step.
	 * @return WizardStepInterface|null
	 */
	private function next_step( WizardStepInterface $current ): ?WizardStepInterface {
		$found = false;

		foreach ( $this->steps as $step ) {
			if ( $found ) {
				return $step;
			}

			if ( $step->id() === $current->id() ) {
				$found = true;
			}
		}

		return null;
	}
}
