<?php

declare(strict_types=1);

namespace Daycode\Sketch\Services;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Yaml\Yaml;

class BlueprintService
{
    public function createYaml(string $name, bool $softDelete = false): string
    {
        $parsedFilePath = $this->parseFilePath(name: Str::title($name));

        $data = [
            'model' => $parsedFilePath->file,
            'primaryKey' => ['name' => 'id', 'type' => 'integer'],
            'fields' => [
                ['name' => 'title', 'type' => 'string', 'nullable' => false],
                ['name' => 'content', 'type' => 'text', 'nullable' => true]
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
                ]
            ],
        ];

        $fullPath = ! is_null($parsedFilePath->path)
            ? $parsedFilePath->path.'/'.$parsedFilePath->file
            : $parsedFilePath->file;

        if (! is_dir(base_path("sketch/{$parsedFilePath->path}"))) {
            mkdir(base_path("sketch/{$parsedFilePath->path}"), 0777, true);
        }

        if (file_exists(base_path("sketch/{$fullPath}.yaml"))) {
            throw new FileException("{$fullPath}.yaml already exists!");
        }

        file_put_contents(base_path("sketch/{$fullPath}.yaml"), Yaml::dump($data));

        return $fullPath;
    }

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
