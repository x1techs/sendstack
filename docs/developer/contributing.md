# SendStack — Contributing Guide

> 📝 **Status:** Stub — content will be added as features are implemented.

This document covers everything you need to know to contribute to SendStack — from reporting bugs to submitting pull requests.

---

## Welcome

Thanks for your interest in contributing to SendStack! Whether you're fixing a typo, reporting a bug, or building a new feature, this guide will help you get started. SendStack is built by X1techs and maintained as a free, open-source WordPress plugin.

## Code of Conduct

Be kind, be constructive, be patient. We follow [GitHub's community guidelines](https://docs.github.com/en/site-policy/github-terms/github-community-guidelines). Harassment, discrimination, and unconstructive criticism are not tolerated.

## How to Contribute

- **Bug reports** — Open a GitHub Issue using the bug report template. Include WordPress version, PHP version, active provider, and steps to reproduce.
- **Feature requests** — Open a GitHub Issue using the feature request template. Describe the use case, not just the solution.
- **Pull requests** — See the PR process below. All contributions must be GPL-compatible.

## Development Setup

See [onboarding.md](onboarding.md) for full environment setup instructions (DDEV, Mailpit, Makefile targets, pre-commit hooks).

## Coding Standards

- **WPCS** — All PHP must pass `phpcs` with the project's `phpcs.xml.dist` config
- **PHPStan level 5** — All PHP must pass static analysis with the project's `phpstan.neon.dist`
- **Text domain** — Always `sendstack` — no exceptions
- **Prefix rules** — PHP namespace `SendStack\`, function prefix `sendstack_`, option prefix `sendstack_`, CSS class prefix `sstk-`, hook prefix `sendstack_`
- See [coding-standards.md](coding-standards.md) for the complete reference

## WordPress.org Compliance Rules

These are non-negotiable. A single violation can get the plugin removed from the WordPress.org directory:

- **No competitor mentions** — never reference other SMTP/email plugins by name in code, UI, or docs shipped with the plugin
- **No upsells** — SendStack is 100% free; no "Pro" nags, no feature gating, no upgrade prompts
- **No telemetry** — no usage tracking, no analytics, no phone-home requests of any kind
- **GPL-compatible only** — all code, dependencies, and assets must be GPL v2+ compatible
- **No external code loading** — no CDN-hosted scripts/styles, no remote PHP includes
- **Proper sanitization/escaping** — every input sanitized, every output escaped, every form nonced
- **No obfuscated code** — all code must be human-readable

## Commit Message Format

We use [Conventional Commits](https://www.conventionalcommits.org/):

```
feat: add Mailgun provider support
fix: correct nonce check on settings save
chore: update PHPStan to level 6
docs: add Gmail OAuth setup screenshots
test: add unit tests for failover logic
```

See the project `CONTRIBUTING.md` (repo root) for the canonical reference.

## Pull Request Process

1. Branch from `develop` — never from `main`
2. Name your branch: `feature/short-description`, `fix/short-description`, or `chore/short-description`
3. Fill in the PR template completely
4. Ensure all CI checks pass (PHPCS, PHPStan, PHPUnit)
5. Request review — at least 1 approval required before merge
6. Squash-merge into `develop`

## Release Process

- We follow [Semantic Versioning](https://semver.org/): `MAJOR.MINOR.PATCH`
- All changes are recorded in `CHANGELOG.md` following [Keep a Changelog](https://keepachangelog.com/) format
- Tagging a release on `main` triggers the GitHub Action that deploys to WordPress.org SVN

## Security Vulnerabilities

**Please report security vulnerabilities via [GitHub Security Advisories](https://docs.github.com/en/code-security/security-advisories), not public issues.** We will acknowledge receipt within 48 hours and aim to release a fix within 7 days for critical issues.

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
