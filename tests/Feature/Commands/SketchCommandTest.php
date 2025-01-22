<?php

declare(strict_types=1);

namespace Daycode\Sketch\Tests\Feature\Commands;

use Illuminate\Support\Facades\File;

test('it can generate files from yaml', function (): void {
    // Setup test directories
    $stubsPath = $this->app->basePath('tests/stubs');
    if (! File::exists($stubsPath)) {
        File::makeDirectory($stubsPath, 0755, true);
    }

    // Create test YAML file
    $yaml = <<<'YAML'
    model: Post
    primaryKey:
        name: id
        type: integer
    fields:
        - { name: title, type: string, nullable: false }
        - { name: content, type: text, nullable: true }
        - { name: status, type: enum, nullable: true, options: ['active', 'inactive'] }
    timestamps: true
    softDeletes: false
    relationships:
        - { foreignKey: user_id, type: belongsTo, model: User, ownerKey: id }
    YAML;

    $yamlPath = $this->app->basePath('tests/stubs/test.yaml');
    File::put($yamlPath, $yaml);

    // Run command
    $this->artisan('sketch:generate', [
        '--file' => $yamlPath,
    ])->assertSuccessful();
});

test('it fails when yaml file does not exist', function (): void {
    $this->artisan('sketch:generate', [
        '--file' => 'non-existent.yaml',
    ])->assertFailed()
        ->expectsOutput('File not found: non-existent.yaml');
});

test('it fails when yaml file is invalid', function (): void {
    $yamlPath = $this->app->basePath('tests/stubs/invalid.yaml');
    File::put($yamlPath, 'invalid: yaml: content:');

    $this->artisan('sketch:generate', [
        '--file' => $yamlPath,
    ])->assertFailed();

    // Clean up test file
    File::delete($yamlPath);
});
