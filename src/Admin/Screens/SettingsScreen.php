<?php
/**
 * Settings admin screen.
 *
 * @package SendStack\Admin\Screens
 * @since   1.0.0
 */

namespace SendStack\Admin\Screens;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\AdminNotices;

/**
 * Renders the plugin settings page and processes form saves.
 *
 * @since 1.0.0
 */
class SettingsScreen extends AbstractScreen {

	/** @var AdminNotices */
	private $notices;

	/**
	 * @param AdminNotices $notices Notice manager for save feedback.
	 */
	public function __construct( AdminNotices $notices ) {
		$this->notices = $notices;
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'sendstack-settings';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'SendStack Settings', 'sendstack' );
	}

	/**
	 * Render the settings form; delegate to handle_save() when POSTed.
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

		ob_start();
		// TODO: include template file from templates/admin/settings.php.
		$content = (string) ob_get_clean();

		$this->wrap( $content );
	}

	/**
	 * Validate and persist submitted settings.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function handle_save(): void {
		if ( ! $this->verify_nonce() ) {
			$this->notices->add( __( 'Security check failed. Please try again.', 'sendstack' ), 'error' );
			return;
		}

		// TODO: validate and save individual settings groups.

		$this->notices->add( __( 'Settings saved.', 'sendstack' ), 'success', true );

		wp_safe_redirect( add_query_arg( 'page', $this->slug(), admin_url( 'admin.php' ) ) );
		exit;
	}
}
