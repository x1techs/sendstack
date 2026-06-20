<?php
/**
 * Alert channel contract.
 *
 * @package SendStack\Alerts
 * @since   1.0.0
 */

namespace SendStack\Alerts;

defined( 'ABSPATH' ) || exit;

/**
 * Every alert channel must implement this interface.
 *
 * @since 1.0.0
 */
interface AlertInterface {

	/**
	 * Machine-readable channel identifier (e.g. 'email', 'slack').
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string;

	/**
	 * Human-readable channel name shown in the settings UI.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string;

	/**
	 * Deliver an alert event through this channel.
	 *
	 * @since  1.0.0
	 * @param  AlertEvent $event Event to deliver.
	 * @return bool True when the alert was dispatched successfully.
	 */
	public function send( AlertEvent $event ): bool;

	/**
	 * Validate channel-specific configuration values.
	 *
	 * Called before saving settings; return false to block the save.
	 *
	 * @since  1.0.0
	 * @param  array $config Submitted configuration values.
	 * @return bool True when the config is valid.
	 */
	public function validate_config( array $config ): bool;
}
