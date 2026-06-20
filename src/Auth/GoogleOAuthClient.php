<?php
/**
 * Google OAuth 2.0 client.
 *
 * @package SendStack\Auth
 * @since   1.0.0
 */

namespace SendStack\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Handles the Google OAuth 2.0 authorization-code flow for the Gmail API.
 *
 * @since 1.0.0
 */
class GoogleOAuthClient {

	/** @var string Google authorization endpoint. */
	private const AUTH_ENDPOINT = 'https://accounts.google.com/o/oauth2/v2/auth';

	/** @var string Google token endpoint. */
	private const TOKEN_ENDPOINT = 'https://oauth2.googleapis.com/token';

	/** @var string Google token revocation endpoint. */
	private const REVOKE_ENDPOINT = 'https://oauth2.googleapis.com/revoke';

	/** @var string Option key for the client ID. */
	private const OPTION_CLIENT_ID = 'sendstack_google_client_id';

	/** @var string Option key for the client secret. */
	private const OPTION_CLIENT_SECRET = 'sendstack_google_client_secret';

	/**
	 * Build the URL that redirects the user to Google's consent screen.
	 *
	 * @since  1.0.0
	 * @param  string   $redirect_uri Registered redirect URI.
	 * @param  string[] $scopes       OAuth scopes to request.
	 * @return string
	 */
	public function authorization_url( string $redirect_uri, array $scopes = array() ): string {
		$params = array(
			'client_id'     => (string) get_option( self::OPTION_CLIENT_ID, '' ),
			'redirect_uri'  => $redirect_uri,
			'response_type' => 'code',
			'scope'         => implode( ' ', $scopes ),
			'access_type'   => 'offline',
			'prompt'        => 'consent',
		);

		return self::AUTH_ENDPOINT . '?' . http_build_query( $params );
	}

	/**
	 * Exchange an authorization code for access and refresh tokens.
	 *
	 * @since  1.0.0
	 * @param  string $code         Authorization code from the callback.
	 * @param  string $redirect_uri Must match the URI used in authorization_url().
	 * @return array<string, string> Token response keys: access_token, refresh_token, expires_in, token_type.
	 */
	public function exchange_code( string $code, string $redirect_uri ): array {
		$response = wp_remote_post(
			self::TOKEN_ENDPOINT,
			array(
				'body' => array(
					'code'          => $code,
					'client_id'     => (string) get_option( self::OPTION_CLIENT_ID, '' ),
					'client_secret' => (string) get_option( self::OPTION_CLIENT_SECRET, '' ),
					'redirect_uri'  => $redirect_uri,
					'grant_type'    => 'authorization_code',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) ? $body : array();
	}

	/**
	 * Use a refresh token to obtain a new access token.
	 *
	 * @since  1.0.0
	 * @param  string $refresh_token Stored refresh token.
	 * @return array<string, string> Token response keys: access_token, expires_in, token_type.
	 */
	public function refresh_token( string $refresh_token ): array {
		$response = wp_remote_post(
			self::TOKEN_ENDPOINT,
			array(
				'body' => array(
					'refresh_token' => $refresh_token,
					'client_id'     => (string) get_option( self::OPTION_CLIENT_ID, '' ),
					'client_secret' => (string) get_option( self::OPTION_CLIENT_SECRET, '' ),
					'grant_type'    => 'refresh_token',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array();
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		return is_array( $body ) ? $body : array();
	}

	/**
	 * Revoke an access or refresh token.
	 *
	 * @since  1.0.0
	 * @param  string $token Token to revoke.
	 * @return bool True when revocation succeeds (HTTP 200).
	 */
	public function revoke( string $token ): bool {
		$response = wp_remote_post(
			self::REVOKE_ENDPOINT,
			array(
				'body' => array( 'token' => $token ),
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return 200 === wp_remote_retrieve_response_code( $response );
	}
}
