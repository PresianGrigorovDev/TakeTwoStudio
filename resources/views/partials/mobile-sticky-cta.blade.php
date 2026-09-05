<div class="mobile-sticky-bar" id="mobileStickyBar">
    <a href="#calculator" onclick="if(document.getElementById('calculator')){ document.getElementById('calculator').scrollIntoView({behavior: 'smooth'}); return false; }" class="mobile-sticky-btn-calc">
        Изчисли Цена & Запази Час
    </a>
    @php
        $isProm = request()->is('proms*') || request()->is('graduation*');
        $phoneE164 = $isProm ? (\App\Support\Settings::phoneSecondary() ?? \App\Support\Settings::phone()) : \App\Support\Settings::phone();
        $phone = \App\Support\Settings::phoneDisplay($phoneE164);
        $cleanPhone = \App\Support\Settings::phoneHref($phoneE164);
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
