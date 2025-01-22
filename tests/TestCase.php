<?php

namespace Daycode\Sketch\Tests;

use Daycode\Sketch\SketchServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->setUpConfig();
        $this->makeDirectories();
    }

    protected function getPackageProviders($app): array
    {
        return [
            SketchServiceProvider::class,
        ];
    }

    protected function setUpConfig(): void
    {
        // Get the absolute path to package root
        $packagePath = realpath(__DIR__.'/..');

        // Set up config
        config([
            'sketch.paths.models' => $this->app->basePath('app/Models'),
            'sketch.paths.requests' => $this->app->basePath('app/Http/Requests'),
            'sketch.paths.migrations' => $this->app->basePath('database/migrations'),
            'sketch.stubs.model' => $packagePath.'/stubs/model.stub',
            'sketch.stubs.migration' => $packagePath.'/stubs/migration.stub',
            'sketch.stubs.request' => $packagePath.'/stubs/form-request.stub',
        ]);
    }

    protected function makeDirectories(): void
    {
        $paths = [
            $this->app->basePath('tests/stubs'),
            $this->app->basePath('app/Models'),
            $this->app->basePath('app/Http/Requests'),
            $this->app->basePath('database/migrations'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path)) {
                mkdir($path, 0777, true);
            }
        }
    }

    protected function defineEnvironment($app): void
    {
        // Setup default database
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }
}
