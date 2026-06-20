<?php
/**
 * Alerts admin screen.
 *
 * @package SendStack\Admin\Screens
 * @since   1.0.0
 */

namespace SendStack\Admin\Screens;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\AdminNotices;
use SendStack\Alerts\AlertManager;

/**
 * Renders the alert-channel configuration form and processes saves.
 *
 * @since 1.0.0
 */
class AlertsScreen extends AbstractScreen {

	/** @var AlertManager */
	private $alert_manager;

	/** @var AdminNotices */
	private $notices;

	/**
	 * @param AlertManager $alert_manager Alert channel manager.
	 * @param AdminNotices $notices       Notice manager for save feedback.
	 */
	public function __construct( AlertManager $alert_manager, AdminNotices $notices ) {
		$this->alert_manager = $alert_manager;
		$this->notices       = $notices;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'sendstack-alerts';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'Alerts', 'sendstack' );
	}

	/**
	 * Render the alerts configuration screen; delegate to handle_save() when POSTed.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'sendstack' ) );
		}

		if ( 'POST' === $_SERVER['REQUEST_METHOD'] ) {
			$this->handle_save();
		}

		$channels = $this->alert_manager->channels();

		ob_start();
		// TODO: include template file from templates/admin/alerts.php.
		$content = (string) ob_get_clean();

		$this->wrap( $content );
	}

	/**
	 * Validate and persist submitted alert channel settings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_save(): void {
		if ( ! $this->verify_nonce() ) {
			$this->notices->add( __( 'Security check failed. Please try again.', 'sendstack' ), 'error' );
			return;
		}

		// TODO: iterate registered channels, validate config, save via update_option.

		$this->notices->add( __( 'Alert settings saved.', 'sendstack' ), 'success', true );

		wp_safe_redirect( add_query_arg( 'page', $this->slug(), admin_url( 'admin.php' ) ) );
		exit;
	}
}
