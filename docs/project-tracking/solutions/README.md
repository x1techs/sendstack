# SendStack Solution Documents

This directory contains fix plans and implementation records derived from the
audits in `../audits/`.

## Completed solutions

- [2026-07-17: Canonical cron hooks and deactivation cleanup](2026-07-17-cron-hook-constants-solution.md)

## Naming convention

Use `YYYY-MM-DD-topic-solution.md`, for example:

```text
2026-07-17-smtp-phpmailer-solution.md
```

## Required sections

Each solution document should include:

1. The audit finding it addresses.
2. Root cause.
3. Intended behavior and acceptance criteria.
4. Files and contracts affected.
5. Implementation decisions and tradeoffs.
6. Automated and manual verification performed.
7. Remaining risks or follow-up work.
8. Relevant commits or pull requests once available.

## Planned solution sequence

1. ~~Canonical cron hook names and deactivation cleanup.~~ Completed 2026-07-17.
2. SMTP/PHPMailer loading and end-to-end delivery.
3. Mailer contract consistency across all consumers.
4. AJAX resend/verification, failover, and retry behavior.
5. Remaining migration lifecycle fixes.
6. Tests for core, database, mailer, logger/stats, and admin actions.
7. PHPCS/PHPStan baseline cleanup and blocking CI enforcement.
