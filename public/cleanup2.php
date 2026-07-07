<?php

header('Content-Type: application/json');

$results = [];
try {
    $columns = \Illuminate\Support\Facades\Schema::getColumnListing('commercial_portfolio_photos');
    $results['table_exists'] = count($columns) > 0;
    $results['columns'] = $columns;
} catch (\Throwable $e) {
    $results['table_exists'] = false;
    $results['error'] = $e->getMessage();
}

echo json_encode($results, JSON_PRETTY_PRINT);
