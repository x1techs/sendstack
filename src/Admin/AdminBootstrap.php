<?php
/**
 * Admin subsystem bootstrapper.
 *
 * @package SendStack\Admin
 * @since   1.0.0
 */

namespace SendStack\Admin;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Ajax\AjaxHandler;

/**
 * Wires up every admin subsystem by attaching action/filter callbacks on the
 * appropriate WordPress hooks.
 *
 * @since 1.0.0
 */
class AdminBootstrap {

	/** @var MenuRegistrar */
	private $menu;

	/** @var Assets */
	private $assets;

	/** @var AdminNotices */
	private $notices;

	/** @var AjaxHandler */
	private $ajax;

	/**
	 * @param MenuRegistrar $menu    Menu page registrar.
	 * @param Assets        $assets  Script/style enqueuer.
	 * @param AdminNotices  $notices Admin notice manager.
	 * @param AjaxHandler   $ajax    AJAX endpoint handler.
	 */
	public function __construct(
		MenuRegistrar $menu,
		Assets $assets,
		AdminNotices $notices,
		AjaxHandler $ajax
	) {
		$this->menu    = $menu;
		$this->assets  = $assets;
		$this->notices = $notices;
		$this->ajax    = $ajax;
	}

	/**
	 * Hook all admin subsystems into WordPress.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		if ( ! is_admin() ) {
			return;
		}

		add_action( 'admin_menu', array( $this->menu, 'register' ) );
		add_action( 'admin_enqueue_scripts', array( $this->assets, 'enqueue' ) );
		add_action( 'admin_notices', array( $this->notices, 'render' ) );
		add_action( 'wp_ajax_sendstack_test_email', array( $this->ajax, 'handle_test_email' ) );
		add_action( 'wp_ajax_sendstack_resend', array( $this->ajax, 'handle_resend' ) );
		add_action( 'wp_ajax_sendstack_verify', array( $this->ajax, 'handle_verify' ) );

		$this->assets->register();
		$this->notices->register();
	}
}
