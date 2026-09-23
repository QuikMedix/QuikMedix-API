.PHONY: run serve check-php check-dev composer-lock prepare-storage ide-helper

HOST ?= 127.0.0.1
PORT ?= 8000
DEV_FLAGS ?=

check-php:
	@php -r 'exit(PHP_MAJOR_VERSION === 8 && PHP_MINOR_VERSION >= 4 ? 0 : 1);' \
		|| { echo "PHP >=8.4 and <9.0 is required. Current version: $$(php -r 'echo PHP_VERSION;')"; exit 1; }

# Starts Laravel's server, queue listener, logs, and Vite in the tabbed dev UI.
# Override the address when needed, for example: make run HOST=0.0.0.0 PORT=8080
run: check-dev prepare-storage
	@SERVER_HOST="$(HOST)" SERVER_PORT="$(PORT)" php artisan dev $(DEV_FLAGS)

check-dev: check-php
	@test -f .env || { echo "Missing .env file. Create and configure it before running the application."; exit 1; }
	@test -f vendor/autoload.php || { echo "Missing Composer dependencies. Run 'composer install' first."; exit 1; }
	@command -v node >/dev/null || { echo "Node.js 24 is required for the development terminal. Run 'nvm use'."; exit 1; }
	@test -x node_modules/.bin/vite -a -x node_modules/.bin/multiplex || { echo "Missing frontend dependencies. Run 'npm ci' first."; exit 1; }

# Runs just the PHP server, for example when assets were built with npm run build.
serve: check-php prepare-storage
	@php artisan serve --host=$(HOST) --port=$(PORT)

# Resolves dependencies and creates composer.lock. Run this with PHP 8.4+.
composer-lock:
	@composer validate --no-check-publish
	@composer update --no-dev --prefer-dist --no-interaction --optimize-autoloader

# Recreates directories Laravel needs at runtime (including in production images).
prepare-storage:
	@mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs
	@chmod -R ug+rwX storage bootstrap/cache
	@test ! -f storage/oauth-private.key || chmod 600 storage/oauth-private.key
	@test ! -f storage/oauth-public.key || chmod 660 storage/oauth-public.key

# Regenerates the Intelephense/IDE type stubs after adding models, columns, or packages.
ide-helper:
	@php artisan ide-helper:generate
	@php artisan ide-helper:meta
	@php artisan ide-helper:models --nowrite
