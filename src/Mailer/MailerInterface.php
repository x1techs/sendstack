<?php
/**
 * Contract every mailer driver must satisfy.
 *
 * @package SendStack\Mailer
 * @since   1.0.0
 */

namespace SendStack\Mailer;

defined( 'ABSPATH' ) || exit;

/**
 * Mailer driver interface.
 *
 * Concrete drivers extend AbstractMailer and implement slug(), label(), and
 * do_send(). Everything else (hook firing, logging) is handled by the base.
 *
 * @since 1.0.0
 */
interface MailerInterface {

	/**
	 * Machine-readable identifier (e.g. "smtp", "sendgrid").
	 *
	 * Must be unique across all registered drivers.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function slug(): string;

	/**
	 * Human-readable provider name shown in the settings UI.
	 *
	 * @since  1.0.0
	 * @return string
	 */
	public function label(): string;

	/**
	 * Attempt to deliver a message and return the outcome.
	 *
	 * @since  1.0.0
	 * @param  MailPayload $payload The message to send.
	 * @return SendResult
	 */
	public function send( MailPayload $payload ): SendResult;

	/**
	 * Ping the provider to confirm credentials are valid.
	 *
	 * Returns a successful SendResult when the connection works, or a
	 * failure result with a descriptive error_message when it does not.
	 *
	 * @since  1.0.0
	 * @return SendResult
	 */
	public function verify_connection(): SendResult;

	/**
	 * Return a list of feature keys this driver supports.
	 *
	 * Standard keys: 'html', 'plain', 'attachments', 'custom_headers', 'oauth'.
	 *
	 * @since  1.0.0
	 * @return string[]
	 */
	public function supports(): array;
}

/*
 * ============================================================
 * Concepts in this file
 * ============================================================
 *
 * WHY AN INTERFACE AND NOT JUST AN ABSTRACT CLASS?
 * The interface is the public contract that any third party (or future
 * built-in) provider must satisfy. It documents the minimum API surface
 * without coupling callers to any particular base class. MailerManager only
 * knows about MailerInterface, so a driver can extend something else entirely.
 *
 * verify_connection() RETURNS SendResult, NOT bool
 * Returning bool throws away the reason for failure. The admin UI needs to
 * show the user WHY a connection failed ("invalid API key", "network timeout",
 * etc.). A rich value object costs nothing compared to debugging blind errors.
 *
 * supports() AS AN ARRAY OF STRINGS
 * Rather than a boolean matrix of capabilities, a string bag lets drivers
 * advertise arbitrary features and lets the UI gate UI affordances on them
 * without hardcoding knowledge of every possible feature.
 */
