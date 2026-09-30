#!/usr/bin/env bash
set -euo pipefail

cd "$(dirname "$0")"

if [[ "${SKIP_INSTALL:-0}" != "1" ]]; then
    composer install --no-interaction --prefer-dist
    npm ci
fi

if [[ ! -f .env ]]; then
    if [[ ! -f .env.example ]]; then
        echo "This legacy checkout has no .env.example. Configure a local .env before running init.sh." >&2
        exit 1
    fi
    cp .env.example .env
    php artisan key:generate --no-interaction
fi

php artisan config:clear --no-interaction
npm run build
composer test

if [[ "${RUN_START_COMMAND:-0}" == "1" ]]; then
    composer run dev
fi
