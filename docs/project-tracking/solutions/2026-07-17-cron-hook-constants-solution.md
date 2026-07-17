# Canonical Cron Hooks and Deactivation Cleanup

**Implementation date:** 2026-07-17
**Status:** Implemented, locally verified, and committed
**Commit:** `473dab8`
**Audit finding:** Core problem 1 in the [features 1-6 implementation audit](../audits/2026-07-17-features-1-6-implementation-audit.md)

## Problem

SendStack scheduled these WordPress cron hooks:

```text
sendstack_prune_logs
sendstack_process_retry_queue
```

The deactivator attempted to clear different names:

```text
sendstack_log_prune
sendstack_retry_process
```

WordPress treats a cron hook as an exact string identifier. Clearing a similar
but different string does not remove the scheduled event, which can leave
orphaned jobs after plugin deactivation.

## Root cause

Hook names were repeated as independent string literals in the scheduler and
deactivator classes. Nothing enforced agreement between those literals, so the
names drifted during scaffold and feature development.

## Decision

Create a constants-only `SendStack\Core\CronHooks` class as the single source of
truth. All scheduling, callback registration, unscheduling, and deactivation
code references these constants rather than defining its own hook-name string.

The canonical values remain unchanged so existing installations retain their
currently scheduled events:

```php
CronHooks::PRUNE_LOGS;
CronHooks::PROCESS_RETRY_QUEUE;
```

## Implementation

### Added

- [`src/Core/CronHooks.php`](../../../src/Core/CronHooks.php)
  - `PRUNE_LOGS = 'sendstack_prune_logs'`
  - `PROCESS_RETRY_QUEUE = 'sendstack_process_retry_queue'`
  - Private constructor to prevent instantiation.

### Updated

- [`src/Core/Deactivator.php`](../../../src/Core/Deactivator.php)
  - Clears both canonical constants.
- [`src/Logger/LogPruner.php`](../../../src/Logger/LogPruner.php)
  - Registers, schedules, and unschedules the pruning constant.
- [`src/Mailer/RetryQueue.php`](../../../src/Mailer/RetryQueue.php)
  - Schedules and unschedules the retry constant.

### Tests added

- [`tests/Unit/Core/CronHooksTest.php`](../../../tests/Unit/Core/CronHooksTest.php)
  - Locks the canonical string values to protect existing scheduled events.
- [`tests/Unit/Core/DeactivatorTest.php`](../../../tests/Unit/Core/DeactivatorTest.php)
  - Verifies that deactivation clears both shared constants.

## Acceptance criteria

- [x] Hook-name strings are defined in one class.
- [x] Log pruning uses `CronHooks::PRUNE_LOGS` everywhere.
- [x] Retry processing uses `CronHooks::PROCESS_RETRY_QUEUE` everywhere.
- [x] Deactivation clears both canonical hooks.
- [x] No mistyped legacy hook is scheduled locally.
- [x] Deactivating the plugin removes every SendStack cron event.
- [x] Reactivating the plugin creates only the required pruning event.
- [x] Regression tests cover the constants and deactivation behavior.

## Verification performed

### Automated tests

The complete unit suite passed:

```text
Tests: 4
Assertions: 4
Skipped: 1
Result: OK (one integration placeholder skipped)
```

Focused PHP_CodeSniffer passed for all source files changed by this solution.
The command still prints the repository's existing PHPCS configuration
deprecation notices, but reports no violation in these files.

Focused PHPStan passed for `CronHooks`, `Deactivator`, and `LogPruner`:

```text
[OK] No errors
```

`RetryQueue` retains the separately audited, pre-existing `handle_send()` type
mismatch. That mailer-contract problem is not caused by the cron constants and
is intentionally deferred to its own solution.

### Runtime lifecycle check

Before deactivation:

```text
sendstack_prune_logs
```

After deactivation:

```text
No SendStack cron events remain.
```

After reactivation:

```text
sendstack_prune_logs
```

The plugin was active at the end of verification.

### Legacy-event inspection

No local events were found for either incorrect historical name:

```text
sendstack_log_prune
sendstack_retry_process
```

No manual legacy-event deletion was necessary.

## Remaining risks and follow-up

- RetryQueue is not yet fully wired into a service provider.
- RetryQueue still passes a `MailPayload` to a manager method that currently
  expects an array.
- Other future cron jobs must be added to `CronHooks` and explicitly included
  in lifecycle cleanup.
- A later integration test should verify deactivation/reactivation in a
  disposable WordPress database, not only through the local runtime check.
