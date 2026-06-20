<?php
/**
 * Validation helpers.
 *
 * @package SendStack\Support
 * @since   1.0.0
 */

namespace SendStack\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Stateless validation methods that return a boolean pass/fail result.
 *
 * @since 1.0.0
 */
class Validator {

	/**
	 * Return true when the value is a valid email address.
	 *
	 * @since  1.0.0
	 * @param  string $value Value to test.
	 * @return bool
	 */
	public static function email( string $value ): bool {
		return false !== is_email( $value );
	}

	/**
	 * Return true when the value is a syntactically valid HTTP/HTTPS URL.
	 *
	 * @since  1.0.0
	 * @param  string $value Value to test.
	 * @return bool
	 */
	public static function url( string $value ): bool {
		return false !== filter_var( $value, FILTER_VALIDATE_URL )
			&& in_array( parse_url( $value, PHP_URL_SCHEME ), array( 'http', 'https' ), true );
	}

	/**
	 * Return true when the value matches the "host:port" pattern.
	 *
	 * Accepts IPv4, IPv6 ([::1]:25), and hostnames. Port must be 1–65535.
	 *
	 * @since  1.0.0
	 * @param  string $value Value to test (e.g. "smtp.example.com:587").
	 * @return bool
	 */
	public static function host_port( string $value ): bool {
		// Support IPv6 notation: [::1]:587
		if ( preg_match( '/^\[([^\]]+)\]:(\d+)$/', $value, $m ) ) {
			$host = $m[1];
			$port = (int) $m[2];
		} else {
			$last_colon = strrpos( $value, ':' );

			if ( false === $last_colon ) {
				return false;
			}

			$host = substr( $value, 0, $last_colon );
			$port = (int) substr( $value, $last_colon + 1 );
		}

		if ( $port < 1 || $port > 65535 ) {
			return false;
		}

		// Validate host: allow hostname, IPv4, or IPv6.
		return '' !== $host && (
			filter_var( $host, FILTER_VALIDATE_IP ) !== false
			|| preg_match( '/^([a-zA-Z0-9]([a-zA-Z0-9\-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]{2,}$/', $host )
			|| 'localhost' === $host
		);
	}

	/**
	 * Return true when the value is a valid HTTPS webhook URL.
	 *
	 * Enforces HTTPS to prevent token leakage over plain HTTP.
	 *
	 * @since  1.0.0
	 * @param  string $value Value to test.
	 * @return bool
	 */
	public static function webhook_url( string $value ): bool {
		return false !== filter_var( $value, FILTER_VALIDATE_URL )
			&& 'https' === parse_url( $value, PHP_URL_SCHEME );
	}
}
