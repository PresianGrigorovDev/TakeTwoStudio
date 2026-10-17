<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * images:webp must be safe after a fresh git checkout (scrambled mtimes): it only
 * creates missing siblings and never replaces a fine one - e.g. the hand-tuned
 * 1920 px header.webp next to the 3600 px social-share header.jpg.
 */
class GenerateWebpImagesTest extends TestCase
{
    private string $publicDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->publicDir = sys_get_temp_dir().'/images-webp-'.uniqid();
        File::ensureDirectoryExists($this->publicDir.'/css/img');
        $this->app->usePublicPath($this->publicDir);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->publicDir);

        parent::tearDown();
    }

    public function test_only_missing_siblings_are_created_and_fine_ones_survive_a_fresh_checkout(): void
    {
        $this->image('new.jpg', 800, 600);
        $this->image('same.jpg', 800, 600);
        $this->image('same.webp', 800, 600);
        $this->image('header.jpg', 1800, 1200);   // social-share source
        $this->image('header.webp', 960, 640);    // hand-tuned, smaller hero encode
        $this->image('stale.jpg', 400, 300);
        $this->image('stale.webp', 1600, 1200);   // made before the source was shrunk

        // A fresh checkout: every source looks newer than its sibling.
        foreach (['same', 'header', 'stale'] as $name) {
            touch($this->path("$name.webp"), time() - 3600);
            touch($this->path("$name.jpg"), time());
        }
        $kept = $this->hashes(['same.webp', 'header.webp', 'stale.webp']);

        $this->artisan('images:webp', ['--only' => 'public'])
            ->expectsOutputToContain('WebP created: 1')
            ->expectsOutputToContain('rebuild with --force')
            ->assertExitCode(0)
            ->run();

        $this->assertDims([800, 600], $this->path('new.webp'));
        $this->assertSame($kept, $this->hashes(['same.webp', 'header.webp', 'stale.webp']), 'existing siblings are never replaced by default');

        // --force only rebuilds the stale (larger than its source) sibling.
        $this->artisan('images:webp', ['--only' => 'public', '--force' => true])
            ->expectsOutputToContain('rebuilt (--force): 1')
            ->assertExitCode(0)
            ->run();

        $this->assertDims([400, 300], $this->path('stale.webp'));
        $this->assertSame(
            array_intersect_key($kept, array_flip(['same.webp', 'header.webp'])),
            $this->hashes(['same.webp', 'header.webp']),
            'same-size and smaller hand-tuned siblings survive --force'
        );

        // Nothing left to do.
        $before = $this->hashes(['new.webp', 'same.webp', 'header.webp', 'stale.webp']);
        $this->artisan('images:webp', ['--only' => 'public', '--force' => true])
            ->expectsOutputToContain('WebP created: 0, rebuilt (--force): 0')
            ->run();
        $this->assertSame($before, $this->hashes(['new.webp', 'same.webp', 'header.webp', 'stale.webp']));
    }

    private function path(string $name): string
    {
        return $this->publicDir.'/css/img/'.$name;
    }

    private function image(string $name, int $width, int $height): void
    {
        $image = imagecreatetruecolor($width, $height);
        imagefilledrectangle($image, 0, 0, (int) ($width / 2), $height, imagecolorallocate($image, 200, 120, 40));

        str_ends_with($name, '.webp') ? imagewebp($image, $this->path($name), 90) : imagejpeg($image, $this->path($name), 90);
    }

    private function hashes(array $names): array
    {
        return collect($names)->mapWithKeys(fn ($name) => [$name => md5_file($this->path($name))])->all();
    }

    private function assertDims(array $expected, string $path): void
    {
        $this->assertFileExists($path);
        $info = getimagesize($path);
        $this->assertSame($expected, [$info[0], $info[1]], basename($path));
    }
}
