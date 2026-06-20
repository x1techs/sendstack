<?php
/**
 * REST controller for statistics.
 *
 * @package SendStack\API\Controllers
 * @since   1.0.0
 */

namespace SendStack\API\Controllers;

defined( 'ABSPATH' ) || exit;

use SendStack\Stats\StatsRepository;

/**
 * Handles GET /stats.
 *
 * @since 1.0.0
 */
class StatsController extends AbstractController {

	/** @var string */
	protected $rest_base = 'stats';

	/** @var StatsRepository */
	private $repository;

	/**
	 * @param StatsRepository $repository Stats repository.
	 */
	public function __construct( StatsRepository $repository ) {
		$this->repository = $repository;
	}

	/**
	 * Register /stats routes.
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
					'callback'            => array( $this, 'get_stats' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'days'  => array(
							'type'    => 'integer',
							'default' => 30,
							'minimum' => 1,
							'maximum' => 365,
						),
						'start' => array(
							'type'   => 'string',
							'format' => 'date',
						),
						'end'   => array(
							'type'   => 'string',
							'format' => 'date',
						),
					),
				),
			)
		);
	}

	/**
	 * GET /sendstack/v1/stats
	 *
	 * Returns a summary or a date-range breakdown depending on params.
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function get_stats( $request ) {
		$start = (string) $request->get_param( 'start' );
		$end   = (string) $request->get_param( 'end' );

		if ( '' !== $start && '' !== $end ) {
			$data = array(
				'range'   => $this->repository->for_range( $start, $end ),
				'summary' => $this->repository->summary(),
			);
		} else {
			$days = (int) $request->get_param( 'days' );
			$data = array(
				'days'    => $days,
				'summary' => $this->repository->summary( $days ),
			);
		}

		return $this->ok( $data );
	}
}
