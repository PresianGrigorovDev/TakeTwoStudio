<?php

namespace App\Console\Commands;

use App\Support\ImageOptimizer;
use App\Support\Images;
use Illuminate\Console\Command;
use Symfony\Component\Finder\Finder;

/**
 *   php artisan images:webp                    # storage/app/public (uploads) + public/css/img
 *   php artisan images:webp --only=storage     # uploads only
 *   php artisan images:webp --force            # also rebuild siblings that are LARGER than their source
 *
 * Creates missing .webp siblings only. An existing sibling is never replaced by
 * default - not even when it looks older than its source: a fresh git checkout
 * scrambles mtimes, and some siblings are hand-tuned (header.webp is a 1920 px
 * encode next to the 3600 px social-share header.jpg; krustene.webp is a tuned
 * q65 encode). With --force only stale siblings - larger than the source in
 * either dimension, i.e. made before the source was shrunk - are rebuilt; a
 * sibling the source's size or smaller is always kept. Re-encoding after a source
 * changes is ImageOptimizer's job (model hooks, images:optimize-all).
 * Filesystem only - nothing is written to the database. Safe to re-run.
 */
class GenerateWebpImages extends Command
{
    protected $signature = 'images:webp {--only= : storage | public} {--quality=80} {--force : Rebuild existing siblings that are larger than their source}';

    protected $description = 'Create missing .webp siblings for JPEG/PNG images so <x-picture> can serve them';

    public function handle(): int
    {
        $roots = match ($this->option('only')) {
            'storage' => [storage_path('app/public')],
            'public' => [public_path('css/img')],
            default => [storage_path('app/public'), public_path('css/img')],
        };

        $made = $rebuilt = $kept = $failed = 0;
        $stale = [];

        foreach ($roots as $root) {
            if (! is_dir($root)) {
                continue;
            }

            foreach (Finder::create()->files()->in($root)->name('/\.(jpe?g|png)$/i') as $file) {
                $source = $file->getRealPath();
                $webp = preg_replace('/\.(jpe?g|png)$/i', '.webp', $source);

                $replacing = is_file($webp);

                if ($replacing) {
                    if (! $this->isLargerThanSource($webp, $source)) {
                        $kept++;

                        continue;
                    }

                    if (! $this->option('force')) {
                        $stale[] = $webp;
                        $kept++;

                        continue;
                    }
                }

                if (! $this->canEncode($source)) {
                    $failed++;

                    continue;
                }

                if ($replacing) {
                    @unlink($webp); // makeWebp() would otherwise trust the sibling's mtime
                }

                Images::makeWebp($source, (int) $this->option('quality'))
                    ? ($replacing ? $rebuilt++ : $made++)
                    : $failed++;
            }
        }

        $this->info("WebP created: {$made}, rebuilt (--force): {$rebuilt}, kept: {$kept}, failed/unsupported: {$failed}");

        if ($stale) {
            $this->warn(count($stale).' sibling(s) are larger than their source (made before it was shrunk) - rebuild with --force:');
            foreach ($stale as $path) {
                $this->line('  '.$path);
            }
        }

        return self::SUCCESS;
    }

    /** Refuse sources GD would turn into a wrong (sideways) or fatal (out of memory) sibling. */
    private function canEncode(string $source): bool
    {
        if (preg_match('/\.jpe?g$/i', $source) && ImageOptimizer::jpegOrientation($source) > 1) {
            $this->warn("{$source}: EXIF-rotated JPEG (GD ignores the rotation) - run php artisan images:optimize-all instead.");

            return false;
        }

        // GD holds the decoded bitmap plus the encoder's copy (~10 bytes/px): never die on memory_limit.
        $info = @getimagesize($source);
        if ($info && ! ImageOptimizer::ensureMemory($info[0] * $info[1] * 10 + 16 * 1024 * 1024)) {
            $this->warn("{$source}: {$info[0]}x{$info[1]} needs more memory than memory_limit allows - skipped (php -d memory_limit=1G artisan images:webp).");

            return false;
        }

        return true;
    }

    /** True when the sibling exceeds the source in either dimension (or cannot be read). */
    private function isLargerThanSource(string $webp, string $source): bool
    {
        $sibling = @getimagesize($webp);
        $original = @getimagesize($source);

        if (! $original) {
            return false; // nothing better to rebuild it from
        }

        return ! $sibling || $sibling[0] > $original[0] || $sibling[1] > $original[1];
    }
}
