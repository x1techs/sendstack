<?php
/**
 * PHPStan bootstrap — defines plugin constants for static analysis.
 *
 * @package SendStack
 */

// Prevent double-definition if already loaded.
if ( ! defined( 'SENDSTACK_VERSION' ) ) {
    define( 'SENDSTACK_VERSION', '0.1.0' );
    define( 'SENDSTACK_FILE', __DIR__ . '/../sendstack.php' );
    define( 'SENDSTACK_PATH', __DIR__ . '/../' );
    define( 'SENDSTACK_PLUGIN_URL', 'https://example.com/wp-content/plugins/sendstack/' );
}