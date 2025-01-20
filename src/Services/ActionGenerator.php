<?php

namespace Daycode\Sketch\Services;

use Daycode\Sketch\Exceptions\GeneratorException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ActionGenerator
{
    protected string $content;

    public function __construct(protected array $config, protected array $schema) {}

    /**
     * Generate the action class.
     */
    public function generate(): string
    {
        // Generate action class
        $this->content = file_get_contents($this->getStubPath());

        $this->replaceClass()
            ->replaceNamespace()
            ->replaceModel()
            ->replaceValidationRules()
            ->replaceMethods();

        // Create directories if they don't exists
        $this->ensureDirectoryExists($this->getOutputPath());

        // Generate form request classes
        $this->generateCreateRequest();
        $this->generateUpdateRequest();

        return $this->getContent();
    }

    /**
     * Get the output path for the action class.
     */
    public function getOutputPath(): string
    {
        return $this->config['paths']['actions'].'/'.
               $this->schema['model'].'/'.
               $this->schema['model'].'Action.php';
    }

    /**
     * Get the stub file path.
     */
    protected function getStubPath(): string
    {
        return $this->config['stubs']['action'];
    }

    /**
     * Get the generated content.
     */
    public function getContent(): string
    {
        return $this->content;
    }

    /**
     * Replace the class name.
     */
    protected function replaceClass(): self
    {
        $this->content = str_replace(
            '{{ class }}',
            $this->schema['model'].'Action',
            $this->content
        );

        return $this;
    }

    /**
     * Replace the namespace.
     */
    protected function replaceNamespace(): self
    {
        $namespace = str_replace('Models', 'Actions', $this->config['model_namespace']);
        $namespace .= '\\'.$this->schema['model'];

        $this->content = str_replace('{{ namespace }}', $namespace, $this->content);

        return $this;
    }

    /**
     * Replace the model.
     */
    protected function replaceModel(): self
    {
        $this->content = str_replace(
            ['{{ model }}', '{{ model_namespace }}'],
            [$this->schema['model'], $this->config['model_namespace']],
            $this->content
        );

        return $this;
    }

    /**
     * Replace validation rules.
     */
    protected function replaceValidationRules(): self
    {
        $createRules = $this->generateValidationRules(true);
        $updateRules = $this->generateValidationRules(false);

        $this->content = str_replace(
            ['{{ createRules }}', '{{ updateRules }}'],
            [$createRules, $updateRules],
            $this->content
        );

        return $this;
    }

    /**
     * Generate validation rules for fields.
     */
    protected function generateValidationRules(bool $isCreate): string
    {
        return collect($this->schema['fields'])
            ->map(function (array $field) use ($isCreate): string {
                $rules = $this->getFieldRules($field, $isCreate);

                return "            '{$field['name']}' => ['".implode("', '", $rules)."']";
            })
            ->when(
                ! empty($this->schema['relationships']),
                fn ($collection) => $collection->merge(
                    $this->generateRelationshipRules($isCreate)
                )
            )
            ->implode(",\n");
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
            ->map(function ($relation) use ($isCreate): string {
                $rules = ['exists:'.Str::snake(Str::pluralStudly($relation['model'])).',id'];

                if ($isCreate) {
                    $rules = array_merge(
                        [$relation['nullable'] ?? false ? 'nullable' : 'required'],
                        $rules
                    );
                } else {
                    $rules = array_merge(['sometimes', 'nullable'], $rules);
                }

                return "            '{$relation['foreignKey']}' => ['".implode("', '", $rules)."']";
            });
    }

    /**
     * Replace the methods in the action class.
     */
    protected function replaceMethods(): self
    {
        $methods = $this->generateMethods();
        $this->content = str_replace('{{ methods }}', $methods, $this->content);

        return $this;
    }

    /**
     * Generate the methods for the action class.
     */
    protected function generateMethods(): string
    {
        return <<<PHP
            /**
             * Create a new {$this->schema['model']}.
             *
             * @param array \$data
             * @return \\{$this->config['model_namespace']}\\{$this->schema['model']}
             */
            public function create(array \$data)
            {
                \$validated = app({$this->schema['model']}CreateRequest::class)->validate(\$data);

                return {$this->schema['model']}::create(\$validated);
            }

            /**
             * Update the specified {$this->schema['model']}.
             *
             * @param \\{$this->config['model_namespace']}\\{$this->schema['model']} \$model
             * @param array \$data
             * @return \\{$this->config['model_namespace']}\\{$this->schema['model']}
             */
            public function update({$this->schema['model']} \$model, array \$data)
            {
                \$validated = app({$this->schema['model']}UpdateRequest::class)->validate(\$data);

                \$model->update(\$validated);

                return \$model;
            }

            /**
             * Delete the specified {$this->schema['model']}.
             *
             * @param \\{$this->config['model_namespace']}\\{$this->schema['model']} \$model
             * @return bool
             */
            public function delete({$this->schema['model']} \$model): bool
            {
                return \$model->delete();
            }
            PHP;
    }

    /**
     * Generate the create request class.
     */
    protected function generateCreateRequest(): void
    {
        $content = $this->generateRequestClass('Create', true);
        $path = $this->getRequestPath('Create');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

        file_put_contents($path, $content);
    }

    /**
     * Generate the update request class.
     */
    protected function generateUpdateRequest(): void
    {
        $content = $this->generateRequestClass('Update', false);
        $path = $this->getRequestPath('Update');

        if (! is_dir(dirname($path))) {
            mkdir(dirname($path), 0777, true);
        }

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
     * Get the path for a request class.
     */
    protected function getRequestPath(string $type): string
    {
        return $this->config['paths']['requests'].'/'.
               $this->schema['model'].'/'.
               $this->schema['model'].$type.'Request.php';
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
