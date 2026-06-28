<?php
/**
 * Admin subsystem service provider.
 *
 * @package SendStack\Admin
 * @since   1.0.0
 */

namespace SendStack\Admin;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Ajax\AjaxHandler;
use SendStack\Admin\Screens\AlertsScreen;
use SendStack\Admin\Screens\DashboardScreen;
use SendStack\Admin\Screens\LogsScreen;
use SendStack\Admin\Screens\SettingsScreen;
use SendStack\Admin\Screens\ToolsScreen;
use SendStack\Alerts\AlertManager;
use SendStack\Alerts\AlertThrottle;
use SendStack\Core\ServiceProvider;

/**
 * Registers the admin menu, screens, assets, notices, and AJAX handlers with
 * the DI container, then boots AdminBootstrap on the admin_init hook.
 *
 * @since 1.0.0
 */
class AdminServiceProvider extends ServiceProvider {

	/**
	 * Bind all admin services as singletons.
	 *
	 * Nothing is instantiated here — factories run lazily on the first make().
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton(
			'admin.notices',
			function () {
				return new AdminNotices();
			}
		);

		$this->container->singleton(
			'admin.dashboard_screen',
			function ( $c ) {
				return new DashboardScreen(
					$c->make( 'stats.repository' ),
					$c->make( 'logger.repository' )
				);
			}
		);

		$this->container->singleton(
			'admin.logs_screen',
			function ( $c ) {
				return new LogsScreen( $c->make( 'logger.repository' ) );
			}
		);

		$this->container->singleton(
			'admin.settings_screen',
			function () {
				return new SettingsScreen();
			}
		);

		$this->container->singleton(
			'admin.alerts_screen',
			function ( $c ) {
				// AlertThrottle and AlertManager have no external dependencies.
				// Full channel registration happens in a later feature.
				return new AlertsScreen( $c->make( 'admin.notices' ) );
			}
		);

		$this->container->singleton(
			'admin.tools_screen',
			function () {
				return new ToolsScreen();
			}
		);

		$this->container->singleton(
			'admin.menu',
			function ( $c ) {
				return new MenuRegistrar(
					$c->make( 'admin.dashboard_screen' ),
					$c->make( 'admin.logs_screen' ),
					$c->make( 'admin.settings_screen' ),
					$c->make( 'admin.alerts_screen' ),
					$c->make( 'admin.tools_screen' )
				);
			}
		);

		$this->container->singleton(
			'admin.assets',
			function () {
				return new Assets(
					SENDSTACK_PLUGIN_URL . 'assets/',
					SENDSTACK_VERSION
				);
			}
		);

		$this->container->singleton(
			'admin.ajax',
			function ( $c ) {
				return new AjaxHandler(
					$c->make( 'mailer.manager' ),
					$c->make( 'logger.repository' )
				);
			}
		);

		$this->container->singleton(
			'admin.bootstrap',
			function ( $c ) {
				return new AdminBootstrap(
					$c->make( 'admin.menu' ),
					$c->make( 'admin.assets' ),
					$c->make( 'admin.notices' ),
					$c->make( 'admin.ajax' )
				);
			}
		);
	}

	/**
	 * Wire admin subsystems into WordPress hooks.
	 *
	 * AdminBootstrap::register() checks is_admin() internally, so no frontend
	 * hooks are added on public-facing requests.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function boot(): void {
		/** @var AdminBootstrap $bootstrap */
		$bootstrap = $this->container->make( 'admin.bootstrap' );
		$bootstrap->register();
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * LAZY SINGLETON FACTORIES
 * Every service is registered as a singleton factory. The factory closure does
 * not run until the first make() call for that key. This means zero admin
 * objects are instantiated on frontend requests — only the Loader pays the cost
 * of running register(), which is just closure assignment.
 *
 * DEPENDENCY ORDER
 * 'admin.ajax' depends on 'mailer.manager' and 'logger.repository'.
 * 'admin.dashboard_screen' depends on 'stats.repository' and 'logger.repository'.
 * Because Loader calls every provider's register() before any boot(), those keys
 * are guaranteed to be bound by MailerServiceProvider and LoggerServiceProvider
 * before AdminServiceProvider's factories ever resolve.
 *
 * AlertThrottle/AlertManager INSTANTIATED DIRECTLY
 * These two classes have no external dependencies and are only used by
 * AlertsScreen in v1.0 as a stub. When Feature 9 (alert channels) lands it will
 * introduce an AlertsServiceProvider that owns the canonical 'alerts.manager'
 * binding. AlertsScreen's factory will then switch to $c->make('alerts.manager').
 *
 * AdminBootstrap::register() GUARDS WITH is_admin()
 * boot() unconditionally calls register() on every request. The guard inside
 * register() means no WordPress admin hooks are attached on frontend or REST
 * requests — admin overhead stays zero on the hot path.
 */
