<?php

namespace App\Console\Commands;

use App\Support\Images;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 *   php artisan images:webp                    # storage/app/public (uploads) + public/css/img
 *   php artisan images:webp --only=storage     # uploads only
 * Filesystem only - nothing is written to the database. Safe to re-run
 * (existing, up-to-date .webp files are skipped).
 */
class GenerateWebpImages extends Command
{
    protected $signature = 'images:webp {--only= : storage | public} {--quality=80}';

    protected $description = 'Create .webp siblings for every JPEG/PNG so <x-picture> can serve them';

    public function handle(): int
    {
        $roots = match ($this->option('only')) {
            'storage' => [storage_path('app/public')],
            'public' => [public_path('css/img')],
            default => [storage_path('app/public'), public_path('css/img')],
        };

        $made = $skipped = 0;

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            foreach (Finder::create()->files()->in($root)->name('/\.(jpe?g|png)$/i') as $file) {
                $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $file->getRealPath());

                if (is_file($webp) && filemtime($webp) >= $file->getMTime()) {
                    $skipped++;

                    continue;
                }

                Images::makeWebp($file->getRealPath(), (int) $this->option('quality')) ? $made++ : $skipped++;
            }
        }

        $this->info("WebP created: {$made}, skipped (up to date / unsupported): {$skipped}");

        return self::SUCCESS;
    }
}
