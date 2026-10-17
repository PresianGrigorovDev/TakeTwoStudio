@php
    // Prints a text colour for a category badge whose background is the category colour set in Filament:
    // #111 (blog.css default) when it reaches WCAG AA 4.5:1 on that colour, otherwise #fff, otherwise #000.
    // Usage: style="background-color: {{ $c }}; color: @include('blog.partials.badge-ink', ['hex' => $c])"
    $rgb = ltrim((string) ($hex ?? ''), '#');
    if (strlen($rgb) === 3) {
        $rgb = $rgb[0].$rgb[0].$rgb[1].$rgb[1].$rgb[2].$rgb[2];
    }
    $ink = '#111';
    if (preg_match('/^[0-9a-f]{6}$/i', $rgb)) {
        $channel = fn (string $pair) => ($c = hexdec($pair) / 255) <= 0.03928 ? $c / 12.92 : (($c + 0.055) / 1.055) ** 2.4;
        $bg = 0.2126 * $channel(substr($rgb, 0, 2)) + 0.7152 * $channel(substr($rgb, 2, 2)) + 0.0722 * $channel(substr($rgb, 4, 2));
        $ratio = fn (float $fg) => (max($bg, $fg) + 0.05) / (min($bg, $fg) + 0.05);
        $candidates = ['#111' => 0.005605, '#fff' => 1.0, '#000' => 0.0];
        $ink = collect($candidates)->filter(fn ($l) => $ratio($l) >= 4.5)->keys()->first()
            ?? collect($candidates)->sortByDesc(fn ($l) => $ratio($l))->keys()->first();
    }
@endphp
{{ $ink }}