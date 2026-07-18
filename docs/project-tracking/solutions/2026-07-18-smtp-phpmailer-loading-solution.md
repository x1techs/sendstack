# WordPress PHPMailer Loading for SMTP Delivery

**Implementation date:** 2026-07-18
**Status:** Implemented, locally verified, and committed
**Commit:** `59a2f96`
**Pull request:** Pending
**Audit finding:** SMTP provider blocking runtime failure in the [features 1-6 implementation audit](../audits/2026-07-17-features-1-6-implementation-audit.md#blocking-runtime-failure)

## Problem

SendStack intercepts outbound messages on WordPress's `pre_wp_mail` filter and
routes them through its provider manager. The SMTP provider constructed
`PHPMailer\PHPMailer\PHPMailer` directly during that interception.

WordPress invokes `pre_wp_mail` before `wp_mail()` loads its bundled PHPMailer
class files. On a clean request, SendStack therefore stopped with:

```text
Class 'PHPMailer\PHPMailer\PHPMailer' not found
```

The same construction helper was used by SMTP connection verification, so that
path could fail for the same reason.

## Root cause

The SMTP provider relied on PHPMailer being loaded as an undocumented request
precondition. That precondition is not valid on `pre_wp_mail`, because WordPress
only loads PHPMailer later if no filter short-circuits the normal mail path.

PHPMailer construction also occurred outside the provider's `try` blocks. A
loader or constructor failure consequently escaped the normal `SendResult`
contract instead of becoming a controlled provider failure.

## Decision

Keep the `pre_wp_mail` architecture and add an explicit WordPress-aware factory
as the only PHPMailer construction path.

Changing the SMTP provider to `phpmailer_init` was rejected because SendStack's
provider selection, hooks, logging, and future failover behavior are built
around `MailerManager` short-circuiting the original `wp_mail()` call.

The factory uses the base `PHPMailer` class bundled with WordPress rather than
`WP_PHPMailer`. SendStack supports WordPress 6.2, while `WP_PHPMailer` was added
in WordPress 6.8. No production Composer dependency was added, avoiding a second
PHPMailer version that could conflict with WordPress or another plugin.

## Implementation

### Added

- [`src/Mailer/WordPressPhpMailerFactory.php`](../../../src/Mailer/WordPressPhpMailerFactory.php)
  - Loads WordPress's bundled `PHPMailer`, `SMTP`, and `Exception` classes only
    when they are not already available.
  - Produces a fresh exception-enabled PHPMailer instance.
  - Uses WordPress's `is_email()` validator, matching core behavior.
  - Throws a catchable `RuntimeException` when a dependency cannot be loaded.

### Updated

- [`src/Mailer/Providers/SmtpMailer.php`](../../../src/Mailer/Providers/SmtpMailer.php)
  - Accepts an optional injectable factory while preserving the existing
    one-argument constructor behavior.
  - Uses the factory for both message delivery and connection verification.
  - Converts construction failures into `SendResult::failure()` with the
    `phpmailer_initialization_failed` error code.
- [`src/Mailer/MailerServiceProvider.php`](../../../src/Mailer/MailerServiceProvider.php)
  - Explicitly supplies the WordPress factory to the built-in SMTP provider.
- [`tests/bootstrap.php`](../../../tests/bootstrap.php)
  - Defines the WordPress `WPINC` constant for isolated unit tests.

### Tests added

- [`tests/Unit/Mailer/WordPressPhpMailerFactoryTest.php`](../../../tests/Unit/Mailer/WordPressPhpMailerFactoryTest.php)
  - Proves a clean PHP process loads all three dependencies from an isolated,
    tracked WordPress-layout fixture suitable for fresh CI checkouts.
  - Proves missing dependency files produce a catchable initialization error.
- [`tests/Unit/Mailer/Providers/SmtpMailerTest.php`](../../../tests/Unit/Mailer/Providers/SmtpMailerTest.php)
  - Proves send and connection verification obtain PHPMailer through the
    factory.
  - Proves initialization failures return controlled results on both paths.

## Acceptance criteria

- [x] A clean `pre_wp_mail` request does not require PHPMailer to be preloaded.
- [x] WordPress's bundled PHPMailer files are used.
- [x] WordPress 6.2 and PHP 7.4 compatibility are preserved.
- [x] Message sending and connection verification share one construction path.
- [x] Initialization failures no longer escape as fatal class-loading errors.
- [x] No production PHPMailer Composer dependency is introduced.
- [x] Unit tests cover loading, use of the factory, and failure behavior.
- [x] A real local `wp_mail()` call completes through SendStack SMTP.

## Verification performed

### Focused regression suite

```text
Tests: 6
Assertions: 20
Result: OK
```

### Complete PHPUnit suite

```text
Tests: 10
Assertions: 24
Skipped: 1
Result: OK (one integration placeholder skipped)
```

Focused PHPCS passed for every PHP file changed by this solution. It prints the
repository's existing PHPCS configuration deprecation notices but reports no
violation in the touched files.

Focused PHPStan passed:

```text
[OK] No errors
```

Both changed source files also passed `php -l` in the PHP 7.4 DDEV container.

### Runtime SMTP smoke test

A temporary WP-CLI smoke script replaced the in-memory SMTP registration with
DDEV's local mail-capture endpoint at `127.0.0.1:1025`. It supplied the active
provider through a request-only option filter, so no database setting or real
credential was changed.

The script then called the real `wp_mail()` function. Its result was:

```json
{
  "loaded_before_wp_mail": false,
  "loaded_after_wp_mail": true,
  "wp_mail_result": true
}
```

This verifies the original failure condition and the complete corrected path:
PHPMailer was absent before `pre_wp_mail`, SendStack loaded it, and the local
SMTP service accepted the message.

## Remaining risks and follow-up

- SMTP credentials are still stored in plaintext inside `sendstack_settings`.
  That is a separate security problem and must be fixed through
  `CredentialStore` on its own branch and pull request.
- Mailer contract drift, AJAX resend/verification, failover, and retry behavior
  remain separate audited findings.
- A permanent integration suite should eventually replace the temporary
  runtime smoke script with an isolated WordPress/Mailpit fixture in CI.
