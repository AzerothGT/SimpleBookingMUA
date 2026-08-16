<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Models\Booking;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Services\MidtransPaymentGateway;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(PaymentGateway::class, MidtransPaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        JsonResource::withoutWrapping();

        RateLimiter::for('login', fn ($request) => Limit::perMinute(5)->by($request->ip()));

        Relation::enforceMorphMap([
            'booking' => Booking::class,
            'transaction' => Transaction::class,
            'service' => Service::class,
            'user' => User::class,
        ]);
    }
}
