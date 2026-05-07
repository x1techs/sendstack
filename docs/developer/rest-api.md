# SendStack — REST API Reference

> 📝 **Status:** Stub — content will be added as features are implemented.

This document covers every REST API endpoint that SendStack registers, including authentication requirements, request/response schemas, and error handling.

---

## Overview

SendStack exposes a REST API under the `/sendstack/v1/` namespace. All endpoints require authentication and appropriate capabilities. The API is used internally by the admin React UI and is available for external integrations.

## Authentication

SendStack REST endpoints use WordPress cookie authentication with nonce verification (the standard `X-WP-Nonce` header). Application Passwords are also supported for external consumers. All endpoints require the `manage_options` capability unless otherwise noted.

## Error Responses

SendStack follows the standard WordPress REST error format:

```json
{
  "code": "sendstack_error_code",
  "message": "Human-readable description",
  "data": { "status": 400 }
}
```

Error codes and their meanings will be documented per-endpoint after implementation.

## Endpoints

| Method | Route | Auth Required | Purpose |
|--------|-------|---------------|---------|
| `GET` | `/sendstack/v1/logs` | Yes | Retrieve paginated email logs |
| `GET` | `/sendstack/v1/logs/{id}` | Yes | Retrieve a single log entry |
| `DELETE` | `/sendstack/v1/logs` | Yes | Bulk delete / purge logs |
| `GET` | `/sendstack/v1/stats` | Yes | Retrieve email statistics |
| `GET` | `/sendstack/v1/settings` | Yes | Retrieve plugin settings |
| `POST` | `/sendstack/v1/settings` | Yes | Update plugin settings |
| `GET` | `/sendstack/v1/connections` | Yes | List configured provider connections |
| `POST` | `/sendstack/v1/connections/test` | Yes | Test a provider connection |
| `POST` | `/sendstack/v1/test-email` | Yes | Send a test email |
| `GET` | `/sendstack/v1/oauth/authorize` | Yes | Initiate OAuth flow (Gmail) |
| `GET` | `/sendstack/v1/oauth/callback` | Yes | Handle OAuth callback (Gmail) |

## Request/Response Schemas

Schemas will be documented here after controllers are implemented. Each endpoint will include a full JSON schema for the request body (where applicable) and the response payload.

## Rate Limiting

No custom rate limiting in v1.0 — relies on WordPress core and server-level limits. If demand arises, a `sendstack_rest_rate_limit` filter will be introduced in a future version.

## Versioning

The current namespace is `/sendstack/v1/`. If breaking changes are introduced in a future major version, a `/sendstack/v2/` namespace will be added while maintaining backward compatibility on `/v1/` for at least one major release cycle.

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
