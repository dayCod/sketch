<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Daycode\Sketch\Exceptions\InvalidYamlException;
use Daycode\Sketch\Services\ActionGenerator;
use Daycode\Sketch\Services\MigrationGenerator;
use Daycode\Sketch\Services\ModelGenerator;
use Daycode\Sketch\Support\YamlParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SketchCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sketch:generate {--file= : The YAML file to process} {--force : Force generate files even if they already exist}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD Files from YAML Specification';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        try {
            $file = $this->option('file');
            $force = $this->option('force');

            if (empty($file)) {
                $this->error('The file option is required.');

                return self::FAILURE;
            }

            if (! file_exists($file)) {
                throw new InvalidYamlException("File not found: {$file}");
            }

            $schema = (new YamlParser)->parse($file);

            // Ensure directories exist
            $this->ensureDirectoriesExist();

            // Generate Model
            $modelGenerator = new ModelGenerator(config('sketch'), $schema);
            $modelPath = $modelGenerator->getOutputPath();

            if (File::exists($modelPath) && ! $force) {
                $this->warn("Model file already exists: {$modelPath}");
            } else {
                $modelContent = $modelGenerator->generate();
                file_put_contents($modelPath, $modelContent);
                $this->info("Model generated: {$modelPath}");
            }

            // Generate Migration
            $migrationGenerator = new MigrationGenerator(config('sketch'), $schema);
            $migrationPath = $migrationGenerator->getOutputPath();

            $existingMigration = $this->findExistingMigration($schema['model']);

            if ($existingMigration && ! $force) {
                $this->warn("Migration file already exists: {$migrationPath}");
            } else {
                $migrationContent = $migrationGenerator->generate();
                file_put_contents($migrationPath, $migrationContent);
                $this->info("Migration generated: {$migrationPath}");
            }

            // Generate Actions
            $actionGenerator = new ActionGenerator(config('sketch'), $schema);
            $actionPath = $actionGenerator->getOutputPath();

            if (File::exists($actionPath) && ! $force) {
                $this->warn("Actions file already exists: {$actionPath}");
            } else {
                $actionContent = $actionGenerator->generate();
                file_put_contents($actionPath, $actionContent);
                $this->info("Actions generated: {$actionPath}");
            }

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());

            return self::FAILURE;
        }
    }

    protected function findExistingMigration(string $model): ?string
    {
        $table = str($model)->snake()->plural();
        $pattern = database_path("migrations/*_create_{$table}_table.php");
        $existing = glob($pattern);

        return $existing === [] || $existing === false ? null : $existing[0];
    }

    protected function ensureDirectoriesExist(): void
    {
        $paths = [
            config('sketch.paths.models'),
            config('sketch.paths.actions'),
            config('sketch.paths.requests'),
            config('sketch.paths.migrations'),
        ];

        foreach ($paths as $path) {
            if (! File::exists($path)) {
                File::makeDirectory($path, 0755, true);
            }
        }
    }
}
