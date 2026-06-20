<?php
/**
 * REST API bootstrapper.
 *
 * @package SendStack\API
 * @since   1.0.0
 */

namespace SendStack\API;

defined( 'ABSPATH' ) || exit;

use SendStack\API\Controllers\LogsController;
use SendStack\API\Controllers\StatsController;
use SendStack\API\Controllers\SettingsController;
use SendStack\API\Controllers\ConnectionsController;
use SendStack\API\Controllers\TestController;
use SendStack\API\Controllers\OAuthController;

/**
 * Registers all SendStack REST routes on the rest_api_init action.
 *
 * @since 1.0.0
 */
class RestBootstrap {

	/** @var LogsController */
	private $logs;

	/** @var StatsController */
	private $stats;

	/** @var SettingsController */
	private $settings;

	/** @var ConnectionsController */
	private $connections;

	/** @var TestController */
	private $test;

	/** @var OAuthController */
	private $oauth;

	/**
	 * @param LogsController        $logs        Logs endpoint controller.
	 * @param StatsController       $stats       Stats endpoint controller.
	 * @param SettingsController    $settings    Settings endpoint controller.
	 * @param ConnectionsController $connections Connections endpoint controller.
	 * @param TestController        $test        Test-email endpoint controller.
	 * @param OAuthController       $oauth       OAuth endpoint controller.
	 */
	public function __construct(
		LogsController $logs,
		StatsController $stats,
		SettingsController $settings,
		ConnectionsController $connections,
		TestController $test,
		OAuthController $oauth
	) {
		$this->logs        = $logs;
		$this->stats       = $stats;
		$this->settings    = $settings;
		$this->connections = $connections;
		$this->test        = $test;
		$this->oauth       = $oauth;
	}

	/**
	 * Hook all controllers into rest_api_init.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		add_action(
			'rest_api_init',
			function () {
				$this->logs->register_routes();
				$this->stats->register_routes();
				$this->settings->register_routes();
				$this->connections->register_routes();
				$this->test->register_routes();
				$this->oauth->register_routes();
			}
		);
	}
}
