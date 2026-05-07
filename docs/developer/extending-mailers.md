# SendStack — Extending Mailers

> 📝 **Status:** Stub — content will be added as features are implemented.

This document explains how to add a custom email provider to SendStack. The mailer system is built on interfaces and the Strategy pattern, so third-party developers can register new providers without modifying plugin code.

---

## Overview

SendStack ships with four providers (SMTP, Gmail, SendGrid, Mailgun), but is designed to be extended. Any class that implements `MailerInterface` can be registered as a provider. SendStack handles provider selection, failover, logging, and alerts — your provider only needs to handle the actual sending.

## MailerInterface

All providers must implement `SendStack\Contracts\MailerInterface`. Required methods:

| Method | Return Type | Description |
|--------|-------------|-------------|
| `get_slug()` | `string` | Unique provider identifier (e.g., `postmark`) |
| `get_name()` | `string` | Human-readable name for the admin UI |
| `get_description()` | `string` | One-line description shown in provider picker |
| `get_settings_fields()` | `array` | Array of field definitions for the settings UI |
| `send( EmailDTO $email )` | `SendResult` | Attempt to send the email; return success/failure result object |
| `verify_connection()` | `ConnectionResult` | Test whether current credentials are valid |
| `is_configured()` | `bool` | Whether all required settings are filled in |

## AbstractMailer

Extending `SendStack\Mailers\AbstractMailer` instead of implementing the interface directly gives you default implementations for common patterns: settings retrieval from the Container, standard error formatting, and retry-compatible exception handling. Override only what your provider needs.

## Step-by-step: Adding a Custom Provider

1. Create a class that extends `AbstractMailer` (or implements `MailerInterface`)
2. Implement all required methods
3. Register the provider via the `sendstack_mailer_providers` filter
4. (Optional) Add a settings fields array for the admin UI
5. Test with the built-in "Send Test Email" and "Verify Connection" features

Detailed walkthrough will be added after the mailer subsystem is implemented.

## Registering via Filter

```php
add_filter( 'sendstack_mailer_providers', function ( array $providers ): array {
    $providers['postmark'] = \MyPlugin\Mailers\PostmarkMailer::class;
    return $providers;
} );
```

The key must match the slug returned by `get_slug()`. SendStack will instantiate the class via the Container when the provider is selected.

## Testing Your Provider

Guidance on writing PHPUnit tests for a custom provider, using the test helpers and mocks provided in `tests/`. Will include example test cases after the test infrastructure is built.

## Example: Minimal Provider Stub

```php
<?php

declare( strict_types=1 );

namespace MyPlugin\Mailers;

use SendStack\Contracts\MailerInterface;
use SendStack\DTOs\EmailDTO;
use SendStack\DTOs\SendResult;
use SendStack\DTOs\ConnectionResult;

/**
 * Minimal custom mailer example.
 */
class PostmarkMailer implements MailerInterface {

    public function get_slug(): string {
        return 'postmark';
    }

    public function get_name(): string {
        return 'Postmark';
    }

    public function get_description(): string {
        return 'Send emails via the Postmark transactional email service.';
    }

    public function get_settings_fields(): array {
        return [
            [
                'id'    => 'server_token',
                'label' => __( 'Server API Token', 'my-plugin' ),
                'type'  => 'password',
            ],
        ];
    }

    public function send( EmailDTO $email ): SendResult {
        // Your sending logic here.
        return SendResult::success( 'postmark-message-id-123' );
    }

    public function verify_connection(): ConnectionResult {
        // Your connection test logic here.
        return ConnectionResult::ok();
    }

    public function is_configured(): bool {
        // Check whether required settings are present.
        return ! empty( $this->get_setting( 'server_token' ) );
    }
}
```

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
