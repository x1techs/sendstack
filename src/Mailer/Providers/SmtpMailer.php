<?php
/**
 * Generic SMTP mailer driver.
 *
 * @package SendStack\Mailer\Providers
 * @since   1.0.0
 */

namespace SendStack\Mailer\Providers;

defined( 'ABSPATH' ) || exit;

use SendStack\Mailer\AbstractMailer;
use SendStack\Mailer\MailPayload;
use SendStack\Mailer\SendResult;
use SendStack\Mailer\WordPressPhpMailerFactory;

// PHPMailer exposes these public transport properties with upstream camel-case names.
// phpcs:disable WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase

/**
 * Sends mail via a user-configured SMTP server using PHPMailer directly.
 *
 * WordPress bundles PHPMailer; we instantiate it ourselves (bypassing wp_mail)
 * so that we own the full transport configuration without creating an infinite
 * loop through the pre_wp_mail filter.
 *
 * @since 1.0.0
 */
class SmtpMailer extends AbstractMailer {

	/**
	 * Factory that loads and constructs WordPress's bundled PHPMailer.
	 *
	 * @since 0.1.0
	 * @var WordPressPhpMailerFactory
	 */
	private $phpmailer_factory;

	/**
	 * Store SMTP settings and the WordPress PHPMailer factory.
	 *
	 * @since 0.1.0
	 * @param array<string,mixed>            $options           SMTP connection settings.
	 * @param WordPressPhpMailerFactory|null $phpmailer_factory Optional factory override.
	 */
	public function __construct(
		array $options = array(),
		?WordPressPhpMailerFactory $phpmailer_factory = null
	) {
		parent::__construct( $options );

		$this->phpmailer_factory = null !== $phpmailer_factory
			? $phpmailer_factory
			: new WordPressPhpMailerFactory();
	}

	/**
	 * Return the SMTP provider slug.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string {
		return 'smtp';
	}

	/**
	 * Return the translated SMTP provider label.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string {
		return __( 'SMTP', 'sendstack' );
	}

	/**
	 * Return the message features supported by SMTP delivery.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public function supports(): array {
		return array( 'html', 'plain', 'attachments', 'custom_headers' );
	}

	// -------------------------------------------------------------------------
	// Transport
	// -------------------------------------------------------------------------

	/**
	 * Deliver the message via SMTP using a directly instantiated PHPMailer.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload Message to deliver.
	 * @return SendResult
	 */
	protected function do_send( MailPayload $payload ): SendResult {
		if ( '' === $this->get_option( 'host', '' ) ) {
			return SendResult::failure(
				__( 'SMTP host is not configured.', 'sendstack' ),
				'missing_host'
			);
		}

		try {
			$phpmailer = $this->make_phpmailer();
		} catch ( \Throwable $e ) {
			return SendResult::failure(
				$e->getMessage(),
				'phpmailer_initialization_failed'
			);
		}

		try {
			// Sender — payload wins; fall back to stored option, then WP defaults.
			$from_email = $payload->from_email();
			if ( '' === $from_email ) {
				$from_email = (string) $this->get_option( 'from_email', get_option( 'admin_email' ) );
			}

			$from_name = $payload->from_name();
			if ( '' === $from_name ) {
				$from_name = (string) $this->get_option( 'from_name', get_option( 'blogname' ) );
			}

			$phpmailer->setFrom( $from_email, $from_name );

			// Recipients.
			foreach ( $payload->to() as $address ) {
				$parsed = $this->parse_address( $address );
				$phpmailer->addAddress( $parsed['email'], $parsed['name'] );
			}

			foreach ( $payload->cc() as $address ) {
				$parsed = $this->parse_address( $address );
				$phpmailer->addCC( $parsed['email'], $parsed['name'] );
			}

			foreach ( $payload->bcc() as $address ) {
				$parsed = $this->parse_address( $address );
				$phpmailer->addBCC( $parsed['email'], $parsed['name'] );
			}

			foreach ( $payload->reply_to() as $address ) {
				$parsed = $this->parse_address( $address );
				$phpmailer->addReplyTo( $parsed['email'], $parsed['name'] );
			}

			$phpmailer->Subject = $payload->subject();

			// Body — HTML or plain text.
			if ( false !== strpos( $payload->content_type(), 'html' ) ) {
				$phpmailer->isHTML( true );
				$phpmailer->Body    = $payload->body();
				$phpmailer->AltBody = wp_strip_all_tags( $payload->body() );
			} else {
				$phpmailer->Body = $payload->body();
			}

			// Attachments — skip any that are unreadable.
			foreach ( $payload->attachments() as $path ) {
				if ( is_readable( $path ) ) {
					$phpmailer->addAttachment( $path );
				}
			}

			// Custom headers — $payload->headers() already excludes promoted ones
			// (From, Content-Type, Cc, Bcc, Reply-To) extracted during parsing.
			foreach ( $payload->headers() as $name => $value ) {
				$phpmailer->addCustomHeader( $name, $value );
			}

			/**
			 * Allow integrations to customize the configured transport.
			 *
			 * @var \PHPMailer\PHPMailer\PHPMailer $phpmailer
			 */
			$phpmailer = apply_filters( 'sendstack_smtp_phpmailer', $phpmailer, $payload );

			$phpmailer->send();

			return SendResult::success( $phpmailer->getLastMessageID() );

		} catch ( \PHPMailer\PHPMailer\Exception $e ) {
			return SendResult::failure(
				$e->getMessage(),
				'smtp_error',
				array( 'smtp_code' => $phpmailer->ErrorInfo )
			);
		} catch ( \Throwable $e ) {
			return SendResult::failure( $e->getMessage(), 'unexpected_error' );
		}
	}

