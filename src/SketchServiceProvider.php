<?php

namespace Daycode\Sketch;

use Illuminate\Support\ServiceProvider;

class SketchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Publish configuration
        $this->publishes([
            __DIR__.'/../config/sketch.php' => config_path('sketch.php'),
        ], 'config');

        // Register artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Daycode\Sketch\Commands\GenerateBlueprintFile::class
            ]);
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/sketch.php', 'sketch'
        );
    }
}
