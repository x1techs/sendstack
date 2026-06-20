<?php
/**
 * Slack alert channel.
 *
 * @package SendStack\Alerts\Channels
 * @since   1.0.0
 */

namespace SendStack\Alerts\Channels;

defined( 'ABSPATH' ) || exit;

use SendStack\Alerts\AlertEvent;
use SendStack\Alerts\AlertInterface;

/**
 * Delivers alerts to a Slack channel via an Incoming Webhook URL.
 *
 * @since 1.0.0
 */
class SlackChannel implements AlertInterface {

	/** @var string Option key for the webhook URL. */
	private const OPTION_WEBHOOK = 'sendstack_alert_slack_webhook_url';

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'slack';
	}

	/**
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'Slack', 'sendstack' );
	}

	/**
	 * POST a formatted message to the Slack Incoming Webhook.
	 *
	 * @since  1.0.0
	 * @param  AlertEvent $event Alert to deliver.
	 * @return bool True on HTTP 200 response.
	 */
	public function send( AlertEvent $event ): bool {
		$webhook_url = (string) get_option( self::OPTION_WEBHOOK, '' );

		if ( '' === $webhook_url ) {
			return false;
		}

		$body = wp_json_encode(
			array(
				'text'        => "*{$event->title()}*\n{$event->message()}",
				'attachments' => array(
					array(
						'color'  => 'danger',
						'footer' => 'SendStack',
						'ts'     => $event->occurred_at(),
						'fields' => $this->context_to_fields( $event->context() ),
					),
				),
			)
		);

		$response = wp_remote_post(
			$webhook_url,
			array(
				'headers' => array( 'Content-Type' => 'application/json' ),
				'body'    => $body,
			)
		);

		if ( is_wp_error( $response ) ) {
			return false;
		}

		return 200 === wp_remote_retrieve_response_code( $response );
	}

	/**
	 * Validate that the webhook URL is an HTTPS Slack URL.
	 *
	 * @since  1.0.0
	 * @param  array $config Submitted config; expects key 'webhook_url'.
	 * @return bool
	 */
	public function validate_config( array $config ): bool {
		$url = (string) ( $config['webhook_url'] ?? '' );

		return '' !== $url
			&& filter_var( $url, FILTER_VALIDATE_URL ) !== false
			&& str_starts_with( $url, 'https://hooks.slack.com/' );
	}

	/**
	 * Convert a flat context array to Slack attachment fields.
	 *
	 * @since  1.0.0
	 * @param  array<string, mixed> $context Event context.
	 * @return array<int, array<string, string>>
	 */
	private function context_to_fields( array $context ): array {
		$fields = array();

		foreach ( $context as $key => $value ) {
			$fields[] = array(
				'title' => ucfirst( str_replace( '_', ' ', $key ) ),
				'value' => (string) $value,
				'short' => true,
			);
		}

		return $fields;
	}
}
