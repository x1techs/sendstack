<?php
/**
 * Minimal PHPMailer fixture used by isolated unit tests.
 *
 * @package SendStack\Tests\Fixtures
 */

namespace PHPMailer\PHPMailer;

/**
 * Provides the PHPMailer surface exercised by SendStack's unit tests.
 */
class PHPMailer {

	/** @var callable|string */
	public static $validator = 'filter_var';

	/** @var string */
	public $Subject = '';

	/** @var string */
	public $Body = '';

	/** @var string */
	public $AltBody = '';

	/** @var string */
	public $ErrorInfo = '';

	/** @var int */
	public $SMTPDebug = 0;

	/** @var string */
	public $Host = '';

	/** @var int */
	public $Port = 0;

	/** @var string */
	public $SMTPSecure = '';

	/** @var bool */
	public $SMTPAutoTLS = true;

	/** @var bool */
	public $SMTPAuth = false;

	/** @var string */
	public $Username = '';

	/** @var string */
	public $Password = '';

	/**
	 * Accept the upstream exception-mode constructor argument.
	 *
	 * @param bool $exceptions Whether transport errors should throw.
	 */
	public function __construct( bool $exceptions = false ) {}

	/** Configure SMTP transport. */
	public function isSMTP(): void {}

	/** Configure the sender. */
	public function setFrom( string $address, string $name = '' ): bool {
		return true;
	}

	/** Add a recipient. */
	public function addAddress( string $address, string $name = '' ): bool {
		return true;
	}

	/** Send the message. */
	public function send(): bool {
		return true;
	}

	/** Return the last message identifier. */
	public function getLastMessageID(): string {
		return '';
	}

	/** Open the SMTP connection. */
	public function smtpConnect(): bool {
		return true;
	}

	/** Close the SMTP connection. */
	public function smtpClose(): void {}
}
