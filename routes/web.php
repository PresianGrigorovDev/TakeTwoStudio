<?php

use Illuminate\Support\Facades\Route;

Route::get('/', [App\Http\Controllers\PageController::class, 'home']);

Route::get('/weddings', [App\Http\Controllers\PageController::class, 'weddings']);

Route::get('/proms', [App\Http\Controllers\PageController::class, 'proms']);

Route::get('/baptism', [App\Http\Controllers\PageController::class, 'baptism']);

Route::get('/commercial', [App\Http\Controllers\PageController::class, 'commercial']);

Route::post('/submit-order', [App\Http\Controllers\OrderController::class, 'submitOrder']);
Route::post('/submit-contact', [App\Http\Controllers\OrderController::class, 'submitContact']);

// Registration Routes
// Route::get('/register', [App\Http\Controllers\Auth\RegisterController::class, 'show'])->name('register');
// Route::post('/register', [App\Http\Controllers\Auth\RegisterController::class, 'register']);

Route::get('/seed-proms', function () {
    $urls = [
        'https://images.pixieset.com/68309375/ed17c1d3bd0de0d96671e76927b2f09f-xlarge.jpeg',
        'https://images.pixieset.com/68309375/9e1ebea2b515992b19c7be5d39003925-xlarge.jpg',
        'https://images.pixieset.com/68309375/04be90ac436e1623b4dddce572f82621-xlarge.jpg',
        'https://images.pixieset.com/68309375/706ee1d591f72b160152e6acab93c8c1-xlarge.jpeg',
        'https://images.pixieset.com/68309375/0d39645088ec6cb3a7e4e377b84c301a-xlarge.jpeg',
        'https://images.pixieset.com/68309375/0a5d71d8e8cfd2f719515af85c2eb2db-xlarge.jpg',
        'https://images.pixieset.com/68309375/04e9671bb821a4c42d115b9ce46392c0-xlarge.jpg',
        'https://images.pixieset.com/68309375/6601590a5f9e5914e5b0fd11f279ddc3-xlarge.jpg',
        'https://images.pixieset.com/68309375/fe72df8ac54d1aa997e38eb4fdaabc9f-xlarge.jpg',
        'https://images.pixieset.com/68309375/a6683c6780f913852a0dd1170ad0ee44-xlarge.jpg',
        'https://images.pixieset.com/68309375/45bdbb033b1406dc310e61df7fe71ecd-xlarge.jpg',
        'https://images.pixieset.com/68309375/ad12d3d7cdf4fceaca842c31dd1807c4-xlarge.jpg',
        'https://images.pixieset.com/68309375/9649ec99b6bbf0b181ec90b9df6979a7-xlarge.jpg',
        'https://images.pixieset.com/68309375/cc17c9694f36567a56a13ffc2d2c9794-xlarge.jpeg'
    ];

    $count = count($urls);
    $output = "Downloading and inserting {$count} photos...<br>";

    foreach ($urls as $i => $url) {
        try {
            $contents = \Illuminate\Support\Facades\Http::get($url)->body();
            $filename = basename(parse_url($url, PHP_URL_PATH));
            $path = 'prom_portfolio_photos/' . $filename;
            
            \Illuminate\Support\Facades\Storage::disk('public')->put($path, $contents);
            
            \App\Models\PromPortfolioPhoto::updateOrCreate(
                ['image_path' => $path],
                [
                    'sort_order' => $i,
                    'is_visible' => true,
                ]
            );
            $output .= "Saved: $path<br>";
        } catch (\Exception $e) {
            $output .= "Error on $url: " . $e->getMessage() . "<br>";
        }
    }
    return $output . "Done!";
});
