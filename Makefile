# Makefile — SendStack plugin development commands.
#
# All commands wrap DDEV and project tooling so you never need to remember
# tool-specific syntax. Requires GNU Make (standard on Linux/macOS; install
# via 'brew install make' on macOS if needed, or use WSL2 on Windows).
#
# Usage: make <target>
#        make wp plugin list          (WP-CLI pass-through)
#        make php-switch PHP=8.1      (PHP version switch)

.DEFAULT_GOAL := help
.PHONY: help up down restart setup reset \
        test test-unit test-integration \
        lint fix logs shell mail \
        xdebug-on xdebug-off php-switch \
        hooks remount zip doctor wp

# Colours for help output
CYAN  := \033[0;36m
RESET := \033[0m

# ──────────────────────────────────────────────────────────────────────────────
# Help — print all targets that have a ## comment
# ──────────────────────────────────────────────────────────────────────────────
help: ## Show this help screen
	@echo ""
	@echo "SendStack — available make targets"
	@echo "────────────────────────────────────────────────────────"
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) \
		| awk 'BEGIN {FS = ":.*?## "}; {printf "  $(CYAN)%-20s$(RESET) %s\n", $$1, $$2}'
	@echo ""

# ──────────────────────────────────────────────────────────────────────────────
# Environment lifecycle
# ──────────────────────────────────────────────────────────────────────────────
up: ## Start the DDEV dev environment
	ddev start

down: ## Stop the DDEV dev environment
	ddev stop

restart: ## Restart DDEV (use after config changes)
	ddev restart

setup: ## First-run setup: start env, install WP, install deps, install hooks
	@echo "→ Starting DDEV..."
	ddev start
	@echo "→ Installing Composer dependencies..."
	@if [ -f composer.json ]; then \
		ddev exec composer install --no-interaction; \
	else \
		echo "  ℹ  composer.json not found — skipping (add in Step 7)."; \
	fi
	@echo "→ Installing Node dependencies..."
	@if [ -f package.json ]; then \
		ddev exec npm ci; \
	else \
		echo "  ℹ  package.json not found — skipping (add in Step 7)."; \
	fi
	@echo "→ Installing pre-commit hooks..."
	@if command -v pre-commit > /dev/null 2>&1; then \
		pre-commit install; \
		echo "  ✓ pre-commit hooks installed."; \
	else \
		echo "  ℹ  pre-commit not found — skipping."; \
		echo "     Install with: pip install pre-commit  then run: make hooks"; \
	fi
	@echo ""
	@echo "✅ Setup complete. Visit https://sendstack.ddev.site"

reset: ## ⚠️  Destroy and rebuild entire environment (asks for confirmation)
	@echo ""
	@echo "⚠️  WARNING: This will delete your local WordPress install,"
	@echo "   vendor/, and node_modules/. Your plugin SOURCE code is safe"
	@echo "   (it lives in the repo root, not wordpress/)."
	@echo ""
	@printf "   Type 'yes' to continue: "; \
	read CONFIRM; \
	if [ "$$CONFIRM" != "yes" ]; then \
		echo "Aborted."; \
		exit 1; \
	fi
	ddev delete -Oy
	rm -rf wordpress vendor node_modules
	$(MAKE) setup

# ──────────────────────────────────────────────────────────────────────────────
# Tests
# ──────────────────────────────────────────────────────────────────────────────
test: ## Run all PHPUnit tests
	@if [ -f composer.json ]; then \
		ddev exec composer run test; \
	else \
		echo "ℹ  No composer.json yet — tests will be available after Step 7."; \
	fi

test-unit: ## Run unit tests only
	@if [ -f composer.json ]; then \
		ddev exec composer run test:unit; \
	else \
		echo "ℹ  No composer.json yet — tests will be available after Step 7."; \
	fi

test-integration: ## Run integration tests only (requires live WP + DB)
	@if [ -f composer.json ]; then \
		ddev exec composer run test:integration; \
	else \
		echo "ℹ  No composer.json yet — tests will be available after Step 7."; \
	fi

# ──────────────────────────────────────────────────────────────────────────────
# Code quality
# ──────────────────────────────────────────────────────────────────────────────
lint: ## Run PHPCS (coding standards) and PHPStan (static analysis)
	@if [ -f composer.json ]; then \
		echo "→ PHPCS..."; \
		ddev exec composer run lint; \
		echo "→ PHPStan..."; \
		ddev exec composer run analyze; \
	else \
		echo "ℹ  No composer.json yet — linting available after Step 7."; \
	fi

fix: ## Auto-fix PHPCS violations with phpcbf
	@if [ -f composer.json ]; then \
		ddev exec composer run lint:fix; \
	else \
		echo "ℹ  No composer.json yet — available after Step 7."; \
	fi

# ──────────────────────────────────────────────────────────────────────────────
# Container access
# ──────────────────────────────────────────────────────────────────────────────
logs: ## Tail DDEV container logs (Ctrl+C to stop)
	ddev logs -f

shell: ## Open a shell inside the DDEV web container
	ddev ssh

# ──────────────────────────────────────────────────────────────────────────────
# WP-CLI pass-through
# Usage: make wp <any wp-cli command>
# Example: make wp plugin list
#          make wp user list
# ──────────────────────────────────────────────────────────────────────────────
wp: ## Pass-through to WP-CLI — usage: make wp plugin list
	ddev exec wp $(filter-out $@,$(MAKECMDGOALS))

# Prevent make from treating extra args as targets
%:
	@:

# ──────────────────────────────────────────────────────────────────────────────
# Mail
# ──────────────────────────────────────────────────────────────────────────────
mail: ## Open Mailpit email UI in your browser
	@echo "→ Opening Mailpit..."
	@if command -v ddev > /dev/null 2>&1; then \
		ddev launch :8026 || echo "  Mailpit URL: https://sendstack.ddev.site:8026"; \
	else \
		echo "  Mailpit URL: https://sendstack.ddev.site:8026"; \
	fi

# ──────────────────────────────────────────────────────────────────────────────
# Debugging
# ──────────────────────────────────────────────────────────────────────────────
xdebug-on: ## Enable xdebug (slows requests — enable only when debugging)
	ddev xdebug on

xdebug-off: ## Disable xdebug
	ddev xdebug off

# ──────────────────────────────────────────────────────────────────────────────
# PHP version switch
# Usage: make php-switch PHP=8.1
#        make php-switch PHP=7.4
# ──────────────────────────────────────────────────────────────────────────────
php-switch: ## Switch PHP version — usage: make php-switch PHP=8.1
ifndef PHP
	@echo "Usage: make php-switch PHP=<version>  (e.g. PHP=8.1 or PHP=7.4)"
	@exit 1
endif
	ddev config --php-version=$(PHP)
	ddev restart
	@echo "✓ Switched to PHP $(PHP). Restart your IDE to re-attach debugger."

# ──────────────────────────────────────────────────────────────────────────────
# Hooks
# ──────────────────────────────────────────────────────────────────────────────
hooks: ## Install pre-commit git hooks (requires Python + pre-commit)
	@if command -v pre-commit > /dev/null 2>&1; then \
		pre-commit install; \
		echo "✓ pre-commit hooks installed."; \
	else \
		echo "✗ pre-commit not found. Install: pip install pre-commit"; \
		exit 1; \
	fi

# ──────────────────────────────────────────────────────────────────────────────
# Mount repair
# ──────────────────────────────────────────────────────────────────────────────
remount: ## Restart DDEV to repair bind-mount (use when plugin not visible in WP)
	@echo "→ Restarting DDEV to re-apply bind mounts..."
	ddev restart
	@echo "✓ Done. Check WP Admin → Plugins for 'SendStack'."

# ──────────────────────────────────────────────────────────────────────────────
# Distribution zip
# Builds a clean plugin zip honouring .distignore for manual install / QA.
# The SVN deploy CI action builds its own zip from a fresh checkout — this
# target is for local testing and sharing with non-technical stakeholders.
# ──────────────────────────────────────────────────────────────────────────────
zip: ## Build a distributable plugin zip (respects .distignore)
	@echo "→ Building plugin zip..."
	@PLUGIN_SLUG="sendstack"; \
	OUT_DIR="dist"; \
	ZIP_NAME="$${PLUGIN_SLUG}.zip"; \
	rm -rf "$${OUT_DIR}"; \
	mkdir -p "$${OUT_DIR}/$${PLUGIN_SLUG}"; \
	rsync -a --exclude-from=".distignore" \
		--exclude=".git" \
		--exclude="dist/" \
		. "$${OUT_DIR}/$${PLUGIN_SLUG}/"; \
	cd "$${OUT_DIR}" && zip -rq "$${ZIP_NAME}" "$${PLUGIN_SLUG}"; \
	cd ..; \
	echo "✓ Built: $${OUT_DIR}/$${ZIP_NAME}"
	@echo ""
	@echo "ℹ  This zip is for local QA only. The CI deploy action builds"
	@echo "   the authoritative zip from a clean tag checkout."

# ──────────────────────────────────────────────────────────────────────────────
# Diagnostics
# ──────────────────────────────────────────────────────────────────────────────
doctor: ## Print versions of all required tools; flag anything missing
	@echo ""
	@echo "SendStack — environment diagnostic"
	@echo "────────────────────────────────────────────────────────"
	@_check() { \
		TOOL=$$1; CMD=$$2; \
		if command -v $$TOOL > /dev/null 2>&1; then \
			VER=$$(eval $$CMD 2>&1 | head -1); \
			printf "  ✓ %-16s %s\n" "$$TOOL" "$$VER"; \
		else \
			printf "  ✗ %-16s NOT FOUND\n" "$$TOOL"; \
		fi; \
	}; \
	_check docker      "docker --version"; \
	_check ddev        "ddev --version"; \
	_check git         "git --version"; \
	_check composer    "composer --version"; \
	_check node        "node --version"; \
	_check npm         "npm --version"; \
	_check pre-commit  "pre-commit --version"; \
	_check make        "make --version"; \
	_check python3     "python3 --version"; \
	echo ""; \
	if command -v ddev > /dev/null 2>&1 && ddev status 2>/dev/null | grep -q running; then \
		echo "  DDEV container PHP version:"; \
		ddev exec php --version 2>/dev/null | head -1 | sed 's/^/    /'; \
	fi
	@echo ""
