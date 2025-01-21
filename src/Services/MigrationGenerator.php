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

        // Primary Key
        $schema .= $this->generatePrimaryKey();

        // Fields
        foreach ($this->schema['fields'] as $field) {
            $schema .= $this->generateField($field);
        }

        // Relationships
        if (! empty($this->schema['relationships'])) {
            foreach ($this->schema['relationships'] as $relation) {
                $schema .= $this->generateRelationField($relation);
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

        return $schema;
    }

    protected function generatePrimaryKey(): string
    {
        $pk = $this->schema['primaryKey'] ?? ['name' => 'id', 'type' => 'id'];

        return "\$table->{$pk['type']}('{$pk['name']}')->primary();\n";
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
