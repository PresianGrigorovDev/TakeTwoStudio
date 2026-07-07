@if(!empty($service->video_url))
    <!-- Section - Behind the scenes Video -->
    <section class="video-showcase-section py-5 text-white text-center">
        <div class="container py-4">
            <h2 class="mb-3 text-uppercase fw-bold">Как работим зад кулисите</h2>
            <div class="section-divider"></div>
            <p class="text-muted col-lg-8 mx-auto mb-5">
                Всеки детайл има значение. Вижте нашето кратко видео, което показва нашия подход, динамика и професионализъм на терен.
            </p>
            <div class="video-cover-card mx-auto position-relative rounded shadow-lg overflow-hidden" style="max-width: 800px; aspect-ratio: 16/9;">
                <img src="{{ !empty($service->hero_image) ? asset('storage/' . $service->hero_image) : asset('css/img/about.webp') }}" class="w-100 h-100 object-fit-cover" alt="Видео превю">
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" style="background: rgba(0, 0, 0, 0.45);">
                    <a href="{{ $service->video_url }}" class="glightbox video-play-btn-large">
                        <span class="play-btn-ring"></span>
                        <span class="play-btn-icon"><i class="fas fa-play"></i></span>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endif
