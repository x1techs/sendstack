<?php
/**
 * Server and WordPress version requirement checks.
 *
 * @package SendStack\Core
 * @since   1.0.0
 */

namespace SendStack\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Checks PHP and WordPress versions against the plugin's minimum requirements.
 *
 * @since 1.0.0
 */
class Requirements {

	/**
	 * Return true when every requirement is satisfied.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public static function met(): bool {
		return empty( self::errors() );
	}

	/**
	 * Collect human-readable error messages for every unmet requirement.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public static function errors(): array {
		$errors = array();

		if ( version_compare( PHP_VERSION, SENDSTACK_MIN_PHP, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: minimum required PHP version, 2: current PHP version */
				esc_html__( 'SendStack requires PHP %1$s or higher. You are running PHP %2$s.', 'sendstack' ),
				SENDSTACK_MIN_PHP,
				PHP_VERSION
			);
		}

		global $wp_version;
		if ( isset( $wp_version ) && version_compare( $wp_version, SENDSTACK_MIN_WP, '<' ) ) {
			$errors[] = sprintf(
				/* translators: 1: minimum required WordPress version, 2: current WordPress version */
				esc_html__( 'SendStack requires WordPress %1$s or higher. You are running WordPress %2$s.', 'sendstack' ),
				SENDSTACK_MIN_WP,
				$wp_version
			);
		}

		return $errors;
	}

	/**
	 * Echo an admin notice listing every unmet requirement.
	 *
	 * Hooked to `admin_notices` by sendstack.php when requirements are not met.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public static function render_notice(): void {
		$errors = self::errors();

		if ( empty( $errors ) ) {
			return;
		}

		echo '<div class="notice notice-error"><p>';
		echo wp_kses_post( implode( '</p><p>', $errors ) );
		echo '</p></div>';
	}
}
