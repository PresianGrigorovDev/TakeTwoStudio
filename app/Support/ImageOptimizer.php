<?php

namespace App\Support;

class ImageOptimizer
{
    /**
     * Resize (if needed) and re-compress an image already saved on the given
     * disk, in place. Skips anything already small enough, and any type it
     * doesn't know how to decode (e.g. SVG).
     */
    public static function optimize(string $disk, string $path, int $maxWidth = 2200, int $quality = 82): void
    {
        $fullPath = \Illuminate\Support\Facades\Storage::disk($disk)->path($path);

        if (! is_file($fullPath)) {
            return;
        }

        $info = @getimagesize($fullPath);
        if ($info === false) {
            return;
        }

        [$width, $height, $type] = $info;

        $image = match ($type) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($fullPath),
            IMAGETYPE_PNG => @imagecreatefrompng($fullPath),
            IMAGETYPE_WEBP => function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($fullPath) : null,
            default => null,
        };

        if (! $image) {
            return;
        }

        if ($width > $maxWidth) {
            $newHeight = (int) round($height * ($maxWidth / $width));
            $resized = imagecreatetruecolor($maxWidth, $newHeight);

            if ($type === IMAGETYPE_PNG) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $maxWidth, $newHeight, $width, $height);
            imagedestroy($image);
            $image = $resized;
        }

        match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $fullPath, $quality),
            IMAGETYPE_PNG => imagepng($image, $fullPath, (int) round((100 - $quality) / 10)),
            IMAGETYPE_WEBP => imagewebp($image, $fullPath, $quality),
            default => null,
        };

        imagedestroy($image);

        // Responsive delivery: keep a WebP sibling in sync for <x-picture>.
        Images::makeWebp($fullPath);
    }
}
