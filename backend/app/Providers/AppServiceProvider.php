<?php

namespace App\Providers;

use App\Support\Identifier;
use Illuminate\Support\Facades\Route;
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
        // Ids inválidos na URL não casam com a rota → 404, em vez de chegarem
        // ao banco e estourarem 500.
        Route::pattern('exam', Identifier::PATTERN);
        Route::pattern('attempt', Identifier::PATTERN);
    }
}
