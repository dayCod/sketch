<?php

declare(strict_types=1);

namespace Daycode\Sketch\Functions;

use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\File\Exception\FileException;

class Helper
{
    /**
     * Scans the directory for existing model and migration files.
     *
     * This function checks if a model with the given name already exists in the
     * app/Models directory, or if a migration with the plural form of the
     * model already exists in the database/migrations directory. If either
     * condition is true, it throws a FileException with a message indicating
     * the path of the existing file. Otherwise, it returns the model name.
     *
     * @param  string|null  $model  The model name to check.
     * @return string
     *
     * @throws FileException
     */
    public static function scanDirectoryByModelName(?string $model)
    {
        $model = Str::contains($model, '/') ? last(explode('/', (string) $model)) : $model;

        $listExistingModel = glob(base_path('app/Models/*.php'));
        $listExistingMigration = glob(database_path('migrations/*.php'));

        foreach ($listExistingModel as $path) {
            if (Str::contains($path, Str::upper($model))) {
                throw new FileException("Model already exists: {$path}");
            }
        }

        foreach ($listExistingMigration as $path) {
            if (Str::contains($path, Str::plural(Str::lower($model)))) {
                throw new FileException("Migration already exists: {$path}");
            }
        }

        return $model;
    }
}
