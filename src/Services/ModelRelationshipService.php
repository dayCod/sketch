<?php

declare(strict_types=1);

namespace Daycode\Sketch\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\Yaml\Yaml;

class ModelRelationshipService
{
    /**
     * Parses the given YAML file and returns its contents as a string of PHP relationship methods.
     *
     * @param  string|null  $yamlPath  The path to the YAML file containing the model's relationships.
     *
     * @throws \Symfony\Component\HttpFoundation\File\Exception\FileException
     */
    public function parseRelationshipsFromYaml(?string $yamlPath): string
    {
        if ($yamlPath === null || $yamlPath === '' || $yamlPath === '0' || ! File::exists($yamlPath)) {
            throw new FileException("{$yamlPath} not found!");
        }

        $yamlData = Yaml::parseFile($yamlPath);
        $relationships = $yamlData['relationships'] ?? [];
        $relationshipMethods = [];

        foreach ($relationships as $relationship) {
            $type = $relationship['type'];
            $model = $relationship['model'];
            $foreignKey = $relationship['foreignKey'] ?? null;
            $ownerKey = $relationship['ownerKey'] ?? null;
            $localKey = $relationship['localKey'] ?? null;

            // Generate the relationship method based on type
            $method = match ($type) {
                'belongsTo' => $this->generateBelongsTo($model, $foreignKey, $ownerKey),
                'hasOne' => $this->generateHasOne($model, $foreignKey, $localKey),
                'hasMany' => $this->generateHasMany($model, $foreignKey, $localKey),
                default => ''
            };

            if ($method !== '' && $method !== '0') {
                $relationshipMethods[] = $method;
            }
        }

        return implode("\n\n    ", $relationshipMethods);
    }

    /**
     * Generate a belongsTo relationship method.
     *
     * @param  string  $model  The related model class name.
     * @param  string  $foreignKey  The foreign key in the model table.
     * @param  string  $ownerKey  The owner key in the related model table. Defaults to 'id'.
     */
    private function generateBelongsTo(string $model, string $foreignKey, ?string $ownerKey = 'id'): string
    {
        $functionName = Str::lower($model);

        return <<<PHP
        /**
             * Get the {$model} that owns the model.
             *
             * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
             */
            public function {$functionName}(): BelongsTo
            {
                return \$this->belongsTo(
                    related: {$model}::class,
                    foreignKey: '{$foreignKey}',
                    ownerKey: '{$ownerKey}'
                );
            }
        PHP;
    }

    /**
     * Generate a hasOne relationship method.
     *
     * @param  string  $model  The related model class name.
     * @param  string|null  $foreignKey  The foreign key in the model table. Optional.
     * @param  string|null  $localKey  The local key in the model table. Optional.
     */
    private function generateHasOne(string $model, ?string $foreignKey, ?string $localKey): string
    {
        $functionName = Str::lower($model);

        return <<<PHP
        /**
             * Get the related {$model}.
             *
             * @return \Illuminate\Database\Eloquent\Relations\HasOne
             */
            public function {$functionName}()
            {
                return \$this->hasOne({$model}::class, '{$foreignKey}', '{$localKey}');
            }
        PHP;
    }

    /**
     * Generate a hasMany relationship method.
     *
     * @param  string  $model  The related model class name.
     * @param  string|null  $foreignKey  The foreign key in the model table. Optional.
     * @param  string|null  $localKey  The local key in the model table. Optional.
     */
    private function generateHasMany(string $model, ?string $foreignKey, ?string $localKey): string
    {
        $pluralModel = Str::plural($model);
        $functionName = Str::lower($pluralModel);

        return <<<PHP
        /**
             * Get the related {$pluralModel}.
             *
             * @return \Illuminate\Database\Eloquent\Relations\HasMany
             */
            public function {$functionName}()
            {
                return \$this->hasMany({$model}::class, '{$foreignKey}', '{$localKey}');
            }
        PHP;
    }
}
