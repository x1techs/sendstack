<?php
/**
 * Abstract admin screen base class.
 *
 * @package SendStack\Admin\Screens
 * @since   1.0.0
 */

namespace SendStack\Admin\Screens;

defined( 'ABSPATH' ) || exit;

/**
 * Provides shared behaviour for all SendStack admin screens.
 *
 * Concrete screens must implement slug(), title(), and render(). The
 * capability() method can be overridden when a screen requires a different
 * minimum capability.
 *
 * @since 1.0.0
 */
abstract class AbstractScreen {

	/**
	 * The URL slug used to register this page with WordPress.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract public function slug(): string;

	/**
	 * The <title> and menu label for this screen.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract public function title(): string;

	/**
	 * The minimum capability required to access this screen.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function capability(): string {
		return 'manage_options';
	}

	/**
	 * Output the full screen HTML.
	 *
	 * Implementations must check user capabilities and nonces where appropriate.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	abstract public function render(): void;

	/**
	 * Wrap inner content in the standard WP admin page chrome.
	 *
	 * @since  1.0.0
	 * @param  string $content Inner HTML to wrap.
	 * @return void
	 */
	protected function wrap( string $content ): void {
		printf(
			'<div class="wrap sendstack-screen sendstack-screen--%s"><h1>%s</h1>%s</div>',
			esc_attr( $this->slug() ),
			esc_html( $this->title() ),
			$content // Already-escaped by calling code.
		);
	}

	/**
	 * Verify the POST nonce for this screen's form submissions.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	protected function verify_nonce(): bool {
		$nonce = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		return (bool) wp_verify_nonce( $nonce, 'sendstack_' . $this->slug() );
	}
}
