<?php
/**
 * REST controller for plugin settings.
 *
 * @package SendStack\API\Controllers
 * @since   1.0.0
 */

namespace SendStack\API\Controllers;

defined( 'ABSPATH' ) || exit;

use SendStack\API\Schemas\SettingsSchema;

/**
 * Handles GET /settings and POST /settings.
 *
 * @since 1.0.0
 */
class SettingsController extends AbstractController {

	/** @var string */
	protected $rest_base = 'settings';

	/** @var SettingsSchema */
	private $schema;

	/**
	 * @param SettingsSchema $schema JSON schema for settings validation.
	 */
	public function __construct( SettingsSchema $schema ) {
		$this->schema = $schema;
	}

	/**
	 * Register /settings routes.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'update' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => $this->schema->definition()['properties'] ?? array(),
				),
			)
		);
	}

	/**
	 * GET /sendstack/v1/settings
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get( $request ) {
		$data = array(
			'active_mailer'      => get_option( 'sendstack_active_mailer', '' ),
			'from_name'          => get_option( 'sendstack_from_name', '' ),
			'from_email'         => get_option( 'sendstack_from_email', '' ),
			'log_retention_days' => (int) get_option( 'sendstack_log_retention_days', 30 ),
			'failover_enabled'   => (bool) get_option( 'sendstack_failover_enabled', false ),
			'backup_mailer'      => get_option( 'sendstack_backup_mailer', '' ),
		);

		return $this->ok( $data );
	}

	/**
	 * POST /sendstack/v1/settings
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function update( $request ) {
		$map = array(
			'active_mailer'      => 'sendstack_active_mailer',
			'from_name'          => 'sendstack_from_name',
			'from_email'         => 'sendstack_from_email',
			'log_retention_days' => 'sendstack_log_retention_days',
			'failover_enabled'   => 'sendstack_failover_enabled',
			'backup_mailer'      => 'sendstack_backup_mailer',
		);

		foreach ( $map as $param => $option_key ) {
			$value = $request->get_param( $param );

			if ( null !== $value ) {
				update_option( $option_key, $value );
			}
		}

		return $this->get( $request );
	}
}