	// -------------------------------------------------------------------------
	// Connection verification
	// -------------------------------------------------------------------------

	/**
	 * Open a test connection to the configured SMTP host without sending mail.
	 *
	 * @since  1.0.0
	 * @return SendResult
	 */
	public function verify_connection(): SendResult {
		if ( '' === $this->get_option( 'host', '' ) ) {
			return SendResult::failure(
				__( 'SMTP host is not configured.', 'sendstack' ),
				'missing_host'
			);
		}

		try {
			$phpmailer = $this->make_phpmailer();
		} catch ( \Throwable $e ) {
			return SendResult::failure(
				$e->getMessage(),
				'phpmailer_initialization_failed'
			);
		}

		$phpmailer->SMTPDebug = 0;

		try {
			if ( $phpmailer->smtpConnect() ) {
				$phpmailer->smtpClose();
				$result = SendResult::success( '', array( 'connected' => true ) );
			} else {
				$result = SendResult::failure(
					__( 'Could not connect to SMTP server.', 'sendstack' ),
					'connection_failed',
					array( 'smtp_info' => $phpmailer->ErrorInfo )
				);
			}
		} catch ( \PHPMailer\PHPMailer\Exception $e ) {
			$result = SendResult::failure( $e->getMessage(), 'smtp_error' );
		} catch ( \Throwable $e ) {
			$result = SendResult::failure( $e->getMessage(), 'unexpected_error' );
		}

		do_action( 'sendstack_smtp_connection_test', $result );

		return $result;
	}

	// -------------------------------------------------------------------------
	// Private helpers
	// -------------------------------------------------------------------------

