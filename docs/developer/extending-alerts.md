# SendStack — Extending Alerts

> 📝 **Status:** Stub — content will be added as features are implemented.

This document explains how to add a custom alert channel to SendStack. The alert system follows the same extensibility pattern as the mailer system — implement an interface, register via filter.

---

## Overview

SendStack ships with three alert channels (Email, Slack, Discord). Alerts fire when configurable conditions are met — e.g., a send failure, consecutive failures exceeding a threshold, or a provider connection going down. Third-party developers can add custom channels (SMS, PagerDuty, Microsoft Teams, etc.) without modifying plugin code.

## AlertInterface

All alert channels must implement `SendStack\Contracts\AlertInterface`. Required methods:

| Method | Return Type | Description |
|--------|-------------|-------------|
| `get_slug()` | `string` | Unique channel identifier (e.g., `pagerduty`) |
| `get_name()` | `string` | Human-readable name for the admin UI |
| `get_description()` | `string` | One-line description shown in channel picker |
| `get_settings_fields()` | `array` | Array of field definitions for the settings UI |
| `send_alert( AlertDTO $alert )` | `bool` | Dispatch the alert notification; return success/failure |
| `is_configured()` | `bool` | Whether all required settings are filled in |

## Step-by-step: Adding a Custom Channel

1. Create a class that implements `AlertInterface`
2. Implement all required methods
3. Register the channel via the `sendstack_alert_channels` filter
4. (Optional) Add settings fields for configuration in the admin UI
5. Test by triggering an alert condition

Detailed walkthrough will be added after the alert subsystem is implemented.

## Registering via Filter

```php
add_filter( 'sendstack_alert_channels', function ( array $channels ): array {
    $channels['pagerduty'] = \MyPlugin\Alerts\PagerDutyChannel::class;
    return $channels;
} );
```

The key must match the slug returned by `get_slug()`. SendStack will instantiate the class via the Container when the channel is active.

## Example: Minimal Channel Stub

```php
<?php

declare( strict_types=1 );

namespace MyPlugin\Alerts;

use SendStack\Contracts\AlertInterface;
use SendStack\DTOs\AlertDTO;

/**
 * Minimal custom alert channel example.
 */
class PagerDutyChannel implements AlertInterface {

    public function get_slug(): string {
        return 'pagerduty';
    }

    public function get_name(): string {
        return 'PagerDuty';
    }

    public function get_description(): string {
        return 'Send alert notifications to PagerDuty.';
    }

    public function get_settings_fields(): array {
        return [
            [
                'id'    => 'routing_key',
                'label' => __( 'Routing Key', 'my-plugin' ),
                'type'  => 'password',
            ],
        ];
    }

    public function send_alert( AlertDTO $alert ): bool {
        // Your PagerDuty API logic here.
        return true;
    }

    public function is_configured(): bool {
        return ! empty( $this->get_setting( 'routing_key' ) );
    }
}
```

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
