<?php

namespace App\Http\Controllers;

class SeedController extends Controller
{
    public function run()
    {
        (new \Database\Seeders\CommercialPortfolioPhotoSeeder())->run();
        (new \Database\Seeders\GraduationContentSeeder())->run();
        (new \Database\Seeders\LegalPageSeeder())->run();
        (new \Database\Seeders\BlogCategorySeeder())->run();
        (new \Database\Seeders\BlogPostSeeder())->run();

        return 'Seed complete' . ', blog posts: ' . \App\Models\BlogPost::count();
    }
}
