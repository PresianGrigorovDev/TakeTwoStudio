@php
    $activePromo = \App\Models\Promotion::with('promoCode')
        ->where('is_active', true)
        ->latest()
        ->first();
@endphp

@if($activePromo)
<div id="promo-popup-overlay" class="promo-popup-overlay" aria-modal="true" role="dialog" aria-label="Промоция">
    <div class="promo-popup-box" id="promo-popup-box">

        {{-- Close button --}}
        <button class="promo-popup-close" id="promo-popup-close" aria-label="Затвори промоцията">
            <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>
            </svg>
        </button>

        {{-- Banner image (clickable) --}}
        @if($activePromo->redirect_url)
            <a href="{{ $activePromo->redirect_url }}" class="promo-popup-image-link" id="promo-popup-link">
                <img src="{{ asset('storage/' . $activePromo->image_path) }}"
                     alt="{{ $activePromo->title }}"
                     class="promo-popup-image"
                     loading="eager">
            </a>
        @else
            <img src="{{ asset('storage/' . $activePromo->image_path) }}"
                 alt="{{ $activePromo->title }}"
                 class="promo-popup-image"
                 loading="eager">
        @endif

        {{-- Countdown Timer --}}
        @if($activePromo->expires_at)
        <div class="promo-popup-timer" id="promo-timer-section">
            <span class="promo-timer-label">Промоцията изтича след:</span>
            <div class="promo-timer-blocks">
                <div class="promo-timer-block">
                    <span id="promo-timer-days">00</span>
                    <small>дни</small>
                </div>
                <div class="promo-timer-sep">:</div>
                <div class="promo-timer-block">
                    <span id="promo-timer-hours">00</span>
                    <small>часа</small>
                </div>
                <div class="promo-timer-sep">:</div>
                <div class="promo-timer-block">
                    <span id="promo-timer-mins">00</span>
                    <small>мин</small>
                </div>
                <div class="promo-timer-sep">:</div>
                <div class="promo-timer-block">
                    <span id="promo-timer-secs">00</span>
                    <small>сек</small>
                </div>
            </div>
        </div>
        @endif

        {{-- Promo Code --}}
        @if($activePromo->promoCode && $activePromo->promoCode->is_active)
        <div class="promo-popup-code-section">
            <p class="promo-code-label">Използвай промо код в калкулатора:</p>
            <div class="promo-code-display">
                <span class="promo-code-text" id="promo-code-value">{{ $activePromo->promoCode->code }}</span>
                <button class="promo-code-copy-btn" id="promo-code-copy-btn" onclick="copyPromoCode()" aria-label="Копирай кода">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none"
                        stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/><path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
                    </svg>
                    <span id="copy-btn-label">Копирай</span>
                </button>
            </div>
            @if($activePromo->promoCode->discount_type === 'percent')
                <p class="promo-code-discount">Намалена цена с {{ number_format((float) $activePromo->promoCode->discount_value, 0) }}%!</p>
            @else
                <p class="promo-code-discount">Намалена цена с €{{ number_format((float) $activePromo->promoCode->discount_value, 0) }}!</p>
            @endif
        </div>
        @endif

    </div>
</div>


<script>
(function () {
    var PROMO_KEY    = 'promo_closed_at_{{ $activePromo->id }}';
    var INTERVAL_DAYS = {{ $activePromo->popup_days_interval }};

    function shouldShow() {
        var closed = localStorage.getItem(PROMO_KEY);
        if (!closed) return true;
        var elapsed = (Date.now() - parseInt(closed)) / (1000 * 60 * 60 * 24);
        return elapsed >= INTERVAL_DAYS;
    }

    function closePopup() {
        var overlay = document.getElementById('promo-popup-overlay');
        if (overlay) {
            overlay.style.opacity = '0';
            overlay.style.transition = 'opacity 0.3s';
            setTimeout(function () { overlay.remove(); }, 320);
        }
        localStorage.setItem(PROMO_KEY, Date.now().toString());
    }

    // Show or hide popup
    if (!shouldShow()) {
        document.getElementById('promo-popup-overlay').remove();
    } else {
        document.getElementById('promo-popup-close').addEventListener('click', closePopup);

        // Close on overlay click (outside box)
        document.getElementById('promo-popup-overlay').addEventListener('click', function (e) {
            if (e.target === this) closePopup();
        });

        // Close on Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closePopup();
        });
    }

    @if($activePromo->expires_at)
    // Countdown timer
    var deadline = new Date("{{ $activePromo->expires_at->toIso8601String() }}").getTime();

    function pad(n) { return n < 10 ? '0' + n : '' + n; }

    function updateTimer() {
        var now  = Date.now();
        var diff = deadline - now;
        if (diff <= 0) {
            document.getElementById('promo-timer-section') && (document.getElementById('promo-timer-section').innerHTML = '<span style="color:#aaa;font-size:.8rem;">Промоцията е приключила.</span>');
            return;
        }
        var days  = Math.floor(diff / 86400000);
        var hours = Math.floor((diff % 86400000) / 3600000);
        var mins  = Math.floor((diff % 3600000) / 60000);
        var secs  = Math.floor((diff % 60000) / 1000);

        var d = document.getElementById('promo-timer-days');
        var h = document.getElementById('promo-timer-hours');
        var m = document.getElementById('promo-timer-mins');
        var s = document.getElementById('promo-timer-secs');
        if (d) d.textContent = pad(days);
        if (h) h.textContent = pad(hours);
        if (m) m.textContent = pad(mins);
        if (s) s.textContent = pad(secs);
    }
    updateTimer();
    setInterval(updateTimer, 1000);
    @endif
})();

function copyPromoCode() {
    var code = document.getElementById('promo-code-value');
    if (!code) return;
    navigator.clipboard.writeText(code.textContent.trim()).then(function () {
        var btn   = document.getElementById('promo-code-copy-btn');
        var label = document.getElementById('copy-btn-label');
        if (btn && label) {
            btn.classList.add('copied');
            label.textContent = 'Копирано!';
            setTimeout(function () {
                btn.classList.remove('copied');
                label.textContent = 'Копирай';
            }, 2500);
        }
    });
}
</script>
@endif
