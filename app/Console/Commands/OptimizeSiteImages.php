<?php

namespace App\Console\Commands;

use App\Models\PortfolioCategory;
use App\Models\TeamMember;
use App\Support\ImageOptimizer;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 *   php artisan images:optimize-all                 # everything (locally: public/css/img ships via git)
 *   php artisan images:optimize-all --dry-run       # report only
 *   php artisan images:optimize-all --skip-public   # on the server: never touch git-tracked files
 *
 * - public/css/img: the static photos pages really load, fitted to the box they are shown in
 * - storage/app/public team/ and portfolio/categories/ (+ any other file team_members /
 *   portfolio_categories point at)
 * - portfolio_categories.cover_image on a foreign host (Pixieset) -> downloaded into
 *   portfolio/categories/<slug>-cover.jpg and the column updated through the model
 *
 * Originals are copied to storage/app/image-backups-YYYY-MM-DD/ first (an existing
 * backup is never overwritten) and .webp siblings are regenerated. Files that already
 * fit are left alone, so a second run changes nothing. Problems are warnings: the
 * exit code is always 0 (safe as a deploy step).
 */
class OptimizeSiteImages extends Command
{
    protected $signature = 'images:optimize-all
        {--dry-run : Only report what would change}
        {--skip-public : Leave the git-tracked public/css/img files alone (use this on the server)}
        {--skip-storage : Leave the uploads in storage/app/public alone}
        {--skip-remote : Do not download remote (e.g. Pixieset) portfolio covers}
        {--backup-dir= : Where originals are copied before they change (default: storage/app/image-backups-YYYY-MM-DD)}';

    protected $description = 'Fit site photos (about, team, portfolio covers, ...) to their display size, regenerate WebP siblings and localise remote covers';

    /**
     * Static images under public/ that pages actually load => [max width, max height] (null = unlimited).
     * A directory entry covers every image directly inside it.
     * Left out on purpose: header.* (LCP hero, already 1920 px), logos, favicons, og/social-share-only
     * images (Коли.jpeg, Edited_IMG_*), placeholders and unreferenced files.
     */
    public const PUBLIC_TARGETS = [
        'css/img/about.webp' => [1080, null],      // home + /za-nas "about" photo, <= ~640 css px wide
        'css/img/krustene.jpg' => [1920, 1920],    // baptism hero background, legacy portfolio cover
        'css/img/реклама.jpg' => [1920, 1920],     // legacy portfolio cover, og image
        'css/img/prom.jpg' => [1920, 1920],        // proms page photo, legacy portfolio cover
        'css/img/Сватба.jpg' => [1920, 1920],      // weddings hero background + photo
        'css/img/Бал.jpeg' => [1920, 1920],        // prom price blog post cover (hero background)
        'css/img/events-cover.jpg' => [1920, 1920], // blog post covers
        'css/img/HG.jpg' => [600, 600],            // legacy team photos (team_members.image_path)
        'css/img/PG.png' => [600, 600],
        'css/img/ST.png' => [600, 600],
        'css/img/ads' => [1920, 1920],             // commercial portfolio grid + full-screen lightbox
    ];

    /** Upload directories on the "public" disk => square box they are fitted into. */
    public const STORAGE_DIRS = [
        'team' => TeamMember::IMAGE_MAX,
        'portfolio/categories' => PortfolioCategory::COVER_MAX,
    ];

    /** Absolute URLs on these hosts are our own files, not something to download. */
    private const OWN_HOSTS = ['taketwostudio1603.com', 'www.taketwostudio1603.com'];

    private const QUALITY = 82;

    private bool $dryRun = false;

    private string $backupRoot = '';

    private int $backups = 0;

    private int $warnings = 0;

    private ?bool $database = null;

    /** @var array<int, array{0: string, 1: array}> */
    private array $rows = [];

    /** @var array<string, true> */
    private array $seen = [];

