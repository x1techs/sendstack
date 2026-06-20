<?php
/**
 * HTTP client wrapper.
 *
 * @package SendStack\Support
 * @since   1.0.0
 */

namespace SendStack\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Thin, testable wrapper over wp_remote_request() that merges plugin-level
 * defaults (User-Agent, timeout) into every outbound request.
 *
 * @since 1.0.0
 */
class HttpClient {

	/** @var int Default request timeout in seconds. */
	private const DEFAULT_TIMEOUT = 15;

	/** @var array<string, mixed> Base args merged into every request. */
	private $base_args;

	/**
	 * @param array<string, mixed> $base_args Optional overrides applied to every request.
	 */
	public function __construct( array $base_args = array() ) {
		$this->base_args = array_merge(
			array(
				'timeout'    => self::DEFAULT_TIMEOUT,
				'user-agent' => 'SendStack/' . ( defined( 'SENDSTACK_VERSION' ) ? SENDSTACK_VERSION : '0.1.0' ),
			),
			$base_args
		);
	}

	/**
	 * Perform a GET request.
	 *
	 * @since  1.0.0
	 * @param  string               $url  Request URL.
	 * @param  array<string, mixed> $args wp_remote_get()-compatible args.
	 * @return array|\WP_Error
	 */
	public function get( string $url, array $args = array() ) {
		return $this->request( 'GET', $url, $args );
	}

	/**
	 * Perform a POST request.
	 *
	 * @since  1.0.0
	 * @param  string               $url  Request URL.
	 * @param  array<string, mixed> $args wp_remote_post()-compatible args (body, headers…).
	 * @return array|\WP_Error
	 */
	public function post( string $url, array $args = array() ) {
		return $this->request( 'POST', $url, $args );
	}

	/**
	 * Perform an HTTP request with an explicit method.
	 *
	 * @since  1.0.0
	 * @param  string               $method HTTP verb (GET, POST, PUT, DELETE…).
	 * @param  string               $url    Request URL.
	 * @param  array<string, mixed> $args  wp_remote_request()-compatible args.
	 * @return array|\WP_Error
	 */
	public function request( string $method, string $url, array $args = array() ) {
		$args = array_merge( $this->base_args, $args, array( 'method' => strtoupper( $method ) ) );

		return wp_remote_request( $url, $args );
	}
}
