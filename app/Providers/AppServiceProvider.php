<?php

namespace App\Providers;

use App\Models\SamuelProfile;
use App\Support\FamilySession;
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
        View::composer('*', function ($view) {
            $view->with('familyMember', FamilySession::member());
            $profile = SamuelProfile::current();
            $view->with('samuelProfile', $profile);
            $view->with('appTheme', $profile->themeColors());
        });
    }
}
