<?php
/**
 * OAuth flow orchestrator.
 *
 * @package SendStack\Auth
 * @since   1.0.0
 */

namespace SendStack\Auth;

defined( 'ABSPATH' ) || exit;

/**
 * Coordinates the OAuth 2.0 authorization-code flow for all supported
 * providers (currently Google / Gmail).
 *
 * @since 1.0.0
 */
class OAuthManager {

	/** @var GoogleOAuthClient */
	private $google_client;

	/** @var CredentialStore */
	private $credentials;

	/** @var string Transient key used to verify state parameter. */
	private const STATE_TRANSIENT = 'sendstack_oauth_state';

	/**
	 * @param GoogleOAuthClient $google_client Google OAuth implementation.
	 * @param CredentialStore   $credentials  Encrypted credential store.
	 */
	public function __construct( GoogleOAuthClient $google_client, CredentialStore $credentials ) {
		$this->google_client = $google_client;
		$this->credentials   = $credentials;
	}

	/**
	 * Initiate the OAuth flow for a provider and return the redirect URL.
	 *
	 * @since  1.0.0
	 * @param  string $provider Provider slug (e.g. 'google').
	 * @return string Authorization URL to redirect the user to.
	 * @throws \InvalidArgumentException For unsupported providers.
	 */
	public function start( string $provider ): string {
		$state = wp_generate_uuid4();
		set_transient( self::STATE_TRANSIENT . '_' . $provider, $state, 600 );

		$redirect_uri = $this->redirect_uri( $provider );

		switch ( $provider ) {
			case 'google':
				return $this->google_client->authorization_url(
					$redirect_uri,
					array( 'https://mail.google.com/' )
				);

			default:
				throw new \InvalidArgumentException( "Unsupported OAuth provider: {$provider}" );
		}
	}

	/**
	 * Handle the provider's authorization-code callback.
	 *
	 * Exchanges the code for tokens and persists them in the credential store.
	 *
	 * @since  1.0.0
	 * @param  string $provider Provider slug.
	 * @param  array  $params   Query parameters from the redirect (code, state, error…).
	 * @return bool True on success, false when the exchange fails or state mismatches.
	 */
	public function handle_callback( string $provider, array $params ): bool {
		if ( ! empty( $params['error'] ) ) {
			return false;
		}

		$state_key = self::STATE_TRANSIENT . '_' . $provider;
		$saved     = get_transient( $state_key );
		delete_transient( $state_key );

		if ( empty( $params['state'] ) || $params['state'] !== $saved ) {
			return false;
		}

		$code = (string) ( $params['code'] ?? '' );

		if ( '' === $code ) {
			return false;
		}

		switch ( $provider ) {
			case 'google':
				$tokens = $this->google_client->exchange_code( $code, $this->redirect_uri( $provider ) );
				if ( empty( $tokens['access_token'] ) ) {
					return false;
				}
				$this->credentials->put( 'gmail_access_token', $tokens['access_token'] );
				if ( ! empty( $tokens['refresh_token'] ) ) {
					$this->credentials->put( 'gmail_refresh_token', $tokens['refresh_token'] );
				}
				return true;

			default:
				return false;
		}
	}

	/**
	 * Revoke tokens and remove stored credentials for a provider.
	 *
	 * @since  1.0.0
	 * @param  string $provider Provider slug.
	 * @return void
	 */
	public function disconnect( string $provider ): void {
		switch ( $provider ) {
			case 'google':
				$token = $this->credentials->get( 'gmail_access_token' );
				if ( ! empty( $token ) ) {
					$this->google_client->revoke( (string) $token );
				}
				$this->credentials->delete( 'gmail_access_token' );
				$this->credentials->delete( 'gmail_refresh_token' );
				break;
		}
	}

	/**
	 * Build the admin-page callback URL for a given provider.
	 *
	 * @since  1.0.0
	 * @param  string $provider Provider slug.
	 * @return string
	 */
	private function redirect_uri( string $provider ): string {
		return add_query_arg(
			array(
				'page'     => 'sendstack-settings',
				'provider' => $provider,
				'action'   => 'oauth_callback',
			),
			admin_url( 'admin.php' )
		);
	}
}
