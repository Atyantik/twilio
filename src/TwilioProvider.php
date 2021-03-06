<?php

namespace NotificationChannels\Twilio;

use Illuminate\Support\ServiceProvider;
use Twilio\Rest\Client as TwilioService;

class TwilioProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     */
    public function boot()
    {
        $this->app->when(TwilioChannel::class)
            ->needs(Twilio::class)
            ->give(function () {
                $config = $this->app['config']['services.twilio'];

                return new Twilio(
                    $this->app->make(TwilioService::class, [
                        $config['account_sid'],
                        $config['auth_token'],
                    ]),
                    $config['from']
                );
            });
    }

    /**
     * Register the application services.
     */
    public function register()
    {
    }
}
