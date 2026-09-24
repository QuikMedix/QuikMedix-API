# Laravel 13 upgrade

The application now runs Laravel 13 with PHP 8.4 or 8.5. `composer.lock` records the tested dependency set. Composer resolves against PHP 8.4.0 so installing on a PHP 8.5 workstation does not select packages that the PHP 8.4 Docker image cannot run.

## What changed

- Updated Laravel, Passport, Laravel UI, Dompdf, barcode generation, Flysystem/S3, Predis, and Intervention Image. Removed the obsolete Fideloper and Fruitcake Laravel middleware packages in favor of framework middleware.
- Updated JWT and the Pusher notification SDK together; the dependency audit reported no advisories at upgrade time.
- Converted application controller routes to class references. All 331 pre-upgrade route method/URI combinations, names, and controller actions are preserved. API login now adds a named rate limiter. Passport handles authentication within its consent controller and explicitly uses the web guard for its management endpoints.
- Uses Laravel 13 request-forgery protection, default provider/facade registration, and current HTTP/CLI entry points. Retains the supported application kernel/provider structure, existing storage roots, session serialization/cookie naming, and Redis/cache prefixes.
- API login validates input through a Form Request, authenticates without creating a web session, limits attempts, and sets token expiry before signing. Normal tokens last seven days; remembered tokens last one calendar month. The response fields remain `token_type`, `token`, and `expires_at`. Invalid inputs now return JSON 422; excessive attempts return 429. Optional `remember_me` must be a boolean and `os` an integer.
- Registers Passport's consent view and browser login redirect. Preserves existing integer client IDs, password grants, and JSON management routes. Device grants remain disabled because they were not previously provided by this application.
- Publishes the four existing Passport migration names for reproducible installations. A new migration adds explicit grant types to legacy client rows without replacing IDs, secrets, or tokens. This avoids rejecting a user's token when their integer ID equals a personal-access client's ID. Backfilling is resumable; legacy columns remain available.
- Converts the user factory and database seeder to class-based Laravel conventions. Extracts signature rotation into a focused action, preserves clockwise rotation after Intervention's angle-convention change, and uses unique destination filenames.
- Adds PHPUnit 12, `composer test`, and a PHP 8.4/8.5 CI matrix. Corrects the stale sample dashboard test to expect the existing guest redirect. The obsolete Makefile recovery recipe, which regenerated Laravel 8 files, is removed; recover tracked files from version control instead.

## Local verification

```sh
composer install
composer test
node tests/smoke/table-navigation.js
node tests/smoke/tariff-area-editor.js
composer check-platform-reqs
composer audit
make run
# Equivalent startup: composer run dev
```

The PHPUnit suite forces SQLite `:memory:`, array sessions/cache/mail, and temporary RSA keys. It never migrates the configured application database. The existing PHP smoke scripts use separate in-memory databases and a fake geocoder. No payment, messaging, or geocoding requests are sent by these checks.

Verified locally on PHP 8.4.23 and 8.5.0: 30 PHPUnit tests with 108 assertions. Also verified 45 routing, 64 tariff, 23 navigation, and 25 map-editor smoke checks, PHP syntax, package discovery, scheduling registration, configuration/route/view caching, and both startup commands serving the login page. The existing trivial unit sample remains in the test count. GitHub CI is configured but has not been run remotely as part of this change.

## Staging and deployment

This change has not been deployed, and no application database migrations or client-secret hashing have been executed outside the test database.

1. Test on a staging copy of the real MySQL schema/data. The repository's historical migrations do not describe every production table or column. SQLite regression fixtures cannot prove compatibility with all production queries, spatial operations, or SQL modes.
2. Back up the database, current code/lockfile, `APP_KEY`, and existing Passport signing keys. Keep keys unchanged: regenerating them invalidates outstanding tokens. OAuth key files are excluded from Docker build contexts and must be mounted or supplied through `PASSPORT_PRIVATE_KEY` / `PASSPORT_PUBLIC_KEY`. For file-based keys, use mode `0600` for the private key and `0660` for the public key, readable by the PHP runtime user. `make prepare-storage` now applies these modes.
3. Confirm the migration history already records the four `2016_06_01_*` Passport migrations when their tables already exist. Review `php artisan migrate:status` and `php artisan migrate --pretend` against staging, including any unrelated pending application migrations. Do not recreate existing OAuth tables.
4. During a maintenance window, pause workers/scheduling, install the locked dependencies with `composer install --no-dev --prefer-dist --optimize-autoloader`, then run the reviewed migrations with `php artisan migrate --force`.
5. Run `php artisan passport:hash --force` once to convert existing plaintext client secrets. The upgrade tests verify that the command is repeatable. Consumers continue using the same plaintext secret they already possess; the database now holds its hash. **This transformation is not reversible without the database backup.** Passport's management response now exposes a newly created secret as `plain_secret`; clients of that deprecated management API must account for Passport 13's response changes.
6. Preserve the existing personal-access client. For a genuinely new installation only, generate keys and create a client using `php artisan passport:keys` and `php artisan passport:client --personal`. Do not force key regeneration in an existing installation.
7. Rebuild deployment caches (`config:cache`, `route:cache`, `view:cache`), restart PHP/workers, and verify browser login/2FA, API login/logout with new and existing tokens, OAuth consent, chat uploads, PDF tickets, signature rotation, S3/Redis, email, payments, and scheduled work using staging accounts. Restore scheduling and remove maintenance mode after these checks pass.

For rollback after hashing secrets, restore the coordinated database backup and previous code/lockfile, keep the original signing keys, rebuild caches, and restart workers. Rolling back only the new migration or only the code is insufficient. The migration's `down()` restores legacy grant flags, but it cannot recover plaintext secrets or reconcile traffic received since a backup.

## Remaining application work

The framework migration is not a complete rewrite or a security audit of the legacy application. Prioritize these separately with endpoint and tenant-isolation coverage:

- Split `LexaAdmin` and the other large controllers by business operation; replace remaining `$_POST` / `$_GET` branches, string-built queries, and inline validation incrementally.
- Audit authorization and pharmacy/tenant scoping across public integrations and write endpoints before moving those operations into policies and Form Requests.
- Remove and rotate the credentials already embedded in application source and configuration, including `app/User.php` and `config/app.php`. Their values are deliberately omitted from this document; they remain an existing deployment concern.
- Recover a complete, sanitized schema baseline and add MySQL-backed tests for billing, dispatch, payments, and spatial queries.
- Replace the placeholder `FeedbackMail` view, audit cached configuration use and outbound service errors, and test actual provider integrations in their sandboxes.
- Modernize the separate Mix 5/Webpack 4/Node 16 frontend build and review Chatify and the pinned legacy integration SDKs. Their current retention limits unrelated API and UI changes during the framework migration.

## References

- [Laravel 9 upgrade](https://laravel.com/docs/9.x/upgrade), [Laravel 10 upgrade](https://laravel.com/docs/10.x/upgrade), [Laravel 11 upgrade](https://laravel.com/docs/11.x/upgrade), [Laravel 12 upgrade](https://laravel.com/docs/12.x/upgrade), [Laravel 13 upgrade](https://laravel.com/docs/13.x/upgrade).
- [Passport upgrade guide](https://github.com/laravel/passport/blob/13.x/UPGRADE.md).
