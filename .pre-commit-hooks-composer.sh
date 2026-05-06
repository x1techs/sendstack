#!/bin/bash
if [ ! -f composer.json ]; then
    echo "composer.json not found — skipping validation."
    exit 0
fi
if ! command -v composer &> /dev/null; then
    echo "composer not found — skipping. Run make setup first."
    exit 0
fi
composer validate --no-check-publish
