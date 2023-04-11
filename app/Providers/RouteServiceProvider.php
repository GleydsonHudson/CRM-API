<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Route;
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * Typically, users are redirected here after authentication.
     *
     * @var string
     */
    public const HOME = '/home';

    /**
     * Define your route model bindings, pattern filters, and other route configuration.
     */
    public function boot(): void
    {
        $this->configureRateLimiting();

        $this->routes(function () {
            foreach ($this->centralDomains() as $domain) {
                Route::middleware(['api', InitializeTenancyByDomain::class, PreventAccessFromCentralDomains::class])
                     ->domain($domain)
                     ->prefix('api')
                     ->group(base_path('routes/api.php'));

                Route::middleware('web')
                     ->domain($domain)
                     ->group(base_path('routes/web.php'));
            }
        });
    }

    protected function centralDomains(): array
    {
        $domains = config('tenancy.central_domains');

        if (! is_array($domains)) {
            throw new \RuntimeException(message: 'Tenancy Central Domain should be an array');
        }

        return (array) $domains;
    }

    /**
     * Configure the rate limiters for the application.
     */
    protected function configureRateLimiting(): void
    {
        RateLimiter::for('api', function (Request $request) {
            return Limit::perMinute(60)->by($request->user()?->id ?: $request->ip());
        });
    }
}
