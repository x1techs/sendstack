# SendStack — Hooks Reference

> 📝 **Status:** Stub — content will be added as features are implemented.

> Full hook reference will be auto-generated via phpDocumentor. This file will be updated after each feature is implemented.

This document lists every action and filter that SendStack exposes for developers. Use these hooks to extend, modify, or integrate with SendStack without editing plugin code.

---

## Overview

SendStack exposes hooks at every major decision point — before and after sending, during logging, when alerts fire, and throughout the admin UI. All hooks follow the naming convention `sendstack_{subsystem}_{event}` and are documented with `@since` tags in the source code.

## How to Use Hooks

Brief primer on using SendStack hooks with `add_action()` and `add_filter()`, including a minimal code example showing where to place hook registrations (e.g., in a custom plugin or theme's `functions.php`).

## Mailer Hooks

| Hook Name | Type | Parameters | Purpose | Since |
|-----------|------|------------|---------|-------|
| *Populated after mailer implementation* | | | | |

## Logger Hooks

| Hook Name | Type | Parameters | Purpose | Since |
|-----------|------|------------|---------|-------|
| *Populated after logger implementation* | | | | |

## Alert Hooks

| Hook Name | Type | Parameters | Purpose | Since |
|-----------|------|------------|---------|-------|
| *Populated after alert implementation* | | | | |

## Admin Hooks

| Hook Name | Type | Parameters | Purpose | Since |
|-----------|------|------------|---------|-------|
| *Populated after admin UI implementation* | | | | |

## REST Hooks

| Hook Name | Type | Parameters | Purpose | Since |
|-----------|------|------------|---------|-------|
| *Populated after REST controller implementation* | | | | |

## Adding Custom Providers

SendStack's mailer system is designed to be extended. See [extending-mailers.md](extending-mailers.md) for a full walkthrough of building and registering a custom mail provider.

## Adding Custom Alert Channels

SendStack's alert system supports custom notification channels beyond the built-in Email, Slack, and Discord. See [extending-alerts.md](extending-alerts.md) for a full walkthrough.

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
