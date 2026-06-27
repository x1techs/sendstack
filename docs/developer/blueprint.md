# SendStack — Plugin Blueprint v1.0

## a. Final Identifiers

| Item | Value |
|---|---|
| Slug | `sendstack` |
| Main file | `sendstack.php` |
| Namespace root | `SendStack\` |
| Text domain | `sendstack` |
| PHP function/hook prefix | `sendstack_` |
| CSS/JS short prefix | `sstk_` |
| DB table prefix | `{$wpdb->prefix}sendstack_` |
| License | GPLv2+ |

**Constants** (defined in `sendstack.php`):

| Constant | Value/Purpose |
|---|---|
| `SENDSTACK_VERSION` | Semver string, single source of truth |
| `SENDSTACK_FILE` | `__FILE__` of bootstrap |
| `SENDSTACK_PATH` | `plugin_dir_path( SENDSTACK_FILE )` |
| `SENDSTACK_URL` | `plugin_dir_url( SENDSTACK_FILE )` |
| `SENDSTACK_BASENAME` | `plugin_basename( SENDSTACK_FILE )` |
| `SENDSTACK_MIN_PHP` | `'7.4'` |
| `SENDSTACK_MIN_WP` | `'6.2'` |
| `SENDSTACK_DB_VERSION` | Schema version, e.g. `'1.0.0'` |

---

## b. Minimum Versions

| Requirement | Value | Justification |
|---|---|---|
| PHP | **7.4** | Typed properties, arrow fns, null-coalescing assignment; WP 6.2+ still supports it; matches brief. |
| WordPress | **6.2** | Stable REST API cookie auth changes, Site Health hooks, `wp_get_environment_type()` mature. |
| MySQL | **5.7** / MariaDB 10.3 | Matches current WP minimum; InnoDB + utf8mb4. |
| Tested up to | Latest stable WP | Updated each release. |

---

## c. File/Folder Tree

```
sendstack/
├── sendstack.php                 # Bootstrap only: header, constants, autoloader, Plugin::instance()->boot()
├── readme.txt                    # WordPress.org format
├── readme.md                     # GitHub-facing
├── LICENSE                       # GPLv2+
├── uninstall.php                 # Conditional cleanup (reads preserve-data flag)
├── CHANGELOG.md
├── CODEOWNERS
├── composer.json
├── package.json
├── phpcs.xml.dist
├── phpstan.neon.dist
├── phpunit.xml.dist
├── .editorconfig
├── .gitignore
├── .gitattributes
├── .github/
│   ├── workflows/
│   │   ├── ci.yml
│   │   ├── deploy.yml
│   │   └── preview.yml
│   ├── ISSUE_TEMPLATE/
│   │   ├── bug_report.yml
│   │   └── feature_request.yml
│   └── PULL_REQUEST_TEMPLATE.md
├── src/
│   ├── Core/
│   │   ├── Plugin.php
│   │   ├── Container.php
│   │   ├── ServiceProvider.php
│   │   ├── Loader.php
│   │   ├── Activator.php
│   │   ├── Deactivator.php
│   │   ├── Requirements.php
│   │   └── Upgrader.php
│   ├── I18n/
│   │   └── TextDomain.php
│   ├── Mailer/
│   │   ├── MailerManager.php
│   │   ├── MailerInterface.php
│   │   ├── AbstractMailer.php
│   │   ├── MailPayload.php
│   │   ├── PhpMailerOverride.php
│   │   ├── FailoverHandler.php
│   │   ├── RetryQueue.php
│   │   ├── SendResult.php
│   │   └── Providers/
│   │       ├── SmtpMailer.php
│   │       ├── GmailMailer.php
│   │       ├── SendGridMailer.php
│   │       └── MailgunMailer.php
│   ├── Auth/
│   │   ├── OAuthManager.php
│   │   ├── GoogleOAuthClient.php
│   │   ├── CredentialStore.php
│   │   └── Encryption.php
│   ├── Logger/
│   │   ├── LogRepository.php
│   │   ├── LogEntry.php
│   │   ├── LogWriter.php
│   │   ├── LogPruner.php
│   │   └── LogStatus.php
│   ├── Stats/
│   │   ├── StatsAggregator.php
│   │   └── StatsRepository.php
│   ├── Alerts/
│   │   ├── AlertManager.php
│   │   ├── AlertInterface.php
│   │   ├── AlertEvent.php
│   │   ├── AlertThrottle.php
│   │   └── Channels/
│   │       ├── EmailChannel.php
│   │       ├── SlackChannel.php
│   │       └── DiscordChannel.php
│   ├── Admin/
│   │   ├── AdminBootstrap.php
│   │   ├── MenuRegistrar.php
│   │   ├── Assets.php
│   │   ├── AdminNotices.php
│   │   ├── Screens/
│   │   │   ├── AbstractScreen.php
│   │   │   ├── DashboardScreen.php
│   │   │   ├── SettingsScreen.php
│   │   │   ├── LogsScreen.php
│   │   │   ├── AlertsScreen.php
│   │   │   └── ToolsScreen.php
│   │   ├── Tables/
│   │   │   └── LogsListTable.php
│   │   ├── Wizard/
│   │   │   ├── SetupWizard.php
│   │   │   ├── WizardStepInterface.php
│   │   │   └── Steps/
│   │   │       ├── WelcomeStep.php
│   │   │       ├── ProviderStep.php
│   │   │       ├── ConfigureStep.php
│   │   │       ├── SenderStep.php
│   │   │       ├── TestStep.php
│   │   │       └── FinishStep.php
│   │   └── Ajax/
│   │       └── AjaxHandler.php
│   ├── API/
│   │   ├── RestBootstrap.php
│   │   ├── Controllers/
│   │   │   ├── AbstractController.php
│   │   │   ├── LogsController.php
│   │   │   ├── StatsController.php
│   │   │   ├── SettingsController.php
│   │   │   ├── ConnectionsController.php
│   │   │   ├── TestController.php
│   │   │   └── OAuthController.php
│   │   └── Schemas/
│   │       ├── LogSchema.php
│   │       ├── SettingsSchema.php
│   │       └── ConnectionSchema.php
│   ├── Database/
│   │   ├── Schema.php
│   │   ├── Installer.php
│   │   ├── Migrator.php
│   │   └── Migrations/
│   │       └── Migration_1_0_0.php
│   ├── Frontend/
│   │   └── FrontendBootstrap.php
│   └── Support/
│       ├── Sanitizer.php
│       ├── Validator.php
│       ├── HttpClient.php
│       ├── DebugLogger.php
│       └── Arr.php
├── assets/
│   ├── src/
│   │   ├── js/
│   │   └── scss/
│   └── build/
├── images/
├── languages/
├── templates/
│   └── emails/
├── tests/
│   ├── bootstrap.php
│   ├── Unit/
│   └── Integration/
└── docs/
    ├── user/
    └── developer/
