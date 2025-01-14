<?php

declare(strict_types=1);

namespace Daycode\Sketch\Commands;

use Illuminate\Console\Command;

class GenerateCrudCommand extends Command
{
    protected string $signature = 'crud:generate {model}';

    protected string $description = 'Generate CRUD for a model based on a blueprint file';

    public function handle(): void
    {
        //
    }
}
