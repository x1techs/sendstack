<?php
/**
 * WordPress PHPMailer factory.
 *
 * @package SendStack\Mailer
 * @since   0.1.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use RuntimeException;

/**
 * Loads WordPress's bundled PHPMailer classes and creates mailer instances.
 *
 * WordPress invokes the pre_wp_mail filter before wp_mail() loads PHPMailer.
 * SendStack short-circuits on that filter, so it must load the same bundled
 * dependencies before constructing its own transport instance.
 *
 * @since 0.1.0
 */
class WordPressPhpMailerFactory {

	/**
	 * Absolute path to the WordPress includes directory.
	 *
	 * @since 0.1.0
	 * @var string
	 */
	private $includes_path;

	/**
	 * Store the WordPress includes directory used to load PHPMailer.
	 *
	 * The optional path supports isolated tests without changing WordPress
	 * globals. Production callers use ABSPATH and WPINC automatically.
	 *
	 * @since 0.1.0
	 * @param string $includes_path Optional absolute WordPress includes path.
	 * @throws RuntimeException When the WordPress includes path is unavailable.
	 */
	public function __construct( string $includes_path = '' ) {
		if ( '' === $includes_path ) {
			if ( ! defined( 'WPINC' ) ) {
				throw new RuntimeException( 'WordPress includes path is unavailable.' );
			}

			$includes_path = ABSPATH . WPINC;
		}

		$this->includes_path = rtrim( $includes_path, '/\\' );
	}

	/**
	 * Create a PHPMailer instance backed by WordPress's bundled classes.
	 *
	 * @since 0.1.0
	 * @return PHPMailer
	 * @throws RuntimeException When a required PHPMailer dependency is unavailable.
	 */
	public function create(): PHPMailer {
		$this->load_dependencies();

		$phpmailer = new PHPMailer( true );

		// Match WordPress core's email-address validation behavior.
		$phpmailer::$validator = static function ( $email ): bool {
			return (bool) is_email( $email );
		};

		return $phpmailer;
	}

	/**
	 * Load each PHPMailer class that WordPress normally loads after pre_wp_mail.
	 *
	 * @since 0.1.0
	 * @return void
	 * @throws RuntimeException When a required PHPMailer dependency is unavailable.
	 */
	private function load_dependencies(): void {
		$this->load_class( PHPMailer::class, 'PHPMailer/PHPMailer.php' );
		$this->load_class( SMTP::class, 'PHPMailer/SMTP.php' );
		$this->load_class( PhpMailerException::class, 'PHPMailer/Exception.php' );
	}

	/**
	 * Load one WordPress-bundled class unless another compatible loader did so.
	 *
	 * @since 0.1.0
	 * @param string $class_name    Fully qualified class name.
	 * @param string $relative_path Path relative to the WordPress includes dir.
	 * @return void
	 * @throws RuntimeException When the dependency file cannot load its class.
	 */
	private function load_class( string $class_name, string $relative_path ): void {
		if ( class_exists( $class_name ) ) {
			return;
		}

		$file = $this->includes_path . '/' . $relative_path;

		if ( ! is_readable( $file ) ) {
			throw new RuntimeException(
				'WordPress PHPMailer dependency is not readable.'
			);
		}

		require_once $file;

		if ( ! class_exists( $class_name, false ) ) {
			throw new RuntimeException(
				'WordPress PHPMailer dependency did not define its expected class.'
			);
		}
	}
}
