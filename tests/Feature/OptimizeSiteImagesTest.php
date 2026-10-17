<?php

namespace Tests\Feature;

use App\Models\BlogPost;
use App\Models\PortfolioCategory;
use App\Models\TeamMember;
use App\Support\ImageOptimizer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * images:optimize-all (PageSpeed phase 3, 17.10.2026): fits photos into their display
 * box, backs originals up, regenerates the .webp sibling <x-picture> serves, localises
 * Pixieset covers - and is safe to run on every deploy (idempotent, never fails).
 */
class OptimizeSiteImagesTest extends TestCase
{
    use RefreshDatabase;

    private string $publicDir;

    private string $backupDir;

    private const PIXIESET = 'https://images.pixieset.com/73162136/bd1e01a7c78224ead9d23b44867bc852-cover-large.jpg';

    protected function setUp(): void
    {
        parent::setUp();

        $root = sys_get_temp_dir().'/optimize-site-images-'.uniqid();
        $this->publicDir = $root.'/public';
        $this->backupDir = $root.'/backups';
        File::ensureDirectoryExists($this->publicDir.'/css/img/ads');
        $this->app->usePublicPath($this->publicDir);

        // A private "public" disk: Storage::fake() shares storage/framework/testing/disks/public
        // with any other test process running at the same time.
        Storage::set('public', Storage::build(['driver' => 'local', 'root' => $root.'/storage']));
    }

    protected function tearDown(): void
    {
        File::deleteDirectory(dirname($this->publicDir));

        parent::tearDown();
    }

    public function test_images_are_fitted_backed_up_and_webp_siblings_regenerated(): void
    {
        $disk = Storage::disk('public');

        $about = $this->image($this->publicDir.'/css/img/about.webp', 2400, 1600);
        $this->image($this->publicDir.'/css/img/ads/shot.jpg', 3000, 2000);
        $this->image($this->publicDir.'/css/img/ads/shot.webp', 3000, 2000); // stale, huge sibling
        $team = $this->image($disk->path('team/portrait.jpg'), 1200, 1600);
        $this->image($disk->path('team/portrait.webp'), 1200, 1600);
        $this->image($disk->path('portfolio/categories/cover.jpg'), 2400, 1600);
        $this->image($disk->path('team/png-named.jpg'), 900, 900, 'png'); // PNG bytes behind a .jpg name

        $this->runCommand()->assertExitCode(0)->run();

        $this->assertDims([1080, 720], $this->publicDir.'/css/img/about.webp');
        $this->assertDims([1920, 1280], $this->publicDir.'/css/img/ads/shot.jpg');
        $this->assertDims([1920, 1280], $this->publicDir.'/css/img/ads/shot.webp', 'the stale sibling is regenerated');
        $this->assertDims([450, 600], $disk->path('team/portrait.jpg'), 'portrait photos fit the 600x600 box too');
        $this->assertDims([450, 600], $disk->path('team/portrait.webp'));
        $this->assertDims([1200, 800], $disk->path('portfolio/categories/cover.jpg'));
        $this->assertDims([1200, 800], $disk->path('portfolio/categories/cover.webp'), 'a missing sibling is created');
        $this->assertSame(IMAGETYPE_JPEG, getimagesize($disk->path('team/png-named.jpg'))[2]);
        $this->assertDims([600, 600], $disk->path('team/png-named.jpg'));

        $this->assertSame($about, md5_file($this->backupDir.'/public/css/img/about.webp'), 'original kept byte for byte');
        $this->assertSame($team, md5_file($this->backupDir.'/team/portrait.jpg'));
        $this->assertFileExists($this->backupDir.'/team/portrait.webp');
        $this->assertFileExists($this->backupDir.'/public/css/img/ads/shot.webp');
    }

    public function test_a_second_run_changes_nothing_and_never_overwrites_a_backup(): void
    {
        $disk = Storage::disk('public');
        $this->image($this->publicDir.'/css/img/about.webp', 2400, 1600);
        $this->image($this->publicDir.'/css/img/ads/shot.jpg', 3000, 2000);
        $this->image($disk->path('team/a.jpg'), 2000, 2000);
        $this->image($disk->path('portfolio/categories/b.jpg'), 2400, 1600);

        $this->runCommand()->run();
        $backup = md5_file($this->backupDir.'/team/a.jpg');
        $snapshot = $this->snapshot();
        $this->assertNotEmpty($snapshot);

        $this->runCommand()->expectsOutputToContain('0 changed')->assertExitCode(0)->run();

        $this->assertSame($snapshot, $this->snapshot());
        $this->assertSame($backup, md5_file($this->backupDir.'/team/a.jpg'));
    }

