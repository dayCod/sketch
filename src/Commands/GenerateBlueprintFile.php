<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Daycode\Sketch\Blueprint;
use Illuminate\Console\Command;

class GenerateBlueprintFile extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sketch:make-blueprint {name} {--soft-delete}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create Blueprint YAML structure for a Laravel resource';

    /**
     * Execute the console command.
     */
    public function handle(Blueprint $blueprint): void
    {
        try {
            $name = $this->argument('name');
            $result = $blueprint->createYaml(name: $name, softDelete: $this->option('soft-delete'));

            $this->info("YAML file for {$this->argument('name')} has been created at: {$result}");
        } catch (\Exception $ex) {
            $this->error($ex->getMessage());
        }
    }
}