    public function handle(): int
    {
        // The command object is reused within one process (tests, Artisan::call): start clean.
        $this->rows = $this->seen = [];
        $this->backups = $this->warnings = 0;
        $this->database = null;

        $this->dryRun = (bool) $this->option('dry-run');
        $this->backupRoot = rtrim((string) ($this->option('backup-dir') ?: storage_path('app/image-backups-'.date('Y-m-d'))), '/');

        if (! function_exists('imagecreatetruecolor')) {
            $this->warn('PHP GD is not available - nothing to do.');

            return self::SUCCESS;
        }

        if (! $this->option('skip-public')) {
            $this->optimizePublic();
        }
        if (! $this->option('skip-storage')) {
            $this->optimizeStorage();
        }
        if (! $this->option('skip-remote')) {
            $this->localiseRemoteCovers();
        }

        $this->report();

        return self::SUCCESS;
    }

    private function optimizePublic(): void
    {
        $base = public_path();

        foreach (self::PUBLIC_TARGETS as $relative => [$maxWidth, $maxHeight]) {
            $path = public_path($relative);
            $files = is_dir($path) ? $this->imagesIn($path) : [$path];

            foreach ($files as $file) {
                $this->process($file, $maxWidth, $maxHeight, $base, 'public');
            }
        }
    }

    private function optimizeStorage(): void
    {
        $disk = Storage::disk('public');
        $base = $disk->path('');

        foreach (self::STORAGE_DIRS as $dir => $max) {
            if (is_dir($disk->path($dir))) {
                foreach ($this->imagesIn($disk->path($dir)) as $file) {
                    $this->process($file, $max, $max, $base);
                }
            }
        }

        // Files the rows point at outside those directories (older uploads, other forms).
        if (! $this->database()) {
            return;
        }

        $referenced = [
            [TeamMember::query()->pluck('image_path'), TeamMember::IMAGE_MAX],
            [PortfolioCategory::query()->pluck('cover_image'), PortfolioCategory::COVER_MAX],
        ];

        foreach ($referenced as [$paths, $max]) {
            foreach ($paths->filter() as $path) {
                if (! preg_match('#^(https?:)?//#i', $path) && ! str_contains($path, '..')) {
                    $this->process($disk->path(ltrim($path, '/')), $max, $max, $base);
                }
            }
        }
    }

    private function localiseRemoteCovers(): void
    {
        if (! $this->database()) {
            return;
        }

        $disk = Storage::disk('public');

        foreach (PortfolioCategory::query()->orderBy('id')->get() as $category) {
            $url = trim((string) $category->cover_image);

            if (! $this->isForeignUrl($url)) {
                continue;
            }

            $target = 'portfolio/categories/'.(Str::slug((string) $category->slug) ?: 'category-'.$category->id).'-cover.jpg';
            $label = "storage/{$target} <- ".parse_url($url, PHP_URL_HOST);

            if ($this->dryRun) {
                $this->rows[] = [$label, ['status' => 'download', 'note' => 'would download it and point cover_image at it'] + $this->emptyResult()];

                continue;
            }

            try {
                [$bytes, $dims] = $this->download($url, $disk->path($target), $disk->path(''));
            } catch (\Throwable $e) {
                $this->warnings++;
                $this->warn("Cover of \"{$category->slug}\" not downloaded ({$url}): ".Str::limit($e->getMessage(), 200).' - left unchanged.');

                continue;
            }

            $this->seen[$disk->path($target)] = true;
            $result = ImageOptimizer::optimizeFile($disk->path($target), PortfolioCategory::COVER_MAX, PortfolioCategory::COVER_MAX, self::QUALITY, false, $this->backupUsing($disk->path('')));

            // Through the model: its saved hook clears the cached nav categories.
            $category->cover_image = $target;
            $category->save();

            $this->rows[] = [$label, [
                'status' => 'download',
                'note' => 'downloaded, cover_image updated',
                'before' => $bytes,
                'dims_before' => $dims,
                'after' => (int) filesize($disk->path($target)),
                'dims_after' => $result['dims_after'],
            ] + $result];
        }
    }

