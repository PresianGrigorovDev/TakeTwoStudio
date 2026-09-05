@php
    // width/height are stripped on purpose: CSS owns the sizing (see App\View\Components\Picture).
    $imgAttributes = $attributes->except(['src', 'alt', 'width', 'height', 'eager', 'sizes', 'fetchpriority', 'loading', 'decoding']);
@endphp
@if($webp)
<picture>
    <source type="image/webp" srcset="{{ $webp }}"@if($sizes) sizes="{{ $sizes }}"@endif>
    <img src="{{ $src }}" alt="{{ $alt }}" @if($eager) fetchpriority="{{ $fetchpriority ?? 'high' }}" decoding="async"@else loading="lazy" decoding="async"@endif {{ $imgAttributes }}>
</picture>
@else
<img src="{{ $src }}" alt="{{ $alt }}" @if($eager) fetchpriority="{{ $fetchpriority ?? 'high' }}" decoding="async"@else loading="lazy" decoding="async"@endif {{ $imgAttributes }}>
@endif
