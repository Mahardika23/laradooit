<?php

namespace App\Providers;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\ServiceProvider;
use RuntimeException;

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
        $this->requirePostgres();

        // Lazy loading, missing attributes and silently discarded attributes
        // are errors everywhere except production, where a strict failure
        // would turn a small bug into a 500 for the one person using this.
        Model::shouldBeStrict(! $this->app->isProduction());
    }

    /**
     * Refuse to boot on anything but PostgreSQL.
     *
     * Laravel merges its own default connections into the config, so deleting
     * them from config/database.php does not stop DB_CONNECTION from selecting
     * one. This turns a silent misconfiguration into an error at boot.
     */
    private function requirePostgres(): void
    {
        $connection = config('database.default');
        $driver = config("database.connections.{$connection}.driver");

        if ($driver !== 'pgsql') {
            throw new RuntimeException(
                "laradooit runs on PostgreSQL only, but DB_CONNECTION is set to '{$connection}' (driver '{$driver}')."
            );
        }
    }
}
