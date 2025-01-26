<?php

namespace Daycode\Sketch\Services;

use Daycode\Sketch\Exceptions\GeneratorException;
use Illuminate\Support\Str;

class ModelGenerator
{
    protected string $content = '';

    public function __construct(protected array $config, protected array $schema) {}

    public function generate(): string
    {
        try {
            // Baca stub file
            $stubPath = $this->getStubPath();
            if (! file_exists($stubPath)) {
                throw new GeneratorException("Model stub not found at: {$stubPath}");
            }

            $stub = file_get_contents($stubPath);
            if ($stub === false) {
                throw new GeneratorException('Failed to read model stub');
            }

            $this->content = $stub;

            // Replace placeholders
            $this->replaceClass()
                ->replaceNamespace()
                ->replaceFillable()
                ->replaceCasts()
                ->replaceRelationships()
                ->replaceSoftDeletes();

            // Ensure output directory exists
            $outputPath = $this->getOutputPath();
            $this->ensureDirectoryExists($outputPath);

            // Write file
            if (file_put_contents($outputPath, $this->getContent()) === false) {
                throw new GeneratorException('Failed to write model file');
            }

            return $this->getContent();
        } catch (\Exception $e) {
            throw new GeneratorException("Failed to generate model: {$e->getMessage()}");
        }
    }