```

---

## d. Class Responsibility Map

### Core

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Core\Plugin` | Singleton bootstrap; wires container + service providers | `instance`, `boot`, `container`, `version` |
| `SendStack\Core\Container` | Minimal PSR-11-ish DI container | `bind`, `singleton`, `make`, `has` |
| `SendStack\Core\ServiceProvider` | Abstract base for module registration | `register`, `boot` |
| `SendStack\Core\Loader` | Registers all service providers and hooks | `register_providers`, `run` |
| `SendStack\Core\Activator` | Runs on activation: schema install, default options | `activate` |
| `SendStack\Core\Deactivator` | Runs on deactivation: clear cron, transients | `deactivate` |
| `SendStack\Core\Requirements` | PHP/WP version + required ext check | `met`, `errors`, `render_notice` |
| `SendStack\Core\Upgrader` | Runs migrations when `SENDSTACK_VERSION` changes | `maybe_upgrade` |

### I18n

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\I18n\TextDomain` | Loads `sendstack.pot` / `.mo` | `load` |

### Mailer

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Mailer\MailerManager` | Resolves active mailer, owns `pre_wp_mail` / `phpmailer_init` hooks | `register`, `handle_send`, `resolve_mailer`, `available_providers` |
| `SendStack\Mailer\MailerInterface` | Provider contract | `slug`, `label`, `send`, `verify_connection`, `supports` |
| `SendStack\Mailer\AbstractMailer` | Shared provider logic (logging hooks, error normalization) | `send`, `log_attempt` |
| `SendStack\Mailer\MailPayload` | Immutable value object for to/subject/body/headers/attachments | `from_args`, `to`, `subject`, `body`, `headers`, `attachments`, `with` |
| `SendStack\Mailer\SendResult` | Value object for send outcome | `success`, `failure`, `is_success`, `error_code`, `error_message`, `provider_response` |
| `SendStack\Mailer\PhpMailerOverride` | Short-circuits WP's PHPMailer when provider is API-based | `apply`, `reset` |
| `SendStack\Mailer\FailoverHandler` | On primary failure, retries via backup connection | `handle_failure`, `is_enabled` |
| `SendStack\Mailer\RetryQueue` | WP-Cron queue for transient provider failures | `schedule`, `process`, `clear` |
| `SendStack\Mailer\Providers\SmtpMailer` | PHPMailer SMTP transport | `send`, `verify_connection` |
| `SendStack\Mailer\Providers\GmailMailer` | Gmail API via OAuth access token | `send`, `verify_connection` |
| `SendStack\Mailer\Providers\SendGridMailer` | SendGrid v3 API | `send`, `verify_connection` |
| `SendStack\Mailer\Providers\MailgunMailer` | Mailgun messages API | `send`, `verify_connection` |

