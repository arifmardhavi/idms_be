<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\GlobalActivityObserver;
use Illuminate\Database\Eloquent\Model;

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
        Model::created(function ($model) {
            (new GlobalActivityObserver)->created($model);
        });

        Model::updated(function ($model) {
            (new GlobalActivityObserver)->updated($model);
        });

        Model::deleted(function ($model) {
            (new GlobalActivityObserver)->deleted($model);
        });
    }
}
