<?php
/**
 * REST controller for email logs.
 *
 * @package SendStack\API\Controllers
 * @since   1.0.0
 */

namespace SendStack\API\Controllers;

defined( 'ABSPATH' ) || exit;

use SendStack\Logger\LogRepository;
use SendStack\Mailer\MailerManager;
use SendStack\Mailer\MailPayload;

/**
 * Handles GET /logs, GET /logs/{id}, DELETE /logs/{id},
 * POST /logs/{id}/resend, and POST /logs/bulk.
 *
 * @since 1.0.0
 */
class LogsController extends AbstractController {

	/** @var string */
	protected $rest_base = 'logs';

	/** @var LogRepository */
	private $repository;

	/** @var MailerManager */
	private $mailer_manager;

	/**
	 * @param LogRepository $repository     Log repository.
	 * @param MailerManager $mailer_manager Used for resend operations.
	 */
	public function __construct( LogRepository $repository, MailerManager $mailer_manager ) {
		$this->repository     = $repository;
		$this->mailer_manager = $mailer_manager;
	}

	/**
	 * Register /logs routes.
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
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'status'   => array( 'type' => 'string' ),
						'provider' => array( 'type' => 'string' ),
						'search'   => array( 'type' => 'string' ),
						'per_page' => array(
							'type'    => 'integer',
							'default' => 25,
							'minimum' => 1,
							'maximum' => 100,
						),
						'page'     => array(
							'type'    => 'integer',
							'default' => 1,
							'minimum' => 1,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => \WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'type'     => 'integer',
							'required' => true,
							'minimum'  => 1,
						),
					),
				),
				array(
					'methods'             => \WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'type'     => 'integer',
							'required' => true,
							'minimum'  => 1,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)/resend',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'resend_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'type'     => 'integer',
							'required' => true,
							'minimum'  => 1,
						),
					),
				),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/bulk',
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'bulk' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'action' => array(
							'type'     => 'string',
							'required' => true,
							'enum'     => array( 'delete', 'resend' ),
						),
						'ids'    => array(
							'type'     => 'array',
							'required' => true,
							'items'    => array( 'type' => 'integer' ),
						),
					),
				),
			)
		);
	}

	/**
	 * GET /sendstack/v1/logs
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_items( $request ) {
		$args    = array(
			'status'   => (string) $request->get_param( 'status' ),
			'provider' => (string) $request->get_param( 'provider' ),
			'search'   => (string) $request->get_param( 'search' ),
			'per_page' => (int) $request->get_param( 'per_page' ),
			'page'     => (int) $request->get_param( 'page' ),
		);
		$entries = $this->repository->query( $args );
		$total   = $this->repository->count( $args );
		$data    = array_map(
			static function ( $entry ) {
				return $entry->to_array();
			},
			$entries
		);

		$response = $this->ok( $data );
		$response->header( 'X-WP-Total', $total );
		$response->header( 'X-WP-TotalPages', (int) ceil( $total / $args['per_page'] ) );

		return $response;
	}

	/**
	 * GET /sendstack/v1/logs/{id}
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_item( $request ) {
		$entry = $this->repository->find( (int) $request['id'] );

		if ( null === $entry ) {
			return $this->error( 'sendstack_log_not_found', __( 'Log entry not found.', 'sendstack' ), 404 );
		}

		return $this->ok( $entry->to_array() );
	}

	/**
	 * DELETE /sendstack/v1/logs/{id}
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function delete_item( $request ) {
		$id    = (int) $request['id'];
		$entry = $this->repository->find( $id );

		if ( null === $entry ) {
			return $this->error( 'sendstack_log_not_found', __( 'Log entry not found.', 'sendstack' ), 404 );
		}

		$this->repository->delete( $id );

		return $this->ok(
			array(
				'deleted' => true,
				'id'      => $id,
			)
		);
	}

	/**
	 * POST /sendstack/v1/logs/{id}/resend
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function resend_item( $request ) {
		$entry = $this->repository->find( (int) $request['id'] );

		if ( null === $entry ) {
			return $this->error( 'sendstack_log_not_found', __( 'Log entry not found.', 'sendstack' ), 404 );
		}

		$payload = MailPayload::from_args(
			array(
				'to'      => array_filter( array_map( 'trim', explode( ',', $entry->to ) ) ),
				'subject' => $entry->subject,
				'message' => $entry->body,
				'headers' => explode( "\n", $entry->headers ),
			)
		);

		$result = $this->mailer_manager->handle_send( $payload );

		if ( ! $result->is_success() ) {
			return $this->error( 'sendstack_resend_failed', $result->error_message() );
		}

		return $this->ok( array( 'resent' => true ) );
	}

	/**
	 * POST /sendstack/v1/logs/bulk
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function bulk( $request ) {
		$action = (string) $request->get_param( 'action' );
		$ids    = array_map( 'intval', (array) $request->get_param( 'ids' ) );

		$processed = 0;

		foreach ( $ids as $id ) {
			switch ( $action ) {
				case 'delete':
					if ( $this->repository->delete( $id ) ) {
						++$processed;
					}
					break;

				case 'resend':
					$entry = $this->repository->find( $id );
					if ( null !== $entry ) {
						$payload = MailPayload::from_args(
							array(
								'to'      => array_filter( array_map( 'trim', explode( ',', $entry->to ) ) ),
								'subject' => $entry->subject,
								'message' => $entry->body,
							)
						);
						$result  = $this->mailer_manager->handle_send( $payload );
						if ( $result->is_success() ) {
							++$processed;
						}
					}
					break;
			}
		}

		return $this->ok(
			array(
				'action'    => $action,
				'processed' => $processed,
				'total'     => count( $ids ),
			)
		);
	}
}
