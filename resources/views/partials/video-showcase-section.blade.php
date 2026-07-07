@php
    $finalVideoUrl = null;
    $videoType = 'local';
    
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
        } elseif (str_contains($finalVideoUrl, 'vimeo.com')) {
            $videoType = 'vimeo';
        }
    } elseif (!empty($service->video_path)) {
        $finalVideoUrl = asset('storage/' . $service->video_path);
        $videoType = 'local';
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
                 style="max-width: 800px; aspect-ratio: 16/9; background: #000; transition: all 0.5s ease;">
                
                <!-- Cover Image -->
                <img src="{{ !empty($service->hero_image) ? asset('storage/' . $service->hero_image) : asset('css/img/about.webp') }}" 
                     class="w-100 h-100 object-fit-cover" 
                     id="video-cover-{{ $service->id }}" 
                     alt="{{ $displayTitle }}">
                
                <!-- Play Button Overlay -->
                <div class="position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center" 
                     id="video-overlay-{{ $service->id }}" 
                     style="background: rgba(0, 0, 0, 0.45);">
                    <button type="button" 
                            class="video-play-btn-large border-0 bg-transparent" 
                            onclick="playVideoInline('{{ $service->id }}', '{{ $finalVideoUrl }}', '{{ $videoType }}')">
                        <span class="play-btn-ring"></span>
                        <span class="play-btn-icon"><i class="fas fa-play"></i></span>
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Inline Player JavaScript -->
    @once
    <script>
    function playVideoInline(serviceId, videoUrl, type) {
        const container = document.getElementById('video-container-' + serviceId);
        let html = '';
        
        if (type === 'instagram') {
            // Apply portrait layout for vertical reels
            container.style.maxWidth = '420px';
            container.style.aspectRatio = '9/16';
            html = `<iframe src="${videoUrl}" width="100%" height="100%" frameborder="0" scrolling="no" allowtransparency="true" allow="autoplay; clipboard-write; encrypted-media; picture-in-picture; web-share" style="border: none; overflow: hidden; width: 100%; height: 100%;"></iframe>`;
        } else if (type === 'youtube') {
            let embedUrl = videoUrl;
            if (videoUrl.includes('watch?v=')) {
                embedUrl = videoUrl.replace('watch?v=', 'embed/') + '?autoplay=1&rel=0';
            } else if (videoUrl.includes('youtu.be/')) {
                const parts = videoUrl.split('/');
                const id = parts[parts.length - 1].split('?')[0];
                embedUrl = `https://www.youtube.com/embed/${id}?autoplay=1&rel=0`;
            } else {
                embedUrl += (embedUrl.includes('?') ? '&' : '?') + 'autoplay=1';
            }
            html = `<iframe src="${embedUrl}" width="100%" height="100%" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen style="border: none; width: 100%; height: 100%;"></iframe>`;
        } else if (type === 'vimeo') {
            let embedUrl = videoUrl;
            if (!videoUrl.includes('player.vimeo.com')) {
                const parts = videoUrl.split('/');
                const id = parts[parts.length - 1].split('?')[0];
                embedUrl = `https://player.vimeo.com/video/${id}?autoplay=1`;
            } else {
                embedUrl += (embedUrl.includes('?') ? '&' : '?') + 'autoplay=1';
            }
            html = `<iframe src="${embedUrl}" width="100%" height="100%" frameborder="0" allow="autoplay; fullscreen; picture-in-picture" allowfullscreen style="border: none; width: 100%; height: 100%;"></iframe>`;
        } else {
            // Local file
            html = `<video src="${videoUrl}" width="100%" height="100%" controls autoplay style="object-fit: contain; background: #000; width: 100%; height: 100%;"></video>`;
        }
        
        container.innerHTML = html;
    }
    </script>
    @endonce
@endif
