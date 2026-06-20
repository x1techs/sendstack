<?php
/**
 * Email alert channel.
 *
 * @package SendStack\Alerts\Channels
 * @since   1.0.0
 */

namespace SendStack\Alerts\Channels;

defined( 'ABSPATH' ) || exit;

use SendStack\Alerts\AlertEvent;
use SendStack\Alerts\AlertInterface;

/**
 * Delivers alerts via wp_mail() to the configured recipient address.
 *
 * @since 1.0.0
 */
class EmailChannel implements AlertInterface {

	/** @var string Option key for the recipient address. */
	private const OPTION_RECIPIENT = 'sendstack_alert_email_recipient';

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'email';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Email', 'sendstack' );
	}

	/**
	 * Send the alert via wp_mail().
	 *
	 * @since  1.0.0
	 * @param  AlertEvent $event Alert to deliver.
	 * @return bool
	 */
	public function send( AlertEvent $event ): bool {
		$recipient = (string) get_option( self::OPTION_RECIPIENT, get_option( 'admin_email', '' ) );

		if ( '' === $recipient ) {
			return false;
		}

		$subject = sprintf(
			/* translators: %s: alert title */
			__( '[SendStack Alert] %s', 'sendstack' ),
			$event->title()
		);

		$body = $event->message();

		if ( ! empty( $event->context() ) ) {
			$body .= "\n\n" . wp_json_encode( $event->context(), JSON_PRETTY_PRINT );
		}

		return wp_mail( $recipient, $subject, $body );
	}

	/**
	 * Validate that the recipient is a valid email address.
	 *
	 * @since  1.0.0
	 * @param  array $config Submitted config; expects key 'recipient'.
	 * @return bool
	 */
	public function validate_config( array $config ): bool {
		return isset( $config['recipient'] ) && is_email( $config['recipient'] );
	}
}
