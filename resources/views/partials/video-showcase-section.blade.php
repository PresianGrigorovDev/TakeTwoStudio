@php
    $finalVideoUrl = null;
    $videoType = 'local';
    $videoId = '';
    $loopUrl = '';
    $fullUrl = '';
    
    if (!empty($service->video_url)) {
        $finalVideoUrl = $service->video_url;
        
        if (str_contains($finalVideoUrl, 'instagram.com')) {
            $videoType = 'instagram';
            if (!str_contains($finalVideoUrl, '/embed')) {
                $cleanUrl = strtok($finalVideoUrl, '?');
                $finalVideoUrl = rtrim($cleanUrl, '/') . '/embed/';
            }
        } elseif (str_contains($finalVideoUrl, 'youtube.com') || str_contains($finalVideoUrl, 'youtu.be')) {
            $videoType = 'youtube';
            if (preg_match('%(?:youtube(?:-nocookie)?\.com/(?:[^/]+/.+/|(?:v|e(?:mbed)?)/||user/.+/|embed/)|youtu\.be/)([^"&?/ ]{11})%i', $finalVideoUrl, $match)) {
                $videoId = $match[1];
            }
            $loopUrl = "https://www.youtube.com/embed/{$videoId}?autoplay=1&mute=1&loop=1&playlist={$videoId}&controls=0&modestbranding=1&rel=0&playsinline=1&enablejsapi=1";
            $fullUrl = "https://www.youtube.com/embed/{$videoId}?autoplay=1&mute=0&controls=1&rel=0";
        } elseif (str_contains($finalVideoUrl, 'vimeo.com')) {
            $videoType = 'vimeo';
            $parts = explode('/', parse_url($finalVideoUrl, PHP_URL_PATH));
            $videoId = end($parts);
            $loopUrl = "https://player.vimeo.com/video/{$videoId}?autoplay=1&muted=1&loop=1&background=1";
            $fullUrl = "https://player.vimeo.com/video/{$videoId}?autoplay=1&muted=0&controls=1";
        }
    } elseif (!empty($service->video_path)) {
        $finalVideoUrl = asset('storage/' . $service->video_path);
        $videoType = 'local';
    }

    // Determine card sizing style depending on format (landscape vs portrait Instagram Reel)
    if ($videoType === 'instagram') {
        $cardStyle = 'max-width: 420px; aspect-ratio: 9/16; background: #000; transition: all 0.5s ease;';
    } else {
        $cardStyle = 'max-width: 800px; aspect-ratio: 16/9; background: #000; transition: all 0.5s ease;';
    }

    // SEO-optimized headings map (incorporating high-value search keywords per service)
    $seoTitles = [
        'weddings' => 'Зад кулисите на сватбения ден: Професионално сватбено видеозаснемане и фотография',
        'proms' => 'Заснемане на абитуриентски балове във Варна: Как протича работният процес',
        'baptism' => 'Свето Кръщение във Варна: Заснемане на ритуала и семейния празник',
        'graduation' => 'Пред-бална фотосесия: Креативни моменти и подготовка зад кадър',
        'commercial' => 'Рекламно видео и продуктова фотография във Варна за вашия бранд',
        'family' => 'Семейна фотосесия на открито и в студио: Уловени чисти емоции',
        'portrait' => 'Артистична портретна фотография: Зад кулисите на индивидуалната сесия',
        'automotive' => 'Професионална автомобилна фотография (Car Photography) във Варна',
        'architectural' => 'Интериорна и архитектурна фотография: Снимки за недвижими имоти и хотели',
        'events' => 'Професионално заснемане на корпоративни, фирмени и частни събития',
    ];

    // SEO-optimized descriptions map
    $seoDescriptions = [
        'weddings' => 'Вижте как нашият екип от сватбени фотографи и видеографи улавя магията на сватбения ден. Предлагаме професионално 4K видеозаснемане с дрон и креативен подход във Варна и цялата страна.',
        'proms' => 'Кратко видео, представящо нашия стил на заснемане на абитуриентски балове. Вижте емоцията, динамиката и подготовката за най-важната вечер.',
        'baptism' => 'Вижте нашия деликатен подход при заснемане на Свето Кръщение. Улавяме святостта на ритуала в храма и празничната емоция на семейството.',
        'graduation' => 'Подготовка, емоции и спонтанни кадри – разберете как създаваме перфектните портрети за вашето изпращане и пред-бална подготовка.',
        'commercial' => 'Висококачествено бизнес видео и продуктови снимки за онлайн магазини, рекламни кампании и социални мрежи. Повишете продажбите си с премиум визия.',
        'family' => 'Зад кулисите на нашите семейни и детски фотосесии в парка, на плажа или в студио. Създаваме непринудена атмосфера за естествени усмивки.',
        'portrait' => 'Професионални портрети за LinkedIn, бизнес цели или лична марка. Вижте как протича работата със студийно осветление и позиране на модела.',
        'automotive' => 'Динамични и статични кадри на коли за автосалони, обяви или тунинг проекти. Премиум автомобилна фотография за истински ценители.',
        'architectural' => 'Визуално представяне на недвижими имоти, хотели, ресторанти и Airbnb апартаменти. Подчертаваме пространството, геометрията и интериорния дизайн.',
        'events' => 'Професионално заснемане на конференции, презентации, фирмени партита и тиймбилдинги. Бърза обработка и представителни кадри за вашия бранд.',
    ];

    $slug = $service->slug ?? 'default';
    $displayTitle = $seoTitles[$slug] ?? 'Как работим зад кулисите';
    $displayDesc = $seoDescriptions[$slug] ?? 'Всеки детайл има значение. Вижте нашето кратко видео, което показва нашия подход, динамика и професионализъм на терен.';
