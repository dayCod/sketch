<?php

declare(strict_types=1);

namespace Daycode\Sketch\Tests\Unit\Support;

use Daycode\Sketch\Exceptions\InvalidYamlException;
use Daycode\Sketch\Support\YamlParser;

test('it can parse valid yaml file', function (): void {
    $yaml = <<<'YAML'
    model: Post
    fields:
        - { name: title, type: string }
    YAML;

    $file = tempnam(sys_get_temp_dir(), 'test_').'.yaml';
    file_put_contents($file, $yaml);

    $parser = new YamlParser;
    $schema = $parser->parse($file);

    expect($schema)
        ->toHaveKey('model')
        ->and($schema['model'])->toBe('Post')
        ->and($schema)->toHaveKey('fields');

    unlink($file);
});

test('it throws exception for invalid yaml', function (): void {
    $yaml = 'invalid: yaml: content:';
    $file = tempnam(sys_get_temp_dir(), 'test_').'.yaml';
    file_put_contents($file, $yaml);

    $parser = new YamlParser;

    expect(fn (): array => $parser->parse($file))
        ->toThrow(InvalidYamlException::class);

    unlink($file);
});
