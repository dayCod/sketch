<?php

declare(strict_types=1);

namespace Daycode\Sketch\Services;

use Illuminate\Support\Facades\File;

class CrudGeneratorService
{
    /**
     * Constructor for CrudGeneratorService.
     *
     * @param  ModelRelationshipService  $modelRelationshipService  Service to handle model relationship parsing and generation.
     */
    public function __construct(
        protected ModelRelationshipService $modelRelationshipService,
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
        $stubPath = __DIR__.'/../../stubs/Model.stub';
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
}
