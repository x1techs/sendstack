<?php
/**
 * Abstract service provider base class.
 *
 * @package SendStack\Core
 * @since   1.0.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * All feature-area providers extend this class and implement register() and boot().
 *
 * @since 1.0.0
 */
abstract class ServiceProvider {

	/** @var Container */
	protected $container;

	/**
	 * @param Container $container The plugin DI container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Bind services into the container.
	 *
	 * Called before any provider is booted, so order of registration does not matter.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	abstract public function register(): void;

	/**
	 * Wire up WordPress hooks after all providers have been registered.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	abstract public function boot(): void;
}
