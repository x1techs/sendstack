<?php
/**
 * Unit tests for the WordPress PHPMailer factory.
 *
 * @package SendStack\Tests\Unit\Mailer
 * @since   0.1.0
 */

namespace SendStack\Tests\Unit\Mailer;

use PHPMailer\PHPMailer\Exception as PhpMailerException;
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use SendStack\Mailer\WordPressPhpMailerFactory;

/**
 * Verifies that SendStack can load WordPress's bundled PHPMailer on demand.
 */
class WordPressPhpMailerFactoryTest extends TestCase {

	/**
	 * Verify that all WordPress-bundled transport classes are loaded.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_create_loads_wordpress_phpmailer_dependencies(): void {
		$this->assertFalse( class_exists( PHPMailer::class, false ) );

		$factory   = new WordPressPhpMailerFactory( $this->fixture_includes_path() );
		$phpmailer = $factory->create();

		$this->assertInstanceOf( PHPMailer::class, $phpmailer );
		$this->assertTrue( class_exists( SMTP::class, false ) );
		$this->assertTrue( class_exists( PhpMailerException::class, false ) );
	}

	/**
	 * Verify that missing WordPress dependency files produce a catchable error.
	 *
	 * @runInSeparateProcess
	 * @preserveGlobalState disabled
	 */
	public function test_create_throws_when_dependency_files_are_unavailable(): void {
		$factory = new WordPressPhpMailerFactory( '/sendstack/missing/wp-includes' );

		$this->expectException( RuntimeException::class );
		$this->expectExceptionMessage( 'WordPress PHPMailer dependency is not readable' );

		$factory->create();
	}

	/**
	 * Return the tracked WordPress-layout includes fixture.
	 */
	private function fixture_includes_path(): string {
		return dirname( __DIR__, 2 ) . '/Fixtures/wordpress/wp-includes';
	}
}
