<?php

declare(strict_types=1);

namespace Daycode\Sketch;

use Daycode\Sketch\Services\MigrationService;
use Daycode\Sketch\Services\ModelRelationshipService;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Yaml\Yaml;

class Generator
{
    /**
     * Constructor for CrudGeneratorService.
     */
    public function __construct(
        protected ModelRelationshipService $modelRelationshipService,
        protected MigrationService $migrationService,
    ) {}

    /**
     * Generate an Eloquent model based on provided stubs.
     *
     * This function reads the content of the Model stub file and replaces the placeholders
     * with the values provided in the $stubs array. The function then generates an Eloquent
     * model class based on the provided values and saves it in the "app/Models" directory.
     *
     * @param  array  $stubs  An array containing the values to replace the placeholders in the stub file.
     *                        The array should contain the following keys: softDelete, classname, tableName, yamlPath.
     * @return string The full path to the generated model file.
     */
    public function generateEloquentModel(?array $stubs = [])
    {
        // Stubs to be replaced
        $stubPath = __DIR__.'/../stubs/model.stub';
        $stubContent = File::get($stubPath);

        // Parsing yaml file
        $relationships = $this->modelRelationshipService->parseRelationshipsFromYaml(yamlPath: $stubs['yamlPath']);

        $stubContent = str_replace(
            ['{{useSoftDeletes}}', '{{className}}', '{{softDeletes}}', '{{tableName}}', '{{relationships}}'],
            [
                $stubs['softDelete'] ? 'use Illuminate\Database\Eloquent\SoftDeletes;' : '',
                $stubs['classname'],
                $stubs['softDelete'] ? 'use SoftDeletes;' : '',
                $stubs['tableName'],
                $relationships,
            ],
            $stubContent
        );

        $modelPath = app_path('Models/'.$stubs['classname'].'.php');
        File::put($modelPath, $stubContent);

        return $modelPath;
    }

    public function generateTableMigration(string $yamlPath): string
    {
        if (! File::exists($yamlPath)) {
            throw new \Exception("YAML file not found at path: {$yamlPath}");
        }

        $yamlData = Yaml::parseFile($yamlPath);

        $modelName = $yamlData['model'];
        $primaryKey = $yamlData['primaryKey'];
        $fields = $yamlData['fields'] ?? [];
        $timestamps = $yamlData['timestamps'] ?? false;
        $softDeletes = $yamlData['softDeletes'] ?? false;
        $relationships = $yamlData['relationships'] ?? [];

        // Generate components
        $tableName = Str::snake(Str::plural($modelName));
        $migrationFields = $this->migrationService->generateFields($primaryKey, $fields, $timestamps, $softDeletes);
        $foreignKeys = $this->migrationService->generateForeignKeys($relationships);

        // Load and replace stub
        $stubPath = __DIR__.'/../stubs/migration.stub';
        $stubContent = File::get($stubPath);

        $stubContent = str_replace(
            ['{{tableName}}', '{{fields}}', '{{foreignKeys}}'],
            [$tableName, $migrationFields, $foreignKeys],
            $stubContent
        );

        $databasePath = database_path('migrations/'.date('Y_m_d_His').'_create_'.$tableName.'.php');
        File::put($databasePath, $stubContent);

        return $databasePath;
    }

    public function generateController(string $model)
    {
        $modelName = $model;
        $modelVariable = strtolower($modelName); // e.g., "post"
        $stub = file_get_contents(__DIR__.'/../stubs/controller.stub');

        // Replace placeholders
        $stub = str_replace('{{modelName}}', $modelName, $stub);
        $stub = str_replace('{{modelVariable}}', $modelVariable, $stub);

        // Save the controller file
        file_put_contents(app_path("Http/Controllers/{$modelName}Controller.php"), $stub);
    }
}
