<?php

namespace san4o101\uploadFiles\providers;

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
            __DIR__ . '/../config/upload_files.php' => config_path('upload_files.php'),
        ], 'san4o101-config');
    }

    public function loadMigrations()
    {
        $this->loadMigrationsFrom(__DIR__ . '/migrations');

        $this->publishes([
            __DIR__ . '/../migrations' => database_path('migrations'),
        ], 'san4o101-migrations');
    }

    public function publishService()
    {
        $serviceName = config('upload_files.models.name');
        $this->publishes([
            __DIR__ . '/../Services/UploadFileService.php' => app_path("Services/{$serviceName}.php"),
        ], 'san4o101-service');
    }

    public function publishModel()
    {
        $modelName = config('upload_files.models.name');
        $this->publishes([
            __DIR__ . '/../Models/SFile.php' => app_path("Models/{$modelName}.php"),
        ], 'san4o101-migrations');
    }
}
