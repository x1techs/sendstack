<?php
/**
 * Mailer registry and dispatch hub.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Maintains the registry of provider drivers, resolves the active one, and
 * dispatches outbound mail through the send pipeline.
 *
 * @since 1.0.0
 */
class MailerManager {

	/**
	 * Registered mailer drivers keyed by their slug.
	 *
	 * @since 1.0.0
	 * @var array<string,MailerInterface>
	 */
	private $mailers = array();

	/**
	 * Cached active provider slug after first resolution.
	 *
	 * Null means the option has not been read yet.
	 *
	 * @since 1.0.0
	 * @var string|null
	 */
	private $active_slug = null;

	// -------------------------------------------------------------------------
	// Registry
	// -------------------------------------------------------------------------

	/**
	 * Add a mailer driver to the registry.
	 *
	 * Keyed by the driver's own slug, so registering the same slug twice
	 * replaces the previous instance (last write wins).
	 *
	 * @since  1.0.0
	 * @param  MailerInterface $mailer Driver instance to register.
	 * @return void
	 */
	public function register( MailerInterface $mailer ): void {
		$this->mailers[ $mailer->slug() ] = $mailer;

		do_action( 'sendstack_mailer_registered', $mailer );
	}

	/**
	 * Return a specific registered driver by slug, or null if not found.
	 *
	 * @since  1.0.0
	 * @param  string $slug Provider slug.
	 * @return MailerInterface|null
	 */
	public function get_mailer( string $slug ): ?MailerInterface {
		return isset( $this->mailers[ $slug ] ) ? $this->mailers[ $slug ] : null;
	}

	/**
	 * Return a summary array of all registered drivers.
	 *
	 * Each entry contains 'slug', 'label', and 'supports' keys, suitable for
	 * populating a provider selection UI without exposing driver instances.
	 *
	 * @since  1.0.0
	 * @return array<int,array<string,mixed>>
	 */
	public function available_providers(): array {
		$providers = array();

		foreach ( $this->mailers as $mailer ) {
			$providers[] = array(
				'slug'     => $mailer->slug(),
				'label'    => $mailer->label(),
				'supports' => $mailer->supports(),
			);
		}

		return $providers;
	}

	// -------------------------------------------------------------------------
	// Dispatch
	// -------------------------------------------------------------------------

	/**
	 * Build a payload from a wp_mail()-style argument map and send it.
	 *
	 * This is the entry point called by PhpMailerOverride. It creates the
	 * MailPayload, applies the pre-send filter, resolves the active driver,
	 * and delegates to it.
	 *
	 * @since  1.0.0
	 * @param  array $args wp_mail() argument map (to, subject, message, headers, attachments).
	 * @return SendResult
	 */
	public function handle_send( array $args ): SendResult {
		$payload = MailPayload::from_args( $args );

		/** @var MailPayload $payload */
		$payload = apply_filters( 'sendstack_pre_send', $payload );

		$mailer = $this->resolve_mailer();
		$result = $mailer->send( $payload );

		if ( ! $result->is_success() ) {
			do_action( 'sendstack_send_failed', $payload, $result, $mailer );
		}

		return $result;
	}

	// -------------------------------------------------------------------------
	// Resolution
	// -------------------------------------------------------------------------

	/**
	 * Resolve and return the active mailer driver.
	 *
	 * Resolution order:
	 *   1. Read the 'provider' key from the sendstack_settings option.
	 *   2. Pass the slug through the sendstack_active_mailer filter.
	 *   3. Return the matching registered driver.
	 *   4. If the slug is not registered, fall back to the first driver and
	 *      write an error_log notice.
	 *   5. If no drivers are registered, throw RuntimeException.
	 *
	 * The resolved slug is cached for the lifetime of the request to avoid
	 * repeated get_option() calls when sending multiple emails.
	 *
	 * @since  1.0.0
	 * @throws \RuntimeException When no drivers have been registered.
	 * @return MailerInterface
	 */
	public function resolve_mailer(): MailerInterface {
		if ( null === $this->active_slug ) {
			$settings = (array) get_option( 'sendstack_settings', array() );
			$slug     = isset( $settings['provider'] ) ? (string) $settings['provider'] : '';

			/** @var string $slug */
			$slug              = apply_filters( 'sendstack_active_mailer', $slug );
			$this->active_slug = $slug;
		}

		if ( isset( $this->mailers[ $this->active_slug ] ) ) {
			return $this->mailers[ $this->active_slug ];
		}

		if ( ! empty( $this->mailers ) ) {
			error_log(
				sprintf(
					'SendStack: provider "%s" is not registered; falling back to the first registered driver.',
					$this->active_slug
				)
			);

			$first = reset( $this->mailers );

			if ( false !== $first ) {
				return $first;
			}
		}

		throw new \RuntimeException( 'SendStack: no mailer drivers are registered.' );
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * REGISTRY (Strategy Pattern)
 * MailerManager is the context in the Strategy pattern. Each MailerInterface
 * implementation is a concrete strategy. The manager holds them all in
 * $mailers and at runtime picks the right one via resolve_mailer(). Adding a
 * new provider requires only calling register() — no changes to manager logic.
 *
 * handle_send() ACCEPTS array, NOT MailPayload
 * The raw wp_mail() argument map is passed in so that handle_send() can own
 * the full construction of MailPayload (including the sendstack_mail_payload
 * filter inside from_args()) BEFORE the sendstack_pre_send filter runs. This
 * gives hooks a clear order: from_args filter → pre_send filter → do_send().
 *
 * sendstack_pre_send vs sendstack_mail_payload
 * sendstack_mail_payload fires inside MailPayload::from_args() — a low-level
 * construction hook for normalising the payload shape.
 * sendstack_pre_send fires in handle_send(), after full construction and just
 * before provider selection — the last chance to redirect or enrich a message.
 *
 * SLUG CACHING ($active_slug)
 * get_option() is an autoloaded option, so it's already in memory after the
 * first WP request bootstrap. But the filter sendstack_active_mailer could
 * theoretically be expensive if third-party code reads a DB. Caching to a
 * property avoids the filter running once per email when a page sends many.
 * The cache is per-request only (not persisted), so it resets on the next
 * request — safe for option changes between requests.
 *
 * FALLBACK AND error_log
 * Silently falling back to the first driver when the saved slug is unknown
 * keeps the site functional after a provider is removed. The error_log call
 * surfaces the inconsistency to developers without crashing visitors.
 *
 * sendstack_send_failed ACTION
 * Fired in handle_send() on failure. FailoverHandler (Feature 9) hooks here
 * to attempt delivery via the backup connection. Alerts (Feature 7) also
 * hook here. Neither feature needs to modify the send pipeline itself.
 */