    /** Download an image into $fullPath (always a JPEG). Returns [remote bytes, remote dimensions]. */
    private function download(string $url, string $fullPath, string $base): array
    {
        $response = Http::timeout(20)->connectTimeout(5)
            ->withUserAgent('Mozilla/5.0 (compatible; TakeTwoStudio image localiser; +https://taketwostudio1603.com)')
            ->get($url);

        if (! $response->successful()) {
            throw new \RuntimeException('HTTP '.$response->status());
        }

        $body = $response->body();
        if ($body === '' || strlen($body) > 40 * 1024 * 1024) {
            throw new \RuntimeException('empty or oversized response ('.strlen($body).' bytes)');
        }

        $info = @getimagesizefromstring($body);
        if (! $info || ! in_array($info[2], [IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP], true)) {
            throw new \RuntimeException('the response is not a JPEG/PNG/WebP image');
        }

        File::ensureDirectoryExists(dirname($fullPath));

        $backup = $this->backupUsing($base);
        foreach ([$fullPath, preg_replace('/\.jpg$/', '.webp', $fullPath)] as $existing) {
            if (is_file($existing)) {
                $backup($existing);
                @unlink($existing); // the old sibling would look up to date
            }
        }

        if ($info[2] === IMAGETYPE_JPEG) {
            $written = file_put_contents($fullPath, $body);
        } else {
            $image = @imagecreatefromstring($body);
            if (! $image) {
                throw new \RuntimeException('cannot decode the downloaded image');
            }
            $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
            imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
            imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));
            $written = imagejpeg($canvas, $fullPath, 90);
        }

        if (! $written) {
            throw new \RuntimeException("cannot write {$fullPath}");
        }

        return [strlen($body), $info[0].'x'.$info[1]];
    }

    private function process(string $file, ?int $maxWidth, ?int $maxHeight, string $base, string $backupPrefix = ''): void
    {
        $key = realpath($file) ?: $file;
        if (isset($this->seen[$key])) {
            return;
        }
        $this->seen[$key] = true;

        $label = trim($backupPrefix === 'public' ? 'public/' : 'storage/', '/').'/'.$this->relative($file, $base);
        $quality = str_ends_with(strtolower($file), '.webp') ? ImageOptimizer::WEBP_QUALITY : self::QUALITY;

        try {
            $result = ImageOptimizer::optimizeFile($file, $maxWidth, $maxHeight, $quality, $this->dryRun, $this->backupUsing($base, $backupPrefix));
        } catch (\Throwable $e) {
            $result = ['status' => 'error', 'note' => Str::limit($e->getMessage(), 200)] + $this->emptyResult();
        }

        if ($result['status'] === 'missing') {
            return;
        }

        if ($result['status'] === 'error') {
            $this->warnings++;
            $this->warn("{$label}: {$result['note']}");
        }

        $this->rows[] = [$label, $result];
    }

    /** Backup callback: copies a file to <backup root>/<prefix>/<path relative to $base>, never overwriting a backup. */
    private function backupUsing(string $base, string $prefix = ''): \Closure
    {
        return function (string $path) use ($base, $prefix): void {
            if ($this->dryRun) {
                return;
            }

            $target = $this->backupRoot.'/'.($prefix !== '' ? $prefix.'/' : '').$this->relative($path, $base);

            if (is_file($target)) {
                return;
            }

            File::ensureDirectoryExists(dirname($target));

            if (! @copy($path, $target)) {
                throw new \RuntimeException("cannot back up {$path} to {$target} - file left unchanged");
            }

            $this->backups++;
        };
    }

    /** Images directly inside $dir: JPEG/PNG sources, plus .webp files that are not a sibling of one. */
    private function imagesIn(string $dir): array
    {
        $files = [];

        foreach (File::files($dir) as $file) {
            $path = $file->getPathname();
            $extension = strtolower($file->getExtension());

            if (in_array($extension, ['jpg', 'jpeg', 'png'], true)) {
                $files[] = $path;
            } elseif ($extension === 'webp') {
                $stem = substr($path, 0, -5);
                $hasSource = collect(['jpg', 'jpeg', 'png', 'JPG', 'JPEG', 'PNG'])->contains(fn ($ext) => is_file("{$stem}.{$ext}"));

                if (! $hasSource) {
                    $files[] = $path;
                }
            }
        }

        sort($files);

        return $files;
    }

    private function relative(string $path, string $base): string
    {
        $base = rtrim($base, '/').'/';

        return str_starts_with($path, $base) ? substr($path, strlen($base)) : ltrim($path, '/');
    }

    private function isForeignUrl(string $url): bool
    {
        if (! preg_match('#^https?://#i', $url)) {
            return false;
        }

        $host = strtolower((string) parse_url($url, PHP_URL_HOST));
        $own = array_merge(self::OWN_HOSTS, [strtolower((string) parse_url((string) config('app.url'), PHP_URL_HOST))]);

        return $host !== '' && ! in_array($host, $own, true);
    }

    private function database(): bool
    {
        if ($this->database !== null) {
            return $this->database;
        }

        try {
            DB::connection()->getPdo();

            return $this->database = Schema::hasTable('portfolio_categories') && Schema::hasTable('team_members');
        } catch (\Throwable $e) {
            $this->warnings++;
            $this->warn('Database unreachable - DB-referenced files and remote covers skipped ('.Str::limit($e->getMessage(), 150).').');

            return $this->database = false;
        }
    }

    private function emptyResult(): array
    {
        return ['status' => 'ok', 'note' => '', 'before' => 0, 'after' => 0, 'dims_before' => null, 'dims_after' => null, 'webp_before' => null, 'webp_after' => null];
    }

    private function report(): void
    {
        $this->newLine();
        if ($this->dryRun) {
            $this->warn('DRY RUN - nothing was written; "After" is what a real run would produce.');
        }

        $kb = fn (?int $bytes): string => $bytes === null ? 'none' : number_format($bytes / 1024).' KB';
        $labels = ['optimized' => 'resized/compressed', 'webp' => 'webp regenerated', 'ok' => 'already fits', 'download' => 'localised', 'unsupported' => 'skipped (format)', 'error' => 'ERROR'];

        $before = $after = $changed = 0;
        $table = [];

        foreach ($this->rows as [$label, $r]) {
            $changes = in_array($r['status'], ['optimized', 'webp', 'download'], true);
            $changed += $changes ? 1 : 0;
            $before += $r['before'] + ($r['webp_before'] ?? 0);
            $after += $r['after'] + ($r['webp_after'] ?? 0);

            $table[] = [
                $label,
                trim(($r['dims_before'] ?? '').' '.$kb($r['before'])),
                $r['status'] === 'optimized' || $r['status'] === 'download' ? trim(($r['dims_after'] ?? '').' '.$kb($r['after'])) : '=',
                $r['webp_before'] === $r['webp_after'] ? ($r['webp_before'] === null ? '-' : $kb($r['webp_before'])) : $kb($r['webp_before']).' -> '.$kb($r['webp_after']),
                trim(($labels[$r['status']] ?? $r['status']).($r['note'] !== '' ? ': '.$r['note'] : '')),
            ];
        }

        if ($table) {
            $this->table(['File', 'Before', 'After', 'WebP sibling', 'Result'], $table);
        }

        $mb = fn (int $bytes): string => number_format($bytes / 1048576, 2).' MB';
        $this->info(sprintf(
            '%d files checked, %d %s. Total (incl. WebP siblings): %s -> %s (%s saved).',
            count($this->rows), $changed, $this->dryRun ? 'would change' : 'changed', $mb($before), $mb($after), $mb(max(0, $before - $after))
        ));

        if ($this->backups > 0) {
            $this->info("{$this->backups} originals backed up to {$this->backupRoot}");
        }
        if ($this->warnings > 0) {
            $this->warn("{$this->warnings} warning(s) - see above. Nothing else was affected.");
        }
    }
}
