<?php
/**
 * REST controller for provider connection verification.
 *
 * @package SendStack\API\Controllers
 * @since   1.0.0
 */

namespace SendStack\API\Controllers;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\MailerManager;

/**
 * Handles POST /connections/verify.
 *
 * @since 1.0.0
 */
class ConnectionsController extends AbstractController {

	/** @var string */
	protected $rest_base = 'connections';

	/** @var MailerManager */
	private $mailer_manager;

	/**
	 * @param MailerManager $mailer_manager Used to resolve and verify providers.
	 */
	public function __construct( MailerManager $mailer_manager ) {
		$this->mailer_manager = $mailer_manager;
	}

	/**
	 * Register /connections routes.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/verify',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'verify' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'provider' => array(
							'type'        => 'string',
							'description' => __( 'Provider slug to verify. Defaults to the active mailer.', 'sendstack' ),
						),
					),
				),
			)
		);
	}

	/**
	 * POST /sendstack/v1/connections/verify
	 *
	 * Verifies the connection for either a specified provider or the active mailer.
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function verify( $request ) {
		$slug      = (string) $request->get_param( 'provider' );
		$providers = $this->mailer_manager->available_providers();

		if ( '' !== $slug ) {
			if ( ! isset( $providers[ $slug ] ) ) {
				return $this->error(
					'sendstack_unknown_provider',
					/* translators: %s: provider slug */
					sprintf( __( 'Unknown provider: %s', 'sendstack' ), $slug ),
					404
				);
			}

			$mailer = $providers[ $slug ];
		} else {
			$mailer = $this->mailer_manager->resolve_mailer();
		}

		$ok = $mailer->verify_connection();

		return $this->ok(
			array(
				'provider' => $mailer->slug(),
				'verified' => $ok,
				'message'  => $ok
					? __( 'Connection verified successfully.', 'sendstack' )
					: __( 'Connection verification failed. Check your credentials.', 'sendstack' ),
			)
		);
	}
}
