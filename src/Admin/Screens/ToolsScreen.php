<?php
/**
 * Tools admin screen.
 *
 * @package SendStack\Admin\Screens
 * @since   1.0.0
 */

namespace SendStack\Admin\Screens;

defined( 'ABSPATH' ) || exit;

/**
 * Renders the SendStack tools page — diagnostics, test emails, log export,
 * and data reset utilities.
 *
 * @since 1.0.0
 */
class ToolsScreen extends AbstractScreen {

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'sendstack-tools';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function title(): string {
		return __( 'SendStack Tools', 'sendstack' );
	}

	/**
	 * Render the tools page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'sendstack' ) );
		}

		ob_start();
		// TODO: include template file from templates/admin/tools.php.
		$content = (string) ob_get_clean();

		$this->wrap( $content );
	}
}
