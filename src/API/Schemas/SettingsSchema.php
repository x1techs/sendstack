<?php
/**
 * JSON schema for plugin settings.
 *
 * @package SendStack\API\Schemas
 * @since   1.0.0
 */

namespace SendStack\API\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the JSON schema definition for the plugin settings object,
 * used by SettingsController for request validation and response documentation.
 *
 * @since 1.0.0
 */
class SettingsSchema {

	/**
	 * Return the JSON schema array describing the settings resource.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'sendstack_settings',
			'type'       => 'object',
			'properties' => array(
				'active_mailer'      => array(
					'type'        => 'string',
					'description' => __( 'Slug of the active mailer driver.', 'sendstack' ),
				),
				'from_name'          => array(
					'type'        => 'string',
					'description' => __( 'Sender display name.', 'sendstack' ),
				),
				'from_email'         => array(
					'type'        => 'string',
					'format'      => 'email',
					'description' => __( 'Sender email address.', 'sendstack' ),
				),
				'log_retention_days' => array(
					'type'        => 'integer',
					'minimum'     => 0,
					'description' => __( 'Number of days to retain log rows (0 = keep forever).', 'sendstack' ),
				),
				'failover_enabled'   => array(
					'type'        => 'boolean',
					'description' => __( 'Whether automatic failover to the backup mailer is enabled.', 'sendstack' ),
				),
				'backup_mailer'      => array(
					'type'        => 'string',
					'description' => __( 'Slug of the backup mailer driver used when failover triggers.', 'sendstack' ),
				),
			),
		);
	}
}
