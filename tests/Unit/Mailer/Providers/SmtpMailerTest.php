<?php
/**
 * Unit tests for the SMTP mailer provider.
 *
 * @package SendStack\Tests\Unit\Mailer\Providers
 * @since   0.1.0
 */

namespace SendStack\Tests\Unit\Mailer\Providers;

use Brain\Monkey;
use Brain\Monkey\Functions;
use PHPMailer\PHPMailer\PHPMailer;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SendStack\Mailer\MailPayload;
use SendStack\Mailer\Providers\SmtpMailer;
use SendStack\Mailer\WordPressPhpMailerFactory;

/**
 * Verifies PHPMailer initialization for send and connection-test paths.
 */
class SmtpMailerTest extends TestCase {

	/**
	 * Set up WordPress function mocks.
	 */
	protected function setUp(): void {
		parent::setUp();
		Monkey\setUp();

		Functions\when( '__' )->alias(
			static function ( string $message ): string {
				return $message;
			}
		);
		Functions\when( 'do_action' )->justReturn( null );
		Functions\when( 'apply_filters' )->alias(
			static function ( string $hook, $value ) {
				return $value;
			}
		);
	}

	/**
	 * Tear down WordPress function mocks.
	 */
	protected function tearDown(): void {
		Monkey\tearDown();
		parent::tearDown();
	}

	/**
	 * Verify that a successful send obtains PHPMailer from the factory.
	 */
	public function test_send_uses_phpmailer_factory(): void {
		$phpmailer = $this->make_phpmailer_mock(
			array( 'isSMTP', 'setFrom', 'addAddress', 'send', 'getLastMessageID' )
		);
		$phpmailer->expects( $this->once() )->method( 'send' )->willReturn( true );
		$phpmailer->method( 'getLastMessageID' )->willReturn( 'smtp-message-id' );

		$factory = $this->createMock( WordPressPhpMailerFactory::class );
		$factory->expects( $this->once() )->method( 'create' )->willReturn( $phpmailer );

		$mailer = new SmtpMailer( $this->smtp_options(), $factory );
		$result = $mailer->send( $this->payload() );

		$this->assertTrue( $result->is_success() );
		$this->assertSame( 'smtp-message-id', $result->message_id() );
	}

	/**
	 * Verify that the connection test obtains PHPMailer from the factory.
	 */
	public function test_verify_connection_uses_phpmailer_factory(): void {
		$phpmailer = $this->make_phpmailer_mock(
			array( 'isSMTP', 'smtpConnect', 'smtpClose' )
		);
		$phpmailer->expects( $this->once() )->method( 'smtpConnect' )->willReturn( true );
		$phpmailer->expects( $this->once() )->method( 'smtpClose' );

		$factory = $this->createMock( WordPressPhpMailerFactory::class );
		$factory->expects( $this->once() )->method( 'create' )->willReturn( $phpmailer );

		$mailer = new SmtpMailer( $this->smtp_options(), $factory );
		$result = $mailer->verify_connection();

		$this->assertTrue( $result->is_success() );
		$this->assertSame( array( 'connected' => true ), $result->provider_response() );
	}

	/**
	 * Verify that send initialization errors are returned instead of thrown.
	 */
	public function test_send_returns_failure_when_phpmailer_cannot_initialize(): void {
		$factory = $this->failing_factory();
		$mailer  = new SmtpMailer( $this->smtp_options(), $factory );

		$result = $mailer->send( $this->payload() );

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'phpmailer_initialization_failed', $result->error_code() );
		$this->assertSame( 'PHPMailer dependencies are unavailable.', $result->error_message() );
	}

	/**
	 * Verify connection-test initialization errors are returned instead of thrown.
	 */
	public function test_verify_connection_returns_failure_when_phpmailer_cannot_initialize(): void {
		$factory = $this->failing_factory();
		$mailer  = new SmtpMailer( $this->smtp_options(), $factory );

		$result = $mailer->verify_connection();

		$this->assertFalse( $result->is_success() );
		$this->assertSame( 'phpmailer_initialization_failed', $result->error_code() );
		$this->assertSame( 'PHPMailer dependencies are unavailable.', $result->error_message() );
	}

	/**
	 * Create a PHPMailer mock after loading WordPress's bundled class files.
	 *
	 * @param string[] $methods Methods replaced by the mock.
	 * @return PHPMailer&MockObject
	 */
	private function make_phpmailer_mock( array $methods ): PHPMailer {
		$factory = new WordPressPhpMailerFactory(
			dirname( __DIR__, 3 ) . '/Fixtures/wordpress/wp-includes'
		);
		$factory->create();

		/** @var PHPMailer&MockObject $phpmailer */
		$phpmailer = $this->getMockBuilder( PHPMailer::class )
			->disableOriginalConstructor()
			->onlyMethods( $methods )
			->getMock();

		return $phpmailer;
	}

	/**
	 * Create a factory that simulates an unavailable WordPress dependency.
	 *
	 * @return WordPressPhpMailerFactory&MockObject
	 */
	private function failing_factory(): WordPressPhpMailerFactory {
		$factory = $this->createMock( WordPressPhpMailerFactory::class );
		$factory->method( 'create' )->willThrowException(
			new RuntimeException( 'PHPMailer dependencies are unavailable.' )
		);

		return $factory;
	}

	/**
	 * Return the minimum SMTP configuration required by the provider.
	 *
	 * @return array<string,mixed>
	 */
	private function smtp_options(): array {
		return array(
			'host'       => 'smtp.example.test',
			'port'       => 587,
			'encryption' => 'tls',
			'username'   => 'smtp-user',
			'password'   => 'smtp-password',
		);
	}

	/**
	 * Create a minimal SMTP message payload.
	 */
	private function payload(): MailPayload {
		return MailPayload::from_args(
			array(
				'to'      => array( 'recipient@example.test' ),
				'subject' => 'SendStack SMTP test',
				'message' => 'SMTP transport body',
				'headers' => array( 'From: Sender <sender@example.test>' ),
			)
		);
	}
}
