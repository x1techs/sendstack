<?php
/**
 * Plugin bootstrap singleton.
 *
 * @package SendStack\Core
 * @since   1.0.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Central plugin object — created once and accessed via Plugin::instance().
 *
 * @since 1.0.0
 */
final class Plugin {

	/** @var self|null */
	private static $instance = null;

	/** @var Container */
	private $container;

	private function __construct() {
		$this->container = new Container();
	}

	/**
	 * Return (and, on first call, create) the singleton instance.
	 *
	 * @since  1.0.0
	 * @return self
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Register and boot all service providers.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function boot(): void {
		// Translations must be loaded on `init` so WP's locale is finalized.
		add_action( 'init', array( 'SendStack\I18n\TextDomain', 'load' ) );

		// Upgrade checks run only in admin — never slow the frontend request path.
		add_action( 'admin_init', array( 'SendStack\Core\Upgrader', 'maybe_upgrade' ) );

		$loader = new Loader( $this->container );
		$loader->register_providers();
		$loader->run();

		// Signal that all providers have registered and booted — third-party code hooks here.
		do_action( 'sendstack_loaded' );
	}

	/**
	 * Return the DI container.
	 *
	 * @since  1.0.0
	 * @return Container
	 */
	public function container(): Container {
		return $this->container;
	}

	/**
	 * Return the plugin version string.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function version(): string {
		return defined( 'SENDSTACK_VERSION' ) ? SENDSTACK_VERSION : '0.1.0';
	}
}

/*
 * Concepts in this file
 * =====================
 *
 * Singleton pattern for Plugin
 *   WordPress includes sendstack.php once per request. We need exactly one
 *   container and one set of booted providers for the whole request. A static
 *   $instance ensures that even if Plugin::instance() is called from multiple
 *   places (e.g., a companion plugin), they all receive the same object.
 *
 * Two-pass provider loading (register all, then boot all)
 *   register() is for binding into the container. boot() is for add_action /
 *   add_filter calls that expect other services to already be bound. By calling
 *   every provider's register() before any boot(), we guarantee that Provider B
 *   can safely call $this->container->make(SomeServiceFromProviderA::class)
 *   inside its boot() — even if Provider A appears later in the list.
 *
 * Upgrader runs on admin_init, not plugins_loaded
 *   maybe_upgrade() reads an option and potentially runs SQL migrations. Every
 *   frontend page load would pay that cost on plugins_loaded. Deferring to
 *   admin_init means migrations only run during admin requests — the path where
 *   an admin has just logged in and can see any resulting errors — leaving the
 *   public-facing request path untouched.
 *
 * Activator checks Requirements before creating tables
 *   dbDelta() and get_bloginfo() both assume a functioning WordPress environment
 *   at the required minimum version. If Requirements::met() returns false, the
 *   schema or option APIs may not behave as expected. Gating on Requirements
 *   first prevents creating a half-initialized plugin state that would then be
 *   hard to clean up on a subsequent re-activation after fixing the environment.
 */
