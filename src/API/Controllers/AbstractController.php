<?php
/**
 * Abstract REST controller base class.
 *
 * @package SendStack\API\Controllers
 * @since   1.0.0
 */

namespace SendStack\API\Controllers;

defined( 'ABSPATH' ) || exit;

/**
 * Extends WP_REST_Controller with shared authentication and schema helpers
 * for all SendStack REST endpoints.
 *
 * @since 1.0.0
 */
abstract class AbstractController extends \WP_REST_Controller {

	/** @var string REST namespace shared by all SendStack routes. */
	protected $namespace = 'sendstack/v1';

	/**
	 * Register the routes for this controller.
	 *
	 * Each concrete controller must call register_rest_route() here.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	abstract public function register_routes(): void;

	/**
	 * Check whether the current user is allowed to access SendStack endpoints.
	 *
	 * Returns true on success or a WP_Error with HTTP 403 on failure.
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Full request object.
	 * @return true|\WP_Error
	 */
	public function permissions_check( \WP_REST_Request $request ) {
		if ( ! current_user_can( 'manage_options' ) ) {
			return new \WP_Error(
				'sendstack_rest_forbidden',
				__( 'You do not have permission to access this resource.', 'sendstack' ),
				array( 'status' => rest_authorization_required_code() )
			);
		}

		return true;
	}

	/**
	 * Wrap a value in a WP_REST_Response with an optional HTTP status code.
	 *
	 * @since  1.0.0
	 * @param  mixed $data   Response data.
	 * @param  int   $status HTTP status code (default 200).
	 * @return \WP_REST_Response
	 */
	protected function ok( $data, int $status = 200 ): \WP_REST_Response {
		return new \WP_REST_Response( $data, $status );
	}

	/**
	 * Return a WP_Error formatted as a REST response.
	 *
	 * @since  1.0.0
	 * @param  string $code    Machine-readable error code.
	 * @param  string $message Human-readable message.
	 * @param  int    $status  HTTP status code (default 400).
	 * @return \WP_Error
	 */
	protected function error( string $code, string $message, int $status = 400 ): \WP_Error {
		return new \WP_Error( $code, $message, array( 'status' => $status ) );
	}
}
