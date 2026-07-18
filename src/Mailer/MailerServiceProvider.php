<?php
/**
 * Mailer subsystem service provider.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

use SendStack\Core\Container;
use SendStack\Core\ServiceProvider;

/**
 * Registers MailerManager and PhpMailerOverride with the DI container, then
 * wires up all provider drivers and hooks on boot.
 *
 * @since 1.0.0
 */
class MailerServiceProvider extends ServiceProvider {

	/**
	 * Bind the mailer services into the container as singletons.
	 *
	 * Called before any provider is booted, so other providers can safely
	 * call $container->make('mailer.manager') inside their own boot().
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton(
			'mailer.manager',
			function () {
				return new MailerManager();
			}
		);

		$this->container->singleton(
			'mailer.override',
			function ( Container $c ) {
				return new PhpMailerOverride( $c->make( 'mailer.manager' ) );
			}
		);
	}

	/**
	 * Register providers, wire the pre_wp_mail hook, and allow third-party
	 * plugins to add their own drivers via the sendstack_register_mailers action.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function boot(): void {
		/**
		 * Shared manager populated with every available mailer.
		 *
		 * @var MailerManager $manager
		 */
		$manager = $this->container->make( 'mailer.manager' );

		// Load SMTP connection config saved under the 'smtp' connection key.
		$settings    = (array) get_option( 'sendstack_settings', array() );
		$connections = isset( $settings['connections']['smtp'] ) ? (array) $settings['connections']['smtp'] : array();
		$manager->register(
			new Providers\SmtpMailer(
				$connections,
				new WordPressPhpMailerFactory()
			)
		);

		// Let other plugins register their own drivers before the hook fires.
		do_action( 'sendstack_register_mailers', $manager );

		// Hook into pre_wp_mail now that all drivers are registered.
		$override = $this->container->make( 'mailer.override' );
		$override->apply();
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * TWO-PASS LOADING: register() THEN boot()
 * Loader calls every provider's register() before calling any boot(). This
 * means that by the time boot() runs, $container->make('mailer.manager') is
 * guaranteed to resolve — even if another provider's boot() also needs it.
 * Never call $this->container->make() inside register(); the target binding
 * may not have been registered yet.
 *
 * SINGLETON BINDINGS
 * Both 'mailer.manager' and 'mailer.override' are singletons. MailerManager
 * caches the active-provider slug per request; multiple calls to make() must
 * return the same instance, otherwise the cache is split across objects and the
 * slug resolution runs more than once. PhpMailerOverride stores an object
 * reference to the manager; a second instance would hold a different registry.
 *
 * SMTP CONNECTION CONFIG PATH
 * The SMTP connection settings are stored under sendstack_settings →
 * connections → smtp. This matches the structure the SettingsScreen will write
 * and CredentialStore will read from. Passing the config array directly to the
 * SmtpMailer constructor keeps the provider stateless (no get_option() calls
 * per email) and testable (unit tests can inject a fixture array).
 *
 * sendstack_register_mailers ACTION
 * Fired after the built-in SMTP driver is registered so third-party plugins
 * can call $manager->register(new MyCustomMailer(...)) without needing to hook
 * into a lower-level bootstrap action. Fired before $override->apply() so that
 * all drivers are in the registry when the first wp_mail() call arrives.
 *
 * $override->apply() CALLED LAST IN boot()
 * PhpMailerOverride hooks pre_wp_mail. If apply() were called before drivers
 * are registered, a wp_mail() call firing during boot (e.g. from another
 * plugin) would hit MailerManager::resolve_mailer() and throw a RuntimeException
 * because no drivers exist yet.
 */
