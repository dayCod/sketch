<?php

declare(strict_types=1);

namespace Daycode\Sketch;

use Symfony\Component\Yaml\Yaml;

class Blueprint
{
    /**
     * Creates a YAML file representing a model blueprint with the specified name.
     *
     * This function generates a YAML file containing model definitions, fields,
     * timestamps, and relationships based on the provided name. The generated
     * YAML file is stored in the `sketch` directory.
     *
     * @param  string  $model  The name of the model for which the YAML blueprint is to be created.
     * @param  bool  $softDelete  Optional. Whether to include soft delete functionality in the model. Default is false.
     */
    public function createYaml(string $model, bool $softDelete = false): string
    {
        $schemaData = [
            'version' => '1.0',
            'lastUpdated' => now()->format('Y-m-d'),
            'description' => "{$model} schema with basic configuration",
            'model' => $model,
            'primaryKey' => [
                'name' => 'id',
                'type' => 'integer',
            ],
            'fields' => [
                [
                    'name' => 'name',
                    'type' => 'string',
                    'nullable' => false,
                ],
                [
                    'name' => 'description',
                    'type' => 'text',
                    'nullable' => true,
                ],
                [
                    'name' => 'email',
                    'type' => 'string',
                    'nullable' => false,
                ],
                [
                    'name' => 'status',
                    'type' => 'enum',
                    'nullable' => false,
                    'options' => ['active', 'inactive'],
                ],
                [
                    'name' => 'published_at',
                    'type' => 'datetime',
                    'nullable' => true,
                ],
                [
                    'name' => 'metadata',
                    'type' => 'json',
                    'nullable' => true,
                ],
            ],
            'timestamps' => true,
            'softDeletes' => $softDelete,
            'relationships' => [
                [
                    'type' => 'belongsTo',
                    'model' => 'User',
                    'foreignKey' => 'user_id',
                    'ownerKey' => 'id',
                ],
                [
                    'type' => 'hasMany',
                    'model' => 'Comment',
                    'foreignKey' => 'post_id',
                    'localKey' => 'id',
                ],
                [
                    'type' => 'hasOne',
                    'model' => 'Profile',
                    'foreignKey' => 'user_id',
                    'localKey' => 'id',
                ],
            ],
        ];

        return Yaml::dump($schemaData);
    }
}
