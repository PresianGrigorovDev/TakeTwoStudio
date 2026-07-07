@php
    $finalVideoUrl = null;
    $lightboxAttrs = '';
    
    if (!empty($service->video_url)) {
        $finalVideoUrl = $service->video_url;
        
        // Detect Instagram links (Reels/Posts)
        if (str_contains($finalVideoUrl, 'instagram.com')) {
            if (!str_contains($finalVideoUrl, '/embed')) {
                $cleanUrl = strtok($finalVideoUrl, '?');
                $finalVideoUrl = rtrim($cleanUrl, '/') . '/embed/';
            }
            // Explicitly set type to iframe and specify portrait sizing for Reels
            $lightboxAttrs = 'data-type="iframe" data-width="480px" data-height="80vh" data-glightbox="type: iframe; width: 480px; height: 80vh;"';
        }
    } elseif (!empty($service->video_path)) {
        $finalVideoUrl = asset('storage/' . $service->video_path);
    }
@endphp

@if($finalVideoUrl)
    <div class="mt-4">
        <a href="{{ $finalVideoUrl }}" class="glightbox btn-video-play d-inline-flex align-items-center text-decoration-none" {!! $lightboxAttrs !!}>
            <div class="video-play-btn-circle d-flex align-items-center justify-content-center me-3">
                <i class="fas fa-play text-white ms-1" style="font-size: 14px;"></i>
            </div>
            <span class="video-play-text text-uppercase fw-bold text-white">Виж нашето видео</span>
        </a>
    </div>
@endif
