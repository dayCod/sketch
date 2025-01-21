<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Illuminate\Support\Str;
use Daycode\Sketch\Blueprint;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateBlueprintFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sketch:make-blueprint {path : The path of the model} {--soft-delete : Enable soft delete in the schema}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate a new YAML schema blueprint file';

    /**
     * Execute the console command.
     */
    public function handle(Blueprint $blueprint)
    {
        try {
            $path = $this->argument('path');
            $softDelete = $this->option('soft-delete');

            // Get model name from the last segment of the path
            $segments = explode('/', $path);
            $model = Str::studly(array_pop($segments));

            // Create base schemas directory
            $baseDirectory = config('sketch.schemas.path', base_path('schemas'));
            if (!File::exists($baseDirectory)) {
                File::makeDirectory($baseDirectory);
            }

            // Create subdirectories if needed
            $currentPath = $baseDirectory;
            foreach ($segments as $segment) {
                $currentPath .= '/' . $segment;
                if (!File::exists($currentPath)) {
                    File::makeDirectory($currentPath);
                }
            }

            // Generate blueprint content
            $blueprint = $blueprint->createYaml($model, $softDelete);

            // Final file path
            $filePath = $baseDirectory . '/' . $path . '.yaml';

            if (File::exists($filePath)) {
                if (!$this->confirm("The file {$filePath} already exists. Do you want to override it?")) {
                    $this->info('Operation cancelled.');
                    return self::SUCCESS;
                }
            }

            File::put($filePath, $blueprint);

            $this->info("Blueprint file created: {$filePath}");
            $this->info("Soft Delete: " . ($softDelete ? 'Enabled' : 'Disabled'));

            return self::SUCCESS;
        } catch (\Exception $e) {
            $this->error($e->getMessage());
            return self::FAILURE;
        }
    }
}
