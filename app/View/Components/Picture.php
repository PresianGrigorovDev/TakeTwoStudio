<?php

namespace App\View\Components;

use App\Support\Images;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-picture :src="asset('storage/' . $photo)" alt="..." class="..." sizes="..." />
 * Emits <picture> with a WebP <source> when photo.webp exists next to the
 * original, adds width/height from the file (cached) unless given, and
 * defaults to loading="lazy" decoding="async". Pass eager="true" (+ fetchpriority)
 * for the above-the-fold image.
 */
class Picture extends Component
{
    public ?string $webp;

    public ?int $width;

    public ?int $height;

    public function __construct(
        public string $src,
        public string $alt = '',
        ?int $width = null,
        ?int $height = null,
        public bool $eager = false,
        public ?string $sizes = null,
        public ?string $fetchpriority = null,
    ) {
        $this->webp = Images::webpUrl($src);

        if ($width && $height) {
            [$this->width, $this->height] = [$width, $height];
        } else {
            $dims = Images::dimensions($src);
            [$this->width, $this->height] = $dims ?? [$width, $height];
        }
    }

    public function render(): View
    {
        return view('components.picture');
    }
}
