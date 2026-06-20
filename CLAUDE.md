# SendStack — Claude Code Project Instructions

## Project Identity
- **Plugin:** SendStack — free WordPress SMTP & email deliverability plugin
- **Company:** X1techs (x1techs.com)
- **Author:** X1techs | support@x1techs.com
- **Repo:** github.com/x1techs/sendstack
- **Slug:** sendstack | **Namespace:** SendStack\ | **Text domain:** sendstack
- **Prefixes:** sendstack_ (PHP), sstk_ (CSS/JS)
- **License:** GPLv2+ | **Min PHP:** 7.4 | **Min WP:** 6.2

## Team
- Awais Irfan (lead), Ali Akbar, Kaleem
- CODEOWNERS: generalist — @x1techs/sendstack-core reviews everything

## Locked v1.0 Decisions
- Providers: SMTP (generic), Gmail OAuth (BYOC), SendGrid, Mailgun
- Features: Setup Wizard, email logging, primary/backup failover, failure alerts (Email/Slack/Discord), test email, dashboard stats
- Gmail OAuth: BYOC only (user supplies own Client ID/Secret)
- WP-CLI: deferred to v1.1
- Migration tool: deferred to v1.1
- No competitor mentions anywhere — ever
- No telemetry, no upsells, no premium tier

## Coding Standards (enforce always)
- `defined('ABSPATH') || exit;` AFTER namespace declaration in every PHP file
- PSR-4 autoloading, OOP with namespaces
- Nonces on every form/AJAX call
- Capability checks before privileged actions (current_user_can())
- Sanitize all input: sanitize_text_field(), sanitize_email(), absint(), wp_kses_post()
- Escape all output: esc_html(), esc_attr(), esc_url(), wp_kses()
- DB queries: always $wpdb->prepare(), never string interpolation
- Enqueue scripts/styles with versioning
- All user-facing strings wrapped in translation functions with text domain 'sendstack'
- WordPress Coding Standards (WPCS) via phpcs.xml.dist
- PHPStan level 5 via phpstan.neon.dist

## Teaching Mode (mandatory)
Every non-trivial file must include a "Concepts in this file" block after the code:
- Explain WordPress-specific patterns (why this hook, why this priority, why this sanitizer)
- Explain architecture decisions (why this class, why this pattern)
- Skip language basics (don't explain public function)
- Call out security and performance implications
- Brief — bullets or short paragraphs, not essays

## Architecture
- Strategy pattern for mailer providers (MailerInterface -> AbstractMailer -> concrete)
- Strategy pattern for alert channels (AlertInterface -> concrete channels)
- AES-256-GCM credential encryption via AUTH_KEY
- Two custom DB tables: sendstack_logs, sendstack_stats
- REST API under /sendstack/v1/
- Minimal DI container in Core\Container

## Git Workflow
- Branches: main (stable), develop (integration), feature/*, hotfix/*
- Conventional Commits: feat:, fix:, chore:, docs:, refactor:, test:, ci:
- PRs required for main and develop
- CI runs PHPCS + PHPStan + PHPUnit on every PR

## Dev Environment
- DDEV + Mailpit for local WordPress
- make up / make down / make test / make lint / make fix
- WordPress at https://sendstack.ddev.site
- Mailpit at https://sendstack.ddev.site:8026

## File Structure Convention
- src/ — PSR-4 namespaced classes
- assets/src/ — source JS/SCSS
- assets/build/ — compiled output
- templates/ — overridable template files
- tests/Unit/ — unit tests (no DB)
- tests/Integration/ — integration tests (real WP)
- docs/user/ — end-user documentation
- docs/developer/ — developer documentation

## Common Pitfalls
- Namespace declaration MUST come before defined('ABSPATH') || exit;
- PHPCS filename rule is disabled — we use PSR-4 filenames (Plugin.php not class-plugin.php)
- Test files do NOT need the ABSPATH exit guard
- WSL2 files may have CRLF — always use LF (pre-commit enforces this)
