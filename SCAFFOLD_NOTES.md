# SendStack Scaffold Notes

## Why this layout

- **PSR-4 vs WP traditional:** `src/` uses PSR-4 via Composer autoload (`SendStack\\` → `src/`), keeping class files out of the global WP includes path and making them importable without manual `require_once` chains.
- **`src/` vs `includes/`:** `includes/` is the WP convention but carries no autoloading support; `src/` signals Composer-managed code, enables IDE auto-complete and static analysis, and keeps test doubles easy to inject.
- **`assets/build/`:** gitignored build artefacts (minified JS/CSS) live here; `assets/src/` holds version-controlled source; the build step (e.g. webpack/vite) is run by CI, not committed to the repo.
- **`.gitattributes` for SVN:** the WordPress.org SVN mirror is populated via `git archive`; `.gitattributes` lines like `tests/ export-ignore`, `docs/ export-ignore`, `assets/src/ export-ignore` strip dev-only directories so the plugin directory stays lean.
- **Namespace-to-directory map:** every top-level namespace corresponds to one directory — `Core/`, `Mailer/`, `Logger/`, `Stats/`, `Alerts/`, `API/`, `Auth/`, `Database/`, `Admin/`, `Support/`, `I18n/`, `Frontend/` — making the location of any class predictable from its FQCN.

## Concepts in `sendstack.php`

- **Constants** (`SENDSTACK_VERSION`, `SENDSTACK_PATH`, etc.) are defined immediately after the autoloader so all classes can read them without a service container call.
- **Requirements gate:** `Requirements::met()` checks PHP and WP versions before any class is booted; on failure `admin_notices` renders the error and the file returns early, leaving no hooks active.
- **`plugins_loaded` priority 5:** boots SendStack before most plugins (which default to 10), ensuring the mailer override is in place before any plugin tries to send mail.
- **Activation/deactivation hooks in the plugin file:** `register_activation_hook()` and `register_deactivation_hook()` must point to the main plugin file; WP resolves the callback at include time and cannot find hooks registered in sub-files.

## Concepts in `uninstall.php`

- **Separate file, not a deactivation hook:** WordPress runs `uninstall.php` only when the user clicks *Delete* in the Plugins screen, providing a distinct lifecycle from Deactivate.
- **`WP_UNINSTALL_PLUGIN` guard:** the constant is set by WP before including the file; the guard at the top (`defined('WP_UNINSTALL_PLUGIN') || exit`) prevents direct HTTP access.
- **Preserve-by-default:** no data is deleted unless the user has enabled the "remove data on uninstall" option; this protects against accidental data loss during re-install or plugin swap.

## Concepts in `composer.json`

- **Dev-only dependencies:** all `require-dev` packages are stripped from production builds via `.gitattributes`; the shipped plugin has no Composer footprint beyond `vendor/autoload.php` and any future production `require` entries.
- **`wpcs` + `phpcs`:** enforces WordPress Coding Standards; catches XSS, nonce, and direct DB-query issues at lint time.
- **`phpstan` + `szepeviktor/phpstan-wordpress`:** static analysis with WP-specific type stubs for `WP_Error`, `WP_Post`, `wpdb`, etc.; eliminates entire classes of runtime errors before they ship.
- **`phpunit` + `brain/monkey` + `10up/wp_mock`:** PHPUnit runs the test suite; Brain Monkey stubs WP functions per-test; WP_Mock bootstraps function stubs globally for integration-style unit tests.
- **`dealerdirect/phpcodesniffer-composer-installer`:** auto-places installed PHPCS standards into the path Composer expects, removing the need to configure the standard path manually.

## Strategy pattern in Mailer

- **`MailerInterface`** defines the contract: `slug`, `label`, `send`, `verify_connection`, `supports` — every driver must satisfy it.
- **`AbstractMailer`** provides shared `log_attempt()` so concrete drivers never duplicate logging code; `send()` is declared abstract, forcing drivers to implement delivery.
- **Concrete drivers** (`SmtpMailer`, `GmailMailer`, `SendGridMailer`, `MailgunMailer`) encapsulate provider-specific transport; swapping a provider requires zero changes outside its own class.
- **`MailerManager`** holds a slug-keyed registry; `resolve_mailer()` reads the `sendstack_active_mailer` option, keeping the selection runtime-configurable without a code deploy.
- **Extensibility:** third-party plugins call `MailerManager::register()` with a custom `MailerInterface` implementation; no core modification needed.

## Strategy pattern in Alerts

- **`AlertInterface`** mirrors `MailerInterface` in structure: `slug`, `label`, `send`, `validate_config` — channels are interchangeable.
- **`EmailChannel`, `SlackChannel`, `DiscordChannel`** are concrete implementations; each encapsulates its own HTTP/API logic.
- **`AlertManager`** routes an `AlertEvent` to every registered, enabled, non-throttled channel; adding a new channel is one `register()` call.
- **`AlertThrottle`** uses WP transients to enforce per-channel/per-event-type cooldown windows, preventing alert storms.
- **`AlertEvent` named constructors** (`send_failed`, `failover_triggered`, `quota_warning`) decouple event *production* (the mailer) from alert *delivery* (the channels), keeping each side independently testable.
