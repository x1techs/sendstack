<?php
/**
 * PHPUnit bootstrap — Brain Monkey + WP_Mock.
 *
 * @package SendStack\Tests
 * @since   1.0.0
 */

// Ensure ABSPATH is set so plugin-file guards pass when files are loaded by the autoloader.
defined( 'ABSPATH' ) || define( 'ABSPATH', dirname( __DIR__ ) . '/wordpress/' );
defined( 'WPINC' ) || define( 'WPINC', 'wp-includes' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';

// Plugin constants expected by class files under src/.
if ( ! defined( 'SENDSTACK_VERSION' ) ) {
	define( 'SENDSTACK_VERSION', '0.1.0' );
	define( 'SENDSTACK_FILE', dirname( __DIR__ ) . '/sendstack.php' );
	define( 'SENDSTACK_PATH', dirname( __DIR__ ) . '/' );
	define( 'SENDSTACK_URL', '' );
	define( 'SENDSTACK_BASENAME', 'sendstack/sendstack.php' );
	define( 'SENDSTACK_MIN_PHP', '7.4' );
	define( 'SENDSTACK_MIN_WP', '6.2' );
	define( 'SENDSTACK_DB_VERSION', '1.0.0' );
}
