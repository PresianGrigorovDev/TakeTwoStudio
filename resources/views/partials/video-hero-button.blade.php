@if(!empty($service->video_url) || !empty($service->video_path))
    <div class="mt-4">
        <a href="#video-showcase-section" 
           onclick="scrollToVideoAndPlay('{{ $service->id }}', event)" 
           class="btn-video-play d-inline-flex align-items-center text-decoration-none">
            <div class="video-play-btn-circle d-flex align-items-center justify-content-center me-3">
                <i class="fas fa-play text-white ms-1" style="font-size: 14px;"></i>
            </div>
            <span class="video-play-text text-uppercase fw-bold text-white">Виж нашето видео</span>
        </a>
    </div>

    @once
    <script>
    function scrollToVideoAndPlay(serviceId, event) {
        event.preventDefault();
        const targetSection = document.getElementById('video-showcase-section');
        if (targetSection) {
            targetSection.scrollIntoView({ behavior: 'smooth' });
            
            // Wait for the scroll animation to complete, then auto-play the video
            setTimeout(() => {
                const playBtn = document.querySelector('#video-container-' + serviceId + ' .video-play-btn-large');
                if (playBtn) {
                    playBtn.click();
                }
            }, 800);
        }
    }
    </script>
    @endonce
@endif
