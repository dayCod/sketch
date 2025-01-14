<?php

declare(strict_types=1);

namespace Daycode\Sketch\Services;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Yaml\Yaml;

class BlueprintService
{
    /**
     * Creates a YAML file representing a model blueprint with the specified name.
     *
     * This function generates a YAML file containing model definitions, fields,
     * timestamps, and relationships based on the provided name. The generated
     * YAML file is stored in the `sketch` directory.
     *
     * @param  string  $name  The name of the model for which the YAML blueprint is to be created.
     * @param  bool  $softDelete  Optional. Whether to include soft delete functionality in the model. Default is false.
     * @return string The full path to the created YAML file, relative to the `sketch` directory.
     *
     * @throws FileException If a YAML file with the specified name already exists.
     */
    public function createYaml(string $name, bool $softDelete = false): string
    {
        $parsedFilePath = $this->parseFilePath(name: Str::title($name));

        $data = [
            'model' => $parsedFilePath->file,
            'primaryKey' => ['name' => 'id', 'type' => 'integer'],
            'fields' => [
                ['name' => 'title', 'type' => 'string', 'nullable' => false],
                ['name' => 'content', 'type' => 'text', 'nullable' => true],
            ],
            'timestamps' => true,
            'softDeletes' => $softDelete,
            'relationships' => [
                [
                    'foreignKey' => 'user_id',
                    'type' => 'belongsTo',
                    'model' => 'User',
                    'ownerKey' => 'id',
                    'onUpdate' => 'cascade',
                    'onDelete' => 'cascade',
                ],
            ],
        ];

        $fullPath = is_null($parsedFilePath->path)
            ? $parsedFilePath->file
            : $parsedFilePath->path.'/'.$parsedFilePath->file;

        if (! is_dir(base_path("sketch/{$parsedFilePath->path}"))) {
            mkdir(base_path("sketch/{$parsedFilePath->path}"), 0777, true);
        }

        if (file_exists(base_path("sketch/{$fullPath}.yaml"))) {
            throw new FileException("{$fullPath}.yaml already exists!");
        }

        file_put_contents(base_path("sketch/{$fullPath}.yaml"), Yaml::dump($data));

        return $fullPath;
    }

    /**
     * Parses a file path string into an object containing path and file components.
     *
     * @param  string  $name  The file path string, potentially including directories.
     * @return object
     */
    private function parseFilePath(string $name)
    {
        $segments = explode('/', $name);

        if (count($segments) > 1) {
            $file = array_pop($segments);
            $path = implode('/', $segments);

            return (object) [
                'path' => $path,
                'file' => $file,
            ];
        }

        return (object) [
            'path' => null,
            'file' => $segments[0],
        ];
    }
}
