<?php

namespace Daycode\Sketch\Services;

use Daycode\Sketch\Exceptions\GeneratorException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class FormRequestGenerator
{
    protected string $content;

    public function __construct(protected array $config, protected array $schema) {}

    /**
     * Generate the form request classes.
     */
    public function generate(): string
    {
        $this->generateCreateRequest();
        $this->generateUpdateRequest();

        return 'Form requests generated successfully';
    }

    /**
     * Get the output paths for both request files.
     */
    public function getOutputPath(): array
    {
        return [
            'create' => $this->config['paths']['requests'].'/'.
                    $this->schema['model'].'/'.
                    $this->schema['model'].'CreateRequest.php',
            'update' => $this->config['paths']['requests'].'/'.
                    $this->schema['model'].'/'.
                    $this->schema['model'].'UpdateRequest.php',
        ];
    }

    /**
     * Get the path for a request class.
     */
    protected function getRequestPath(string $type): string
    {
        return $this->config['paths']['requests'].'/'.
               $this->schema['model'].'/'.
               $this->schema['model'].$type.'Request.php';
    }

    /**
     * Generate the create request class.
     */
    protected function generateCreateRequest(): void
    {
        $content = $this->generateRequestClass('Create', true);
        $path = $this->getRequestPath('Create');

        $this->ensureDirectoryExists($path);
        file_put_contents($path, $content);
    }

    /**
     * Generate the update request class.
     */
    protected function generateUpdateRequest(): void
    {
        $content = $this->generateRequestClass('Update', false);
        $path = $this->getRequestPath('Update');

        $this->ensureDirectoryExists($path);
        file_put_contents($path, $content);
    }

    /**
     * Generate a form request class.
     */
    protected function generateRequestClass(string $type, bool $isCreate): string
    {
        $stub = file_get_contents($this->config['stubs']['request']);

        $namespace = str_replace(
            'Models',
            'Http\\Requests\\'.$this->schema['model'],
            $this->config['model_namespace']
        );

        return str_replace(
            [
                '{{ namespace }}',
                '{{ class }}',
                '{{ rules }}',
            ],
            [
                $namespace,
                $this->schema['model'].$type.'Request',
                $this->generateValidationRules($isCreate),
            ],
            $stub
        );
    }

    /**
     * Generate validation rules for fields.
     */
    protected function generateValidationRules(bool $isCreate): string
    {
        $rules = collect($this->schema['fields'])
            ->map(function (array $field) use ($isCreate): string {
                $rules = $this->getFieldRules($field, $isCreate);

                return sprintf("            '%s' => ['%s']", $field['name'], implode("', '", $rules));
            })
            ->when(
                ! empty($this->schema['relationships']),
                fn ($collection) => $collection->merge(
                    $this->generateRelationshipRules($isCreate)
                )
            )
            ->implode(",\n");

        if (! empty($rules)) {
            $rules = "\n".$rules."\n        ";
        }

        return sprintf(
            'return [%s];',
            $rules
        );
    }

    /**
     * Get validation rules for a field.
     */
    protected function getFieldRules(array $field, bool $isCreate): array
    {
        $rules = [];

        // Required/nullable rule
        if ($isCreate) {
            $rules[] = $field['nullable'] ?? false ? 'nullable' : 'required';
        } else {
            $rules[] = 'sometimes';
            $rules[] = $field['nullable'] ?? false ? 'nullable' : 'required';
        }

        // Type-specific rules
        switch ($field['type']) {
            case 'string':
                $rules[] = 'string';
                $rules[] = 'max:255';
                break;
            case 'text':
                $rules[] = 'string';
                break;
            case 'integer':
                $rules[] = 'integer';
                break;
            case 'decimal':
            case 'float':
            case 'double':
                $rules[] = 'numeric';
                break;
            case 'boolean':
                $rules[] = 'boolean';
                break;
            case 'date':
            case 'datetime':
                $rules[] = 'date';
                break;
            case 'email':
                $rules[] = 'email:rfc,dns';
                break;
            case 'url':
                $rules[] = 'url';
                break;
            case 'enum':
                if (! empty($field['options'])) {
                    $rules[] = 'in:'.implode(',', $field['options']);
                }
                break;
            case 'json':
                $rules[] = 'json';
                break;
        }

        // Custom rules from schema
        if (! empty($field['rules'])) {
            return array_merge($rules, (array) $field['rules']);
        }

        return $rules;
    }

    /**
     * Generate validation rules for relationships.
     */
    protected function generateRelationshipRules(bool $isCreate): Collection
    {
        return collect($this->schema['relationships'])
            ->map(function (array $relation) use ($isCreate): string {
                $rules = ['exists:'.Str::snake(Str::pluralStudly($relation['model'])).',id'];

                if ($isCreate) {
                    array_unshift($rules, $relation['nullable'] ?? false ? 'nullable' : 'required');
                } else {
                    array_unshift($rules, 'sometimes', 'nullable');
                }

                return sprintf("            '%s' => ['%s']", $relation['foreignKey'], implode("', '", $rules));
            });
    }

    /**
     * Check if directory exists or not
     */
    protected function ensureDirectoryExists(string $path): void
    {
        $directory = dirname($path);
        if (! is_dir($directory) && ! mkdir($directory, 0777, true)) {
            throw new GeneratorException("Failed to create directory: $directory");
        }
    }
}
