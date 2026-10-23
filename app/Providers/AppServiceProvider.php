<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use App\Models\Brand;
use Illuminate\Support\ServiceProvider;

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
    public function boot(): void
    {
        RateLimiter::for('login', fn ($request) => Limit::perMinute(10)->by(strtolower((string) $request->input('email')).'|'.$request->ip()));
        RateLimiter::for('register', fn ($request) => Limit::perMinute(5)->by($request->ip()));
        RateLimiter::for('chat', fn ($request) => Limit::perMinute(12)->by($request->ip().'|'.session()->getId()));
        View::composer('partials.navbar', function ($view) {
            $view->with('navBrands', Brand::where('is_active', true)
                ->whereHas('products', fn ($products) => $products->where('products.is_active', true)->whereHas('variants', fn ($variants) => $variants->where('is_active', true)))
                ->orderBy('name')->get());
        });
    }
}
