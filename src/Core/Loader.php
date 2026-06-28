<?php
/**
 * Service-provider loader.
 *
 * @package SendStack\Core
 * @since   1.0.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Instantiates, registers, and boots all service providers.
 *
 * @since 1.0.0
 */
class Loader {

	/** @var Container */
	private $container;

	/**
	 * Ordered list of service-provider class names to load.
	 *
	 * @var array<class-string<ServiceProvider>>
	 */
	private $provider_classes = array(
		\SendStack\Mailer\MailerServiceProvider::class,
	);

	/** @var ServiceProvider[] Instantiated providers, ready to be booted. */
	private $providers = array();

	/**
	 * @param Container $container The plugin DI container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Instantiate each provider and call its register() method.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_providers(): void {
		foreach ( $this->provider_classes as $class ) {
			/** @var ServiceProvider $provider */
			$provider = new $class( $this->container );
			$provider->register();
			$this->providers[] = $provider;
		}
	}

	/**
	 * Boot every registered provider.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function run(): void {
		foreach ( $this->providers as $provider ) {
			$provider->boot();
		}
	}
}
