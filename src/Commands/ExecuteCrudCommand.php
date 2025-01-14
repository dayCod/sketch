<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Illuminate\Console\Command;

class ExecuteCrudCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sketch:execute {model}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate CRUD for a model based on a blueprint file';

    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        //
    }
}
