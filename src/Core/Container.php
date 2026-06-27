<?php
/**
 * Dependency-injection container.
 *
 * @package SendStack\Core
 * @since   1.0.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Lightweight DI container with transient and singleton bindings.
 *
 * @since 1.0.0
 */
class Container {

	/** @var array<string, callable> Transient factories (new instance per make() call). */
	private $bindings = array();

	/** @var array<string, callable> Singleton factories (resolved once, then cached). */
	private $singletons = array();

	/** @var array<string, mixed> Already-resolved singleton instances. */
	private $resolved = array();

	/**
	 * Register a transient binding.
	 *
	 * @since  1.0.0
	 * @param  string   $abstract Identifier or class name.
	 * @param  callable $factory  Factory that receives this container and returns an instance.
	 * @return void
	 */
	public function bind( string $abstract, callable $factory ): void {
		$this->bindings[ $abstract ] = $factory;
	}

	/**
	 * Register a singleton binding (resolved at most once).
	 *
	 * @since  1.0.0
	 * @param  string   $abstract Identifier or class name.
	 * @param  callable $factory  Factory that receives this container and returns an instance.
	 * @return void
	 */
	public function singleton( string $abstract, callable $factory ): void {
		$this->singletons[ $abstract ] = $factory;
	}

	/**
	 * Resolve an abstract from the container.
	 *
	 * @since  1.0.0
	 * @param  string $abstract Identifier or class name.
	 * @return mixed
	 */
	public function make( string $abstract ) {
		if ( isset( $this->resolved[ $abstract ] ) ) {
			return $this->resolved[ $abstract ];
		}

		if ( isset( $this->singletons[ $abstract ] ) ) {
			$this->resolved[ $abstract ] = ( $this->singletons[ $abstract ] )( $this );
			return $this->resolved[ $abstract ];
		}

		if ( isset( $this->bindings[ $abstract ] ) ) {
			return ( $this->bindings[ $abstract ] )( $this );
		}

		// Fall back to direct instantiation for unregistered concrete classes.
		if ( class_exists( $abstract ) ) {
			return new $abstract();
		}

		throw new \RuntimeException(
			sprintf( 'Cannot resolve [%s] from the SendStack container.', $abstract )
		);
	}

	/**
	 * Return whether an abstract has a registered binding.
	 *
	 * @since  1.0.0
	 * @param  string $abstract Identifier or class name.
	 * @return bool
	 */
	public function has( string $abstract ): bool {
		return isset( $this->bindings[ $abstract ] ) || isset( $this->singletons[ $abstract ] );
	}
}
