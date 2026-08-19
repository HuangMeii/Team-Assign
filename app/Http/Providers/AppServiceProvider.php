<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot()
    {
        // Macro để log tất cả cookies trước khi response về browser
        Response::macro('logAllCookies', function () {
            $cookies = $this->headers->getCookies();
            Log::info('=== All Final Cookies in Response ===');
            foreach ($cookies as $cookie) {
                Log::info('Cookie Name: ' . $cookie->getName() . ' | Value: ' . $cookie->getValue() . ' | Expires: ' . ($cookie->getExpiresTime(true) ? date('Y-m-d H:i:s', $cookie->getExpiresTime(true)) : 'Session'));
                if (strpos($cookie->getName(), 'remember_web') !== false) {
                    Log::info('*** REMEMBER COOKIE FOUND! Name: ' . $cookie->getName() . ' | Expires: ' . date('Y-m-d H:i:s', $cookie->getExpiresTime(true)) . ' | Value (base64): ' . $cookie->getValue());
                }
            }
            Log::info('=== End Cookies ===');
            return $this;  // Chainable
        });
    }
}
