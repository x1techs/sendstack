<?php
/**
 * Front-end hook bootstrapper.
 *
 * @package SendStack\Frontend
 * @since   1.0.0
 */

namespace SendStack\Frontend;

defined( 'ABSPATH' ) || exit;

/**
 * Placeholder for public-facing (non-admin) WordPress hooks.
 *
 * SendStack is primarily a back-end plugin with no front-end output; this
 * class exists as the extension point for any future public hooks such as
 * unsubscribe pages, email tracking pixels, or REST endpoints.
 *
 * @since 1.0.0
 */
class FrontendBootstrap {

	/**
	 * Register any public-facing action and filter hooks.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		// No public hooks in the initial release.
		// Future: unsubscribe route, tracking endpoint, REST API, etc.
	}
}