	/**
	 * Instantiate and configure a PHPMailer object with the stored SMTP settings.
	 *
	 * Shared by do_send() and verify_connection() so transport configuration
	 * lives in exactly one place.
	 *
	 * @since  1.0.0
	 * @return \PHPMailer\PHPMailer\PHPMailer
	 */
	private function make_phpmailer(): \PHPMailer\PHPMailer\PHPMailer {
		$phpmailer = $this->phpmailer_factory->create();
		$phpmailer->isSMTP();

		$phpmailer->Host = (string) $this->get_option( 'host', '' );
		$phpmailer->Port = (int) $this->get_option( 'port', 587 );

		$encryption = (string) $this->get_option( 'encryption', 'tls' );
		if ( 'none' === $encryption || '' === $encryption ) {
			// Relay servers that don't use TLS at all — disable auto-upgrade.
			$phpmailer->SMTPSecure  = '';
			$phpmailer->SMTPAutoTLS = false;
		} else {
			// Use the secure transport selected in the SMTP settings.
			$phpmailer->SMTPSecure = $encryption;
		}

		$username            = (string) $this->get_option( 'username', '' );
		$phpmailer->SMTPAuth = ! empty( $username );
		$phpmailer->Username = $username;
		$phpmailer->Password = (string) $this->get_option( 'password', '' );

		return $phpmailer;
	}

	/**
	 * Split an RFC-ish address string into email + display name parts.
	 *
	 * Handles three formats:
	 *   "Display Name <email@example.com>"
	 *   "<email@example.com>"
	 *   "email@example.com"
	 *
	 * @since  1.0.0
	 * @param  string $address Raw address string.
	 * @return array{email: string, name: string}
	 */
	private function parse_address( string $address ): array {
		$address = trim( $address );

		if ( preg_match( '/^"?([^"<]*)"?\s*<([^>]+)>\s*$/', $address, $matches ) ) {
			return array(
				'email' => trim( $matches[2] ),
				'name'  => trim( $matches[1] ),
			);
		}

		return array(
			'email' => $address,
			'name'  => '',
		);
	}
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * WHY do_send() AND NOT send()
 * AbstractMailer owns the send() pipeline: before-hook → do_send() → log →
 * after-hook. SmtpMailer only implements do_send() — the raw transport step.
 * This guarantees every driver fires the same hooks without copy-pasting.
 *
 * WHY WE DO NOT CALL wp_mail() INSIDE do_send()
 * PhpMailerOverride intercepts pre_wp_mail to route sends here. If do_send()
 * called wp_mail(), that call would hit the same filter and re-enter this
 * method — an infinite loop. Instantiating PHPMailer directly and calling
 * $phpmailer->send() sidesteps the WordPress mail pipeline entirely.
 *
 * make_phpmailer() EXTRACTED AS PRIVATE HELPER
 * do_send() and verify_connection() need identical SMTP transport config.
 * Extracting it removes duplication and ensures the settings we test with
 * verify_connection() are always the same settings we send with.
 *
 * ENCRYPTION EDGE CASE — 'none' OR EMPTY STRING
 * When SMTPSecure is set to '' but SMTPAutoTLS is left true, PHPMailer still
 * tries to upgrade the connection via STARTTLS. Setting SMTPAutoTLS = false
 * explicitly disables that for relay servers that do not advertise STARTTLS.
 * Without this, plain-port-25 relay connections fail with a TLS negotiation
 * error even though the admin intentionally chose "No encryption".
 *
 * sendstack_smtp_phpmailer FILTER
 * Applied after all our configuration but before $phpmailer->send(). Third-
 * party code (e.g. a plugin that needs to set SMTP options PHPMailer exposes
 * but we don't surface in our UI) can hook here. Passing $payload gives the
 * hook context about which message is being sent so it can make per-message
 * decisions (e.g. swap SMTP credentials for a specific "from" domain).
 *
 * parse_address() INSTEAD OF PHPMailer::parseAddresses()
 * PHPMailer has its own address parser, but it returns a different array shape
 * and is designed for comma-separated lists. Our payload always contains one
 * address per array element (MailPayload splits on commas during header
 * parsing). A local regex avoids adding an API dependency on PHPMailer internals.
 *
 * $payload->headers() CONTAINS ONLY UNHANDLED HEADERS
 * MailPayload::parse_headers() already promotes From, Content-Type, Cc, Bcc,
 * and Reply-To into typed properties before storing the remainder in headers().
 * We therefore add all remaining headers to PHPMailer as custom headers without
 * needing to filter them again.
 */
