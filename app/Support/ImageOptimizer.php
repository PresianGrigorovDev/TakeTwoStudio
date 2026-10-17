<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;

/**
 * Server-side image optimisation with plain GD (the shared host has no CLI image
 * tools): fit inside a max box (never upscale, never crop), apply the JPEG EXIF
 * orientation, re-encode in the same format and keep the photo.webp sibling that
 * <x-picture> serves in sync.
 *
 * A file that already fits the box is only rewritten when that saves at least
 * MIN_SAVING of its bytes, so re-running it (model hooks, images:optimize-all)
 * is a no-op instead of another generation of JPEG loss.
 */
class ImageOptimizer
{
    /** Re-encode a file that already fits only when it gets at least this much smaller. */
    public const MIN_SAVING = 0.10;

    /** Default quality of the generated .webp siblings (same as Images::makeWebp). */
    public const WEBP_QUALITY = 80;

    /**
     * Optimise an image already saved on the given disk, in place. Without
     * $maxHeight only the width is limited (the original behaviour); with it the
     * image is fitted inside the $maxWidth x $maxHeight box.
     */
    public static function optimize(string $disk, string $path, int $maxWidth = 2200, int $quality = 82, ?int $maxHeight = null, int $webpQuality = self::WEBP_QUALITY): void
    {
        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return;
        }

