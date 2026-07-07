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
@endphp

@if($finalVideoUrl)
    <!-- Section - Behind the scenes Video -->
    <section class="video-showcase-section py-5 text-white text-center" id="video-showcase-section">
        <div class="container py-4">
            <h2 class="mb-3 text-uppercase fw-bold text-white">Как работим зад кулисите</h2>
            <div class="section-divider"></div>
            <p class="text-muted col-lg-8 mx-auto mb-5">
                Всеки детайл има значение. Вижте нашето кратко видео, което показва нашия подход, динамика и професионализъм на терен.
            </p>
            <div class="video-cover-card mx-auto position-relative rounded shadow-lg overflow-hidden" 
                 id="video-container-{{ $service->id }}" 
                 style="max-width: 800px; aspect-ratio: 16/9; background: #000; transition: all 0.5s ease;">
                
                <!-- Cover Image -->
                <img src="{{ !empty($service->hero_image) ? asset('storage/' . $service->hero_image) : asset('css/img/about.webp') }}" 
                     class="w-100 h-100 object-fit-cover" 
                     id="video-cover-{{ $service->id }}" 
                     alt="Видео превю">
                
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
