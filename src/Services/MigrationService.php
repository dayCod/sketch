<?php

declare(strict_types=1);

namespace Daycode\Sketch\Services;

use Illuminate\Support\Str;

class MigrationService
{
    /**
     * Generates fields for a migration based on provided configuration.
     *
     * This function takes a primary key definition, a list of fields, and
     * flags for timestamps and soft deletes. It generates the code for the
     * fields, including the primary key, fields, timestamps, and soft deletes
     * based on the provided configuration.
     *
     * @param  array  $primaryKey  The definition of the primary key.
     * @param  array  $fields  The list of fields to generate.
     * @param  bool  $timestamps  Whether to include timestamps in the migration.
     * @param  bool  $softDeletes  Whether to include soft deletes in the migration.
     * @return string
     */
    public function generateFields(array $primaryKey, array $fields, bool $timestamps, bool $softDeletes): string
    {
        $output = [];

        $output[] = "\$table->{$primaryKey['type']}('{$primaryKey['name']}')->primary();";

        foreach ($fields as $field) {
            $nullable = $field['nullable'] ? '->nullable()' : '';

            if ($field['type'] === 'enum') {
                $options = implode("', '", $field['options']);
                $output[] = "\$table->enum('{$field['name']}', ['{$options}']){$nullable};";
            } else {
                $output[] = "\$table->{$field['type']}('{$field['name']}'){$nullable};";
            }
        }

        if ($timestamps) {
            $output[] = "\$table->timestamps();";
        }

        if ($softDeletes) {
            $output[] = "\$table->softDeletes();";
        }

        return implode("\n            ", $output);
    }

    /**
     * Generates foreign key constraints for a migration based on provided relationships.
     *
     * This function takes a list of relationships and generates the code for the
     * foreign key constraints. It assumes that the foreign key name is the
     * singular of the related model name with "_id" appended to the end.
     *
     * @param  array  $relationships  The list of relationships to generate foreign key constraints for.
     * @return string
     */
    public function generateForeignKeys(array $relationships): string
    {
        $output = [];

        foreach ($relationships as $relationship) {
            if ($relationship['type'] === 'belongsTo') {
                $onUpdate = $relationship['onUpdate'] ?? 'cascade';
                $onDelete = $relationship['onDelete'] ?? 'cascade';

                $output[] = "\$table->foreign('{$relationship['foreignKey']}')"
                    . "->references('{$relationship['ownerKey']}')"
                    . "->on('".Str::snake(Str::plural($relationship['model']))."')"
                    . "->onUpdate('{$onUpdate}')"
                    . "->onDelete('{$onDelete}');";
            }
        }

        return implode("\n            ", $output);
    }
}
