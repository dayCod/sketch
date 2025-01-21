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
        'actions' => app_path('Actions'),
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
        'action' => __DIR__.'/../stubs/action.stub',
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
