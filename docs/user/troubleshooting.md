# Troubleshooting

> 📝 **Status:** Stub — content will be added as features are implemented.

This guide covers common issues you might run into with SendStack and how to fix them. If your issue isn't listed here, check the [FAQ](faq.md) or open a question on [GitHub Discussions](https://github.com/x1techs/sendstack/discussions).

---

## Common Issues

| Problem | Possible Cause | Solution |
|---------|---------------|----------|
| Emails not sending | Provider not configured | Go to **Settings → SendStack** and complete the Setup Wizard |
| Emails going to spam | Provider reputation or missing DNS records | Check your provider's dashboard for SPF/DKIM/DMARC setup guides |
| OAuth connection fails (Gmail) | Redirect URI mismatch | Verify the Authorized Redirect URI in your Google Cloud Console matches exactly what SendStack shows |
| Logs not showing | Logging disabled | Go to **Settings → SendStack → Logging** and enable email logging |
| Test email fails | Wrong credentials or server unreachable | Use the **Verify Connection** button first to confirm your credentials work, then retry |
| "Permission denied" errors | Insufficient WordPress role | SendStack settings require the Administrator role (`manage_options` capability) |
| Failover not activating | Secondary provider not configured | Configure a second provider in **Settings → SendStack → Connections** |
| Alerts not firing | Alert channels not set up | Go to **Settings → SendStack → Alerts** and configure at least one channel |

## Still Stuck?

1. Check the **SendStack → Email Log** for error messages — they often point to the exact problem
2. Try sending a test email from **SendStack → Test Email** and note any error details
3. If the issue is provider-specific, check your provider's status page and documentation
4. Open a [GitHub Issue](https://github.com/x1techs/sendstack/issues) with your WordPress version, PHP version, provider name, and the error message

---

*Last updated: v0.0.0 (stub created during initial project scaffolding)*
