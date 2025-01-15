<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Daycode\Sketch\Functions\Helper;
use Daycode\Sketch\Services\BlueprintService;
use Daycode\Sketch\Services\CrudGeneratorService;
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
    public function handle(CrudGeneratorService $crudGeneratorService, BlueprintService $blueprintService): void
    {
        try {
            Helper::scanDirectoryByModelName(model: $this->argument('model'));

            $parsedFilePath = $blueprintService->parseFilePath(name: Str::title($this->argument('model')));
            $fullPath = is_null($parsedFilePath->path)
                ? $parsedFilePath->file
                : $parsedFilePath->path.'/'.$parsedFilePath->file;

            // Generate Eloquent Model
            $eloquentModelPath = $crudGeneratorService->generateEloquentModel([
                'softDelete' => true,
                'classname' => Str::studly($this->argument('model')),
                'tableName' => Str::plural(Str::snake($this->argument('model'))),
                'yamlPath' => config('sketch.blueprint_path')."/{$fullPath}.yaml",
            ]);

            // Generate Migrations
            $tableMigrationPath = $crudGeneratorService->generateTableMigration(yamlPath: config('sketch.blueprint_path')."/{$fullPath}.yaml");

            $this->info("Model: {$eloquentModelPath} Migration: {$tableMigrationPath}");
        } catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
    }
}
