<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Daycode\Sketch\Exceptions\InvalidYamlException;
use Daycode\Sketch\Services\FormRequestGenerator;
use Daycode\Sketch\Services\MigrationGenerator;
use Daycode\Sketch\Services\ModelGenerator;
use Daycode\Sketch\Services\ServiceRepositoryGenerator;
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
    protected $signature = 'sketch:generate
                                {--file= : The YAML file to process}
                                {--force : Force generate files even if they already exist}
                                {--service-repository : Generate both service and repository}
                                {--service-only : Generate service only}
                                {--repository-only : Generate repository with model binding}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate files from YAML schema';

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

            // // Generate Form Requests
            $formRequestGenerator = new FormRequestGenerator(config('sketch'), $schema);
            $formRequestPath = $formRequestGenerator->getOutputPath();

            if (File::exists($formRequestPath['create']) && ! $force) {
                $this->warn("Form request files already exist: {$formRequestPath['create']}");
            } else {
                $formRequestGenerator->generate();
                $this->info('Form requests (create & update) generated successfully');
            }

            // Generate Service Repository
            $serviceRepoOptions = [
                'service-repository' => $this->option('service-repository'),
                'service-only' => $this->option('service-only'),
                'repository-only' => $this->option('repository-only'),
            ];

            // Check if any service repository option is enabled
            if (array_filter($serviceRepoOptions) !== []) {
                $serviceRepoGenerator = new ServiceRepositoryGenerator(
                    config('sketch'),
                    $schema,
                    $serviceRepoOptions
                );

                $serviceRepoGenerator->generate();
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
