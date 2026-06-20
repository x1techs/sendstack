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
		$loader = new Loader( $this->container );
		$loader->register_providers();
		$loader->run();
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
