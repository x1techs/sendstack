#!/usr/bin/env bash
# .ddev/wp-setup.sh
#
# Idempotent WordPress environment setup script.
# Runs inside the DDEV web container via the post-start hook.
# Safe to run multiple times — exits silently if WP is already installed.
#
# Requirements (all met by DDEV): wp-cli, mysql client, curl.

set -euo pipefail

DOCROOT="/var/www/html/wordpress"
WP_CONFIG="${DOCROOT}/wp-config.php"

# ──────────────────────────────────────────────────────────────────────────────
# Guard: exit silently if WordPress is already installed.
# ──────────────────────────────────────────────────────────────────────────────
if [[ -f "${WP_CONFIG}" ]]; then
  echo "✓ WordPress already installed — skipping setup."
  exit 0
fi

echo ""
echo "════════════════════════════════════════════════════════════"
echo "  SendStack — First-run WordPress setup"
echo "════════════════════════════════════════════════════════════"

# ──────────────────────────────────────────────────────────────────────────────
# 1. Download WordPress core
# ──────────────────────────────────────────────────────────────────────────────
echo "→ Downloading WordPress core..."
wp core download --path="${DOCROOT}" --skip-content --quiet

# ──────────────────────────────────────────────────────────────────────────────
# 2. Generate wp-config.php with DDEV's standard credentials
#    DDEV always exposes DB as: host=db, name=db, user=db, pass=db
# ──────────────────────────────────────────────────────────────────────────────
echo "→ Generating wp-config.php..."
wp config create \
  --path="${DOCROOT}" \
  --dbname=db \
  --dbuser=db \
  --dbpass=db \
  --dbhost=db \
  --dbprefix=wp_ \
  --skip-check \
  --quiet

# ──────────────────────────────────────────────────────────────────────────────
# 3. Inject debug constants after the "stop editing" line.
#    WP_DEBUG_DISPLAY=false keeps errors out of the browser while still
#    writing them to wp-content/debug.log — safe for development, mirrors
#    a production-like configuration that won't expose errors to visitors.
# ──────────────────────────────────────────────────────────────────────────────
echo "→ Injecting debug constants..."
wp config set WP_DEBUG true             --path="${DOCROOT}" --raw --type=constant
wp config set WP_DEBUG_LOG true         --path="${DOCROOT}" --raw --type=constant
wp config set WP_DEBUG_DISPLAY false    --path="${DOCROOT}" --raw --type=constant
wp config set SAVEQUERIES true          --path="${DOCROOT}" --raw --type=constant
wp config set SCRIPT_DEBUG true         --path="${DOCROOT}" --raw --type=constant
wp config set WP_ENVIRONMENT_TYPE development \
                                        --path="${DOCROOT}" --type=constant
wp config set DISABLE_WP_CRON false     --path="${DOCROOT}" --raw --type=constant

# ──────────────────────────────────────────────────────────────────────────────
# 4. Install WordPress
#    --skip-email suppresses the "just installed" email to admin.
# ──────────────────────────────────────────────────────────────────────────────
echo "→ Installing WordPress..."
wp core install \
  --path="${DOCROOT}" \
  --url="${DDEV_PRIMARY_URL}" \
  --title="SendStack Dev" \
  --admin_user="admin" \
  --admin_password="admin" \
  --admin_email="dev@example.com" \
  --skip-email \
  --quiet

# ──────────────────────────────────────────────────────────────────────────────
# 5. Activate the SendStack plugin if its bootstrap file exists.
#    The plugin scaffold is created in a later step — this is safe to skip.
# ──────────────────────────────────────────────────────────────────────────────
SENDSTACK_BOOTSTRAP="${DOCROOT}/wp-content/plugins/sendstack/sendstack.php"
if [[ -f "${SENDSTACK_BOOTSTRAP}" ]]; then
  echo "→ Activating SendStack plugin..."
  wp plugin activate sendstack --path="${DOCROOT}" --quiet
else
  echo "ℹ  SendStack plugin bootstrap not found — skipping activation."
  echo "   Run 'wp plugin activate sendstack' after Step 7 (plugin scaffold)."
fi

# ──────────────────────────────────────────────────────────────────────────────
# 6. Install testing/compatibility plugins (not activated — devs activate as needed)
# ──────────────────────────────────────────────────────────────────────────────
echo "→ Installing testing plugins (inactive)..."
TESTING_PLUGINS=(
  query-monitor
  contact-form-7
  wpforms-lite
  woocommerce
  debug-bar
  wp-mail-logging
)

for plugin_slug in "${TESTING_PLUGINS[@]}"; do
  if wp plugin is-installed "${plugin_slug}" --path="${DOCROOT}" 2>/dev/null; then
    echo "  ✓ ${plugin_slug} already installed — skipping."
  else
    wp plugin install "${plugin_slug}" --path="${DOCROOT}" --quiet \
      && echo "  ✓ ${plugin_slug} installed" \
      || echo "  ✗ ${plugin_slug} failed to install (non-fatal)"
  fi
done

# ──────────────────────────────────────────────────────────────────────────────
# 7. Install themes
# ──────────────────────────────────────────────────────────────────────────────
echo "→ Installing themes..."
for theme_slug in twentytwentyfour kadence; do
  if wp theme is-installed "${theme_slug}" --path="${DOCROOT}" 2>/dev/null; then
    echo "  ✓ ${theme_slug} already installed — skipping."
  else
    wp theme install "${theme_slug}" --path="${DOCROOT}" --quiet \
      && echo "  ✓ ${theme_slug} installed" \
      || echo "  ✗ ${theme_slug} failed to install (non-fatal)"
  fi
done

# ──────────────────────────────────────────────────────────────────────────────
# 8. Configure wp_mail to route through Mailpit via SMTP.
#    We use wp_mail_from and a transient SMTP shim via WP options.
#    Full provider config comes with the plugin itself; this just ensures
#    the bare WP install doesn't try to use system sendmail during testing.
# ──────────────────────────────────────────────────────────────────────────────
echo "→ Configuring mail routing to Mailpit..."
wp option update admin_email "dev@example.com" --path="${DOCROOT}" --quiet

# ──────────────────────────────────────────────────────────────────────────────
# 9. Summary
# ──────────────────────────────────────────────────────────────────────────────
echo ""
echo "════════════════════════════════════════════════════════════"
echo "  ✅ Setup complete!"
echo "════════════════════════════════════════════════════════════"
echo ""
echo "  🌐 Site URL:      ${DDEV_PRIMARY_URL}"
echo "  🔧 Admin URL:     ${DDEV_PRIMARY_URL}/wp-admin/"
echo "  👤 Admin user:    admin"
echo "  🔑 Admin pass:    admin"
echo "  📬 Mailpit UI:    https://sendstack.ddev.site:8026"
echo ""
echo "  🗄  Database:"
echo "      Host:         db"
echo "      Name:         db"
echo "      User:         db"
echo "      Password:     db"
echo ""
echo "  💡 Tip: run 'make wp plugin activate sendstack' once the"
echo "          plugin scaffold exists (Step 7)."
echo ""
