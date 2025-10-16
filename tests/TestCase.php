<?php

declare(strict_types=1);

namespace JimmyAhalpara\LaravelDatatable\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use JimmyAhalpara\LaravelDatatable\DataTableServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }

    protected function getPackageProviders($app): array
    {
        return [
            DataTableServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        config()->set('database.default', 'testing');

        config()->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);

        config()->set('datatable', [
            'default_items_per_page' => 10,
            'max_items_per_page' => 100,
            'default_search_type' => 'contains',
            'case_insensitive' => true,
            'download' => [
                'max_execution_time' => 1800,
                'default_filename' => 'export',
                'default_format' => 'xlsx',
            ],
            'cache' => [
                'enabled' => false,
                'ttl' => 300,
                'prefix' => 'datatable',
            ],
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        $this->loadMigrationsFrom(__DIR__ . '/database/migrations');
    }
}