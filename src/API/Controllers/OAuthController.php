<?php
/**
 * REST controller for Google OAuth 2.0 flows.
 *
 * @package SendStack\API\Controllers
 * @since   1.0.0
 */

namespace SendStack\API\Controllers;

defined( 'ABSPATH' ) || exit;

use SendStack\Auth\OAuthManager;

/**
 * Handles GET /oauth/google/start, GET /oauth/google/callback,
 * and POST /oauth/google/disconnect.
 *
 * @since 1.0.0
 */
class OAuthController extends AbstractController {

	/** @var string */
	protected $rest_base = 'oauth';

	/** @var OAuthManager */
	private $oauth_manager;

	/**
	 * @param OAuthManager $oauth_manager OAuth flow orchestrator.
	 */
	public function __construct( OAuthManager $oauth_manager ) {
		$this->oauth_manager = $oauth_manager;
	}

	/**
	 * Register /oauth/google/* routes.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/google/start',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'start' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/google/callback',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'callback' ),
					// Callback is public — Google redirects here with no auth header.
					'permission_callback' => '__return_true',
					'args'                => array(
						'code'  => array( 'type' => 'string' ),
						'state' => array( 'type' => 'string' ),
						'error' => array( 'type' => 'string' ),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/google/disconnect',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'disconnect' ),
					'permission_callback' => array( $this, 'permissions_check' ),
				),
			)
		);
	}

	/**
	 * GET /sendstack/v1/oauth/google/start
	 *
	 * Returns the Google authorization URL the client should redirect to.
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function start( $request ) {
		$url = $this->oauth_manager->start( 'google' );

		return $this->ok( array( 'authorization_url' => $url ) );
	}

	/**
	 * GET /sendstack/v1/oauth/google/callback
	 *
	 * Receives the authorization code from Google and exchanges it for tokens.
	 * On success, redirects the browser to the plugin settings page.
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function callback( $request ) {
		$params = array(
			'code'  => (string) $request->get_param( 'code' ),
			'state' => (string) $request->get_param( 'state' ),
			'error' => (string) $request->get_param( 'error' ),
		);

		$ok = $this->oauth_manager->handle_callback( 'google', $params );

		if ( ! $ok ) {
			return $this->error(
				'sendstack_oauth_failed',
				__( 'OAuth authorization failed. Please try again.', 'sendstack' )
			);
		}

		// Redirect to the settings page; the REST response is normally unused for OAuth callbacks.
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'  => 'sendstack-settings',
					'oauth' => 'success',
				),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	/**
	 * POST /sendstack/v1/oauth/google/disconnect
	 *
	 * Revokes tokens and removes stored Google credentials.
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function disconnect( $request ) {
		$this->oauth_manager->disconnect( 'google' );

		return $this->ok( array( 'disconnected' => true ) );
	}
}
