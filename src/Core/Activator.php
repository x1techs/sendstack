<?php
/**
 * Plugin activation handler.
 *
 * @package SendStack\Core
 * @since   1.0.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Runs once when the plugin is activated from the Plugins screen.
 *
 * @since 1.0.0
 */
class Activator {

	/**
	 * Gate on requirements, run migrations, and flush rewrite rules.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function activate(): void {
		if ( ! Requirements::met() ) {
			deactivate_plugins( SENDSTACK_BASENAME );
			wp_die(
				wp_kses_post( implode( '<br>', Requirements::errors() ) ),
				esc_html__( 'Plugin Activation Error', 'sendstack' ),
				array( 'back_link' => true )
			);
		}

		Upgrader::maybe_upgrade();

		flush_rewrite_rules();
	}
}
