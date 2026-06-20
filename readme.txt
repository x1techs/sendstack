=== SendStack ===

Contributors:      x1techs
Tags:              smtp, email, mail, deliverability, sendgrid, mailgun, gmail
Requires at least: 6.2
Tested up to:      6.8
Requires PHP:      7.4
Stable tag:        0.1.0
License:           GPLv2 or later
License URI:       https://www.gnu.org/licenses/gpl-2.0.html

Reliable email delivery for WordPress — multiple SMTP and API providers, logging, failover, and alerts.

== Description ==

SendStack replaces the default WordPress PHPMailer with a flexible delivery layer that supports:

* Multiple providers: SMTP, Gmail (OAuth 2.0), SendGrid, and Mailgun
* Email logging — every send attempt is recorded with status and response
* Automatic failover — if one provider fails, SendStack retries via a backup
* Alert channels — get notified on failures via Email, Slack, or Discord
* REST API — full programmatic access to logs, stats, and settings
* Setup wizard — get configured in minutes

== Installation ==

1. Upload the `sendstack` folder to `/wp-content/plugins/`.
2. Activate **SendStack** through the *Plugins* menu in WordPress.
3. Navigate to **SendStack → Setup Wizard** and follow the steps.

== Frequently Asked Questions ==

= Does SendStack replace wp_mail()? =

Yes. SendStack hooks into WordPress's email system transparently; existing code calling `wp_mail()` will automatically use the configured provider.

= Is my API key stored securely? =

Credentials are encrypted with AES-256-GCM before being stored in the database.

= Can I use more than one provider? =

Yes. You can configure a primary provider and a failover provider. SendStack will automatically retry through the failover provider on failure.

== Screenshots ==

1. Dashboard — recent send activity and delivery stats.
2. Settings — provider configuration and credentials.
3. Logs — searchable table of all send attempts.
4. Alerts — notification channel configuration.

== Changelog ==

= 0.1.0 =
* Added: Initial scaffolding — core architecture, service providers, database schema, admin screens, REST API, mailer abstraction, logging, stats, and alerts framework.

== External Services ==

SendStack connects to third-party services only when you configure a provider that requires one.

* **SendGrid API** — `https://api.sendgrid.com/v3/mail/send` — sends email via the SendGrid HTTP API. See [SendGrid Privacy Policy](https://sendgrid.com/policies/privacy/).
* **Mailgun API** — `https://api.mailgun.net/v3/{domain}/messages` — sends email via the Mailgun HTTP API. See [Mailgun Privacy Policy](https://www.mailgun.com/privacy-policy/).
* **Google OAuth 2.0 / Gmail API** — `https://oauth2.googleapis.com` and `https://www.googleapis.com/gmail/v1/users/me/messages/send` — authenticates your Google account and sends email via Gmail. See [Google Privacy Policy](https://policies.google.com/privacy).

No data is sent to X1techs or any SendStack-owned endpoint.
