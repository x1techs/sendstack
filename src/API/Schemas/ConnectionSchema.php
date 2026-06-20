<?php
/**
 * JSON schema for connection verification responses.
 *
 * @package SendStack\API\Schemas
 * @since   1.0.0
 */

namespace SendStack\API\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the JSON schema definition for connection verification responses
 * returned by ConnectionsController::verify().
 *
 * @since 1.0.0
 */
class ConnectionSchema {

	/**
	 * Return the JSON schema array describing a connection verification result.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'sendstack_connection',
			'type'       => 'object',
			'properties' => array(
				'provider' => array(
					'type'        => 'string',
					'description' => __( 'Slug of the provider that was tested.', 'sendstack' ),
				),
				'verified' => array(
					'type'        => 'boolean',
					'description' => __( 'Whether the connection succeeded.', 'sendstack' ),
				),
				'message'  => array(
					'type'        => 'string',
					'description' => __( 'Human-readable result description.', 'sendstack' ),
				),
			),
			'required'   => array( 'provider', 'verified', 'message' ),
		);
	}
}
