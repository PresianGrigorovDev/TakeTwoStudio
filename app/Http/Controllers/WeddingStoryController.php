<?php

namespace App\Http\Controllers;

use App\Models\WeddingGallery;
use App\Support\Seo\Seo;
use Illuminate\Support\Str;

/** /svatbi/{slug}: a real wedding as a case study (venue entity + photos + film). */
class WeddingStoryController extends Controller
{
    public function show(string $slug)
    {
        $gallery = WeddingGallery::with('photos')
            ->where('is_active', true)
            ->where('slug', $slug)
            ->firstOrFail();

        $related = WeddingGallery::where('is_active', true)
            ->whereNotNull('slug')
            ->where('id', '!=', $gallery->id)
            ->orderByDesc('event_date')
            ->take(3)
            ->get();

        $videoId = null;
        if ($gallery->video_url && preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/|shorts/|watch\?v=|embed/)|youtu\.be/)([A-Za-z0-9_-]{11})%i', $gallery->video_url, $m)) {
            $videoId = $m[1];
        }

        $this->registerSeo($gallery, $videoId);

        return view('weddings.story', compact('gallery', 'related', 'videoId'));
    }

    private function registerSeo(WeddingGallery $gallery, ?string $videoId): void
    {
        $seo = app(Seo::class)->setPageType('ImageGallery');
        $current = url()->current();

        $seo->setBreadcrumbs([
            ['name' => 'Начало', 'url' => url('/')],
            ['name' => 'Сватби', 'url' => url('/weddings')],
            ['name' => 'Сватбата на '.$gallery->title, 'url' => null],
        ]);
        $seo->setDates($gallery->created_at, $gallery->updated_at);

        if ($gallery->venue || $gallery->location) {
            $seo->addNode(array_filter([
                '@type' => 'Place',
                '@id' => $current.'#venue',
                'name' => $gallery->venue ?: $gallery->location,
                'address' => ['@type' => 'PostalAddress', 'addressLocality' => $gallery->location ?: 'Варна', 'addressCountry' => 'BG'],
            ]));
        }

        foreach ($gallery->photos->take(30) as $i => $photo) {
            $url = preg_match('#^https?://#i', $photo->image_path) ? $photo->image_path : asset('storage/'.$photo->image_path);
            $seo->addNode([
                '@type' => 'ImageObject',
                '@id' => $current.'#photo-'.($i + 1),
                'contentUrl' => $url,
                'url' => $url,
                'caption' => 'Сватбата на '.$gallery->title.($gallery->place_label ? ' – '.$gallery->place_label : '').' | Take Two Studio 1603',
                'creator' => ['@id' => Seo::rootId('organization')],
                'creditText' => 'Take Two Studio 1603',
                'copyrightNotice' => '© '.($gallery->event_date?->year ?? date('Y')).' Take Two Studio 1603',
                'license' => route('legal.terms'),
                'acquireLicensePage' => url('/kontakti'),
            ]);
        }

        if ($videoId) {
            $seo->addNode(array_filter([
                '@type' => 'VideoObject',
                '@id' => $current.'#video',
                'name' => 'Сватбен филм: '.$gallery->title.($gallery->place_label ? ' – '.$gallery->place_label : ''),
                'description' => Str::limit(strip_tags((string) $gallery->description), 300) ?: 'Сватбен филм от Take Two Studio 1603, Варна.',
                'thumbnailUrl' => ["https://i.ytimg.com/vi/{$videoId}/maxresdefault.jpg", "https://i.ytimg.com/vi/{$videoId}/hqdefault.jpg"],
                'uploadDate' => ($gallery->event_date ?? $gallery->created_at)?->toDateString(),
                'embedUrl' => "https://www.youtube.com/embed/{$videoId}",
                'contentUrl' => $gallery->video_url,
                'publisher' => ['@id' => Seo::rootId('organization')],
            ]));
        }
    }
}
