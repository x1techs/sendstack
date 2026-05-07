# SendStack — Coding Standards

> 📝 **Status:** Stub — content will be added as features are implemented.

This document is the single reference for all coding conventions enforced in SendStack. CI will reject PRs that violate these rules.

---

## PHP Standards

All PHP code must pass PHPCS using the project's `phpcs.xml.dist` configuration, which extends `WordPress-Extra` and `WordPress-Docs` rulesets. Key rules enforced: strict type declarations, Yoda conditions, proper spacing, meaningful variable names, and WordPress-standard docblocks.

## Static Analysis

All PHP code must pass PHPStan at level 5 using the project's `phpstan.neon.dist` configuration. The goal is to catch type errors, undefined method calls, and dead code before review. PHPStan level may be raised in future versions.

## Naming Conventions

| Scope | Convention | Example |
|-------|-----------|---------|
| PHP namespace | `SendStack\` | `SendStack\Mailers\SmtpMailer` |
| Functions (global, if any) | `sendstack_` prefix | `sendstack_get_active_provider()` |
| Options | `sendstack_` prefix | `sendstack_settings` |
| Transients | `sendstack_` prefix | `sendstack_stats_dashboard` |
| Hooks (actions/filters) | `sendstack_` prefix | `sendstack_before_send` |
| DB tables | `sendstack_` prefix (after `$wpdb->prefix`) | `wp_sendstack_logs` |
| CSS classes | `sstk-` prefix | `sstk-settings-form` |
| JS globals (if any) | `sendstack` or `sstk` | `window.sendstackAdmin` |
| Constants | `SENDSTACK_` prefix | `SENDSTACK_VERSION` |

## Security Checklist

Every PHP file and every PR must satisfy:

- `defined( 'ABSPATH' ) || exit;` at the top of every file
- Nonce verification on every form submission and AJAX handler
- Capability checks (`current_user_can()`) before any privileged action
- Input sanitization: `sanitize_text_field()`, `sanitize_email()`, `absint()`, `wp_kses_post()`, etc.
- Output escaping: `esc_html()`, `esc_attr()`, `esc_url()`, `wp_kses()` at the point of output
- Database queries via `$wpdb->prepare()` — never string interpolation or concatenation

## Performance Guidelines

- **Autoload flag** — only set `autoload = yes` on options read on every page load (settings, connections, alerts, db_version). All others set `autoload = no`.
- **Transients** — cache expensive queries (dashboard stats, connection test results) with appropriate TTLs
- **No queries in loops** — batch lookups, use `IN` clauses, or pre-fetch
- **Lazy loading** — admin-only code loads only on admin pages; frontend code loads only when needed
- **Enqueue properly** — scripts/styles registered with `wp_register_*` and enqueued only on relevant screens

## i18n Rules

- All user-facing strings must be wrapped in translation functions: `__()`, `_e()`, `esc_html__()`, `esc_attr__()`, `_n()`, `_x()`
- Text domain is always `sendstack` — no exceptions, no variables
- Never concatenate translatable strings — use `sprintf()` with placeholders
- Plurals use `_n()`, context-dependent strings use `_x()`

## File Structure Rules

- One class per file
- Filename matches classname: `MailerInterface.php` contains `MailerInterface`
- PSR-4 directory structure mirrors namespace: `src/Mailers/SmtpMailer.php` → `SendStack\Mailers\SmtpMailer`
- Interfaces go in `src/Contracts/`
- Abstract base classes live alongside their implementations
- DTOs (Data Transfer Objects) go in `src/DTOs/`

## Documentation Requirements

- Docblocks on all public methods with `@since`, `@param`, `@return`
- Docblocks on all class declarations with `@since` and a one-line summary
- All hooks documented inline with `@since`, parameter descriptions, and usage example
- Complex logic gets inline comments explaining *what* (the concept block explains *why*)

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
