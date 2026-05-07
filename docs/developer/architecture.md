# SendStack — Architecture Overview

> 📝 **Status:** Stub — content will be added as features are implemented.

This document describes the internal architecture of the SendStack plugin: how it boots, how classes are organized, how email routing decisions are made, and how the system is designed to be extended by third-party developers.

---

## Overview

High-level description of how the plugin is structured — single entry point, PSR-4 autoloading, service-provider pattern, strategy-based mailer routing, and WordPress hook integration.

## Design Principles

Summary of the architectural principles guiding all code decisions: PSR-4 autoloading, OOP with interfaces and abstract base classes, the Strategy pattern for swappable mailer and alert providers, separation of concerns via service providers, and deep integration with WordPress hooks for extensibility.

## Directory Structure

Canonical file tree for the plugin. Refer to the project blueprint for the authoritative version; this section mirrors it with brief annotations explaining the purpose of each top-level directory.

## Class Diagram

Class diagram will be generated via phpDocumentor after the v1.0 scaffold is complete. This section will contain the rendered diagram plus a prose walkthrough of the key relationships.

## Bootstrap Flow

Step-by-step explanation of what happens when WordPress loads SendStack:

1. **Constants** — version, paths, file references
2. **Autoloader** — Composer PSR-4 autoloader registration
3. **Requirements check** — PHP and WordPress version gate
4. **Plugin::instance()->boot()** — singleton initialization
5. **Service providers** — each provider registers its hooks, classes, and assets
6. **wp_mail override** — the `wp_mail` filter/pluggable replacement is connected

## Service Providers

What service providers are, why SendStack uses them to organize subsystems, and a table of all providers shipped in v1.0 (Admin, Mailer, Logger, Alert, REST, etc.) with one-line descriptions.

## Dependency Injection

How the lightweight Container class works: binding interfaces to concrete implementations, resolving dependencies, and how service providers use it to wire things together.

## Hook Architecture

How actions and filters are registered via the Loader class, why hooks are batched and registered in a single pass, and the naming convention for all SendStack hooks (`sendstack_{subsystem}_{event}`).

## Database Layer

Overview of the two custom tables (`sendstack_logs`, `sendstack_stats`), why custom tables were chosen over post meta or options for high-volume data, and a pointer to [database-schema.md](database-schema.md) for full column definitions.

## Request Lifecycle

What happens from the moment `wp_mail()` is called to the moment the email is handed off to a provider — including provider selection, connection validation, the send attempt, failover logic, logging, and alert evaluation.

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
