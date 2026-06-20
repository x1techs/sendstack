<?php
/**
 * SendStack Uninstall Handler.
 *
 * Runs when the plugin is deleted from the WordPress Plugins screen.
 * Only removes data when the user has explicitly opted out of preservation.
 *
 * @package SendStack
 * @since   1.0.0
 */

defined( 'WP_UNINSTALL_PLUGIN' ) || exit;

/**
 * Drop tables, options, transients and cron events for the current blog.
 *
 * @since 1.0.0
 * @return void
 */
function sendstack_run_uninstall() {
	global $wpdb;

	// Honour the preserve-data preference (default: keep data).
	if ( get_option( 'sendstack_preserve_data_on_uninstall', true ) ) {
		return;
	}

	// Drop plugin tables.
	// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery,WordPress.DB.DirectDatabaseQuery.NoCaching,WordPress.DB.PreparedSQL.NotPrepared
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}sendstack_logs`" );
	$wpdb->query( "DROP TABLE IF EXISTS `{$wpdb->prefix}sendstack_stats`" );

	// Delete all sendstack_* options.
	$wpdb->query( "DELETE FROM `{$wpdb->options}` WHERE `option_name` LIKE 'sendstack\_%'" );

	// Delete transients.
	$wpdb->query(
		"DELETE FROM `{$wpdb->options}`
		WHERE `option_name` LIKE '_transient_sendstack\_%'
		   OR `option_name` LIKE '_transient_timeout_sendstack\_%'"
	);
	// phpcs:enable

	// Clear scheduled cron events.
	$sendstack_hooks = array(
		'sendstack_process_retry_queue',
		'sendstack_prune_logs',
		'sendstack_aggregate_stats',
	);

	foreach ( $sendstack_hooks as $sendstack_hook ) {
		$sendstack_ts = wp_next_scheduled( $sendstack_hook );
		if ( false !== $sendstack_ts ) {
			wp_unschedule_event( $sendstack_ts, $sendstack_hook );
		}
	}

	delete_option( 'sendstack_preserve_data_on_uninstall' );
}

if ( is_multisite() ) {
	foreach ( get_sites() as $sendstack_site ) {
		switch_to_blog( (int) $sendstack_site->blog_id );
		sendstack_run_uninstall();
		restore_current_blog();
	}
} else {
	sendstack_run_uninstall();
}