### Auth

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Auth\OAuthManager` | Orchestrates OAuth flows (state, callback routing) | `start`, `handle_callback`, `disconnect` |
| `SendStack\Auth\GoogleOAuthClient` | Google-specific OAuth 2.0 client (PKCE) | `authorization_url`, `exchange_code`, `refresh_token`, `revoke` |
| `SendStack\Auth\CredentialStore` | Read/write connection credentials with encryption | `get`, `put`, `delete`, `all` |
| `SendStack\Auth\Encryption` | AUTH_KEY-derived AES-256-GCM encrypt/decrypt | `encrypt`, `decrypt`, `is_available` |

### Logger

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Logger\LogRepository` | CRUD over `sendstack_logs` table | `insert`, `update`, `find`, `delete`, `query`, `count`, `purge_older_than` |
| `SendStack\Logger\LogEntry` | DTO wrapping a log row | `to_array`, `from_row`, `redacted` |
| `SendStack\Logger\LogWriter` | Subscribes to mailer events, writes entries | `register`, `on_before_send`, `on_after_send` |
| `SendStack\Logger\LogPruner` | WP-Cron job that enforces retention | `schedule`, `run` |
| `SendStack\Logger\LogStatus` | Status enum constants | `QUEUED`, `SENT`, `FAILED`, `RETRYING` |

### Stats

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Stats\StatsRepository` | CRUD over `sendstack_stats` daily rollup table | `increment`, `for_range`, `summary` |
| `SendStack\Stats\StatsAggregator` | Subscribes to mailer events; updates rollups + cache | `register`, `on_send_success`, `on_send_failed` |

### Alerts

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Alerts\AlertManager` | Routes alert events to enabled channels | `register`, `dispatch`, `channels` |
| `SendStack\Alerts\AlertInterface` | Channel contract | `slug`, `label`, `send`, `validate_config` |
| `SendStack\Alerts\AlertEvent` | Value object (type, subject, body, context) | `send_failed`, `failover_triggered`, `quota_warning` |
| `SendStack\Alerts\AlertThrottle` | Rate-limits channel spam | `should_send`, `record` |
| `SendStack\Alerts\Channels\EmailChannel` | Plain email alert (via local/separate transport) | `send`, `validate_config` |
| `SendStack\Alerts\Channels\SlackChannel` | Slack incoming webhook | `send`, `validate_config` |
| `SendStack\Alerts\Channels\DiscordChannel` | Discord webhook | `send`, `validate_config` |

