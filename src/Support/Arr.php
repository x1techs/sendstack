<?php
/**
 * Array utility helpers.
 *
 * @package SendStack\Support
 * @since   1.0.0
 */

namespace SendStack\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Stateless helpers for common array operations not covered by PHP's built-ins.
 *
 * @since 1.0.0
 */
class Arr {

	/**
	 * Retrieve a value from an array using dot-notation or a plain key.
	 *
	 * Returns $default when the key is absent or the value is null.
	 *
	 * @since  1.0.0
	 * @param  array  $array   Source array.
	 * @param  string $key     Dot-delimited key path (e.g. "db.host").
	 * @param  mixed  $default Value returned when the key is missing.
	 * @return mixed
	 */
	public static function get( array $array, string $key, $default = null ) {
		if ( isset( $array[ $key ] ) ) {
			return $array[ $key ];
		}

		// Dot-notation traversal.
		foreach ( explode( '.', $key ) as $segment ) {
			if ( ! is_array( $array ) || ! array_key_exists( $segment, $array ) ) {
				return $default;
			}

			$array = $array[ $segment ];
		}

		return $array;
	}

	/**
	 * Return a new array containing only the specified keys.
	 *
	 * @since  1.0.0
	 * @param  array    $array Source array.
	 * @param  string[] $keys  Keys to keep.
	 * @return array
	 */
	public static function only( array $array, array $keys ): array {
		return array_intersect_key( $array, array_flip( $keys ) );
	}

	/**
	 * Return a new array with the specified keys removed.
	 *
	 * @since  1.0.0
	 * @param  array    $array Source array.
	 * @param  string[] $keys  Keys to remove.
	 * @return array
	 */
	public static function except( array $array, array $keys ): array {
		return array_diff_key( $array, array_flip( $keys ) );
	}

	/** Not instantiable. */
	private function __construct() {}
}
