<?php
/**
 * REST controller for test email dispatch.
 *
 * @package SendStack\API\Controllers
 * @since   1.0.0
 */

namespace SendStack\API\Controllers;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\MailerManager;
use SendStack\Mailer\MailPayload;

/**
 * Handles POST /test-email.
 *
 * @since 1.0.0
 */
class TestController extends AbstractController {

	/** @var string */
	protected $rest_base = 'test-email';

	/** @var MailerManager */
	private $mailer_manager;

	/**
	 * @param MailerManager $mailer_manager Used to dispatch the test message.
	 */
	public function __construct( MailerManager $mailer_manager ) {
		$this->mailer_manager = $mailer_manager;
	}

	/**
	 * Register /test-email route.
	 *
	 * @since  1.0.0
	 * @return void
	 */
	public function register_routes(): void {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => \WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'send' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'recipient' => array(
							'type'              => 'string',
							'format'            => 'email',
							'required'          => true,
							'sanitize_callback' => 'sanitize_email',
							'validate_callback' => 'is_email',
						),
						'provider'  => array(
							'type'        => 'string',
							'description' => __( 'Override the active mailer for this test.', 'sendstack' ),
						),
					),
				),
			)
		);
	}

	/**
	 * POST /sendstack/v1/test-email
	 *
	 * @since  1.0.0
	 * @param  \WP_REST_Request $request Request object.
	 * @return \WP_REST_Response|\WP_Error
	 */
	public function send( $request ) {
		$recipient = (string) $request->get_param( 'recipient' );

		$payload = MailPayload::from_args(
			array(
				'to'      => array( $recipient ),
				'subject' => __( 'SendStack Test Email', 'sendstack' ),
				'message' => __( 'This is a test email sent via the SendStack REST API. If you are reading this, email delivery is working correctly.', 'sendstack' ),
			)
		);

		// Allow a one-off provider override for this test.
		$slug      = (string) $request->get_param( 'provider' );
		$providers = $this->mailer_manager->available_providers();

		if ( '' !== $slug && isset( $providers[ $slug ] ) ) {
			$result = $providers[ $slug ]->send( $payload );
		} else {
			$result = $this->mailer_manager->handle_send( $payload );
		}

		if ( $result->is_success() ) {
			return $this->ok(
				array(
					'sent'      => true,
					'recipient' => $recipient,
				)
			);
		}

		return $this->error( 'sendstack_test_failed', $result->error_message() );
	}
}
