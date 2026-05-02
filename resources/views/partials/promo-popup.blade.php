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

<style>
.promo-popup-overlay {
    position: fixed;
    inset: 0;
    z-index: 9999;
    background: rgba(0,0,0,0.72);
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 1rem;
    backdrop-filter: blur(4px);
    animation: promo-fade-in 0.4s ease;
}

@keyframes promo-fade-in {
    from { opacity: 0; }
    to   { opacity: 1; }
}

.promo-popup-box {
    position: relative;
    background: #111;
    border-radius: 16px;
    overflow: hidden;
    max-width: 540px;
    width: 100%;
    box-shadow: 0 25px 60px rgba(0,0,0,0.6), 0 0 0 1px rgba(255,255,255,0.06);
    animation: promo-slide-up 0.4s cubic-bezier(0.34, 1.56, 0.64, 1);
}

@keyframes promo-slide-up {
    from { transform: translateY(40px) scale(0.96); opacity: 0; }
    to   { transform: translateY(0)    scale(1);    opacity: 1; }
}

.promo-popup-close {
    position: absolute;
    top: 12px;
    right: 12px;
    z-index: 10;
    background: rgba(0,0,0,0.6);
    border: 1px solid rgba(255,255,255,0.15);
    border-radius: 50%;
    width: 36px;
    height: 36px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    color: #fff;
    transition: background 0.2s, transform 0.2s;
}
.promo-popup-close:hover {
    background: rgba(255,255,255,0.2);
    transform: rotate(90deg);
}

.promo-popup-image-link {
    display: block;
    cursor: pointer;
}
.promo-popup-image {
    width: 100%;
    display: block;
    object-fit: cover;
    max-height: 320px;
    transition: opacity 0.2s;
}
.promo-popup-image-link:hover .promo-popup-image {
    opacity: 0.92;
}

/* Timer */
.promo-popup-timer {
    padding: 14px 20px 10px;
    text-align: center;
    border-bottom: 1px solid rgba(255,255,255,0.06);
}
.promo-timer-label {
    display: block;
    font-size: 0.75rem;
    color: #aaa;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    margin-bottom: 8px;
}
.promo-timer-blocks {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 4px;
}
.promo-timer-block {
    display: flex;
    flex-direction: column;
    align-items: center;
    background: rgba(255,255,255,0.06);
    border-radius: 8px;
    padding: 6px 12px;
    min-width: 56px;
}
.promo-timer-block span {
    font-size: 1.5rem;
    font-weight: 700;
    color: #f5a623;
    line-height: 1;
    font-variant-numeric: tabular-nums;
}
.promo-timer-block small {
    font-size: 0.65rem;
    color: #888;
    text-transform: uppercase;
    margin-top: 2px;
}
.promo-timer-sep {
    font-size: 1.4rem;
    font-weight: 700;
    color: #f5a623;
    margin: 0 2px;
    padding-bottom: 12px;
}

/* Promo Code */
.promo-popup-code-section {
    padding: 16px 20px 20px;
    text-align: center;
}
.promo-code-label {
    font-size: 0.78rem;
    color: #aaa;
    margin-bottom: 10px;
    text-transform: uppercase;
    letter-spacing: 0.05em;
}
.promo-code-display {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: rgba(245,166,35,0.1);
    border: 2px dashed #f5a623;
    border-radius: 10px;
    padding: 10px 16px;
}
.promo-code-text {
    font-family: 'Courier New', monospace;
    font-size: 1.5rem;
    font-weight: 800;
    color: #f5a623;
    letter-spacing: 0.1em;
}
.promo-code-copy-btn {
    display: flex;
    align-items: center;
    gap: 5px;
    background: #f5a623;
    color: #111;
    border: none;
    border-radius: 6px;
    padding: 6px 12px;
    font-size: 0.78rem;
    font-weight: 700;
    cursor: pointer;
    transition: background 0.2s, transform 0.15s;
    white-space: nowrap;
}
.promo-code-copy-btn:hover {
    background: #ffc144;
    transform: scale(1.04);
}
.promo-code-copy-btn.copied {
    background: #22c55e;
    color: #fff;
}
.promo-code-discount {
    font-size: 0.8rem;
    color: #22c55e;
    margin-top: 8px;
    font-weight: 600;
}

@media (max-width: 480px) {
    .promo-popup-box { border-radius: 12px; }
    .promo-timer-block { min-width: 46px; padding: 4px 8px; }
    .promo-timer-block span { font-size: 1.2rem; }
    .promo-code-text { font-size: 1.2rem; }
}
</style>

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
