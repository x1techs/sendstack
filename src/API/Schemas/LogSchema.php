<?php
/**
 * JSON schema for log entries.
 *
 * @package SendStack\API\Schemas
 * @since   1.0.0
 */

namespace SendStack\API\Schemas;

defined( 'ABSPATH' ) || exit;

/**
 * Provides the reusable JSON schema definition for a single log row,
 * used in REST response schemas and request validation.
 *
 * @since 1.0.0
 */
class LogSchema {

	/**
	 * Return the JSON schema array describing a log entry.
	 *
	 * @since  1.0.0
	 * @return array<string, mixed>
	 */
	public function definition(): array {
		return array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'sendstack_log',
			'type'       => 'object',
			'properties' => array(
				'id'                => array(
					'type'        => 'integer',
					'readonly'    => true,
					'description' => __( 'Unique log row ID.', 'sendstack' ),
				),
				'status'            => array(
					'type' => 'string',
					'enum' => array( 'queued', 'sent', 'failed', 'retrying' ),
				),
				'provider'          => array(
					'type'        => 'string',
					'description' => __( 'Provider slug that handled the send.', 'sendstack' ),
				),
				'to'                => array(
					'type'        => 'string',
					'description' => __( 'Comma-separated recipient list.', 'sendstack' ),
				),
				'subject'           => array( 'type' => 'string' ),
				'body'              => array( 'type' => 'string' ),
				'headers'           => array( 'type' => 'string' ),
				'attachments'       => array( 'type' => 'string' ),
				'error_code'        => array(
					'type'        => 'string',
					'description' => __( 'Machine-readable error code; empty on success.', 'sendstack' ),
				),
				'error_message'     => array( 'type' => 'string' ),
				'provider_response' => array( 'type' => 'string' ),
				'sent_at'           => array(
					'type'   => array( 'string', 'null' ),
					'format' => 'date-time',
				),
				'created_at'        => array(
					'type'     => 'string',
					'format'   => 'date-time',
					'readonly' => true,
				),
			),
		);
	}
}
