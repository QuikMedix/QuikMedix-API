<?php

// Offline smoke checks: no database access, messages, or persistent config changes.
require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();
config([
    'session.driver' => 'array',
    'cache.default' => 'array',
    'mail.default' => 'array',
    'app.url' => 'http://localhost',
    'branding.support_phone' => null,
    'branding.support_email' => null,
    'branding.billing_email' => null,
    'branding.address' => null,
    'branding.invoice_signatory' => null,
    'branding.invoice_signature' => null,
    'branding.telegram_auth_url' => null,
    'branding.download_url' => null,
    'services.telegram.bot_token' => null,
    'services.beams.instance_id' => null,
    'services.beams.secret_key' => null,
]);
Illuminate\Support\Facades\URL::forceRootUrl('http://localhost');
app('session')->start();
view()->share('errors', new Illuminate\Support\ViewErrorBag());
$checks = 0;
$check = function ($condition, string $message) use (&$checks) {
    if (!$condition) {
        throw new RuntimeException($message);
    }
    $checks++;
};
$clean = function (string $html) use ($check) {
    $check(!preg_match('/a2brx|a2b[ .-]?rx|a2rx|zozland|\blexa\b|themesbrand/i', $html), 'Legacy branding in rendered HTML');
};

$login = view('auth.login')->render();
$clean($login);
$check(str_contains($login, 'quikmedix-logo.png'), 'Login logo missing');
$check(str_contains($login, 'quikmedix-1'), 'Theme cache version missing');
$check(App\Support\Branding::supportContact() === 'your pharmacy', 'Missing contact fallback failed');
$check(App\Support\Branding::appAccessMessage() === 'Open QuikMedix: http://localhost', 'App link fallback failed');

// Supply an in-memory user only. This does not log into or change an account.
$user = new App\User();
$user->phone = '2025550100';
Illuminate\Support\Facades\Auth::setUser($user);
$twoFactor = view('auth.2fa')->render();
$clean($twoFactor);
$check(!str_contains($twoFactor, 'Add Telegram Auth'), 'Unconfigured Telegram link is visible');
config(['branding.telegram_auth_url' => 'https://example.invalid/quikmedix-auth']);
$check(str_contains(view('auth.2fa')->render(), 'https://example.invalid/quikmedix-auth'), 'Configured Telegram link missing');

$invoiceData = [
    'invoice' => (object) [
        'id' => 101, 'created' => '2026-09-06', 'date_from' => '2026-09-01',
        'date_to' => '2026-09-06', 'amount' => 50, 'corrections' => 0,
        'copay' => 0, 'payed' => '0', 'payed_amount' => 0,
    ],
    'pharmacy' => (object) [
        'name' => 'Example Pharmacy', 'address' => 'Example address',
        'email' => 'pharmacy@example.invalid', 'phone' => '2025550100', 'copay_bill' => '0',
    ],
    'payment_account' => null,
];
foreach (['next_day', 'same_day', 'asap', 'after_hours', 'pharmacy_driver'] as $key) {
    $invoiceData[$key] = ['count' => 0, 'amount' => 0];
}
$invoice = view('billing.print', $invoiceData)->render();
$clean($invoice);
$check(!str_contains($invoice, 'Authorized signature'), 'Missing signature should be omitted');
config([
    'branding.legal_name' => 'QuikMedix & Example',
    'branding.billing_email' => 'billing@example.invalid',
    'branding.address' => "Example address\nSuite 1",
]);
$invoice = view('billing.print', $invoiceData)->render();
$check(str_contains($invoice, 'QuikMedix &amp; Example'), 'Invoice legal name is not escaped');
$check(str_contains($invoice, 'billing@example.invalid'), 'Invoice contact missing');
$check(str_contains($invoice, 'Suite 1'), 'Invoice address missing');

config(['branding.documents.authorization' => null]);
$documentData = ['document' => 'authorization', 'description' => 'Authorization form'];
$check(str_contains(view('layouts.partials.document-image', $documentData)->render(), 'data-document-unavailable'), 'Missing form status absent');
config(['branding.documents.authorization' => 'images/branding/quikmedix-logo.png']);
$check(!str_contains(view('layouts.partials.document-image', $documentData)->render(), 'data-document-unavailable'), 'Existing local artwork rejected');
config(['branding.documents.authorization' => 'images/does-not-exist.png']);
$check(App\Support\Branding::documentPath('authorization') === null, 'Nonexistent form accepted');

$ticket = view('orders.ticket_pdf', [
    'order' => (object) ['id' => 101, 'count_bags' => 2, 'rxs' => [], 'fridge' => 0, 'signature' => 0],
    'patient' => (object) ['last_name' => 'Example', 'name' => 'Patient', 'address' => 'Example address', 'zip' => '00000', 'apartment' => ''],
    'pharmacy' => $invoiceData['pharmacy'],
    'wish' => (object) ['text' => 'Thank you'],
])->render();
$clean($ticket);
$check(substr_count($ticket, 'alt="QuikMedix"') === 2, 'Delivery labels missing logos');
$check(!str_contains($ticket, 'src="http'), 'PDF labels still fetch external images');

foreach (['telegramAuth', 'pusher_auth'] as $method) {
    try {
        (new App\Http\Controllers\LexaAdminApiNoAuth())->$method(new Illuminate\Http\Request());
        throw new RuntimeException('Unconfigured service was not rejected: '.$method);
    } catch (Symfony\Component\HttpKernel\Exception\HttpException $exception) {
        $check($exception->getStatusCode() === 503, 'Incorrect missing-service response');
    }
}
foreach (['LexaAdmin', 'LexaAdminApi', 'LexaAdminApiNoAuth'] as $controller) {
    $check(class_exists('App\\Http\\Controllers\\'.$controller), 'Controller does not autoload');
}
$uris = array_map(function ($route) { return $route->uri(); }, iterator_to_array(app('router')->getRoutes()));
$check(in_array('support-chat', $uris, true), 'Support route missing');
foreach (['a2bchat', 'github_pull', 'github_pull_zoz', 'livetex_hook'] as $uri) {
    $check(!in_array($uri, $uris, true), 'Legacy demo/deployment route still exposed');
}

$assets = glob(public_path('images/branding/*.png'));
foreach ($assets as $asset) {
    $png = imagecreatefrompng($asset);
    $opaque = false;
    $transparent = false;
    for ($y = 0; $y < imagesy($png) && !($opaque && $transparent); $y++) {
        for ($x = 0; $x < imagesx($png); $x++) {
            $alpha = (imagecolorat($png, $x, $y) >> 24) & 127;
            $opaque = $opaque || $alpha === 0;
            $transparent = $transparent || $alpha === 127;
        }
    }
    $check($opaque && $transparent, 'Logo lacks real transparency: '.basename($asset));
    imagedestroy($png);
}
echo 'PASS: '.$checks.' branding smoke checks; no database writes or messages sent.'.PHP_EOL;
