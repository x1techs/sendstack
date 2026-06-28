<?php
/**
 * Logger subsystem service provider.
 *
 * @package SendStack\Logger
 * @since   1.0.0
 */

namespace SendStack\Logger;

defined( 'ABSPATH' ) || exit;

use SendStack\Core\ServiceProvider;

/**
 * Registers LogRepository, LogWriter, and LogPruner with the DI container,
 * then wires up hooks and schedules the retention cron event on boot.
 *
 * @since 1.0.0
 */
class LoggerServiceProvider extends ServiceProvider {

	/**
	 * Bind the logger services into the container as singletons.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		$this->container->singleton(
			'logger.repository',
			function () {
				return new LogRepository();
			}
		);

		$this->container->singleton(
			'logger.writer',
			function ( $c ) {
				return new LogWriter( $c->make( 'logger.repository' ) );
			}
		);

		$this->container->singleton(
			'logger.pruner',
			function ( $c ) {
				return new LogPruner( $c->make( 'logger.repository' ) );
			}
		);
	}

	/**
	 * Register WordPress hooks and schedule the cron event.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function boot(): void {
		/** @var LogWriter $writer */
		$writer = $this->container->make( 'logger.writer' );
		$writer->register();

		/** @var LogPruner $pruner */
		$pruner = $this->container->make( 'logger.pruner' );
		$pruner->register();
		$pruner->schedule();
	}
}
