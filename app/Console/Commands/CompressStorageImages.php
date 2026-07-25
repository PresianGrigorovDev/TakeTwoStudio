<?php

namespace App\Console\Commands;

use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class CompressStorageImages extends Command
{
    protected $signature = 'images:compress-storage {--min-kb=500 : Skip files smaller than this}';

    protected $description = 'Resize/compress oversized images already in storage/app/public, backing up originals first';

    public function handle(): int
    {
        $minBytes = (int) $this->option('min-kb') * 1024;
        $root = Storage::disk('public')->path('');
        $backupRoot = storage_path('app/image-backups-'.date('Y-m-d'));

        $files = File::allFiles($root);
        $totalBefore = 0;
        $totalAfter = 0;
        $count = 0;

        foreach ($files as $file) {
            $ext = strtolower($file->getExtension());
            if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                continue;
            }

            $size = $file->getSize();
            if ($size < $minBytes) {
                continue;
            }

            $relativePath = ltrim(str_replace($root, '', $file->getPathname()), '/');

            $backupPath = $backupRoot.'/'.$relativePath;
            File::ensureDirectoryExists(dirname($backupPath));
            if (! is_file($backupPath)) {
                File::copy($file->getPathname(), $backupPath);
            }

            ImageOptimizer::optimize('public', $relativePath);

            clearstatcache(true, $file->getPathname());
            $after = filesize($file->getPathname());

            $totalBefore += $size;
            $totalAfter += $after;
            $count++;

            $this->line(sprintf(
                '%s: %s KB -> %s KB',
                $relativePath,
                number_format($size / 1024),
                number_format($after / 1024)
            ));
        }

        $this->newLine();
        $this->info("Processed {$count} files.");
        $this->info('Before: '.number_format($totalBefore / 1024 / 1024, 1).' MB');
        $this->info('After:  '.number_format($totalAfter / 1024 / 1024, 1).' MB');
        $this->info("Originals backed up to: {$backupRoot}");

        return self::SUCCESS;
    }
}
