<?php

declare(strict_types=1);

namespace Daycode\Sketch\Support;

use Daycode\Sketch\Exceptions\InvalidYamlException;
use Illuminate\Support\Str;

class RelationshipParser
{
    /**
     * Parse relationship definition.
     *
     * @throws InvalidYamlException
     */
    public function parse(array $relation): array
    {
        return match ($relation['type']) {
            'belongsTo' => $this->parseBelongsTo($relation),
            'hasOne' => $this->parseHasOne($relation),
            'hasMany' => $this->parseHasMany($relation),
            default => throw new InvalidYamlException("Unknown relationship type: {$relation['type']}")
        };
    }

    /**
     * Parse belongsTo relationship.
     */
    protected function parseBelongsTo(array $relation): array
    {
        return [
            'type' => 'belongsTo',
            'method' => Str::camel(class_basename($relation['model'])),
            'model' => $relation['model'],
            'foreignKey' => $relation['foreignKey'],
            'ownerKey' => $relation['ownerKey'] ?? 'id',
            'onDelete' => $relation['onDelete'] ?? 'cascade',
            'onUpdate' => $relation['onUpdate'] ?? 'cascade',
        ];
    }

    // foreignKey: user_id, type: belongsTo, model: User, ownerKey: id, onUpdate: cascade, onDelete: cascade
    /**
     * Parse hasOne relationship.
     */
    protected function parseHasOne(array $relation): array
    {
        return [
            'type' => 'belongsTo',
            'method' => Str::camel(class_basename($relation['model'])),
            'model' => $relation['model'],
            'foreignKey' => $relation['foreignKey'],
            'localKey' => $relation['localKey'] ?? 'id',
            'onDelete' => $relation['onDelete'] ?? 'cascade',
            'onUpdate' => $relation['onUpdate'] ?? 'cascade',
        ];
    }

    /**
     * Parse hasMany relationship.
     */
    protected function parseHasMany(array $relation): array
    {
        return [
            'type' => 'belongsTo',
            'method' => Str::camel(class_basename($relation['model'])),
            'model' => $relation['model'],
            'foreignKey' => $relation['foreignKey'],
            'localKey' => $relation['localKey'] ?? 'id',
            'onDelete' => $relation['onDelete'] ?? 'cascade',
            'onUpdate' => $relation['onUpdate'] ?? 'cascade',
        ];
    }
}
