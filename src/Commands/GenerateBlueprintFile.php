<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Daycode\Sketch\Services\BlueprintService;
use Illuminate\Console\Command;

class GenerateBlueprintFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sketch:generate {name} {--soft-delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate YAML structure for a Laravel resource';

    /**
     * Execute the console command.
     */
    public function handle(BlueprintService $blueprintService): void
    {
        try {
            $name = $this->argument('name');
            $result = $blueprintService->createYaml(name: $name, softDelete: $this->option('soft-delete'));

            $this->info("YAML file for {$this->argument('name')} has been created at: {$result}");
        } catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
    }
}