### Admin

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Admin\AdminBootstrap` | Registers admin subsystems | `register` |
| `SendStack\Admin\MenuRegistrar` | Registers top-level + submenu pages | `register` |
| `SendStack\Admin\Assets` | Enqueues admin JS/CSS per screen | `register`, `enqueue` |
| `SendStack\Admin\AdminNotices` | Shows requirements/OAuth/test-email notices | `register`, `add`, `render` |
| `SendStack\Admin\Screens\AbstractScreen` | Base for admin screens | `slug`, `title`, `capability`, `render` |
| `SendStack\Admin\Screens\DashboardScreen` | Stats tiles + recent failures | `render` |
| `SendStack\Admin\Screens\SettingsScreen` | Primary + backup connection config tabs | `render`, `handle_save` |
| `SendStack\Admin\Screens\LogsScreen` | Wraps `LogsListTable` | `render` |
| `SendStack\Admin\Screens\AlertsScreen` | Alert channels config | `render`, `handle_save` |
| `SendStack\Admin\Screens\ToolsScreen` | Test email, export, diagnostics | `render` |
| `SendStack\Admin\Tables\LogsListTable` | `WP_List_Table` for logs | `prepare_items`, `column_default`, `get_bulk_actions`, `process_bulk_action` |
| `SendStack\Admin\Wizard\SetupWizard` | First-run onboarding controller | `maybe_redirect`, `render`, `handle_step` |
| `SendStack\Admin\Wizard\WizardStepInterface` | Step contract | `id`, `title`, `render`, `validate`, `save` |
| `SendStack\Admin\Wizard\Steps\*` | Individual step views | `render`, `validate`, `save` |
| `SendStack\Admin\Ajax\AjaxHandler` | Admin-AJAX endpoints (test connection, resend) | `register`, `handle_test_email`, `handle_resend`, `handle_verify` |

### API

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\API\RestBootstrap` | Registers REST routes on `rest_api_init` | `register` |
| `SendStack\API\Controllers\AbstractController` | Shared auth/schema plumbing | `permissions_check`, `register_routes` |
| `SendStack\API\Controllers\LogsController` | `/logs`, `/logs/{id}`, `/logs/{id}/resend`, `/logs/bulk` | `get_items`, `get_item`, `delete_item`, `resend_item`, `bulk` |
| `SendStack\API\Controllers\StatsController` | `/stats` | `get_stats` |
| `SendStack\API\Controllers\SettingsController` | `/settings` | `get`, `update` |
| `SendStack\API\Controllers\ConnectionsController` | `/connections/verify` | `verify` |
| `SendStack\API\Controllers\TestController` | `/test-email` | `send` |
| `SendStack\API\Controllers\OAuthController` | `/oauth/google/*` | `start`, `callback`, `disconnect` |
| `SendStack\API\Schemas\*` | Reusable JSON schemas | `definition` |

### Database

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Database\Schema` | Table DDL (`dbDelta`) | `tables`, `for` |
| `SendStack\Database\Installer` | Creates/updates tables | `install`, `uninstall` |
| `SendStack\Database\Migrator` | Runs version-gated migrations | `migrate`, `current_version` |
| `SendStack\Database\Migrations\Migration_1_0_0` | Baseline schema | `up`, `down` |

### Frontend

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Frontend\FrontendBootstrap` | Placeholder for public-facing hooks | `register` |

### Support

| FQN | Responsibility | Key public methods |
|---|---|---|
| `SendStack\Support\Sanitizer` | Centralized sanitization helpers | `text`, `email`, `url`, `html`, `int`, `array_of` |
| `SendStack\Support\Validator` | Validation helpers | `email`, `url`, `host_port`, `webhook_url` |
| `SendStack\Support\HttpClient` | Thin wrapper over `wp_remote_request` with retry/logging | `get`, `post`, `request` |
| `SendStack\Support\DebugLogger` | Plugin-level debug log gated on `WP_DEBUG` | `debug`, `info`, `error` |
| `SendStack\Support\Arr` | Small array utilities | `get`, `only`, `except` |

---

## e. Public Extensibility API

### Mailer Hooks

| Hook | Type | Params | Purpose | Since |
|---|---|---|---|---|
| `sendstack_mail_payload` | filter | `MailPayload $payload` | Mutate outgoing message before send | 1.0.0 |
| `sendstack_active_mailer` | filter | `string $slug, MailPayload $payload` | Override routing per-message | 1.0.0 |
| `sendstack_mailer_providers` | filter | `array $providers` | Register third-party providers | 1.0.0 |
| `sendstack_before_send` | action | `MailPayload $payload, MailerInterface $mailer` | Pre-send observer | 1.0.0 |
| `sendstack_send_success` | action | `MailPayload $payload, SendResult $result, MailerInterface $mailer` | Success observer | 1.0.0 |
| `sendstack_send_failed` | action | `MailPayload $payload, SendResult $result, MailerInterface $mailer` | Failure observer | 1.0.0 |
| `sendstack_failover_enabled` | filter | `bool $enabled, MailPayload $payload` | Skip failover per-message | 1.0.0 |
| `sendstack_retry_attempts` | filter | `int $attempts, string $provider_slug` | Override retry count | 1.0.0 |
| `sendstack_retry_delay` | filter | `int $seconds, int $attempt_number` | Backoff control | 1.0.0 |

### Logger Hooks

| Hook | Type | Params | Purpose | Since |
|---|---|---|---|---|
| `sendstack_should_log` | filter | `bool $log, MailPayload $payload` | Skip logging specific mail | 1.0.0 |
| `sendstack_log_entry` | filter | `array $row, MailPayload $payload` | Modify row before insert | 1.0.0 |
| `sendstack_log_created` | action | `int $log_id, array $row` | Post-insert observer | 1.0.0 |
| `sendstack_log_retention_days` | filter | `int $days` | Override retention | 1.0.0 |
| `sendstack_log_redacted_fields` | filter | `array $fields` | Fields scrubbed before storage | 1.0.0 |

