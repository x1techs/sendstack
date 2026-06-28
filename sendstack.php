<?php
/**
 * SendStack — WordPress Email Delivery Plugin.
 *
 * @package     SendStack
 * @author      X1techs <support@x1techs.com>
 * @copyright   2024 X1techs
 * @license     GPL-2.0+
 *
 * @wordpress-plugin
 * Plugin Name:       SendStack
 * Plugin URI:        https://github.com/x1techs/sendstack
 * Description:       Reliable email delivery for WordPress — multiple SMTP and API providers, logging, failover, and alerts.
 * Version:           0.1.0
 * Author:            X1techs
 * Author URI:        https://x1techs.com
 * License:           GPL-2.0+
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       sendstack
 * Domain Path:       /languages
 * Requires at least: 6.2
 * Requires PHP:      7.4
 */

defined( 'ABSPATH' ) || exit;

// Plugin constants.
define( 'SENDSTACK_VERSION', '0.1.0' );
define( 'SENDSTACK_FILE', __FILE__ );
define( 'SENDSTACK_PATH', plugin_dir_path( __FILE__ ) );
define( 'SENDSTACK_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'SENDSTACK_URL', plugin_dir_url( __FILE__ ) );
define( 'SENDSTACK_BASENAME', plugin_basename( __FILE__ ) );
define( 'SENDSTACK_MIN_PHP', '7.4' );
define( 'SENDSTACK_MIN_WP', '6.2' );
define( 'SENDSTACK_DB_VERSION', '1.0.0' );

// Composer autoloader.
if ( file_exists( __DIR__ . '/vendor/autoload.php' ) ) {
	require_once __DIR__ . '/vendor/autoload.php';
}

// Requirements gate — bail with an admin notice if PHP/WP version is too old.
if ( ! \SendStack\Core\Requirements::met() ) {
	add_action( 'admin_notices', array( 'SendStack\Core\Requirements', 'render_notice' ) );
	return;
}

// Activation and deactivation hooks registered here so WP can find them on a direct include.
register_activation_hook( __FILE__, array( 'SendStack\Core\Activator', 'activate' ) );
register_deactivation_hook( __FILE__, array( 'SendStack\Core\Deactivator', 'deactivate' ) );

// Boot at priority 5 so other plugins can hook at 10+.
add_action(
	'plugins_loaded',
	static function () {
		\SendStack\Core\Plugin::instance()->boot();
	},
	5
);
