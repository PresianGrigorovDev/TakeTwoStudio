@if(!empty($service->video_url))
    <div class="mt-4">
        <a href="{{ $service->video_url }}" class="glightbox btn-video-play d-inline-flex align-items-center text-decoration-none">
            <div class="video-play-btn-circle d-flex align-items-center justify-content-center me-3">
                <i class="fas fa-play text-white ms-1" style="font-size: 14px;"></i>
            </div>
            <span class="video-play-text text-uppercase fw-bold text-white">Виж нашето видео</span>
        </a>
    </div>
@endif
