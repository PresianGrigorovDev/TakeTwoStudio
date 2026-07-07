<?php

$results = [];

// 1. Delete * 2.php files in database/migrations
$migrationsDir = __DIR__ . '/../database/migrations';
if (is_dir($migrationsDir)) {
    $files = scandir($migrationsDir);
    foreach ($files as $file) {
        if (str_ends_with($file, ' 2.php')) {
            $filePath = $migrationsDir . '/' . $file;
            if (unlink($filePath)) {
                $results[] = "Deleted migration: " . $file;
            } else {
                $results[] = "Failed to delete: " . $file;
            }
        }
    }
}

// 2. Delete corrupted git refs
$gitRef = __DIR__ . '/../.git/refs/remotes/origin/HEAD 2';
if (file_exists($gitRef)) {
    if (unlink($gitRef)) {
        $results[] = "Deleted corrupted git ref: HEAD 2";
    } else {
        $results[] = "Failed to delete corrupted git ref: HEAD 2";
    }
}

// 3. Run migrations via Laravel Console Kernel
try {
    // Register Composer Autoloader
    require __DIR__.'/../vendor/autoload.php';

    // Bootstrap Laravel
    $app = require_once __DIR__.'/../bootstrap/app.php';
    $kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
    $kernel->bootstrap();
    
    // Run migrate
    $status = \Illuminate\Support\Facades\Artisan::call('migrate');
    $results[] = "Artisan migrate status: " . $status;
    $results[] = "Artisan migrate output: " . \Illuminate\Support\Facades\Artisan::output();
} catch (\Throwable $e) {
    $results[] = "Migration Error: " . $e->getMessage() . "\nFile: " . $e->getFile() . " Line: " . $e->getLine();
}

header('Content-Type: application/json');
echo json_encode($results, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
