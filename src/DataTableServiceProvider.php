<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable;

use Illuminate\Support\ServiceProvider;
use JimmyAhalpara\LaravelDatatable\Contracts\DataTableServiceInterface;

class DataTableServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../config/datatable.php',
            'datatable'
        );

        $this->app->bind(DataTableServiceInterface::class, DataTableService::class);
        $this->app->alias(DataTableServiceInterface::class, 'datatable');
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../config/datatable.php' => config_path('datatable.php'),
            ], 'datatable-config');
        }
    }
}