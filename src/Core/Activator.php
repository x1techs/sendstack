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
	 * Gate on requirements, create tables, seed options, and flush rewrite rules.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function activate(): void {
		if ( ! Requirements::met() ) {
			// Undo the activation so the plugin doesn't show as active on an incompatible host.
			deactivate_plugins( SENDSTACK_BASENAME );
			wp_die(
				wp_kses_post( implode( '<br>', Requirements::errors() ) ),
				esc_html__( 'Plugin Activation Error', 'sendstack' ),
				array( 'back_link' => true )
			);
		}

		// Create or upgrade custom database tables via dbDelta.
		\SendStack\Database\Installer::install();

		// Run any pending schema migrations.
		Upgrader::maybe_upgrade();

		// Seed defaults — add_option() is a no-op when the key already exists,
		// so re-activation on an existing install preserves user settings.
		add_option(
			'sendstack_settings',
			array(
				'sender_name'      => '',
				'sender_email'     => '',
				'force_from'       => false,
				'log_enabled'      => true,
				'failover_enabled' => false,
				'hide_from_admins' => false,
			)
		);
		add_option( 'sendstack_preserve_data_on_uninstall', true );
		add_option( 'sendstack_wizard_completed', false );

		// Record the installed plugin version so the Upgrader can detect future changes.
		update_option( 'sendstack_version', SENDSTACK_VERSION );

		// Short-lived transient the Setup Wizard reads to trigger its initial redirect.
		set_transient( 'sendstack_activation_redirect', true, 30 );

		// Register custom REST routes before any redirect happens.
		flush_rewrite_rules();
	}
}
