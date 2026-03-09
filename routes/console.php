<?php

use App\Services\DemoCatalogImportService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('demo:import-catalog {provider=dummyjson} {--limit=30}', function (DemoCatalogImportService $service) {
    $provider = (string) $this->argument('provider');
    $limit = (int) $this->option('limit');

    $this->info("Importing demo catalog from [{$provider}]...");

    try {
        $stats = $service->import($provider, $limit);

        $this->newLine();
        $this->info('Import completed successfully.');
        $this->table(['Metric', 'Count'], [
            ['Categories', $stats['categories']],
            ['Products', $stats['products']],
            ['Reviews', $stats['reviews']],
        ]);
    } catch (\Throwable $e) {
        $this->error('Import failed: ' . $e->getMessage());
        return 1;
    }

    return 0;
})->purpose('Import demo categories/products/reviews from a provider (default: dummyjson).');
