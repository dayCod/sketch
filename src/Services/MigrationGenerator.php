<?php

namespace Daycode\Sketch\Services;

use Illuminate\Support\Str;

class MigrationGenerator
{
    protected string $content;

    public function __construct(protected array $config, protected array $schema) {}

    public function generate(): string
    {
        $stub = file_get_contents($this->getStubPath());
        $tableName = Str::snake(Str::pluralStudly($this->schema['model']));

        $this->content = $stub;

        $this->content = str_replace(
            ['{{ table }}', '{{ schema }}'],
            [$tableName, $this->generateSchema()],
            $this->content
        );

        return $this->getContent();
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getOutputPath(): string
    {
        $tableName = Str::snake(Str::pluralStudly($this->schema['model']));
        $timestamp = now()->format('Y_m_d_His');

        return $this->config['paths']['migrations']."/{$timestamp}_create_{$tableName}_table.php";
    }

    protected function getStubPath(): string
    {
        return $this->config['stubs']['migration'];
    }

    protected function generateSchema(): string
    {
        $schema = '';
        $pivotTables = [];

        // Primary Key
        $schema .= $this->generatePrimaryKey();

        // Fields
        foreach ($this->schema['fields'] as $field) {
            $schema .= $this->generateField($field);
        }

        // Relationships
        if (!empty($this->schema['relationships'])) {
            foreach ($this->schema['relationships'] as $relation) {
                if ($relation['type'] === 'belongsToMany') {
                    $pivotTables[] = $this->generatePivotTable($relation);
                } elseif (!in_array($relation['type'], ['hasOneThrough', 'hasManyThrough'])) {
                    $schema .= $this->generateRelationField($relation);
                }
            }
        }

        // Timestamps
        if ($this->schema['timestamps'] ?? true) {
            $schema .= "            \$table->timestamps();\n";
        }

        // Soft Deletes
        if ($this->schema['softDeletes'] ?? false) {
            $schema .= "            \$table->softDeletes();\n";
        }

        // Generate pivot tables if any
        if (!empty($pivotTables)) {
            $schema .= "\n            // Pivot Tables\n";
            $schema .= implode("\n", $pivotTables);
        }

        return $schema;
    }

    protected function generatePrimaryKey(): string
    {
        $pk = $this->schema['primaryKey'] ?? ['name' => 'id', 'type' => 'id'];

        return "\$table->{$pk['type']}('{$pk['name']}')->primary();\n";
    }

    protected function generatePivotTable(array $relation): string
    {
        $pivotTable = $relation['pivotTable'] ?? $this->generatePivotTableName($this->schema['model'], $relation['model']);
        $tableKeyType = $relation['pivotTableKeyType'] ?? 'integer';

        $schema = "\n            Schema::create('{$pivotTable}', function (Blueprint \$table) {\n";

        // Generate pivot table primary key
        if ($tableKeyType === 'uuid') {
            $schema .= "                \$table->uuid('id')->primary();\n";
        } elseif ($tableKeyType === 'ulid') {
            $schema .= "                \$table->ulid('id')->primary();\n";
        } else {
            $schema .= "                \$table->id();\n";
        }

        // Generate foreign pivot key
        $foreignPivot = $relation['foreignPivot'];
        $schema .= $this->generatePivotForeignKey(
            key: $foreignPivot['key'],
            keyType: $foreignPivot['type'],
            references: $foreignPivot['references'] ?? 'id',
            tableName: $foreignPivot['table']
        );

        // Generate related pivot key
        $relatedPivot = $relation['relatedPivot'];
        $schema .= $this->generatePivotForeignKey(
            key: $relatedPivot['key'],
            keyType: $relatedPivot['type'],
            references: $relatedPivot['references'] ?? 'id',
            tableName: $relatedPivot['table']
        );

        // Generate additional pivot columns
        if (isset($relation['pivotColumns']) && is_array($relation['pivotColumns'])) {
            foreach ($relation['pivotColumns'] as $column) {
                $schema .= $this->generatePivotColumn($column);
            }
        }

        // Add timestamps if specified
        if ($relation['withTimestamps'] ?? false) {
            $schema .= "                \$table->timestamps();\n";
        }

        $schema .= "            });\n";

        return $schema;
    }

    protected function generatePivotForeignKey(string $key, string $keyType, string $references, string $tableName): string
    {
        $schema = '';

        if ($keyType === 'uuid') {
            $schema .= "                \$table->foreignUuid('{$key}')\n";
        } elseif ($keyType === 'ulid') {
            $schema .= "                \$table->foreignUlid('{$key}')\n";
        } else {
            $schema .= "                \$table->foreignId('{$key}')\n";
        }

        $schema .= "                    ->references('{$references}')\n";
        $schema .= "                    ->on('{$tableName}')\n";
        $schema .= "                    ->cascadeOnDelete()\n";
        $schema .= "                    ->cascadeOnUpdate();\n";

        return $schema;
    }

    protected function generatePivotColumn(array $column): string
    {
        $name = $column['name'];
        $type = $column['type'];
        $nullable = $column['nullable'] ?? false;

        $schema = "                \$table->{$type}('{$name}')";

        if ($nullable) {
            $schema .= "->nullable()";
        }

        return $schema . ";\n";
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

    protected function generateField(array $field): string
    {
        $type = $field['type'];
        $name = $field['name'];
        $nullable = $field['nullable'] ?? false;

        $column = match ($type) {
            'enum' => "\$table->enum('{$name}', ['".implode("', '", $field['options'])."'])",
            default => "\$table->{$type}('{$name}')"
        };

        if ($nullable) {
            $column .= '->nullable()';
        }

        return "            {$column};\n";
    }

    protected function generateRelationField(array $relation): string
    {
        $tableName = Str::plural(Str::camel($relation['model']));
        $primaryKey = $relation['ownerKey'] ?? $relation['localKey'];
        $keyType = $relation['keyType'] ?? 'integer';
        $foreignKey = $relation['foreignKey'];
        $onUpdate = $relation['onUpdate'] ?? 'cascade';
        $onDelete = $relation['onDelete'] ?? 'cascade';

        if ($keyType == 'uuid') {
            $schema = "            \$table->foreignUuid('{$foreignKey}')\n";
        } elseif ($keyType == 'ulid') {
            $schema = "            \$table->foreignUlid('{$foreignKey}')\n";
        } else {
            $schema = "            \$table->foreignId('{$foreignKey}')\n";
        }

        $schema .= "                ->references('{$primaryKey}')\n";
        $schema .= "                ->on('{$tableName}')\n";
        $schema .= "                ->onUpdate('{$onUpdate}')\n";

        return $schema."                ->onDelete('{$onDelete}');\n";
    }
}
