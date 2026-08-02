<?php

namespace App\Providers;

use App\Support\AdminNotificationViewData;
use Illuminate\Support\Facades\View;
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
        View::composer('components.admin.topbar', function ($view): void {
            $view->with('notifications', app(AdminNotificationViewData::class)->items(session('bbh_api_token')));
        });
    }
}
