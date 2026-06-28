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
 * Renders the SendStack tools page.
 *
 * Test email and diagnostics tools are wired in Feature 7.
 * This class provides the screen registration and capability gate.
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
		return __( 'Tools', 'sendstack' );
	}

	/**
	 * Render the tools page.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function content(): void {
		?>
		<div class="sstk-card">
			<h2 class="sstk-card__title"><?php esc_html_e( 'Send Test Email', 'sendstack' ); ?></h2>
			<p><?php esc_html_e( 'Test email functionality is available in a future release.', 'sendstack' ); ?></p>
		</div>
		<?php
	}
}
