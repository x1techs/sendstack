# SendStack — Database Schema

> 📝 **Status:** Stub — content will be added as features are implemented.

This document defines every custom table, `wp_options` entry, and transient that SendStack uses. It serves as the single reference for data storage decisions.

---

## Overview

SendStack uses two custom database tables for high-volume data (email logs and aggregated statistics) rather than `wp_postmeta` or `wp_options`. Custom tables were chosen because email logs can grow to tens of thousands of rows, need indexed lookups by date/status/provider, and must support efficient bulk deletion with retention policies — none of which perform well in WordPress's EAV-style meta tables.

Plugin configuration is stored in `wp_options` as a small number of serialized arrays. Ephemeral data uses the Transients API.

## Table: `{$wpdb->prefix}sendstack_logs`

Stores one row per email send attempt.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | `BIGINT(20) UNSIGNED` | No | AUTO_INCREMENT | Primary key |
| `message_id` | `VARCHAR(255)` | Yes | `NULL` | Provider-assigned message ID |
| `status` | `VARCHAR(20)` | No | — | `sent`, `failed`, `queued` |
| `provider` | `VARCHAR(50)` | No | — | Provider slug (e.g., `smtp`, `gmail`, `sendgrid`, `mailgun`) |
| `from_email` | `VARCHAR(255)` | No | — | Sender address |
| `from_name` | `VARCHAR(255)` | Yes | `NULL` | Sender display name |
| `to_json` | `TEXT` | No | — | JSON array of recipient addresses |
| `cc_json` | `TEXT` | Yes | `NULL` | JSON array of CC addresses |
| `bcc_json` | `TEXT` | Yes | `NULL` | JSON array of BCC addresses |
| `subject` | `TEXT` | No | — | Email subject line |
| `headers_json` | `TEXT` | Yes | `NULL` | JSON object of additional headers |
| `attachments_count` | `SMALLINT UNSIGNED` | No | `0` | Number of attachments (names not stored for privacy) |
| `error_message` | `TEXT` | Yes | `NULL` | Error details on failure |
| `error_code` | `VARCHAR(50)` | Yes | `NULL` | Machine-readable error code |
| `retries` | `TINYINT UNSIGNED` | No | `0` | Number of retry/failover attempts |
| `created_at` | `DATETIME` | No | `CURRENT_TIMESTAMP` | When the send was attempted |

**Indexes:**

| Name | Columns | Purpose |
|------|---------|---------|
| `PRIMARY` | `id` | Row lookup |
| `idx_status_created` | `status`, `created_at` | Filtering logs by status + date range |
| `idx_provider` | `provider` | Per-provider filtering |
| `idx_created_at` | `created_at` | Retention policy cleanup, date-range queries |

**Retention policy:** Logs older than the configured retention period (default 30 days) are deleted via a daily WP-Cron event.

## Table: `{$wpdb->prefix}sendstack_stats`

Stores daily aggregated email statistics per provider.

| Column | Type | Nullable | Default | Description |
|--------|------|----------|---------|-------------|
| `id` | `BIGINT(20) UNSIGNED` | No | AUTO_INCREMENT | Primary key |
| `stat_date` | `DATE` | No | — | The date these stats cover |
| `provider` | `VARCHAR(50)` | No | — | Provider slug |
| `sent` | `INT UNSIGNED` | No | `0` | Emails successfully sent |
| `failed` | `INT UNSIGNED` | No | `0` | Emails that failed |
| `retried` | `INT UNSIGNED` | No | `0` | Emails that required failover |

**Unique key:** `uniq_date_provider` on (`stat_date`, `provider`) — one row per provider per day, updated via `INSERT ... ON DUPLICATE KEY UPDATE`.

## Options Reference

All options are stored under the `sendstack_` prefix.

| Option Name | Autoload | Purpose | Structure |
|-------------|----------|---------|-----------|
| `sendstack_settings` | Yes | Main plugin settings (active provider, from address, logging toggle, retention days, etc.) | Serialized array |
| `sendstack_connections` | Yes | Provider connection credentials (encrypted where applicable) | Serialized array keyed by provider slug |
| `sendstack_alerts` | Yes | Alert channel configuration (email addresses, webhook URLs, trigger conditions) | Serialized array |
| `sendstack_db_version` | Yes | Current database schema version for migration tracking | String (e.g., `1.0.0`) |
| `sendstack_activated` | No | Timestamp of first activation (used for setup wizard logic) | Integer (Unix timestamp) |
| `sendstack_setup_complete` | No | Whether the setup wizard has been completed | Boolean (`1`/`0`) |

## Transients Reference

| Transient Key | TTL | Purpose |
|---------------|-----|---------|
| `sendstack_stats_dashboard` | 1 hour | Cached dashboard widget statistics |
| `sendstack_gmail_token_{hash}` | Per token expiry | Cached Gmail OAuth access token |
| `sendstack_connection_status_{provider}` | 5 minutes | Cached result of last connection test |

## Migration System

SendStack uses a `Migrator` class that compares `SENDSTACK_DB_VERSION` (defined in the main plugin file) against the `sendstack_db_version` option. When the code version is higher, the Migrator runs any pending migration callbacks in order. Each migration is a callable registered with a version string — the Migrator executes only those whose version is greater than the stored DB version, then updates the option.

Full implementation details will be added after the Database scaffold is built.

## Upgrade Path

On every `admin_init`, SendStack checks whether `SENDSTACK_DB_VERSION` exceeds the stored `sendstack_db_version` option. If so, it runs the Migrator. This approach ensures schema updates happen automatically after a plugin file update, whether via the WordPress updater, WP-CLI, or manual upload.

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