@endphp

@if($finalVideoUrl)
    <!-- Section - Behind the scenes Video -->
    <section class="video-showcase-section py-5 text-center" id="video-showcase-section">
        <div class="container py-4">
            <h2 class="mb-3 text-uppercase fw-bold">{{ $displayTitle }}</h2>
            <div class="section-divider"></div>
            <p class="col-lg-8 mx-auto mb-5">
                {{ $displayDesc }}
            </p>
            <div class="video-cover-card mx-auto position-relative rounded shadow-lg overflow-hidden" 
                 id="video-container-{{ $service->id }}" 
                 style="{{ $cardStyle }}">
                
                @if($videoType === 'local')
                    <video id="video-player-{{ $service->id }}" 
                           src="{{ $finalVideoUrl }}" 
                           autoplay loop muted playsinline 
                           class="w-100 h-100 object-fit-cover"
                           style="cursor: pointer;"
                           onclick="enableFullVideoLocal('{{ $service->id }}')">
                    </video>
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                         id="video-overlay-{{ $service->id }}" 
                         style="background: rgba(0, 0, 0, 0.15); transition: all 0.3s ease; pointer-events: none;">
                        <button type="button" class="video-play-btn-large border-0 bg-transparent" style="pointer-events: auto;" onclick="enableFullVideoLocal('{{ $service->id }}')">
                            <span class="play-btn-ring"></span>
                            <span class="play-btn-icon"><i class="fas fa-volume-up"></i></span>
                        </button>
                    </div>
                @elseif($videoType === 'youtube' || $videoType === 'vimeo')
                    <iframe id="video-iframe-{{ $service->id }}" 
                            src="{{ $loopUrl }}" 
                            class="w-100 h-100" 
                            frameborder="0" 
                            allow="autoplay; fullscreen" 
                            allowfullscreen 
                            style="pointer-events: none; border: none; width: 100%; height: 100%;">
                    </iframe>
                    <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                         id="video-overlay-{{ $service->id }}" 
                         style="background: rgba(0, 0, 0, 0.15); cursor: pointer; transition: all 0.3s ease;"
                         onclick="enableFullVideoIframe('{{ $service->id }}', '{{ $fullUrl }}')">
                        <button type="button" class="video-play-btn-large border-0 bg-transparent">
                            <span class="play-btn-ring"></span>
                            <span class="play-btn-icon"><i class="fas fa-volume-up"></i></span>
                        </button>
                    </div>
                @elseif($videoType === 'instagram')
                    <iframe id="video-iframe-{{ $service->id }}" 
                            src="{{ $finalVideoUrl }}" 
                            width="100%" 
                            height="100%" 
                            frameborder="0" 
                            scrolling="no" 
                            allowtransparency="true" 
                            allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" 
                            style="border: none; overflow: hidden; width: 100%; height: 100%;">
                    </iframe>
                @endif
            </div>
        </div>
    </section>

    <!-- Inline Player JavaScript -->
    @once
    <script>
    function enableFullVideoLocal(serviceId) {
        const video = document.getElementById('video-player-' + serviceId);
        const overlay = document.getElementById('video-overlay-' + serviceId);
        if (video) {
            video.muted = false;
            video.controls = true;
        }
        if (overlay) {
            overlay.style.opacity = '0';
            overlay.style.pointerEvents = 'none';
        }
    }

    function enableFullVideoIframe(serviceId, fullUrl) {
        const iframe = document.getElementById('video-iframe-' + serviceId);
        const overlay = document.getElementById('video-overlay-' + serviceId);
        if (iframe) {
            iframe.src = fullUrl;
            iframe.style.pointerEvents = 'auto';
        }
        if (overlay) {
            overlay.style.opacity = '0';
            overlay.style.pointerEvents = 'none';
        }
    }
    </script>
    @endonce
@endif
