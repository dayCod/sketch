<?php

declare(strict_types=1);

namespace Daycode\Sketch\Services;

use Daycode\Sketch\Exceptions\GeneratorException;
use Ferdinalaxewall\ServiceRepositoryGenerator\Facades\RepositoryGenerator;
use Ferdinalaxewall\ServiceRepositoryGenerator\Facades\ServiceGenerator;
use Ferdinalaxewall\ServiceRepositoryGenerator\Facades\ServiceRepositoryGenerator as VendorServiceRepositoryGenerator;
use Illuminate\Support\Facades\Log;

class ServiceRepositoryGenerator
{
    public function __construct(
        protected array $config,
        protected array $schema,
        protected array $options = []
    ) {}

    /**
     * Generate service repository files based on options.
     */
    public function generate(): array
    {
        try {
            $results = [];

            if ($this->options['service-repository'] ?? false) {
                $results[] = $this->generateServiceRepository();
            }

            if ($this->options['service-only'] ?? false) {
                $results[] = $this->generateService();
            }

            if ($this->options['repository-only'] ?? false) {
                $results[] = $this->generateRepositoryWithModel();
            }

            return array_filter($results);

        } catch (\Exception $e) {
            Log::error('ServiceRepositoryGenerator failed:', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'options' => $this->options,
                'schema' => $this->schema,
            ]);

            throw new GeneratorException("Failed to generate service repository: {$e->getMessage()}");
        }
    }

    /**
     * Generate complete Service Repository
     */
    protected function generateServiceRepository(): ?string
    {
        try {
            VendorServiceRepositoryGenerator::generateFromModel($this->schema['model'], 'service,repository');

            return "Service Repository generated for {$this->schema['model']}";
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Generate Service Only
     */
    protected function generateService(): ?string
    {
        try {
            ServiceGenerator::generate($this->schema['model'], true);

            return "Service generated: {$this->schema['model']}";
        } catch (\Exception) {
            return null;
        }
    }

    /**
     * Generate Repository with Model
     */
    protected function generateRepositoryWithModel(): ?string
    {
        try {
            RepositoryGenerator::generate($this->schema['model'], false, $this->schema['model']);

            if (! file_exists('Repositories/BaseRepository.php')) {
                RepositoryGenerator::generateBaseRepository();
            }

            return "Repository generated with model: {$this->schema['model']}";
        } catch (\Exception) {
            return null;
        }
    }
}
