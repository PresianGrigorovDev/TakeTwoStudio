<?php

header('Content-Type: application/json');

// 1. Load Composer Autoloader
require __DIR__.'/../vendor/autoload.php';

// 2. Load Laravel Application (use require, not require_once)
$app = require __DIR__.'/../bootstrap/app.php';

// 3. Bootstrap the Kernel to initialize database connection & Facades
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$results = [];
try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('commercial_portfolio_photos');
    $results['table_exists'] = count($columns) > 0;
    $results['columns'] = $columns;
} catch (\Throwable $e) {
    $results['table_exists'] = false;
    $results['error'] = $e->getMessage() . "\nFile: " . $e->getFile() . " Line: " . $e->getLine();
}

echo json_encode($results, JSON_PRETTY_PRINT);
