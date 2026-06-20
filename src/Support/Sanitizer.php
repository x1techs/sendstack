<?php
/**
 * Centralised sanitization helpers.
 *
 * @package SendStack\Support
 * @since   1.0.0
 */

namespace SendStack\Support;

defined( 'ABSPATH' ) || exit;

/**
 * Thin wrappers around WordPress sanitization functions providing a single
 * import point for all plugin sanitization needs.
 *
 * @since 1.0.0
 */
class Sanitizer {

	/**
	 * Sanitize a plain-text string (strips tags, trims whitespace).
	 *
	 * @since  1.0.0
	 * @param  string $value Raw input.
	 * @return string
	 */
	public static function text( string $value ): string {
		return sanitize_text_field( $value );
	}

	/**
	 * Sanitize and validate an email address.
	 *
	 * Returns an empty string when the value is not a valid email.
	 *
	 * @since  1.0.0
	 * @param  string $value Raw input.
	 * @return string
	 */
	public static function email( string $value ): string {
		return (string) sanitize_email( $value );
	}

	/**
	 * Sanitize a URL.
	 *
	 * @since  1.0.0
	 * @param  string $value Raw input.
	 * @return string
	 */
	public static function url( string $value ): string {
		return esc_url_raw( $value );
	}

	/**
	 * Sanitize HTML content against an allow-list of tags/attributes.
	 *
	 * When $allowed_tags is empty, falls back to wp_kses_post().
	 *
	 * @since  1.0.0
	 * @param  string $value        Raw HTML.
	 * @param  array  $allowed_tags Custom allow-list passed to wp_kses().
	 * @return string
	 */
	public static function html( string $value, array $allowed_tags = array() ): string {
		if ( empty( $allowed_tags ) ) {
			return wp_kses_post( $value );
		}

		return wp_kses( $value, $allowed_tags );
	}

	/**
	 * Cast a value to an integer.
	 *
	 * @since  1.0.0
	 * @param  mixed $value Raw input.
	 * @return int
	 */
	public static function int( $value ): int {
		return (int) $value;
	}

	/**
	 * Apply a sanitization callback to every element of an array.
	 *
	 * @since  1.0.0
	 * @param  array    $values   Input array.
	 * @param  callable $callback Sanitizer to apply to each element.
	 * @return array
	 */
	public static function array_of( array $values, callable $callback ): array {
		return array_map( $callback, $values );
	}
}
