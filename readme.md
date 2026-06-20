# SendStack

> Reliable email delivery for WordPress.

![WordPress tested up to](https://img.shields.io/badge/WordPress-6.8-blue)
![PHP minimum](https://img.shields.io/badge/PHP-7.4%2B-purple)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green)

SendStack replaces the default WordPress PHPMailer with a modern delivery layer: multiple provider support (SMTP, Gmail, SendGrid, Mailgun), full email logging, automatic failover, and alert notifications.

## Requirements

| Requirement | Version |
|---|---|
| WordPress | 6.2+ |
| PHP | 7.4+ |
| MySQL | 5.7+ / MariaDB 10.3+ |

## Dev Setup

```bash
# Clone
git clone https://github.com/x1techs/sendstack.git
cd sendstack

# Start environment and install everything
make setup

# Visit the local site
open https://sendstack.ddev.site
```

## Running Tests

```bash
make test           # all suites
make test-unit      # unit only (no DB required)
make test-integration  # requires DDEV + live WP
```

## Linting

```bash
make lint    # PHPCS + PHPStan
make fix     # auto-fix PHPCS violations
```

## Contributing

Please read [CONTRIBUTING.md](CONTRIBUTING.md) before opening a pull request.
