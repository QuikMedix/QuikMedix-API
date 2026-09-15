<?php

return [
    'name' => 'QuikMedix',
    'tagline' => 'Your Health - Our Priority',
    'legal_name' => env('QUIKMEDIX_LEGAL_NAME', 'QuikMedix'),
    'support_email' => env('QUIKMEDIX_SUPPORT_EMAIL', 'info@quikmedix.com'),
    'support_phone' => env('QUIKMEDIX_SUPPORT_PHONE', '(929) 969-8910'),
    'billing_email' => env('QUIKMEDIX_BILLING_EMAIL', 'info@quikmedix.com'),
    'address' => env('QUIKMEDIX_BUSINESS_ADDRESS', '1833 W 12th Street, Brooklyn, NY 11223'),
    'invoice_signatory' => env('QUIKMEDIX_INVOICE_SIGNATORY'),
    'invoice_signature' => env('QUIKMEDIX_INVOICE_SIGNATURE'),
    'telegram_auth_url' => env('QUIKMEDIX_TELEGRAM_AUTH_URL'),
    'download_url' => env('QUIKMEDIX_DOWNLOAD_URL'),
    // Internal identifiers for accounts registered with a phone number only.
    'account_email_domain' => env('QUIKMEDIX_ACCOUNT_EMAIL_DOMAIN', 'accounts.quikmedix.invalid'),
    'documents' => [
        'authorization' => env('QUIKMEDIX_AUTHORIZATION_FORM'),
        'authorization_instructions' => env('QUIKMEDIX_AUTHORIZATION_INSTRUCTIONS'),
        'partner_header' => env('QUIKMEDIX_PARTNER_FORM_HEADER'),
        'partner_footer' => env('QUIKMEDIX_PARTNER_FORM_FOOTER'),
    ],
];
