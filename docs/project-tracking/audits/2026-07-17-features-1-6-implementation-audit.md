# Features 1-6 Implementation Audit

**Audit date:** 2026-07-17
**Branch:** `develop`
**Commit audited:** `3840a1d27302c4ddf46a4c8edcda450f21efd8c2`
**Plugin version:** `0.1.0`
**Environment:** DDEV 1.25.3, PHP 7.4, WordPress 7.0.1, MySQL 8.0

## Purpose

This audit verifies the first six items previously reported as done:

1. Core skeleton
2. Database layer
3. Mailer foundation
4. SMTP provider
5. Logger and stats
6. Admin shell

The presence of files or merged feature branches is not treated as proof of
completion. A feature is considered complete only when its main runtime path,
lifecycle behavior, integration contracts, and meaningful tests agree.

## Executive summary

The repository contains substantial implementations for all six items, but the
original completion list overstates their readiness. The admin shell renders
successfully, while the SMTP delivery path is currently broken. The other four
areas are partial because of lifecycle, integration, or test gaps.

| # | Feature | Audit status | Summary |
|---|---|---|---|
| 1 | Core skeleton | Partial | Plugin boots, but lifecycle hooks and provider loading are incomplete. |
| 2 | Database layer | Partial | Tables and indexes exist, but migration tracking is inconsistent and untested. |
| 3 | Mailer foundation | Partial | Core abstractions exist, but multiple consumers use incompatible contracts. |
| 4 | SMTP provider | Broken | SendStack SMTP delivery fatals because PHPMailer is not loaded. |
| 5 | Logger and stats | Partial | Persistence and hooks exist, but no successful SendStack send completes the pipeline. |
| 6 | Admin shell | Verified for shell only | Pages and assets render; several actions behind the UI remain broken or placeholders. |

No audited feature should currently be considered production-ready.

## Resolution updates

The findings below remain unchanged as a point-in-time record. Resolved items
are linked here so later reviews can distinguish the original state from the
current implementation.

- **2026-07-17 — Core problem 1 resolved:** Cron hook names now use shared
  constants, and deactivation was verified to remove all SendStack cron events.
  See [Canonical cron hooks and deactivation cleanup](../solutions/2026-07-17-cron-hook-constants-solution.md).

## Runtime evidence

The following behavior was verified in the local DDEV environment:

- DDEV web and database services were healthy.
- The public site and WordPress login returned HTTP 200.
- SendStack was active at version 0.1.0.
- The Dashboard, Email Logs, Settings, Alerts, and Tools pages returned HTTP 200.
- The SendStack admin CSS and JavaScript returned HTTP 200 and appeared once each on the Dashboard.
- `wp_sendstack_logs` and `wp_sendstack_stats` existed with the expected primary and compound indexes.
- The `sendstack_prune_logs` cron event was scheduled.
- A normal WordPress email reached Mailpit before an SMTP provider was selected.
- A direct SendStack SMTP attempt failed with a missing PHPMailer class and left a queued log row.
- No `sendstack/v1` REST routes were registered.

At the end of the audit, the local SendStack settings selected SMTP with
`127.0.0.1:1025`, no encryption, and no authentication. This is a local
development configuration, not a production credential set.

## 1. Core skeleton

### What works

- `Plugin::instance()` boots and reports version 0.1.0.
- The requirements check passes in the current environment.
- The dependency container resolves registered services.
- Activation created the expected tables and default options.
- Mailer, Logger, Stats, and Admin service providers are registered.

### Problems

The deactivation hook clears cron names that do not match the names used by the
implementations:

- [`Deactivator.php`](../../../src/Core/Deactivator.php) clears `sendstack_log_prune`.
- [`LogPruner.php`](../../../src/Logger/LogPruner.php) schedules `sendstack_prune_logs`.
- `Deactivator.php` clears `sendstack_retry_process`.
- [`RetryQueue.php`](../../../src/Mailer/RetryQueue.php) schedules `sendstack_process_retry_queue`.

