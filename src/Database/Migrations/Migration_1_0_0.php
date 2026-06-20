<?php
/**
 * Baseline schema migration — v1.0.0.
 *
 * @package SendStack\Database\Migrations
 * @since   1.0.0
 */

namespace SendStack\Database\Migrations;

defined( 'ABSPATH' ) || exit;

use SendStack\Database\Installer;
use SendStack\Database\Schema;

/**
 * Creates the initial sendstack_logs and sendstack_stats tables.
 *
 * @since 1.0.0
 */
class Migration_1_0_0 {

	/**
	 * Apply the migration — create baseline tables.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function up(): void {
		Installer::install();
	}

	/**
	 * Reverse the migration — drop baseline tables.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function down(): void {
		Installer::uninstall();
	}
}
