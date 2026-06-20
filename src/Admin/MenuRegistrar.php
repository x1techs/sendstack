<?php
/**
 * Admin menu page registrar.
 *
 * @package SendStack\Admin
 * @since   1.0.0
 */

namespace SendStack\Admin;

defined( 'ABSPATH' ) || exit;

use SendStack\Admin\Screens\DashboardScreen;
use SendStack\Admin\Screens\LogsScreen;
use SendStack\Admin\Screens\SettingsScreen;
use SendStack\Admin\Screens\AlertsScreen;
use SendStack\Admin\Screens\ToolsScreen;

/**
 * Registers the top-level SendStack admin menu and all submenu pages.
 *
 * @since 1.0.0
 */
class MenuRegistrar {

	/** @var DashboardScreen */
	private $dashboard;

	/** @var LogsScreen */
	private $logs;

	/** @var SettingsScreen */
	private $settings;

	/** @var AlertsScreen */
	private $alerts;

	/** @var ToolsScreen */
	private $tools;

	/**
	 * @param DashboardScreen $dashboard Dashboard screen.
	 * @param LogsScreen      $logs      Logs screen.
	 * @param SettingsScreen  $settings  Settings screen.
	 * @param AlertsScreen    $alerts    Alerts screen.
	 * @param ToolsScreen     $tools     Tools screen.
	 */
	public function __construct(
		DashboardScreen $dashboard,
		LogsScreen $logs,
		SettingsScreen $settings,
		AlertsScreen $alerts,
		ToolsScreen $tools
	) {
		$this->dashboard = $dashboard;
		$this->logs      = $logs;
		$this->settings  = $settings;
		$this->alerts    = $alerts;
		$this->tools     = $tools;
	}

	/**
	 * Register the top-level menu and all submenus via add_menu_page / add_submenu_page.
	 *
	 * Hooked to admin_menu.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		add_menu_page(
			__( 'SendStack', 'sendstack' ),
			__( 'SendStack', 'sendstack' ),
			$this->dashboard->capability(),
			'sendstack',
			array( $this->dashboard, 'render' ),
			'dashicons-email-alt',
			25
		);

		$submenus = array(
			array( __( 'Dashboard', 'sendstack' ), 'sendstack', $this->dashboard ),
			array( __( 'Email Logs', 'sendstack' ), 'sendstack-logs', $this->logs ),
			array( __( 'Settings', 'sendstack' ), 'sendstack-settings', $this->settings ),
			array( __( 'Alerts', 'sendstack' ), 'sendstack-alerts', $this->alerts ),
			array( __( 'Tools', 'sendstack' ), 'sendstack-tools', $this->tools ),
		);

		foreach ( $submenus as $item ) {
			list( $label, $slug, $screen ) = $item;

			add_submenu_page(
				'sendstack',
				$label,
				$label,
				$screen->capability(),
				$slug,
				array( $screen, 'render' )
			);
		}
	}
}
