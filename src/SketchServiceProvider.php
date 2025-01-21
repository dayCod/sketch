<?php

namespace Daycode\Sketch;

use Daycode\Sketch\Facades\Sketch;
use Illuminate\Support\ServiceProvider;

class SketchServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        // Register artisan commands
        if ($this->app->runningInConsole()) {
            $this->commands([
                \Daycode\Sketch\Commands\GenerateBlueprintFile::class,
                \Daycode\Sketch\Commands\SketchCommand::class,
            ]);

            // Publish configuration
            $this->publishes([
                __DIR__.'/../config/sketch.php' => config_path('sketch.php'),
            ], 'sketch');

            $this->publishes([
                __DIR__.'/../stubs' => base_path('stubs/sketch'),
            ], 'sketch-stubs');
        }
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__.'/../config/sketch.php',
            'sketch'
        );

        $this->app->singleton('sketch', fn ($app): \Daycode\Sketch\Facades\Sketch => new Sketch);
    }
}