As a result, scheduled events can remain after plugin deactivation.

[`Loader.php`](../../../src/Core/Loader.php) currently loads only Mailer, Logger,
Stats, and Admin providers. REST, OAuth, alert, retry, failover, and frontend
subsystems are not fully wired through the loader.

[`Requirements.php`](../../../src/Core/Requirements.php) checks PHP and WordPress
versions but does not implement the blueprint's required-extension checks.

### Verdict

**Partial.** The runtime skeleton exists, but lifecycle and loading behavior must
be corrected and tested before this item is marked complete.

## 2. Database layer

### What works

- `wp_sendstack_logs` exists.
- `wp_sendstack_stats` exists.
- The log table has its primary key and the expected status/date indexes.
- The stats table has its primary key and unique date/provider/status index.
- Logger insertion successfully created a row during the SMTP test.
- Schema, installer, migrator, and baseline migration classes exist.

### Problems

The local database has `sendstack_version=0.1.0`, but the expected
`sendstack_db_version` option is missing. [`Upgrader.php`](../../../src/Core/Upgrader.php)
gates migration work on the plugin version. Once the plugin version is current,
it returns early even when the database migration version is absent. This means
the current state does not self-repair.

The only database integration test is a deliberate skip in
[`SchemaTest.php`](../../../tests/Integration/Database/SchemaTest.php). Fresh
activation, reactivation, migration, rollback, and uninstall behavior are not
covered by meaningful tests.

PHP_CodeSniffer also reports the interpolated `DROP TABLE` statement in
[`Installer.php`](../../../src/Database/Installer.php). The names originate from
a fixed internal list, so direct user-controlled SQL injection is unlikely, but
the implementation still violates the declared prepared-SQL policy and needs an
explicitly safe approach or a carefully documented exception.

### Verdict

**Partial.** The installed schema looks correct, but migration state and lifecycle
correctness are not reliable or tested.

## 3. Mailer foundation

### What works

- `MailerInterface`, `AbstractMailer`, `MailPayload`, `SendResult`,
  `MailerManager`, and `PhpMailerOverride` contain substantive implementations.
- The manager can register and resolve the SMTP provider.
- The normal `pre_wp_mail` interception design is reasonable.
- The payload and result value objects expose typed accessors.

### Problems

[`MailerManager::handle_send()`](../../../src/Mailer/MailerManager.php) accepts a
raw argument array and constructs a `MailPayload` internally. Several consumers
instead construct a `MailPayload` and pass that object into `handle_send()`.
Affected consumers include:

- [`AjaxHandler.php`](../../../src/Admin/Ajax/AjaxHandler.php)
- setup wizard test handling
- REST test and resend controllers
- [`RetryQueue.php`](../../../src/Mailer/RetryQueue.php)

Those paths produce a runtime `TypeError` because an object is passed where an
array is required.

[`FailoverHandler.php`](../../../src/Mailer/FailoverHandler.php) calls
`available_providers()`, which returns display-summary arrays, and then treats a
summary entry as a mailer object by calling `send()` on it.

Retry and failover are not fully registered by the Mailer service provider.

Future Gmail, SendGrid, and Mailgun scaffold classes still implement an older
provider contract. They define incompatible verification return types and do not
implement the current abstract `do_send()` method, so they cannot be safely
loaded in their present form.

### Verdict

**Partial.** The central abstractions are useful, but their consumers and older
stubs must be synchronized with the current contract.

## 4. SMTP provider

### What works

- SMTP settings can be saved through the admin Settings page.
- Host, port, encryption, authentication, sender, recipients, content type,
  headers, and attachments are handled in the provider implementation.
- A connection verification method exists.

### Blocking runtime failure

