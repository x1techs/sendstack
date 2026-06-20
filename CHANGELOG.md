# Changelog

All notable changes to SendStack will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [0.1.0] - 2024-01-01

### Added

- Initial scaffolding: core architecture, DI container, and service provider pattern.
- PSR-4 autoloading via Composer.
- Database schema for `sendstack_logs` and `sendstack_stats` tables.
- Mailer abstraction layer: `MailerInterface`, `AbstractMailer`, SMTP, Gmail, SendGrid, and Mailgun provider stubs.
- OAuth 2.0 foundation for Gmail authentication.
- Email logging subsystem with pruning via WP-Cron.
- Stats aggregation with rollup repository.
- Alert channels: Email, Slack, Discord (all configurable).
- Admin screens: Dashboard, Settings, Logs, Alerts, Tools.
- First-run setup wizard (six steps).
- WP-Ajax endpoints for test email, resend, and connection verify.
- REST API: logs, stats, settings, connections, test email, and OAuth endpoints.
- Frontend bootstrap placeholder for public-facing hooks.
- Uninstall handler with opt-in data preservation.
- PHPUnit test bootstrap (Brain Monkey + WP_Mock).

[Unreleased]: https://github.com/x1techs/sendstack/compare/v0.1.0...HEAD
[0.1.0]: https://github.com/x1techs/sendstack/releases/tag/v0.1.0