### Alert Hooks

| Hook | Type | Params | Purpose | Since |
|---|---|---|---|---|
| `sendstack_alert_channels` | filter | `array $channels` | Register custom channels | 1.0.0 |
| `sendstack_alert_payload` | filter | `array $payload, AlertEvent $event, string $channel_slug` | Mutate channel payload | 1.0.0 |
| `sendstack_alert_dispatched` | action | `AlertEvent $event, string $channel_slug, bool $success` | Observer | 1.0.0 |
| `sendstack_alert_throttle_window` | filter | `int $seconds, string $channel_slug` | Adjust throttle | 1.0.0 |

### Admin Hooks

| Hook | Type | Params | Purpose | Since |
|---|---|---|---|---|
| `sendstack_admin_menu_position` | filter | `int $position` | Reorder top-level menu | 1.0.0 |
| `sendstack_admin_menu_registered` | action | `MenuRegistrar $registrar` | Add custom submenu pages | 1.0.0 |
| `sendstack_settings_saved` | action | `array $new, array $old, string $screen` | Post-save observer | 1.0.0 |
| `sendstack_wizard_steps` | filter | `array $steps` | Add/reorder onboarding steps | 1.0.0 |
| `sendstack_wizard_completed` | action | `array $state` | Post-onboarding observer | 1.0.0 |
| `sendstack_logs_columns` | filter | `array $columns` | Customize list table columns | 1.0.0 |
| `sendstack_logs_row_actions` | filter | `array $actions, LogEntry $entry` | Add inline row actions | 1.0.0 |

### REST Hooks

| Hook | Type | Params | Purpose | Since |
|---|---|---|---|---|
| `sendstack_rest_capability` | filter | `string $cap, string $route` | Override required cap per route | 1.0.0 |
| `sendstack_rest_api_init` | action | `RestBootstrap $rest` | Register additional routes | 1.0.0 |

---

## f. Data Layer

### Options

| Name | Autoload | Purpose | Structure |
|---|---|---|---|
| `sendstack_version` | yes | Installed version string for upgrader | `string` |
| `sendstack_db_version` | no | Schema version for migrator | `string` |
| `sendstack_settings` | yes | Global plugin prefs | `{ sender_name, sender_email, force_from, log_enabled, failover_enabled, hide_from_admins }` |
| `sendstack_connections` | yes | Primary + backup provider configs, credentials encrypted at rest | `{ primary: { provider, config }, backup: { provider, config } }` |
| `sendstack_alerts` | no | Per-channel alert settings | `{ email: {...}, slack: {...}, discord: {...}, triggers: [...] }` |
| `sendstack_logs_config` | no | Retention + redaction | `{ retention_days, store_body, redacted_headers, max_body_bytes }` |
| `sendstack_wizard_state` | no | Wizard progress (deleted on completion) | `{ step, provider, partial_config }` |
| `sendstack_wizard_completed` | yes | Gate first-run redirect | `bool` |
| `sendstack_preserve_data_on_uninstall` | yes | Honored by `uninstall.php` | `bool` (default `true`) |

### Transients

| Name | TTL | Purpose |
|---|---|---|
| `sendstack_stats_7d` | 15 min | Cached dashboard tiles |
| `sendstack_stats_30d` | 1 hour | Cached 30-day chart |
| `sendstack_alert_throttle_{channel}_{event}` | configurable (default 5 min) | Prevent alert storms |
| `sendstack_oauth_state_{hash}` | 10 min | OAuth CSRF state |
| `sendstack_provider_verify_{slug}` | 5 min | Last verification result cache |

### Custom Tables

| Table | Purpose | Key Columns | Indexes |
|---|---|---|---|
| `{prefix}sendstack_logs` | Email log entries | `id`, `message_id`, `status`, `provider`, `from_email`, `to_json`, `subject`, `body_mime`, `headers_json`, `error_code`, `error_message`, `attempt_count`, `created_at`, `updated_at` | `PK id`, `idx_status_created`, `idx_created` |
| `{prefix}sendstack_stats` | Daily rollup per provider/status | `id`, `stat_date`, `provider`, `status`, `count` | `PK id`, `UNIQUE (stat_date, provider, status)` |

---

## g. REST API Surface

