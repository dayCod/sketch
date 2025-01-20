<?php

declare(strict_types=1);

namespace Daycode\Sketch\Tests\Unit\Support;

use Daycode\Sketch\Exceptions\InvalidYamlException;
use Daycode\Sketch\Support\RelationshipParser;

test('it can parse belongsTo relationship', function (): void {
    $relation = [
        'type' => 'belongsTo',
        'model' => 'User',
        'foreignKey' => 'user_id',
    ];

    $parser = new RelationshipParser;
    $parsed = $parser->parse($relation);

    expect($parsed)
        ->toHaveKey('type')
        ->and($parsed['type'])->toBe('belongsTo')
        ->and($parsed)->toHaveKey('method')
        ->and($parsed['method'])->toBe('user');
});

test('it throws exception for unknown relationship type', function (): void {
    $relation = [
        'type' => 'unknown',
        'model' => 'User',
    ];

    $parser = new RelationshipParser;

    expect(fn (): array => $parser->parse($relation))
        ->toThrow(InvalidYamlException::class);
});
