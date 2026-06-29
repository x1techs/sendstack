<?php
/**
 * Admin asset registration and conditional enqueuing.
 *
 * @package SendStack\Admin
 * @since   1.0.0
 */

namespace SendStack\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Registers plugin admin scripts and stylesheets with WordPress and enqueues
 * them only on SendStack admin pages.
 *
 * @since 1.0.0
 */
class Assets {

	/** @var string Base URL for the plugin assets directory. */
	private $assets_url;

	/** @var string Plugin version string used as a cache-busting query var. */
	private $version;

	/**
	 * @param string $assets_url Absolute URL to the plugin's /assets directory.
	 * @param string $version    Plugin version string.
	 */
	public function __construct( string $assets_url, string $version ) {
		$this->assets_url = trailingslashit( $assets_url );
		$this->version    = $version;
	}

	/**
	 * Hook into admin_enqueue_scripts for conditional asset loading.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue' ) );
	}

	/**
	 * Register and enqueue assets on SendStack screens.
	 *
	 * Called inside admin_enqueue_scripts — the correct hook for
	 * wp_register_style / wp_register_script. Registers first, then
	 * enqueues, all in one pass on SendStack pages only.
	 *
	 * @since  1.0.0
	 * @param  string $hook Current page hook suffix.
	 * @return void
	 */
	public function enqueue( string $hook ): void {
		if ( ! $this->is_sendstack_page( $hook ) ) {
			return;
		}

		wp_register_style(
			'sendstack-admin',
			$this->assets_url . 'css/admin.css',
			array(),
			$this->version
		);

		wp_register_script(
			'sendstack-admin',
			$this->assets_url . 'js/admin.js',
			array( 'jquery', 'wp-util' ),
			$this->version,
			true
		);

		wp_localize_script(
			'sendstack-admin',
			'sendstackAdmin',
			array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'sendstack_admin' ),
				'i18n'    => array(
					'sending'        => __( 'Sending…', 'sendstack' ),
					'verifying'      => __( 'Verifying…', 'sendstack' ),
					'success'        => __( 'Success!', 'sendstack' ),
					'error'          => __( 'An error occurred.', 'sendstack' ),
					'show'           => __( 'Show', 'sendstack' ),
					'hide'           => __( 'Hide', 'sendstack' ),
					'confirm_delete' => __( 'Are you sure you want to delete the selected log entries? This cannot be undone.', 'sendstack' ),
				),
			)
		);

		wp_enqueue_style( 'sendstack-admin' );
		wp_enqueue_script( 'sendstack-admin' );
	}

	/**
	 * Return true when $hook belongs to a SendStack admin page.
	 *
	 * @since  1.0.0
	 * @param  string $hook Page hook suffix.
	 * @return bool
	 */
	private function is_sendstack_page( string $hook ): bool {
		return false !== strpos( $hook, 'sendstack' );
	}
}