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


/**
 * Renders the alert-channel configuration form and processes saves.
 *
 * Channel-specific fields and save logic are wired in Feature 9.
 * This class provides the screen registration and capability gate.
 *
 * @since 1.0.0
 */
class AlertsScreen extends AbstractScreen {


	/** @var AdminNotices */
	private $notices;

	/**
	 * @param AdminNotices $notices       Notice manager for save feedback.
	 */
	public function __construct( AdminNotices $notices ) {
		// Alert manager used in Feature 9.
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
	 * Render the alerts configuration screen.
	 *
	 * Channel-specific forms are added in Feature 9 when the concrete channel
	 * classes (EmailChannel, SlackChannel, DiscordChannel) are wired in.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function content(): void {
		if ( 'POST' === $_SERVER['REQUEST_METHOD'] && $this->verify_nonce( 'sendstack_save_alerts' ) ) {
			$this->handle_save();
		}

		?>
		<p><?php esc_html_e( 'Alert channels are configured in a future release.', 'sendstack' ); ?></p>
		<?php
	}

	/**
	 * Validate and persist submitted alert channel settings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_save(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// TODO: iterate registered channels, validate config, save via update_option.

		$this->notices->add( __( 'Alert settings saved.', 'sendstack' ), 'success', true );

		wp_safe_redirect( add_query_arg( 'page', $this->slug(), admin_url( 'admin.php' ) ) );
		exit;
	}
}
