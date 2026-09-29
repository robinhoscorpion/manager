<?php

namespace App\Providers;

use Illuminate\Support\Facades\Vite;
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
        Vite::prefetch(concurrency: 3);

        \Illuminate\Support\Facades\Gate::before(function ($user, $ability) {
            if ($user->hasRole('admin')) {
                return true;
            }
        });

        // Configura dinamicamente o SMTP salvo no banco de dados
        try {
            if (\Illuminate\Support\Facades\Schema::hasTable('settings')) {
                \App\Http\Controllers\Admin\MailSettingsController::applyMailConfig();
            }
        } catch (\Throwable $e) {
            // Silenciosamente ignorado durante execuções iniciais do console/migrações
        }
    }
}
