#!/bin/bash
if [ ! -x vendor/bin/phpcs ]; then
    echo "vendor/bin/phpcs not found — skipping. Run make setup first."
    exit 0
fi
vendor/bin/phpcs "$@"
