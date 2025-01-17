<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Daycode\Sketch\Blueprint;
use Daycode\Sketch\Functions\Helper;
use Daycode\Sketch\Generator;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class ExecuteCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sketch:execute {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD for a model based on a blueprint file';

    /**
     * Execute the console command.
     */
    public function handle(Generator $generator, Blueprint $blueprint): void
    {
        try {
            Helper::scanDirectoryByModelName(model: $this->argument('model'));

            $parsedFilePath = $blueprint->parseFilePath(name: Str::title($this->argument('model')));
            $fullPath = is_null($parsedFilePath->path)
                ? $parsedFilePath->file
                : $parsedFilePath->path.'/'.$parsedFilePath->file;

            // Generate Eloquent Model
            $eloquentModelPath = $generator->generateEloquentModel([
                'softDelete' => true,
                'classname' => Str::studly($this->argument('model')),
                'tableName' => Str::plural(Str::snake($this->argument('model'))),
                'yamlPath' => config('sketch.blueprint_path')."/{$fullPath}.yaml",
            ]);

            // Generate Migrations
            $tableMigrationPath = $generator->generateTableMigration(yamlPath: config('sketch.blueprint_path')."/{$fullPath}.yaml");

            // Generator Controllers
            $generator->generateController(model: Str::studly($this->argument('model')));

            $this->info("Model: {$eloquentModelPath} Migration: {$tableMigrationPath}");
        } catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
    }
}
