<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */

    'name' => env('APP_NAME', 'QuikMedix'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */

    'env' => env('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */

    'debug' => (bool) env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */

    'url' => env('APP_URL', 'https://app.quikmedix.com'),

    'asset_url' => env('ASSET_URL', null),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */

    'timezone' => 'America/New_York',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */

    'locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Authorize Payment Api keys
    |--------------------------------------------------------------------------
    */

    'MERCHANT_LOGIN_ID' => '67ZEqw3r',
    'MERCHANT_TRANSACTION_KEY' => '6aX7y53hkPR59UVv',

    /*
    |--------------------------------------------------------------------------
    | squareup Api keys
    |--------------------------------------------------------------------------
    */

    'SQUARE_ACCESS_TOKEN' => env('SQUARE_ACCESS_TOKEN', null),
    'SQUARE_LOCATION_ID' => env('SQUARE_LOCATION_ID', null),
    'SQUARE_APPLICATION_ID' => env('SQUARE_APPLICATION_ID', null),
    'SQUARE_ENVIRONMENT' => env('SQUARE_ENVIRONMENT', 'production'), //one of production, sandbox, custom

    /*
    |--------------------------------------------------------------------------
    | Twilio Api keys
    |--------------------------------------------------------------------------
    */
    
    'twilio_sid' => env('TWILIO_SID', null),
    'twilio_auth_token' => env('TWILIO_AUTH_TOKEN'),
    'twilio_from_phone' => env('TWILIO_FROM_PHONE'),
    'twilio_chatServiceSid' => env('TWILIO_CHAT_SERVICE_SID'),
    'twilio_notifyClientServiceSid' => env('TWILIO_NOTIFY_CLIENT_SERVICE_SID'),
    'twilio_notifyClientIOSServiceSid' => env('TWILIO_NOTIFY_CLIENT_IOS_SERVICE_SID'),
    'twilio_notifyDriverIOSServiceSid' => env('TWILIO_NOTIFY_DRIVER_IOS_SERVICE_SID'),
    'twilio_notifyDriverServiceSid' => env('TWILIO_NOTIFY_DRIVER_SERVICE_SID'),
    'twilio_apiKey' => env('TWILIO_API_KEY'),
    'twilio_apiSecret' => env('TWILIO_API_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | HERE Api keys
    |--------------------------------------------------------------------------
    */

    'hereApiKey' => env('HERE_API_KEY', null),
    'hereAccessId' => env('HERE_ACCESS_ID', null),
    'hereAccessSecret' => env('HERE_ACCESS_SECRET', null),
    

    /*
    |--------------------------------------------------------------------------
    | Application Fallback Locale
    |--------------------------------------------------------------------------
    |
    | The fallback locale determines the locale to use when the current one
    | is not available. You may change the value to correspond to any of
    | the language folders that are provided through your application.
    |
    */

    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Faker Locale
    |--------------------------------------------------------------------------
    |
    | This locale will be used by the Faker PHP library when generating fake
    | data for your database seeds. For example, this will be used to get
    | localized telephone numbers, street address information and more.
    |
    */

    'faker_locale' => 'en_US',

    /*
    |--------------------------------------------------------------------------
    | API KEY Google Maps
    |--------------------------------------------------------------------------
    |
    */

    'googlemaps_apikey' => env('GOOGLE_MAPS_API_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Suppurt Phones
    |--------------------------------------------------------------------------
    |
    */

    'support_phone' => env('QUIKMEDIX_SUPPORT_PHONE', '(929) 969-8910'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */

    'key' => env('APP_KEY'),

    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */

    'providers' => Illuminate\Support\ServiceProvider::defaultProviders()->merge([
        App\Providers\AppServiceProvider::class,
        App\Providers\AuthServiceProvider::class,
        App\Providers\EventServiceProvider::class,
        App\Providers\RouteServiceProvider::class,
        App\Providers\TwilioAccessTokenProvider::class,
        App\Providers\TwilioChatGrantProvider::class,
    ])->toArray(),

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */

    'aliases' => Illuminate\Support\Facades\Facade::defaultAliases()->merge([
        'DNS1D' => Milon\Barcode\Facades\DNS1DFacade::class,
        'DNS2D' => Milon\Barcode\Facades\DNS2DFacade::class,
    ])->toArray(),

];
