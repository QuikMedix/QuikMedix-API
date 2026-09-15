# QuikMedix branding

This repository contains the QuikMedix API and web portal. The interface, authentication pages, navigation, browser icons, invoices, delivery labels, notification text, and generated links now use QuikMedix. Primary controls and invoice accents use `#c90016`. Transparent logo variants support light and dark surfaces.

Logo sources and reproduction instructions are in [resources/images/branding/README.md](resources/images/branding/README.md). Build logo assets with `php scripts/build-brand-assets.php`.

## Deployment configuration

Merge the relevant values from [.env.branding.example](.env.branding.example) into the deployment environment. Do not replace an existing environment file or overwrite configured credentials with blank examples.

The existing `APP_URL=https://app.quikmedix.com` is used for generated web links. A separate app download URL can be supplied with `QUIKMEDIX_DOWNLOAD_URL`. Phone-only accounts use a reserved, non-deliverable `accounts.quikmedix.invalid` identifier; existing customer login identifiers are unchanged.

The configured support and billing email is `info@quikmedix.com`, the current phone is `(929) 969-8910`, and the business address is `1833 W 12th Street, Brooklyn, NY 11223`. These details appear in the support page and invoices; patient messages use the configured support phone. They can be changed with the `QUIKMEDIX_*` settings. The supplied email's comma was normalized to a period. An invoice signatory, authorized signature, and Telegram bot link have not been supplied and remain omitted.

Original authorization and partner form artwork was hosted at unavailable legacy URLs. Set the four `QUIKMEDIX_*FORM*` / `QUIKMEDIX_AUTHORIZATION_INSTRUCTIONS` paths to the correct local artwork beneath `public/`. Pages whose artwork is missing display an unavailable message and cannot be printed. Old provider signature images are no longer reused. The medical/partner form text and legitimate partner identities remain intact.

Hardcoded legacy SMTP, Twilio, Beams, FCM, Telegram and Tidio settings were replaced with environment configuration. Existing deployment values are preserved. Configure the services used by this deployment with QuikMedix-owned accounts. Missing Telegram and Beams credentials return an unavailable response; unconfigured web push and the external support widget are not started. SMS and email features require working service credentials. `FCM_NOTIFICATION_ICON` must match a drawable shipped by the native Android app. Rebuilding the frontend also removed the previously bundled Larasocket token; supply `MIX_LARASOCKET_TOKEN` before building if that chat service is used.

The old demo chat route is replaced by `/support-chat`, which opens `/chat`. Obsolete remote deployment and debug-bot hooks were removed. The original controller filenames and classes (`LexaAdmin`, `LexaAdminApi`, and `LexaAdminApiNoAuth`) are retained for familiarity, and routes reference those original names. These internal names are not displayed as app branding; access is controlled by authentication and permission checks.

## Verification

```sh
php scripts/verify-branding.php
php artisan quikmedix:branding-audit --database
php artisan view:cache
npm run production
```

The smoke script uses synthetic data and does not send messages or access the database. It checks rendered login, two-factor authentication, invoices and delivery labels, missing form handling, unavailable service responses, controller loading, and real PNG transparency. The branding audit reports counts without printing record contents or changing records. It exits with status 1 for legacy matches and 2 if the database cannot be checked.

On this workspace's Node 24, the existing Webpack 4 build needs `NODE_OPTIONS=--openssl-legacy-provider npm run production`. The project's Node 16 build environment does not need that workaround. Laravel 8 emits an existing nullable-parameter deprecation on PHP 8.4.

After deploying the files and setting environment values, rebuild Laravel configuration/views and restart long-lived workers so they use the current notification text and settings. Stylesheet and logo URLs include new cache versions.

Verified locally: production and development builds; 46 offline branding checks; login page appearance; PHP and Blade syntax; route registration. The configured database's news, patient news, FAQs, banner URLs, and notifications returned zero legacy-text matches. This text audit cannot identify a logo inside an arbitrarily named uploaded image.

Library license/source credits and actual pharmacy, payment-provider, and medical-partner identities are retained. Native iOS/Android binaries, app-store listings, external service dashboards, and an externally deployed site are outside this repository; this change has not deployed or changed those products.
