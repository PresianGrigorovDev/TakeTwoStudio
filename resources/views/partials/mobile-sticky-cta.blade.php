<style>
.mobile-sticky-bar {
    display: none;
    position: fixed;
    bottom: 0;
    left: 0;
    right: 0;
    z-index: 9990;
    background: rgba(18, 18, 18, 0.95);
    backdrop-filter: blur(10px);
    border-top: 1px solid rgba(212, 175, 55, 0.3);
    padding: 10px 15px;
    box-shadow: 0 -4px 20px rgba(0, 0, 0, 0.4);
    opacity: 0;
    transform: translateY(100%);
    transition: opacity 0.3s ease, transform 0.3s ease;
    pointer-events: none;
}

.mobile-sticky-bar.visible {
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
}

@media (max-width: 767.98px) {
    .mobile-sticky-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 10px;
    }
}

.mobile-sticky-btn-calc {
    flex: 1;
    background: linear-gradient(135deg, #d4af37 0%, #aa820a 100%);
    color: #000;
    font-weight: 700;
    font-size: 0.85rem;
    padding: 10px 14px;
    border-radius: 25px;
    text-decoration: none;
    text-align: center;
    box-shadow: 0 2px 10px rgba(212, 175, 55, 0.3);
    transition: transform 0.2s ease;
}

.mobile-sticky-btn-call {
    background: #22c55e;
    color: #fff;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    text-decoration: none;
    font-size: 1.1rem;
    box-shadow: 0 2px 10px rgba(34, 197, 94, 0.3);
}
</style>

<div class="mobile-sticky-bar" id="mobileStickyBar">
    <a href="#calculator" onclick="if(document.getElementById('calculator')){ document.getElementById('calculator').scrollIntoView({behavior: 'smooth'}); return false; }" class="mobile-sticky-btn-calc">
        Изчисли Цена & Запази Час
    </a>
    @php
        $phone = \App\Models\SiteSetting::find(4)?->setting_value ?? '088 619 0124';
        $cleanPhone = preg_replace('/[^0-9+]/', '', $phone);
    @endphp
    <a href="tel:{{ $cleanPhone }}" class="mobile-sticky-btn-call" title="Обадете се ({{ $phone }})">
        <i class="fas fa-phone-alt"></i>
    </a>
</div>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const stickyBar = document.getElementById('mobileStickyBar');
    const calcSection = document.getElementById('calculator');

    // Only enable if page has a calculator section and screen is mobile
    if (!stickyBar || !calcSection) return;

    function checkVisibility() {
        if (window.innerWidth >= 768) {
            stickyBar.classList.remove('visible');
            return;
        }

        const scrollY = window.scrollY || window.pageYOffset;
        const rect = calcSection.getBoundingClientRect();
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;

        // Hide when in Hero section (top 250px)
        if (scrollY < 250) {
            stickyBar.classList.remove('visible');
            return;
        }

        // Hide when calculator is currently visible on screen
        const isCalcInView = (rect.top <= windowHeight * 0.8) && (rect.bottom >= windowHeight * 0.2);
        if (isCalcInView) {
            stickyBar.classList.remove('visible');
            return;
        }

        // Otherwise show sticky bar
        stickyBar.classList.add('visible');
    }

    window.addEventListener('scroll', checkVisibility, { passive: true });
    window.addEventListener('resize', checkVisibility, { passive: true });
    checkVisibility();
});
</script>
