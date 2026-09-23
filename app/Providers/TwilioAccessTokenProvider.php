<?php
namespace App\Providers;
use Illuminate\Support\ServiceProvider;
use Twilio\Jwt\AccessToken;

class TwilioAccessTokenProvider extends ServiceProvider
{
    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(
            AccessToken::class, function () {
                $TWILIO_ACCOUNT_SID = config()->string('app.twilio_sid');
                $TWILIO_API_KEY = config()->string('app.twilio_apiKey');
                $TWILIO_API_SECRET = config()->string('app.twilio_apiSecret');

                $token = new AccessToken(
                    $TWILIO_ACCOUNT_SID,
                    $TWILIO_API_KEY,
                    $TWILIO_API_SECRET,
                    3600
                );

                return $token;
            }
        );
    }
}