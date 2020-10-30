<?php

namespace san4o101\uploadFiles;

use Illuminate\Support\ServiceProvider;

class UploadFileServiceProvider extends ServiceProvider
{

    public function register()
    {
        $this->loadMigrations();
        $this->publishConfig();
        // $this->publishService();
        // $this->publishModel();
    }

    public function boot()
    {
        //
    }

    public function publishConfig()
    {
        $this->publishes([
            __DIR__.'/config/upload_files.php' => config_path('upload_files.php'),
        ], 'san4o101-config');
    }

    public function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/migrations');

        $this->publishes([
            __DIR__.'/migrations' => database_path('migrations/san4o101'),
        ], 'san4o101-migrations');
    }

    public function publishService()
    {
        $this->publishes([
            __DIR__.'/Services/UploadFileService.php' => app_path('Services/san4o101/UploadFileService.php'),
        ], 'san4o101-service');
    }

    public function publishModel()
    {
        $this->publishes([
            __DIR__.'/Models/SFile.php' => app_path('Models/san4o101/UploadFileService.php'),
        ], 'san4o101-migrations');
    }
}