A direct SendStack SMTP send failed at
[`SmtpMailer.php`](../../../src/Mailer/Providers/SmtpMailer.php) when it attempted
to instantiate `PHPMailer\PHPMailer\PHPMailer`.

WordPress applies the `pre_wp_mail` filter before it loads its PHPMailer class
files. SendStack intercepts mail on that filter and creates PHPMailer immediately,
so the class does not exist yet. The request stops with:

```text
Class 'PHPMailer\PHPMailer\PHPMailer' not found
```

The provider must load the WordPress PHPMailer dependencies before creating the
object, or use another WordPress-compatible construction path.

The SMTP password is also stored directly inside `sendstack_settings` by
[`SettingsScreen.php`](../../../src/Admin/Screens/SettingsScreen.php). This does
not satisfy the blueprint requirement for encrypted credential storage through
`CredentialStore`.

### Verdict

**Broken.** The primary SMTP delivery path does not complete and this item must
not be reported as done.

## 5. Logger and stats

### What works

- Log and stats repositories have substantive read/write implementations.
- Logger and Stats service providers are registered.
- `LogWriter` subscribes to before-send and after-send events.
- `StatsAggregator` subscribes to the after-send event.
- The dashboard reads summary statistics and recent failures.
- Log pruning is scheduled.

### Problems

During the SMTP test, `LogWriter` inserted a queued row before transport. The
PHPMailer fatal happened before the after-send event, so the row was never
updated to `sent` or `failed`. Stats were not incremented for the same reason.

There is no meaningful automated coverage for repository queries, logging state
transitions, stats aggregation, transient invalidation, or pruning.

The deactivation cron-name mismatch described in the Core section also affects
the logger lifecycle.

### Verdict

**Partial.** The components exist and insertion was observed, but the full
queued-to-final-state and stats pipeline has not completed successfully.

## 6. Admin shell

### What works

- Dashboard, Logs, Settings, Alerts, and Tools pages render with HTTP 200.
- Admin CSS and JavaScript load with HTTP 200.
- Each asset is included exactly once.
- Dashboard tiles and recent-failure content render.
- The Assets fix is already committed in audited commit `3840a1d`.

The earlier note that the Assets fix still needed a commit is therefore stale.

### Problems outside the visual shell

- `Assets.php` does not end with a newline, so the file-hygiene pre-commit hook fails.
- Test-email and resend AJAX actions pass the wrong type to `handle_send()`.
- Resend code accesses private `LogEntry` properties instead of using getters.
- Connection verification treats a `SendResult` object as a boolean. PHP objects
  are truthy, so a failed result can be reported as successful.
- Alerts explicitly display that channels are for a future release.
- Several setup-wizard render and save methods remain TODOs.

### Verdict

**Verified for the shell only.** Menus, pages, and assets work. The actions and
future feature content behind the shell are not complete.

## Automated-check results

### PHPUnit

```text
Tests: 2
Assertions: 1
Skipped: 1
```

The passing assertion only confirms that the `Plugin` class exists. The skipped
test is the database integration placeholder. A green PHPUnit result therefore
does not validate the audited functionality.

### PHP_CodeSniffer

```text
Errors: 321
Warnings: 82
Affected files: 72
```

Largest categories:

| Count | Category | Meaning |
|---:|---|---|
| 263 | Missing doc-comment summaries | Documentation standard; usually not runtime-breaking. |
| 24 | Statement alignment | Formatting only. |
| 22 | Lines over the recommended length | Readability/style. |
| 16 | Missing parameter comments | Documentation standard. |
| 15 | Non-snake-case properties | WordPress naming standard. |
| 4 | Nonce verification warnings | Mostly caused by PHPCS not following the custom AJAX authentication helper. |
| 3 | Unvalidated request method access | `$_SERVER['REQUEST_METHOD']` is used without checking that the key exists. |

Approximately 82% of PHPCS errors are missing short documentation summaries.
PHPCS uses the word "error" for standards violations, so these are not all
runtime failures or exploitable security problems.

