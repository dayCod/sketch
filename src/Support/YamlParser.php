<?php

declare(strict_types=1);

namespace Daycode\Sketch\Support;

use Daycode\Sketch\Exceptions\InvalidYamlException;
use Symfony\Component\Yaml\Yaml;

class YamlParser
{
    /**
     * Parse YAML file and validate schema.
     *
     * @throws InvalidYamlException
     */
    public function parse(string $file): array
    {
        try {
            $content = file_get_contents($file);
            $schema = Yaml::parse($content);

            $this->validate($schema);

            return $schema;
        } catch (\Exception $e) {
            throw new InvalidYamlException("Failed to parse YAML file: {$e->getMessage()}");
        }
    }

    /**
     * Validate schema structure.
     *
     * @throws InvalidYamlException
     */
    protected function validate(array $schema): void
    {
        if (empty($schema['model'])) {
            throw new InvalidYamlException('Model name is required');
        }

        if (empty($schema['fields'])) {
            throw new InvalidYamlException('At least one field is required');
        }

        foreach ($schema['fields'] as $field) {
            if (empty($field['name'])) {
                throw new InvalidYamlException('Field name is required');
            }

            if (empty($field['type'])) {
                throw new InvalidYamlException("Field type is required for field {$field['name']}");
            }

            if ($field['type'] === 'enum' && empty($field['options'])) {
                throw new InvalidYamlException("Enum options are required for field {$field['name']}");
            }
        }

        if (! empty($schema['relationships'])) {
            foreach ($schema['relationships'] as $relation) {
                if (empty($relation['type'])) {
                    throw new InvalidYamlException('Relationship type is required');
                }

                if (empty($relation['model'])) {
                    throw new InvalidYamlException('Related model is required');
                }

                if (in_array($relation['type'], ['belongsTo', 'hasMany', 'hasOne']) && empty($relation['foreignKey'])) {
                    throw new InvalidYamlException('Foreign key is required');
                }

                // if ($relation['type'] == 'belongsToMany' && empty($relation['foreignPivotKey'])) {
                //     throw new InvalidYamlException('Foreign Pivot key is required');
                // }

                if (in_array($relation['type'], ['hasOneThrough', 'hasManyThrough']) && empty($relation['through'])) {
                    throw new InvalidYamlException('Through model is required');
                }
            }
        }
    }
}