        self::optimizeFile(Storage::disk($disk)->path($path), $maxWidth, $maxHeight, $quality, false, null, $webpQuality);
    }

    /**
     * Optimise one file on disk.
     *
     * $beforeWrite($path) is called right before an existing file (the image or its
     * .webp sibling) is overwritten - the images:optimize-all command uses it for
     * backups. With $dryRun nothing is written; the sizes in the result are what the
     * run would produce. Result status: optimized | webp (only the sibling changed) |
     * ok (nothing to do) | missing | unsupported | error.
     *
     * @return array{status: string, note: string, before: int, after: int, dims_before: ?string, dims_after: ?string, webp_before: ?int, webp_after: ?int}
     */
    public static function optimizeFile(
        string $fullPath,
        ?int $maxWidth,
        ?int $maxHeight = null,
        int $quality = 82,
        bool $dryRun = false,
        ?callable $beforeWrite = null,
        int $webpQuality = self::WEBP_QUALITY,
    ): array {
        $result = [
            'status' => 'ok', 'note' => '', 'before' => 0, 'after' => 0,
            'dims_before' => null, 'dims_after' => null, 'webp_before' => null, 'webp_after' => null,
        ];

        if (! is_file($fullPath)) {
            return ['status' => 'missing'] + $result;
        }

        clearstatcache(true, $fullPath);
        $result['before'] = $result['after'] = (int) filesize($fullPath);

        $info = @getimagesize($fullPath);
        $type = $info[2] ?? null;
        $decoders = [IMAGETYPE_JPEG => 'imagecreatefromjpeg', IMAGETYPE_PNG => 'imagecreatefrompng', IMAGETYPE_WEBP => 'imagecreatefromwebp'];

        if (! $info || ! isset($decoders[$type]) || ! function_exists($decoders[$type])) {
            return ['status' => 'unsupported'] + $result;
        }

        [$width, $height] = $info;
        $result['dims_before'] = $result['dims_after'] = "{$width}x{$height}";

        // Write the format the extension promises (a PNG saved as photo.jpg becomes a real JPEG).
        $outType = match (strtolower(pathinfo($fullPath, PATHINFO_EXTENSION))) {
            'jpg', 'jpeg' => IMAGETYPE_JPEG,
            'png' => IMAGETYPE_PNG,
            'webp' => IMAGETYPE_WEBP,
            default => $type,
        };
        $converts = $outType !== $type;

        $orientation = $type === IMAGETYPE_JPEG ? self::jpegOrientation($fullPath) : 1;
        $swap = $orientation >= 5;
        [$targetW, $targetH] = self::fitWithin($swap ? $height : $width, $swap ? $width : $height, $maxWidth, $maxHeight);
        $needsResize = $targetW < ($swap ? $height : $width) || $targetH < ($swap ? $width : $height);

        $webp = ($outType !== IMAGETYPE_WEBP && function_exists('imagewebp')) ? preg_replace('/\.(jpe?g|png)$/i', '.webp', $fullPath) : null;
        if ($webp === $fullPath) {
            $webp = null; // unexpected extension (e.g. .jpe): no sibling convention to follow
        }
        $result['webp_before'] = $result['webp_after'] = ($webp && is_file($webp)) ? (int) filesize($webp) : null;

        // Decoding needs ~5 bytes per pixel (plus the resized copy); never die on memory_limit.
        $needed = (int) (($width * $height + $targetW * $targetH * 2) * 5) + 16 * 1024 * 1024;
        if (! self::ensureMemory($needed)) {
            $result['note'] = sprintf('needs ~%d MB of memory (memory_limit=%s) - re-run with: php -d memory_limit=1G artisan images:optimize-all', $needed / 1048576, ini_get('memory_limit'));

            return ['status' => 'error'] + $result;
        }

        $image = @$decoders[$type]($fullPath);
        if (! $image) {
            $result['note'] = 'cannot decode';

            return ['status' => 'error'] + $result;
        }

        $keepsAlpha = $outType !== IMAGETYPE_JPEG;

        if (! $keepsAlpha && $type !== IMAGETYPE_JPEG && ! $needsResize) {
            $image = self::flatten($image); // transparent pixels -> white instead of black
        }

        if ($needsResize) {
            [$canvasW, $canvasH] = $swap ? [$targetH, $targetW] : [$targetW, $targetH];
            $resized = imagecreatetruecolor($canvasW, $canvasH);

            if ($keepsAlpha) {
                imagealphablending($resized, false);
                imagesavealpha($resized, true);
                imagefill($resized, 0, 0, imagecolorallocatealpha($resized, 0, 0, 0, 127));
            } elseif ($type !== IMAGETYPE_JPEG) {
                imagefill($resized, 0, 0, imagecolorallocate($resized, 255, 255, 255));
            }

            imagecopyresampled($resized, $image, 0, 0, 0, 0, $canvasW, $canvasH, $width, $height);
            $image = $resized;
        }

        if ($orientation > 1) {
            $image = self::applyOrientation($image, $orientation);
        }

        if ($keepsAlpha) {
            imagepalettetotruecolor($image);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }

        $tmp = self::encode($image, $outType, $quality);
        if ($tmp === null) {
            $result['note'] = 'cannot encode';

            return ['status' => 'error'] + $result;
        }

        $newSize = (int) filesize($tmp);
        $rewrite = $needsResize || $orientation > 1 || $converts || $newSize <= $result['before'] * (1 - self::MIN_SAVING);

        if ($rewrite) {
            $result['status'] = 'optimized';
            $result['after'] = $newSize;
            $result['dims_after'] = imagesx($image).'x'.imagesy($image);

            if (! $dryRun) {
                try {
                    $beforeWrite && $beforeWrite($fullPath); // a failing backup aborts the overwrite
                    $copied = copy($tmp, $fullPath); // copy (not rename) keeps the original's owner/permissions
                } finally {
                    @unlink($tmp);
                }
                clearstatcache(true, $fullPath);

                if (! $copied) {
                    $result['note'] = 'cannot write the file';

                    return ['status' => 'error', 'after' => $result['before'], 'dims_after' => $result['dims_before']] + $result;
                }
            }
        }
        @unlink($tmp);

        if ($webp) {
            // Stale = missing, source rewritten, or not the source's size (e.g. made from the
            // huge original). Deliberately not mtime-based: a fresh git checkout scrambles mtimes.
            $stale = $result['webp_before'] === null || $rewrite;

            if (! $stale) {
                $siblingInfo = @getimagesize($webp);
                $stale = ! $siblingInfo || $result['dims_after'] !== $siblingInfo[0].'x'.$siblingInfo[1];
            }

            if ($stale) {
                $webpTmp = self::encode($image, IMAGETYPE_WEBP, $webpQuality);

                if ($webpTmp !== null) {
                    $result['webp_after'] = (int) filesize($webpTmp);

                    if (! $dryRun) {
                        try {
                            if (is_file($webp)) {
                                $beforeWrite && $beforeWrite($webp);
                            }
                            copy($webpTmp, $webp);
                        } finally {
                            @unlink($webpTmp);
                        }
                        clearstatcache(true, $webp);
                        // The sibling must never look older than its source (Images::makeWebp / images:webp).
                        @touch($webp, max(time(), (int) filemtime($fullPath)));
                    }
                    @unlink($webpTmp);

                    if ($result['status'] === 'ok') {
                        $result['status'] = 'webp';
                    }
                }
            }
        }

        return $result;
    }

    /** [width, height] scaled down (never up) to fit the box; a null side is unlimited. */
    public static function fitWithin(int $width, int $height, ?int $maxWidth, ?int $maxHeight): array
    {
        $scale = 1.0;

        if ($maxWidth && $width > $maxWidth) {
            $scale = min($scale, $maxWidth / $width);
        }
        if ($maxHeight && $height > $maxHeight) {
            $scale = min($scale, $maxHeight / $height);
        }

        if ($scale >= 1.0) {
            return [$width, $height];
        }

        return [max(1, (int) round($width * $scale)), max(1, (int) round($height * $scale))];
    }

    /**
     * EXIF orientation (1-8) of a JPEG, read straight from the APP1 segment so it
     * works without the exif extension. GD drops EXIF on re-encode, so the pixels
     * have to be turned upright before saving. 1 when absent/unreadable.
     */
    public static function jpegOrientation(string $path): int
    {
        $fh = @fopen($path, 'rb');
        if (! $fh) {
            return 1;
        }
        $data = (string) fread($fh, 262144);
        fclose($fh);

        $len = strlen($data);
        if ($len < 4 || substr($data, 0, 2) !== "\xFF\xD8") {
            return 1;
        }

        $pos = 2;
        while ($pos + 4 <= $len) {
            if ($data[$pos] !== "\xFF") {
                return 1;
            }
            $marker = ord($data[$pos + 1]);

            if ($marker === 0xFF) { // fill byte
                $pos++;

                continue;
            }
            if ($marker === 0xDA || $marker === 0xD9) { // start of scan / end of image: no more headers
                return 1;
            }
            if ($marker === 0x01 || ($marker >= 0xD0 && $marker <= 0xD7)) { // markers without a length
                $pos += 2;

                continue;
            }

            $segmentLength = unpack('n', substr($data, $pos + 2, 2))[1];

            if ($marker === 0xE1 && substr($data, $pos + 4, 6) === "Exif\0\0") {
                return self::tiffOrientation($data, $pos + 10, min($len, $pos + 2 + $segmentLength));
            }

            $pos += 2 + $segmentLength;
        }

        return 1;
    }

    private static function tiffOrientation(string $data, int $tiff, int $end): int
    {
        $order = substr($data, $tiff, 2);
        if ($order !== 'II' && $order !== 'MM') {
            return 1;
        }
        $le = $order === 'II';

        $u16 = fn (int $at): ?int => $at + 2 <= $end ? unpack($le ? 'v' : 'n', substr($data, $at, 2))[1] : null;
        $u32 = fn (int $at): ?int => $at + 4 <= $end ? unpack($le ? 'V' : 'N', substr($data, $at, 4))[1] : null;

        $ifdOffset = $u32($tiff + 4);
        if ($ifdOffset === null) {
            return 1;
        }

        $ifd = $tiff + $ifdOffset;
        $count = $u16($ifd);

        for ($i = 0; $count !== null && $i < $count; $i++) {
            $entry = $ifd + 2 + $i * 12;

            if ($u16($entry) === 0x0112) {
                $value = $u16($entry + 8);

                return ($value >= 1 && $value <= 8) ? $value : 1;
            }
            if ($entry + 12 > $end) {
                break;
            }
        }

        return 1;
    }

    private static function flatten(\GdImage $image): \GdImage
    {
        $canvas = imagecreatetruecolor(imagesx($image), imagesy($image));
        imagefill($canvas, 0, 0, imagecolorallocate($canvas, 255, 255, 255));
        imagecopy($canvas, $image, 0, 0, 0, 0, imagesx($image), imagesy($image));

        return $canvas;
    }

    /** Turn the stored pixels so they display upright (EXIF orientation 2-8). */
    private static function applyOrientation(\GdImage $image, int $orientation): \GdImage
    {
        if (in_array($orientation, [2, 5, 7], true)) {
            imageflip($image, IMG_FLIP_HORIZONTAL);
        }
        if ($orientation === 4) {
            imageflip($image, IMG_FLIP_VERTICAL);
        }

        $angle = match ($orientation) {
            3 => 180,
            5, 8 => 90,   // GD rotates counter-clockwise
            6, 7 => -90,
            default => 0,
        };

        if ($angle !== 0) {
            $rotated = imagerotate($image, $angle, 0);
            if ($rotated) {
                $image = $rotated;
            }
        }

        return $image;
    }

    /** Encode to a temp file; returns its path or null. */
    private static function encode(\GdImage $image, int $type, int $quality): ?string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'imgopt');
        if ($tmp === false) {
            return null;
        }

        if ($type === IMAGETYPE_JPEG) {
            imageinterlace($image, true); // progressive: renders early, usually a little smaller
        }

        $ok = match ($type) {
            IMAGETYPE_JPEG => imagejpeg($image, $tmp, $quality),
            IMAGETYPE_PNG => imagepng($image, $tmp, 9), // lossless - always the strongest compression
            IMAGETYPE_WEBP => imagewebp($image, $tmp, $quality),
            default => false,
        };

        if (! $ok || ! filesize($tmp)) {
            @unlink($tmp);

            return null;
        }

        return $tmp;
    }

    /** Make sure $bytes more can be allocated, raising memory_limit when the host allows it. */
    public static function ensureMemory(int $bytes): bool
    {
        $limit = self::iniBytes((string) ini_get('memory_limit'));
        if ($limit <= 0) {
            return true; // unlimited
        }

        $required = memory_get_usage(true) + $bytes;
        if ($required <= $limit) {
            return true;
        }

        @ini_set('memory_limit', (int) ceil(($required + 32 * 1048576) / 1048576).'M');

        $limit = self::iniBytes((string) ini_get('memory_limit'));

        return $limit <= 0 || $required <= $limit;
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return -1;
        }

        $number = (int) $value;

        return match (strtolower(substr($value, -1))) {
            'g' => $number * 1024 ** 3,
            'm' => $number * 1024 ** 2,
            'k' => $number * 1024,
            default => $number,
        };
    }
}
