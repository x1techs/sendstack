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
 * Concrete screens must implement slug(), title(), and content(). The render()
 * method is final infrastructure — it handles the capability gate, wrapper
 * markup, and notice hook so every screen gets them for free. Override
 * capability() when a screen needs a non-default minimum capability.
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
	 * The <title> and heading for this screen.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	abstract public function title(): string;

	/**
	 * Output the screen-specific inner content.
	 *
	 * Called by render() after the capability check and wrapper opening.
	 * Implementations must escape every dynamic value before output.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	abstract protected function content(): void;

	/**
	 * The minimum capability required to access this screen.
	 *
	 * Filterable so site operators can delegate access without code changes.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function capability(): string {
		return apply_filters( 'sendstack_screen_capability', 'manage_options', $this->slug() );
	}

	/**
	 * Render the full screen: capability gate → wrapper → notices → content.
	 *
	 * Registered as the menu callback by MenuRegistrar. Do not override.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		if ( ! current_user_can( $this->capability() ) ) {
			wp_die( esc_html__( 'You do not have permission to view this page.', 'sendstack' ) );
		}

		echo '<div class="wrap sstk-wrap">';
		echo '<h1>' . esc_html( $this->title() ) . '</h1>';
		$this->notices();
		$this->content();
		echo '</div>';
	}

	/**
	 * Fire the per-screen admin notices action.
	 *
	 * Third-party code can hook sendstack_admin_notices_{slug} to inject notices
	 * into a specific screen without touching core.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	protected function notices(): void {
		do_action( 'sendstack_admin_notices_' . $this->slug() );
	}

	/**
	 * Output a hidden nonce field for a form action.
	 *
	 * All SendStack forms use 'sendstack_nonce' as the field name so verify_nonce()
	 * knows where to find it without needing the action string.
	 *
	 * @since  1.0.0
	 * @param  string $action Nonce action string.
	 * @return void
	 */
	protected function nonce_field( string $action ): void {
		wp_nonce_field( $action, 'sendstack_nonce' );
	}

	/**
	 * Verify the nonce submitted with a POST request.
	 *
	 * @since  1.0.0
	 * @param  string $action Nonce action string to verify against.
	 * @return bool
	 */
	protected function verify_nonce( string $action ): bool {
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$nonce = isset( $_POST['sendstack_nonce'] )
			? sanitize_text_field( wp_unslash( $_POST['sendstack_nonce'] ) )
			: '';

		return (bool) wp_verify_nonce( $nonce, $action );
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * TEMPLATE METHOD PATTERN
 * render() is the fixed algorithm: gate → wrap → notices → content. Concrete
 * screens only implement what varies (slug, title, content). This guarantees
 * the capability check and wrapper markup can never be accidentally skipped.
 *
 * sendstack_screen_capability FILTER
 * Returning a filterable capability from capability() lets a site operator
 * delegate a single screen to an editor role without touching plugin code.
 * The slug is passed as the second argument so one filter callback can apply
 * different logic per screen (e.g. relax only the Logs screen).
 *
 * sendstack_admin_notices_{slug} ACTION
 * Firing a per-screen action lets third-party plugins (or core features like
 * the setup wizard) queue notices targeted at one specific screen rather than
 * blasting every admin page via admin_notices.
 *
 * NONCE FIELD NAME 'sendstack_nonce'
 * Using a single, consistent field name means verify_nonce() never needs to
 * guess where the nonce is — every SendStack form uses the same key. The
 * action string is what makes each nonce unique, not the field name.
 */