Security-related findings must still be reviewed individually. Some direct
database and schema warnings are expected for custom tables, while request
validation, output escaping, credential storage, and SQL construction deserve
specific fixes or narrowly documented exclusions.

### PHPStan

```text
Total reported errors: 45
File errors: 43
Configuration/unmatched-ignore errors: 2
```

Important categories:

- Arrays and `MailPayload` objects are passed interchangeably despite strict signatures.
- Private `LogEntry` properties are accessed directly instead of through getters.
- Future provider stubs violate `AbstractMailer` and `MailerInterface` contracts.
- `SendResult` objects are used as booleans, making failure checks always truthy.
- Failover treats provider-summary arrays as mailer objects.
- REST response headers receive integers where strings are required.
- The PHPStan bootstrap omits constants that exist in the real plugin bootstrap.
- Two configured ignored-error patterns no longer match an error and need cleanup.

PHPStan findings are generally more relevant to runtime correctness than the
bulk of the PHPCS documentation and formatting findings.

### Pre-commit

The end-of-file hook attempted to modify:

- `phpstan.neon.dist`
- `src/Admin/Assets.php`
- `tests/phpstan-bootstrap.php`

PHPCS then failed on the existing standards violations. The audit restored the
hook-created edits before documentation work began.

## Why these problems were merged

### Contract drift from the original scaffold

The repository initially added broad class stubs. Later feature branches changed
the Mailer and Logger contracts, but older AJAX, REST, retry, failover, and future
provider stubs were not updated. Git history shows those consumers remaining
largely untouched since the initial scaffold while the foundation classes changed.

### CI does not block lint or static-analysis failures

The PHPCS and PHPStan steps in
[`ci.yml`](../../../.github/workflows/ci.yml) use `continue-on-error: true`.
Consequently, their failures do not block pull requests.

### Tests do not exercise feature behavior

PHPUnit is the blocking CI check, but its only active assertion checks class
existence. No end-to-end or meaningful integration test currently exercises the
SMTP, logging, stats, migration, AJAX, or admin-action paths.

## Corrected completion record

### Verified

- Admin menu/page shell
- Admin asset delivery
- Local WordPress/DDEV/Mailpit plumbing when SendStack does not intercept mail
- Presence and basic shape of the two custom database tables

### Partial

- Core skeleton
- Database layer
- Mailer foundation
- Logger and stats
- Admin functionality behind the shell

### Broken

- SendStack SMTP delivery
- AJAX test-email and resend paths
- Failover and retry contract paths
- Connection verification result handling

### Unverified or future work

- Migration and uninstall lifecycle
- Successful SendStack logging and stats end-to-end flow
- Gmail, SendGrid, and Mailgun providers
- REST API registration and behavior
- Alert channels
- Complete setup wizard

## Recommended solution order

Solution documents should be created in `../solutions/` in this order:

1. ~~Centralize cron hook names and correct deactivation cleanup.~~ Resolved 2026-07-17.
2. Repair the SMTP/PHPMailer load path and add an end-to-end Mailpit test.
3. Choose one `MailerManager::handle_send()` contract and update every consumer.
4. Correct resend, verification, failover, and retry behavior.
5. Fix the remaining migration-state gating issue.
6. Add meaningful unit and integration tests for features 1-6.
7. Resolve real PHPCS security findings and bulk documentation/style debt separately.
8. Make PHPCS and PHPStan blocking CI checks after the baseline is clean.

## Reproduction commands

Run from WSL in the repository root:

```bash
make up
make test
make lint
pre-commit run --all-files
ddev describe
ddev exec wp plugin list --path=/var/www/html/wordpress
ddev exec wp cron event list --path=/var/www/html/wordpress
ddev exec wp db tables --all-tables --path=/var/www/html/wordpress
```

Always inspect `git status` and `git diff` after pre-commit because file-hygiene
hooks can modify files.
