<p align="center">
  <img src="https://github.com/dayCod/sketch/blob/master/art/sketch-logo.png" alt="Sketch Logo">
</p>

<p align="center">
  <a href="https://packagist.org/packages/daycode/sketch"><img src="https://img.shields.io/packagist/v/daycode/sketch" alt="Latest Stable Version"></a>
  <a href="https://packagist.org/packages/daycode/sketch"><img src="https://img.shields.io/packagist/dt/daycode/sketch" alt="Total Downloads"></a>
  <a href="https://packagist.org/packages/daycode/sketch"><img src="https://img.shields.io/packagist/l/daycode/sketch" alt="License"></a>
</p>

## Blueprint-Based CRUD Generator for Laravel

Sketch is a powerful, easy-to-use package for rapidly generating CRUD functionality in Laravel applications. With **Sketch**, you can effortlessly define models, migrations, form request validations, and actions, all based on simple YAML blueprints. This package streamlines the development process and ensures consistency across your application.

## Features

- 📝 **Blueprint-Based Generation**
  - Define your application structure using simple YAML blueprints
  - Generate models, migrations, and actions from a single source
  - Maintain consistency across your application components

- ⚡ **Rapid Development**
  - Eliminate repetitive boilerplate code
  - Generate complete CRUD functionality in seconds
  - Focus on business logic instead of scaffolding

- 🧩 **Built-in Relationships**
  - Support for all Laravel relationships:
    - belongsTo
    - hasOne
    - hasMany
    - belongsToMany
  - Automatic foreign key generation
  - Proper relationship method generation

- 🔧 **Modern Laravel Features**
  - Laravel 11.x support
  - PHP 8.3 compatibility
  - Form request validation
  - Action-based architecture
  - Soft deletes support

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
return [
    'schemas' => [
        'path' => base_path('schemas'),
    ],
    'paths' => [
        'models' => app_path('Models'),
        'migrations' => database_path('migrations'),
        'actions' => app_path('Actions'),
        'requests' => app_path('Http/Requests'),
    ],
    'stubs' => [
        'model' => __DIR__.'/../stubs/model.stub',
        'migration' => __DIR__.'/../stubs/migration.stub',
        'action' => __DIR__.'/../stubs/action.stub',
        'request' => __DIR__.'/../stubs/request.stub',
    ],
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

Generate Files:
```bash
# Generate from blueprint
php artisan sketch:generate --file=path/to/schema.yaml

# Force regenerate (override existing files)
php artisan sketch:generate --file=path/to/schema.yaml --force
```

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
