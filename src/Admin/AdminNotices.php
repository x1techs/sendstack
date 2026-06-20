<?php
/**
 * Admin notice manager.
 *
 * @package SendStack\Admin
 * @since   1.0.0
 */

namespace SendStack\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * Queues and renders WordPress admin notices.
 *
 * Notices can be added from any point in the request cycle and are rendered
 * during the admin_notices action.
 *
 * @since 1.0.0
 */
class AdminNotices {

	/**
	 * Pending notices to render.
	 *
	 * Each entry: ['message' => string, 'type' => string, 'dismissible' => bool]
	 *
	 * @var array<int, array<string, mixed>>
	 */
	private $queue = array();

	/** @var string Transient key for notices that must survive a redirect. */
	private const TRANSIENT_KEY = 'sendstack_admin_notices';

	/**
	 * Restore any notices that were queued before the last redirect.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register(): void {
		$stored = get_transient( self::TRANSIENT_KEY );

		if ( is_array( $stored ) ) {
			$this->queue = array_merge( $this->queue, $stored );
			delete_transient( self::TRANSIENT_KEY );
		}
	}

	/**
	 * Queue a notice for the current or next page load.
	 *
	 * When $persistent is true the notice survives a redirect by storing it
	 * in a transient (useful after settings saves that do a redirect).
	 *
	 * @since  1.0.0
	 * @param  string $message     HTML-escaped message text.
	 * @param  string $type        One of: success, error, warning, info.
	 * @param  bool   $dismissible Whether the notice shows an × button.
	 * @param  bool   $persistent  Persist via transient across redirects.
	 * @return void
	 */
	public function add(
		string $message,
		string $type = 'success',
		bool $dismissible = true,
		bool $persistent = false
	): void {
		$notice = array(
			'message'     => $message,
			'type'        => $type,
			'dismissible' => $dismissible,
		);

		if ( $persistent ) {
			$stored   = (array) get_transient( self::TRANSIENT_KEY );
			$stored[] = $notice;
			set_transient( self::TRANSIENT_KEY, $stored, 60 );
		} else {
			$this->queue[] = $notice;
		}
	}

	/**
	 * Output all queued notices.
	 *
	 * Hooked to admin_notices.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function render(): void {
		foreach ( $this->queue as $notice ) {
			$class = 'notice notice-' . sanitize_html_class( $notice['type'] );

			if ( $notice['dismissible'] ) {
				$class .= ' is-dismissible';
			}

			printf(
				'<div class="%s"><p>%s</p></div>',
				esc_attr( $class ),
				wp_kses_post( $notice['message'] )
			);
		}

		$this->queue = array();
	}
}
