<?php
/**
 * Intercepts WordPress's outbound mail pipeline.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Hooks into the pre_wp_mail filter (WP 5.7+) to short-circuit PHPMailer and
 * route email through MailerManager instead.
 *
 * When SendStack is not yet configured (no provider saved), the hook returns
 * the filter value unchanged so WordPress falls back to PHPMailer normally.
 *
 * @since 1.0.0
 */
class PhpMailerOverride {

	/**
	 * Active mailer manager.
	 *
	 * @since 1.0.0
	 * @var MailerManager
	 */
	private $manager;

	/**
	 * @since 1.0.0
	 * @param MailerManager $manager Active mailer manager instance.
	 */
	public function __construct( MailerManager $manager ) {
		$this->manager = $manager;
	}

	/**
	 * Register the pre_wp_mail intercept filter.
	 *
	 * Must be called after all mailer drivers have been registered so that
	 * resolve_mailer() can immediately find them.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function apply(): void {
		add_filter( 'pre_wp_mail', array( $this, 'intercept' ), 10, 2 );
	}

	/**
	 * Remove the intercept filter, restoring default PHPMailer behaviour.
	 *
	 * Useful in tests that need to send mail through PHPMailer or when
	 * deliberately disabling SendStack for a single send.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function reset(): void {
		remove_filter( 'pre_wp_mail', array( $this, 'intercept' ), 10 );
	}

	/**
	 * Intercept a wp_mail() call and route it through MailerManager.
	 *
	 * Returns true  — delivery handled successfully.
	 * Returns false — delivery failed (also fires sendstack_wp_mail_failed).
	 * Returns $return unchanged — SendStack not configured; WordPress continues
	 *                             with its own PHPMailer delivery.
	 *
	 * @since  1.0.0
	 * @param  bool|null $return    Current filter value (null by default from WP).
	 * @param  array     $atts      wp_mail() argument map.
	 * @return bool|null
	 */
	public function intercept( $return, array $atts ) {
		$settings = (array) get_option( 'sendstack_settings', array() );
		$provider = isset( $settings['provider'] ) ? (string) $settings['provider'] : '';

		if ( '' === $provider ) {
			return $return;
		}

		$result = $this->manager->handle_send( $atts );

		if ( $result->is_success() ) {
			return true;
		}

		do_action(
			'sendstack_wp_mail_failed',
			new \WP_Error(
				$result->error_code() ? $result->error_code() : 'sendstack_send_failed',
				$result->error_message()
			)
		);

		return false;
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * WHY pre_wp_mail AND NOT phpmailer_init?
 * phpmailer_init fires after WordPress has already configured the PHPMailer
 * object — headers are parsed, attachments are attached. It is designed for
 * tweaking PHPMailer settings (e.g. enabling SMTPS), not for replacing the
 * entire transport. pre_wp_mail fires before any of that work happens:
 * if the filter returns non-null, wp_mail() exits immediately. This is the
 * correct hook for API-based providers (SendGrid, Mailgun) that don't use
 * PHPMailer at all. SMTP providers could use phpmailer_init, but using the
 * same hook for all drivers keeps the architecture uniform.
 *
 * PRE_WP_MAIL FILTER CONTRACT
 * WordPress checks: if ( null !== $pre ) { return $pre; }
 * • Return null  → WP continues with PHPMailer (pass-through).
 * • Return true  → WP treats the email as sent.
 * • Return false → WP treats delivery as failed.
 * Our intercept() returns $return (typically null) when not configured,
 * true on success, and false on failure.
 *
 * CONFIGURATION GUARD
 * The 'provider' key check prevents SendStack from intercepting mail before
 * the admin has finished the setup wizard. Without this guard, emails sent
 * during the wizard itself (e.g. a test email) would fail silently because
 * no driver has been registered yet.
 *
 * sendstack_wp_mail_failed ACTION
 * WordPress fires wp_mail_failed (different hook) through its own PHPMailer
 * error path. Since we short-circuit PHPMailer, that action never fires for
 * our sends. sendstack_wp_mail_failed provides the same contract — a WP_Error
 * object — so themes/plugins that hook wp_mail_failed can be updated to hook
 * our action with identical callback signatures.
 *
 * intercept() MUST BE public
 * add_filter and remove_filter resolve the callable [object, 'method'] by
 * comparing the method name and object instance. If intercept() were protected
 * or private, remove_filter in reset() would fail to unregister it because
 * WordPress verifies the callable is callable at the point of removal.
 */