Base: `/wp-json/sendstack/v1`
Default capability: `manage_options` (filterable via `sendstack_rest_capability`).

| Method | Route | Auth | Purpose |
|---|---|---|---|
| GET | `/logs` | cap | Paginated log list |
| GET | `/logs/{id}` | cap | Single log |
| POST | `/logs/{id}/resend` | cap | Re-send from stored payload |
| DELETE | `/logs/{id}` | cap | Delete log |
| POST | `/logs/bulk` | cap | Bulk delete/resend |
| GET | `/stats` | cap | Dashboard stats |
| GET | `/settings` | cap | Current settings (credentials redacted) |
| POST | `/settings` | cap | Update settings |
| POST | `/connections/verify` | cap | Test without saving |
| POST | `/test-email` | cap | Test send |
| POST | `/oauth/google/start` | cap | Begin OAuth |
| GET | `/oauth/google/callback` | public (state-validated) | OAuth callback |
| POST | `/oauth/google/disconnect` | cap | Revoke tokens |

---

## h. Admin UI Map

| Screen | Shows | Actions | Capability |
|---|---|---|---|
| Dashboard | Stats tiles, 30-day chart, recent failures | Link to Logs, Retry | `manage_options` |
| Settings | Sender identity, provider forms, retention | Save, Verify, OAuth connect/disconnect | `manage_options` |
| Logs | WP_List_Table with filters | View, Resend, Delete, Bulk, Export CSV | `manage_options` |
| Alerts | Channel configs, trigger toggles | Save, Send test alert | `manage_options` |
| Tools | Test email, system info, export/import | Send, Download, Import | `manage_options` |
| Setup Wizard | 6-step onboarding | Next/Back/Skip | `manage_options` |

---

## i. Security Model

| Surface | Mechanism | Enforced in |
|---|---|---|
| Admin forms | `wp_nonce_field` + `check_admin_referer` | All Screen save handlers |
| Admin-AJAX | `check_ajax_referer` + `current_user_can` | `AjaxHandler` |
| REST | `permissions_check` + cookie auth | `AbstractController` |
| OAuth callback | State token validated against transient | `OAuthManager`, `OAuthController` |
| Input sanitization | Central `Sanitizer` + REST schema `sanitize_callback` | `Sanitizer`, `Schemas` |
| Output escaping | `esc_html`, `esc_attr`, `esc_url`, `wp_kses_post` at every echo | All Screen render methods |
| SQL | Exclusively `$wpdb->prepare()` | `LogRepository`, `StatsRepository` |
| Credential storage | AES-256-GCM keyed via AUTH_KEY | `Encryption`, `CredentialStore` |
| Capability gates | `current_user_can('manage_options')` everywhere | All entrypoints |

---

## j. Uninstall Behavior

**Setting:** `sendstack_preserve_data_on_uninstall` (default `true`)

If `true` → exit silently (preserves all data).
If `false` → drop both custom tables, delete all `sendstack_*` options/transients, clear cron events, revoke OAuth tokens. Multisite-aware.

---

## k. WordPress.org Review Risk Assessment

| Risk | Severity | Mitigation |
|---|---|---|
| External service calls | Medium | All user-configured. Document every endpoint in readme.txt External Services section. |
| OAuth token exchange | Low | BYOC — user-supplied Client ID/Secret. No shared app. |
| Credential encryption | Low | Standard pattern using AUTH_KEY. Document fallback. |
| Email body logging (PII) | Medium | Opt-in store_body, retention policy, redaction filter. |
| Bundled Composer packages | Low | Dev-only deps. Zero runtime deps in v1.0. |
| Premium upsells | None | No premium version exists. |
| Telemetry | None | No analytics, no tracking. |
| Competitor mentions | None | Enforced by content review. |

---

## l. Version Roadmap

**v1.0.0 (locked)**
- SMTP, Gmail OAuth (BYOC), SendGrid, Mailgun providers
- Setup Wizard (6 steps)
- Email logging with resend, bulk actions, retention, redaction
- Primary + backup failover connection
- Alerts via Email, Slack, Discord
- Test email tool
- Dashboard (sent/failed, 7d/30d)
- REST API v1

**v1.1 candidates**
- Microsoft 365 / Outlook OAuth provider
- Amazon SES provider
- Postmark provider
- Brevo provider
- One-click migration from other SMTP plugins
- WP-CLI commands
- Multisite network settings

**v1.2+ candidates**
- Conditional routing rules
- Open/click tracking
- WooCommerce integration
- Weekly digest email report
- Scheduled log exports
