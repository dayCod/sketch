<p align="center">
  <img src="https://github.com/dayCod/sketch/blob/master/art/sketch-logo.png" alt="Sketch Logo">
</p>

<p align="center">
  <a href="https://packagist.org/packages/daycode/sketch"><img src="https://img.shields.io/packagist/v/daycode/sketch" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/daycode/sketch"><img src="https://img.shields.io/packagist/dt/daycode/sketch" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/daycode/sketch"><img src="https://img.shields.io/packagist/l/daycode/sketch" alt="License"></a>
</p>

## Blueprint-Based Structure Generator

Sketch is a powerful Laravel package that transforms your application development workflow. Instead of starting with migrations or models, Sketch allows you to define your entire application structure using simple YAML blueprints. This schema-first approach ensures consistency and accelerates development across your Laravel applications.

## Features

- 📝 **Blueprint-Based Generation**
  - Define your entire application structure in YAML
  - Generate models, migrations, and services from a single source
  - Maintain consistency across your application components

- ⚡ **Rapid Development**
  - Eliminate repetitive boilerplate code
  - Generate complete application components in seconds
  - Focus on business logic instead of scaffolding

- 🧩 **Built-in Relationships**
  - Support for all Laravel relationships
  - Automatic foreign key generation
  - Proper relationship method generation

- 🏗️ **Service Repository Pattern**
  - Generate service and repository layers
  - Follow SOLID principles automatically
  - Maintain clean architecture effortlessly

## Quick Installation

1. Install the package via Composer:
```bash
composer require daycode/sketch
```

2. Publish the configuration:
```bash
php artisan vendor:publish --provider="Daycode\Sketch\SketchServiceProvider"
```

## Quick Usage

1. Create a YAML blueprint:
```bash
php artisan sketch:make-blueprint models/blog/post
```

2. Define your schema in the generated YAML file:
```yaml
model: Post
primaryKey:
    name: id
    type: integer
fields:
    - { name: title, type: string, nullable: false }
    - { name: content, type: text, nullable: true }
    - { name: status, type: enum, nullable: true, options: ['draft', 'published'] }
timestamps: true
softDeletes: true
relationships:
    - { type: belongsTo, model: User, foreignKey: user_id }
```

3. Execute Specific Files
```bash
php artisan sketch:generate --file=schemas/models/blog/post.yaml
```

## Configuration

After publishing the configuration file, you can modify these settings in `config/sketch.php`:
```php
<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Schema Path Configuration
    |--------------------------------------------------------------------------
    |
    | This value determines where your YAML schema files will be stored.
    | By default, schemas will be placed in the 'schemas' directory
    | in the root of your project.
    |
    */
    'schemas' => [
        'path' => base_path('schemas'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Output Path Configuration
    |--------------------------------------------------------------------------
    |
    | This value determines where your generated files will be placed.
    | By default, models will be placed in app/Models,
    | migrations in database/migrations, and actions in app/Actions.
    |
    */
    'paths' => [
        'models' => app_path('Models'),
        'migrations' => database_path('migrations'),
        'requests' => app_path('Http/Requests'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Stub Path Configuration
    |--------------------------------------------------------------------------
    |
    | This value determines where your stub files are located.
    | You can publish these stubs and modify them according to your needs.
    |
    */
    'stubs' => [
        'model' => __DIR__.'/../stubs/model.stub',
        'migration' => __DIR__.'/../stubs/migration.stub',
        'request' => __DIR__.'/../stubs/form-request.stub',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Model Namespace
    |--------------------------------------------------------------------------
    |
    | This value determines the default namespace for your models.
    |
    */
    'model_namespace' => 'App\\Models',

];

```

## Available Commands

Generate Blueprints:
```bash
# Simple blueprint
php artisan sketch:make-blueprint post

# Nested directory blueprint
php artisan sketch:make-blueprint models/blog/post

# With soft delete
php artisan sketch:make-blueprint models/blog/post --soft-delete
```

### Component Generation
```bash
# Generate from blueprint
php artisan sketch:generate --file=path/to/schema.yaml [options]
```

Generation options:
- `--force`: Override existing files
- `--service-repository`: Generate both service and repository layers
- `--service-only`: Generate service layer only
- `--repository-only`: Generate repository layer only

## Testing

```bash
composer test
```

## Contributing

Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details on contributing to Sketch.

## Security

If you discover any security-related issues, please email daycodestudioproject@gmail.com instead of using the issue tracker.

## Credits

- [Daycode](https://github.com/dayCod)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
