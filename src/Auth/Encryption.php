<?php
/**
 * AES-256-GCM symmetric encryption helper.
 *
 * @package SendStack\Auth
 * @since   1.0.0
 */

namespace SendStack\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Encrypts and decrypts strings using AES-256-GCM via the OpenSSL extension.
 *
 * Output format: base64( iv[12] . tag[16] . ciphertext ).
 *
 * @since 1.0.0
 */
class Encryption {

	/** @var string OpenSSL cipher method. */
	private const CIPHER = 'aes-256-gcm';

	/** @var int GCM IV length in bytes (NIST-recommended). */
	private const IV_LENGTH = 12;

	/** @var int GCM authentication tag length in bytes. */
	private const TAG_LENGTH = 16;

	/**
	 * Encrypt a plaintext string.
	 *
	 * Returns an empty string when OpenSSL is unavailable or encryption fails.
	 *
	 * @since  1.0.0
	 * @param  string $plaintext Value to encrypt.
	 * @return string Base64-encoded ciphertext blob, or '' on failure.
	 */
	public function encrypt( string $plaintext ): string {
		if ( ! self::is_available() ) {
			return '';
		}

		$key        = $this->derive_key();
		$iv         = random_bytes( self::IV_LENGTH );
		$tag        = '';
		$ciphertext = openssl_encrypt(
			$plaintext,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag,
			'',
			self::TAG_LENGTH
		);

		if ( false === $ciphertext ) {
			return '';
		}

		// Prepend IV and tag so decrypt() can recover them without separate storage.
		return base64_encode( $iv . $tag . $ciphertext );
	}

	/**
	 * Decrypt a ciphertext blob produced by encrypt().
	 *
	 * Returns an empty string on failure (wrong key, corrupted data, OpenSSL unavailable).
	 *
	 * @since  1.0.0
	 * @param  string $ciphertext Base64-encoded blob from encrypt().
	 * @return string Plaintext, or '' on failure.
	 */
	public function decrypt( string $ciphertext ): string {
		if ( ! self::is_available() ) {
			return '';
		}

		$data = base64_decode( $ciphertext, true );

		// Minimum length: IV + tag bytes.
		if ( false === $data || strlen( $data ) < self::IV_LENGTH + self::TAG_LENGTH ) {
			return '';
		}

		$key       = $this->derive_key();
		$iv        = substr( $data, 0, self::IV_LENGTH );
		$tag       = substr( $data, self::IV_LENGTH, self::TAG_LENGTH );
		$encrypted = substr( $data, self::IV_LENGTH + self::TAG_LENGTH );

		$plaintext = openssl_decrypt(
			$encrypted,
			self::CIPHER,
			$key,
			OPENSSL_RAW_DATA,
			$iv,
			$tag
		);

		return false !== $plaintext ? $plaintext : '';
	}

	/**
	 * Return whether AES-256-GCM is available on this server.
	 *
	 * @since  1.0.0
	 * @return bool
	 */
	public static function is_available(): bool {
		return function_exists( 'openssl_encrypt' )
			&& in_array( self::CIPHER, openssl_get_cipher_methods(), true );
	}

	/**
	 * Derive a 32-byte key from the site's secret constants.
	 *
	 * Prefers a plugin-specific constant so site owners can rotate the key
	 * without affecting WordPress core secrets.
	 *
	 * @since  1.0.0
	 * @return string 32 raw bytes.
	 */
	private function derive_key(): string {
		if ( defined( 'SENDSTACK_ENCRYPTION_KEY' ) && '' !== SENDSTACK_ENCRYPTION_KEY ) {
			$secret = SENDSTACK_ENCRYPTION_KEY;
		} elseif ( defined( 'AUTH_KEY' ) && '' !== AUTH_KEY ) {
			$secret = AUTH_KEY;
		} else {
			$secret = (string) get_option( 'auth_key', '' );
		}

		// SHA-256 always produces exactly 32 bytes in raw mode.
		return hash( 'sha256', $secret, true );
	}
}