    protected function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true)) {
            throw new GeneratorException("Failed to create directory: $directory");
        }
    }

    protected function replaceClass(): self
    {
        $this->content = str_replace('{{ class }}', $this->schema['model'], $this->content);

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getOutputPath(): string
    {
        return $this->config['paths']['models'].'/'.$this->schema['model'].'.php';
    }

    protected function getStubPath(): string
    {
        $path = $this->config['stubs']['model'] ?? '';

        if (empty($path)) {
            throw new GeneratorException('Model stub path not configured');
        }

        return $path;
    }

    protected function replaceNamespace(): self
    {
        $namespace = $this->config['model_namespace'] ?? 'App\\Models';

        $this->content = str_replace('{{ namespace }}', $namespace, $this->content);

        return $this;
    }

    protected function replaceFillable(): self
    {
        $fillable = collect($this->schema['fields'] ?? [])
            ->pluck('name')
            ->map(fn ($field): string => sprintf("        '%s'", $field))
            ->implode(",\n");

        if (! empty($fillable)) {
            $fillable = "\n".$fillable."\n    ";
        }

        $this->content = str_replace('{{ fillable }}', $fillable, $this->content);

        return $this;
    }

    protected function replaceCasts(): self
    {
        $casts = collect($this->schema['fields'] ?? [])
            ->filter(fn ($field): bool => in_array($field['type'], ['enum', 'json', 'datetime', 'boolean']))
            ->map(function (array $field): string {
                $type = match ($field['type']) {
                    'enum' => 'string',
                    'json' => 'array',
                    'datetime' => 'datetime',
                    'boolean' => 'boolean',
                    default => $field['type']
                };

                return "'{$field['name']}' => '{$type}'";
            })
            ->implode(",\n");

        $this->content = str_replace('{{ casts }}', $casts, $this->content);

        return $this;
    }

    protected function replaceRelationships(): self
    {
        if (empty($this->schema['relationships'])) {
            $this->content = str_replace('{{ relationships }}', '', $this->content);

            return $this;
        }

        $relationships = collect($this->schema['relationships'])
            ->map(function (array $relation): string {
                $method = Str::camel(class_basename($relation['model']));
                $relationClass = $relation['model'];

                return match ($relation['type']) {
                    'belongsTo' => $this->generateBelongsToRelation($method, $relationClass, $relation),
                    'hasOne' => $this->generateHasOneRelation($method, $relationClass, $relation),
                    'hasMany' => $this->generateHasManyRelation($method, $relationClass, $relation),
                    'belongsToMany' => $this->generateBelongsToManyRelation($method, $relationClass, $relation),
                    'hasOneThrough' => $this->generateHasOneThroughRelation($method, $relationClass, $relation),
                    'hasManyThrough' => $this->generateHasManyThroughRelation($method, $relationClass, $relation),
                    default => throw new GeneratorException("Unknown relationship type: {$relation['type']}")
                };
            })
            ->implode("\n\n");

        $this->content = str_replace('{{ relationships }}', $relationships, $this->content);

        return $this;
    }

    protected function replaceSoftDeletes(): self
    {
        if ($this->schema['softDeletes'] ?? false) {
            $this->content = str_replace('{{ useSoftDeletes }}', 'use Illuminate\Database\Eloquent\SoftDeletes;', $this->content);
            $this->content = str_replace('{{ softDeletes }}', '    use SoftDeletes;', $this->content);
        } else {
            $this->content = str_replace('{{ useSoftDeletes }}', '', $this->content);
            $this->content = str_replace('{{ softDeletes }}', '', $this->content);
        }

        return $this;
    }

    protected function generateBelongsToRelation(string $method, string $relationClass, array $relation): string
    {
        return <<<PHP
        /**
             * Get the {$method} that owns the model.
             */
            public function {$method}(): BelongsTo
            {
                return \$this->belongsTo({$relationClass}::class, '{$relation['foreignKey']}', '{$relation['ownerKey']}');
            }
        PHP;
    }

    protected function generateHasOneRelation(string $method, string $relationClass, array $relation): string
    {
        $foreignKey = $relation['foreignKey'] ?? Str::snake($this->schema['model']).'_id';
        $localKey = $relation['localKey'] ?? 'id';

        return <<<PHP
            /**
             * Get the {$method} associated with the {$this->schema['model']}.
             */
            public function {$method}(): HasOne
            {
                return \$this->hasOne({$relationClass}::class, '{$foreignKey}', '{$localKey}');
            }
        PHP;
    }

    protected function generateHasManyRelation(string $method, string $relationClass, array $relation): string
    {
        $foreignKey = $relation['foreignKey'] ?? Str::snake($this->schema['model']).'_id';
        $localKey = $relation['localKey'] ?? 'id';

        return <<<PHP
            /**
             * Get the {$method} for the {$this->schema['model']}.
             */
            public function {$method}(): HasMany
            {
                return \$this->hasMany({$relationClass}::class, '{$foreignKey}', '{$localKey}');
            }
        PHP;
    }

    protected function generateBelongsToManyRelation(string $method, string $relationClass, array $relation): string
    {
        $table = $relation['pivotTable'] ?? $this->generatePivotTableName($this->schema['model'], class_basename($relationClass));
        $foreignPivotKey = $relation['foreignPivot']['key'] ?? Str::snake($this->schema['model']) . '_id';
        $relatedPivotKey = $relation['relatedPivot']['key'] ?? Str::snake(class_basename($relationClass)) . '_id';

        // Build withPivot call if there are additional columns
        $withPivot = '';
        if (isset($relation['pivotColumns']) && is_array($relation['pivotColumns'])) {
            $pivotColumns = array_map(function($column) {
                return $column['name'];
            }, $relation['pivotColumns']);

            if (!empty($pivotColumns)) {
                $withPivot = "->withPivot('" . implode("', '", $pivotColumns) . "')";
            }
        }

        // Add timestamps if specified
        if ($relation['withTimestamps'] ?? false) {
            $withPivot .= '->withTimestamps()';
        }

        return <<<PHP
            /**
             * The {$method} that belong to the {$this->schema['model']}.
             */
            public function {$method}(): BelongsToMany
            {
                return \$this->belongsToMany({$relationClass}::class, '{$table}', '{$foreignPivotKey}', '{$relatedPivotKey}'){$withPivot};
            }
        PHP;
    }

    protected function generateHasOneThroughRelation(string $method, string $relationClass, array $relation): string
    {
        $through = $relation['through'];
        $firstKey = $relation['firstKey'] ?? Str::snake($this->schema['model']) . '_id';
        $secondKey = $relation['secondKey'] ?? Str::snake(class_basename($through)) . '_id';
        $localKey = $relation['localKey'] ?? 'id';
        $secondLocalKey = $relation['secondLocalKey'] ?? 'id';

        return <<<PHP
            /**
             * Get the {$method} associated with the {$this->schema['model']} through {$through}.
             */
            public function {$method}(): HasOneThrough
            {
                return \$this->hasOneThrough(
                    {$relationClass}::class,
                    {$through}::class,
                    '{$firstKey}',
                    '{$secondKey}',
                    '{$localKey}',
                    '{$secondLocalKey}'
                );
            }
        PHP;
    }

    protected function generateHasManyThroughRelation(string $method, string $relationClass, array $relation): string
    {
        $through = $relation['through'];
        $firstKey = $relation['firstKey'] ?? Str::snake($this->schema['model']) . '_id';
        $secondKey = $relation['secondKey'] ?? Str::snake(class_basename($through)) . '_id';
        $localKey = $relation['localKey'] ?? 'id';
        $secondLocalKey = $relation['secondLocalKey'] ?? 'id';

        return <<<PHP
            /**
             * Get all {$method} associated with the {$this->schema['model']} through {$through}.
             */
            public function {$method}(): HasManyThrough
            {
                return \$this->hasManyThrough(
                    {$relationClass}::class,
                    {$through}::class,
                    '{$firstKey}',
                    '{$secondKey}',
                    '{$localKey}',
                    '{$secondLocalKey}'
                );
            }
        PHP;
    }

    protected function generateWithPivot(array $columns): string
    {
        if ($columns === []) {
            return '';
        }

        return "->withPivot('".implode("', '", $columns)."')";
    }

    protected function generatePivotTableName(string $model1, string $model2): string
    {
        $models = [
            Str::snake(Str::plural($model1)),
            Str::snake(Str::plural($model2))
        ];
        sort($models);
        return implode('_', $models);
    }
}
