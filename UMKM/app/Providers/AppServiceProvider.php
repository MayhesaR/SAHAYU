<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL;
use OpenAI;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Override the default OpenAI client to fix SSL on Windows/XAMPP
        $this->app->singleton(\OpenAI\Client::class, function () {
            $apiKey = config('openai.api_key');
            $baseUri = config('openai.base_uri', 'api.openai.com/v1');
            $timeout = config('openai.request_timeout', 60);

            // Determine CA bundle path
            $caBundle = 'C:\\xampp\\php\\extras\\ssl\\cacert.pem';

            $httpClient = new \GuzzleHttp\Client([
                'verify' => false,
                'timeout' => $timeout,
            ]);

            return OpenAI::factory()
                ->withApiKey($apiKey ?? '')
                ->withBaseUri($baseUri)
                ->withHttpClient($httpClient)
                ->make();
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if (config('app.env') === 'production') {
            URL::forceScheme('https');
        }
    }
}
