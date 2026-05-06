# Developer Onboarding — SendStack

This document is the deeper companion to [CONTRIBUTING.md](../../CONTRIBUTING.md). CONTRIBUTING covers the what; this document covers the why and what-to-do-when-it-breaks.

---

## Architecture overview

> Full architecture documentation is in `docs/developer/architecture.md` (coming in Step 7 — plugin scaffold).

SendStack is a WordPress plugin structured with PSR-4 namespaced classes under `src/`, a single bootstrap file at `sendstack.php`, and no procedural code outside that bootstrap. The core design uses a Loader/Hook pattern (see `src/Core/`) and a Strategy pattern for mail providers (see `src/Mailer/`). A summary class diagram lives in `docs/developer/architecture.md`.

---

## Team setup

CODEOWNERS uses the GitHub team `@x1techs/sendstack-core`. This team must exist in the GitHub org before anyone can be auto-assigned as a reviewer.

**To set up (one-time, done by Awais Irfan):**
1. Go to github.com/orgs/x1techs/teams → New team → name: `sendstack-core`
2. Add members: Awais Irfan, Ali Akbar, Kaleem
3. Grant the team **Write** access to the `sendstack` repo

Until this team exists, CODEOWNERS auto-assignment silently does nothing — PRs can still be reviewed manually.

---

## What `make setup` actually does

1. `ddev start` — Docker pulls DDEV images (first run: ~2 min; cached after that), creates the network, starts web and DB containers
2. DDEV runs the `post-start` hook → `.ddev/wp-setup.sh` inside the container:
   - Downloads WP core into `./wordpress/` via WP-CLI
   - Generates `wp-config.php` (DDEV injects DB credentials automatically)
   - Writes `WP_DEBUG`, `SAVEQUERIES`, `SCRIPT_DEBUG`, etc. into `wp-config.php`
   - Runs `wp core install` with `admin`/`admin` credentials
   - Installs (but does not activate) testing plugins from WordPress.org
   - Tries to activate `sendstack` — skips gracefully if the plugin doesn't exist yet
3. `composer install` — installs dev dependencies into `vendor/` (skipped if no `composer.json` yet)
4. `npm ci` — installs Node dev dependencies (skipped if no `package.json` yet)
5. `pre-commit install` — wires git hooks into `.git/hooks/` (skipped if pre-commit not installed)

---

## First-run checklist

After `make setup` completes, verify each item:

- [ ] **https://sendstack.ddev.site** loads the WordPress front page
- [ ] **https://sendstack.ddev.site/wp-admin/** accepts `admin` / `admin`
- [ ] WP Admin → Plugins shows "SendStack" (only after Step 7 — plugin scaffold)
- [ ] **https://sendstack.ddev.site:8026** shows the Mailpit UI (empty inbox is correct)
- [ ] WP Admin → Tools → Query Monitor loads (after you activate it manually)

> **Screenshot placeholders:** add browser screenshots here once the plugin scaffold exists and the full admin UI is built (Step 7+). Update this section with each major UI milestone.

---

## xdebug + IDE setup

xdebug is disabled by default to keep request times fast. Enable it only when you need step debugging.

```bash
make xdebug-on    # enable
make xdebug-off   # disable when done
```

**VS Code:**

1. Install the [PHP Debug](https://marketplace.visualstudio.com/items?itemName=xdebug.php-debug) extension
2. Add to `.vscode/launch.json` (create if missing — this file is gitignored unless you add it to `.gitattributes`):

```json
{
  "version": "0.2.0",
  "configurations": [
    {
      "name": "Listen for Xdebug (DDEV)",
      "type": "php",
      "request": "launch",
      "port": 9003,
      "pathMappings": {
        "/var/www/html": "${workspaceFolder}/wordpress"
      }
    }
  ]
}
```

3. Press F5 to start listening, then reload a WP page.

**PhpStorm:** Follow the [official DDEV + PhpStorm guide](https://ddev.readthedocs.io/en/stable/users/debugging-profiling/step-debugging/#phpstorm-debugging).

---

## WP-CLI access

Two ways to run WP-CLI commands:

```bash
# Option 1: make pass-through (no need to open a shell)
make wp plugin list
make wp user list
make wp option get siteurl

# Option 2: shell into container, then use wp directly
make shell
wp plugin list
exit
```

---

## Mail testing

All `wp_mail()` calls are routed to Mailpit. Nothing leaves your machine.

```bash
make mail   # opens Mailpit UI in your browser
```

Mailpit UI: **https://sendstack.ddev.site:8026**

Use the "Send test email" feature in the SendStack admin (once built) to trigger a test send and verify it lands in Mailpit.

---

## Switching PHP versions

The project minimum is PHP 7.4. DDEV starts on 7.4 by default. To test on 8.1:

```bash
make php-switch PHP=8.1
# do your testing
make php-switch PHP=7.4   # always switch back before committing
```

This rewrites `.ddev/config.yaml` — do **not** commit the version change unless you intentionally want to raise the team's default.

---

## Common pitfalls

| Symptom | Likely cause | Fix |
|---|---|---|
| `ddev start` hangs or errors out | Docker not running | Open Docker Desktop and wait for the whale icon |
| `command not found: ddev` on Windows | Running outside WSL2 | Open a WSL2 terminal |
| Plugin not visible in WP Admin → Plugins | Bind-mount failed | `make remount` |
| Emails not captured by Mailpit | Mailpit add-on not active, or wrong SMTP config | Verify Mailpit at https://sendstack.ddev.site:8026; run `ddev restart` |
| PHPUnit fails with DB connection error | WP install incomplete or DB out of sync | `make reset` |
| `make: *** Error N` with unclear cause | Tool missing or composer.json absent | `make doctor` to identify missing tools |
| `pre-commit: command not found` | Python not installed or not in PATH | `pip install pre-commit` then `make hooks`; or skip with `git commit --no-verify` |
| File changes not reflected in container | macOS bind-mount cache stale | `make restart` |
| `ddev get ddev/ddev-mailpit` error on `ddev start` | Add-on already installed | Safe to ignore — Mailpit is already configured |
| WP admin shows 500 error after `make reset` | WP install script ran but DB import not complete | Wait 10 seconds and reload; if persistent, run `make reset` again |
| `wp: command not found` inside container | WP-CLI not on PATH | Use `ddev exec wp` instead of bare `wp` when outside the container |

---

## How to update everything

After pulling new changes from `develop`:

```bash
git pull origin develop
make setup   # idempotent — installs any newly added deps, re-runs any new setup steps
```

`make setup` is safe to run anytime. It skips steps that are already complete.
