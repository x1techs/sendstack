<?php
/**
 * Encrypted credential storage backed by wp_options.
 *
 * @package SendStack\Auth
 * @since   1.0.0
 */

namespace SendStack\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Stores and retrieves OAuth tokens and API keys using AES-256-GCM encryption.
 *
 * Each credential is stored as an individual wp_options entry with the prefix
 * "sendstack_cred_" to keep them easy to enumerate and remove.
 *
 * @since 1.0.0
 */
class CredentialStore {

	/** @var Encryption */
	private $encryption;

	/** @var string Common option-key prefix for all stored credentials. */
	private const PREFIX = 'sendstack_cred_';

	/**
	 * @param Encryption $encryption Encryption helper.
	 */
	public function __construct( Encryption $encryption ) {
		$this->encryption = $encryption;
	}

	/**
	 * Retrieve and decrypt a stored credential.
	 *
	 * @since  1.0.0
	 * @param  string $key Credential identifier (without the option prefix).
	 * @return mixed Decrypted value, or null when the key is not set.
	 */
	public function get( string $key ) {
		$raw = get_option( self::PREFIX . $key, null );

		if ( null === $raw ) {
			return null;
		}

		return $this->encryption->decrypt( (string) $raw );
	}

	/**
	 * Encrypt and persist a credential.
	 *
	 * @since  1.0.0
	 * @param  string $key   Credential identifier.
	 * @param  mixed  $value Plaintext value to store.
	 * @return void
	 */
	public function put( string $key, $value ): void {
		$encrypted = $this->encryption->encrypt( (string) $value );
		update_option( self::PREFIX . $key, $encrypted, false );
	}

	/**
	 * Remove a stored credential.
	 *
	 * @since  1.0.0
	 * @param  string $key Credential identifier.
	 * @return void
	 */
	public function delete( string $key ): void {
		delete_option( self::PREFIX . $key );
	}

	/**
	 * Return all stored credentials whose keys begin with the given prefix,
	 * decrypted and indexed by their short key (without the option prefix).
	 *
	 * @since  1.0.0
	 * @param  string $prefix Optional sub-prefix to filter by (e.g. 'gmail').
	 * @return array<string, string>
	 */
	public function all( string $prefix = '' ): array {
		global $wpdb;

		$like = $wpdb->esc_like( self::PREFIX . $prefix ) . '%';
		$rows = $wpdb->get_results(
			$wpdb->prepare( "SELECT option_name, option_value FROM {$wpdb->options} WHERE option_name LIKE %s", $like ),
			ARRAY_A
		);

		$result = array();

		foreach ( (array) $rows as $row ) {
			$short_key            = substr( (string) $row['option_name'], strlen( self::PREFIX ) );
			$result[ $short_key ] = $this->encryption->decrypt( (string) $row['option_value'] );
		}

		return $result;
	}
}
