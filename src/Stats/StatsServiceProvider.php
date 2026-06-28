<?php
/**
 * Stats subsystem service provider.
 *
 * @package SendStack\Stats
 * @since   1.0.0
 */

namespace SendStack\Stats;

defined( 'ABSPATH' ) || exit;

use SendStack\Core\ServiceProvider;

/**
 * Registers StatsRepository and StatsAggregator with the DI container,
 * then wires up the sendstack_after_send listener on boot.
 *
 * @since 1.0.0
 */
class StatsServiceProvider extends ServiceProvider {

	/**
	 * Bind the stats services into the container as singletons.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton(
			'stats.repository',
			function () {
				return new StatsRepository();
			}
		);

		$this->container->singleton(
			'stats.aggregator',
			function ( $c ) {
				return new StatsAggregator( $c->make( 'stats.repository' ) );
			}
		);
	}

	/**
	 * Register WordPress hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function boot(): void {
		/** @var StatsAggregator $aggregator */
		$aggregator = $this->container->make( 'stats.aggregator' );
		$aggregator->register();
	}
}