    public function test_dry_run_writes_nothing(): void
    {
        Http::fake();
        $this->image($this->publicDir.'/css/img/about.webp', 2400, 1600);
        $this->image(Storage::disk('public')->path('team/a.jpg'), 2000, 2000);
        $category = $this->category('weddings', self::PIXIESET);
        $snapshot = $this->snapshot();

        $this->runCommand(['--dry-run' => true])->expectsOutputToContain('DRY RUN')->assertExitCode(0)->run();

        $this->assertSame($snapshot, $this->snapshot());
        $this->assertDirectoryDoesNotExist($this->backupDir);
        $this->assertSame(self::PIXIESET, $category->fresh()->cover_image);
        Http::assertNothingSent();
    }

    public function test_remote_cover_is_downloaded_and_the_nav_cache_cleared(): void
    {
        Http::fake(['images.pixieset.com/*' => Http::response($this->jpegBytes(2048, 1365), 200, ['Content-Type' => 'image/jpeg'])]);

        $weddings = $this->category('weddings', self::PIXIESET);
        $proms = $this->category('proms', 'https://taketwostudio1603.com/css/img/prom.jpg');
        $this->assertSame(self::PIXIESET, PortfolioCategory::visibleCached()->firstWhere('slug', 'weddings')->cover_image);

        $this->runCommand()->expectsOutputToContain('localised')->assertExitCode(0)->run();

        $path = 'portfolio/categories/weddings-cover.jpg';
        $this->assertSame($path, $weddings->fresh()->cover_image);
        $this->assertDims([1200, 800], Storage::disk('public')->path($path));
        $this->assertFileExists(Storage::disk('public')->path('portfolio/categories/weddings-cover.webp'));
        $this->assertSame($path, PortfolioCategory::visibleCached()->firstWhere('slug', 'weddings')->cover_image, 'cached nav categories were invalidated');

        $this->assertSame('https://taketwostudio1603.com/css/img/prom.jpg', $proms->fresh()->cover_image, 'own-host URLs are local files, not downloads');
        Http::assertSentCount(1);
    }

    public function test_a_failed_download_is_a_warning_not_an_error(): void
    {
        Http::fake([
            'images.pixieset.com/*' => Http::response('Not found', 404),
            'cdn.example.com/*' => Http::response('<html>not an image</html>', 200, ['Content-Type' => 'text/html']),
            'down.example.com/*' => Http::failedConnection('Connection timed out'),
        ]);

        $a = $this->category('weddings', self::PIXIESET);
        $b = $this->category('baptism', 'https://cdn.example.com/cover.jpg');
        $c = $this->category('proms', 'https://down.example.com/cover.jpg');
        $this->image($this->publicDir.'/css/img/about.webp', 2400, 1600);

        $this->runCommand()
            ->expectsOutputToContain('Cover of "weddings" not localised')
            ->expectsOutputToContain('3 warning(s)')
            ->assertExitCode(0)
            ->run();

        $this->assertSame(self::PIXIESET, $a->fresh()->cover_image);
        $this->assertSame('https://cdn.example.com/cover.jpg', $b->fresh()->cover_image);
        $this->assertSame('https://down.example.com/cover.jpg', $c->fresh()->cover_image);
        $this->assertDims([1080, 720], $this->publicDir.'/css/img/about.webp', 'the rest of the run still happens');
    }

    public function test_skip_public_leaves_git_tracked_files_alone(): void
    {
        $about = $this->image($this->publicDir.'/css/img/about.webp', 2400, 1600);
        $this->image(Storage::disk('public')->path('team/a.jpg'), 2000, 2000);

        $this->runCommand(['--skip-public' => true])->assertExitCode(0)->run();

        $this->assertSame($about, md5_file($this->publicDir.'/css/img/about.webp'));
        $this->assertDims([600, 600], Storage::disk('public')->path('team/a.jpg'));
    }

