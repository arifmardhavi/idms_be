<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Observers\GlobalActivityObserver;
use App\Services\ActivityLogger;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Model;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->singleton(ActivityLogger::class);
    }

    /**
     * Bootstrap any application services.
     *
     * Gunakan wildcard listener supaya menangkap event untuk SEMUA kelas model
     * konkret (eloquent.created: App\Models\Xxx), bukan hanya kelas dasar Model.
     */
    public function boot(): void
    {
        $observer = new GlobalActivityObserver;

        Event::listen('eloquent.created: *', function ($eventName, $payload) use ($observer) {
            $model = $payload[0] ?? null;
            if ($model instanceof Model) {
                $observer->created($model);
            }
        });

        Event::listen('eloquent.updated: *', function ($eventName, $payload) use ($observer) {
            $model = $payload[0] ?? null;
            if ($model instanceof Model) {
                $observer->updated($model);
            }
        });

        Event::listen('eloquent.deleted: *', function ($eventName, $payload) use ($observer) {
            $model = $payload[0] ?? null;
            if ($model instanceof Model) {
                $observer->deleted($model);
            }
        });
    }
}
