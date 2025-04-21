<?php

namespace TheRealJanJanssens\PakkaCore;

use Illuminate\Support\ServiceProvider;

class PakkaCoreServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap any package services.
     */
    public function boot(): void
    {
        $this->loadMigrationsFrom([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ]);

        // New Method since Laravel 11.x
        // $this->publishesMigrations([
        //     __DIR__.'/../database/migrations' => database_path('migrations'),
        // ]);

        $this->loadRoutesFrom(__DIR__.'/../routes/api.php');
    }

    public static function allMigrations()
    {
        $path = __DIR__ . '/../database/migrations';
        $files = array_values(array_diff(scandir($path), ['.', '..','.DS_Store']));

        for ($i = 0; $i < count($files); ++$i) {
            $result[$i] = str_replace('.php.stub', '', $files[$i]);
        }

        return $result;
    }
}