    public function test_model_hooks_fit_photos_saved_any_way(): void
    {
        $disk = Storage::disk('public');

        $this->image($disk->path('team/new.jpg'), 2000, 1500);
        TeamMember::create(['name' => 'Тест', 'role_bg' => 'Фотограф', 'image_path' => 'team/new.jpg']);
        $this->assertDims([600, 450], $disk->path('team/new.jpg'));
        $this->assertFileExists($disk->path('team/new.webp'));

        $category = $this->category('family', null);
        $this->image($disk->path('portfolio/categories/new.jpg'), 3000, 2000);
        $category->update(['cover_image' => 'portfolio/categories/new.jpg']);
        $this->assertDims([1200, 800], $disk->path('portfolio/categories/new.jpg'));

        // BlogPost used wasChanged() only, which is false on create: new covers were never optimised.
        $this->image($disk->path('blog/new.jpg'), 3000, 2000);
        BlogPost::create(['title' => 'Тест', 'slug' => 'test', 'excerpt' => 'x', 'body' => 'x', 'cover_image' => 'blog/new.jpg']);
        $this->assertDims([2200, 1467], $disk->path('blog/new.jpg'));

        // Remote and missing values are ignored, never fetched.
        Http::fake();
        TeamMember::create(['name' => 'Легаси', 'role_bg' => 'Видео', 'image_path' => 'https://taketwostudio1603.com/css/img/PG.png']);
        TeamMember::create(['name' => 'Липсва', 'role_bg' => 'Видео', 'image_path' => 'team/missing.jpg']);
        Http::assertNothingSent();
    }

    public function test_exif_orientation_is_applied_before_the_metadata_is_dropped(): void
    {
        $path = Storage::disk('public')->path('team/rotated.jpg');
        File::ensureDirectoryExists(dirname($path));
        file_put_contents($path, $this->withExifOrientation($this->jpegBytes(400, 200), 6));
        $this->assertSame(6, ImageOptimizer::jpegOrientation($path));

        $result = ImageOptimizer::optimizeFile($path, 1000, 1000);

        $this->assertSame('optimized', $result['status']);
        $this->assertDims([200, 400], $path, 'orientation 6 = rotate 90 degrees clockwise');
        $this->assertSame(1, ImageOptimizer::jpegOrientation($path));
    }

    // ---------------------------------------------------------------------

    private function runCommand(array $options = [])
    {
        return $this->artisan('images:optimize-all', ['--backup-dir' => $this->backupDir] + $options);
    }

    private function category(string $slug, ?string $cover): PortfolioCategory
    {
        return PortfolioCategory::create(['slug' => $slug, 'name_bg' => ucfirst($slug), 'subtitle_bg' => 'Тест', 'cover_image' => $cover]);
    }

    /** Write a busy test image (so compression has something to do); returns its md5. */
    private function image(string $path, int $width, int $height, ?string $format = null): string
    {
        File::ensureDirectoryExists(dirname($path));
        $image = $this->drawing($width, $height);

        match ($format ?? strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => imagepng($image, $path),
            'webp' => imagewebp($image, $path, 95),
            default => imagejpeg($image, $path, 95),
        };

        return md5_file($path);
    }

    private function jpegBytes(int $width, int $height): string
    {
        ob_start();
        imagejpeg($this->drawing($width, $height), null, 95);

        return (string) ob_get_clean();
    }

    private function drawing(int $width, int $height): \GdImage
    {
        $image = imagecreatetruecolor($width, $height);
        mt_srand($width * 7 + $height);

        for ($i = 0; $i < 150; $i++) {
            $x = mt_rand(0, $width);
            $y = mt_rand(0, $height);
            imagefilledrectangle($image, $x, $y, $x + mt_rand(20, 400), $y + mt_rand(20, 400), imagecolorallocate($image, mt_rand(0, 255), mt_rand(0, 255), mt_rand(0, 255)));
        }

        return $image;
    }

    /** Insert an APP1/EXIF segment with the given orientation right after the JPEG SOI marker. */
    private function withExifOrientation(string $jpeg, int $orientation): string
    {
        $tiff = 'MM'.pack('n', 42).pack('N', 8)        // big-endian TIFF header, IFD0 at offset 8
            .pack('n', 1)                                // one entry
            .pack('nnN', 0x0112, 3, 1).pack('n', $orientation)."\0\0" // Orientation, SHORT, count 1
            .pack('N', 0);                               // no next IFD
        $payload = "Exif\0\0".$tiff;

        return "\xFF\xD8\xFF\xE1".pack('n', strlen($payload) + 2).$payload.substr($jpeg, 2);
    }

    private function assertDims(array $expected, string $path, string $message = ''): void
    {
        $this->assertFileExists($path);
        $info = getimagesize($path);
        $this->assertSame($expected, [$info[0], $info[1]], $message ?: basename($path));
    }

    /** md5 + mtime of every file the command may touch. */
    private function snapshot(): array
    {
        $files = array_merge(File::allFiles($this->publicDir), File::allFiles(Storage::disk('public')->path('')));
        $snapshot = [];

        foreach ($files as $file) {
            $snapshot[$file->getPathname()] = md5_file($file->getPathname()).'@'.$file->getMTime();
        }
        ksort($snapshot);

        return $snapshot;
    }
}
