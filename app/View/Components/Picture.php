<?php

namespace App\View\Components;

use App\Support\Images;
use Illuminate\View\Component;
use Illuminate\View\View;

/**
 * <x-picture :src="asset('storage/' . $photo)" alt="..." class="..." sizes="..." />
 * Emits <picture> with a WebP <source> when photo.webp exists next to the
 * original and defaults to loading="lazy" decoding="async". Pass eager="true"
 * (+ fetchpriority) for the above-the-fold image.
 *
 * Deliberately emits NO width/height attributes: the site's CSS sizes images
 * by class (width: 100%, fixed thumbnail boxes with object-fit, ...) and an
 * intrinsic height attribute next to a CSS width distorts them.
 */
class Picture extends Component
{
    public ?string $webp;

    public function __construct(
        public string $src,
        public string $alt = '',
        public bool $eager = false,
        public ?string $sizes = null,
        public ?string $fetchpriority = null,
    ) {
        $this->webp = Images::webpUrl($src);
    }

    public function render(): View
    {
        return view('components.picture');
    }
}
