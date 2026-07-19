<div id="cookieConsentBanner" class="cookie-banner" style="display: none;">
    <div class="cookie-banner-inner">
        <div class="cookie-banner-text">
            <strong>Използваме бисквитки</strong>
            <p class="mb-0">
                Този сайт използва строго необходими бисквитки за функционирането си, както и бисквитки от трети страни (Google Maps, Google Fonts) за подобряване на потребителското изживяване.
                Като продължавате да използвате сайта, Вие се съгласявате с употребата им.
                Повече информация в нашата <a href="{{ route('legal.cookies') }}">Политика за бисквитки</a>.
            </p>
        </div>
        <div class="cookie-banner-actions">
            <button type="button" id="cookieAcceptAll" class="btn btn-cookie btn-cookie-primary">Приемам всички</button>
            <button type="button" id="cookieRejectOptional" class="btn btn-cookie btn-cookie-secondary">Само необходими</button>
        </div>
    </div>
</div>

<style>
    .cookie-banner {
        position: fixed;
        bottom: 0;
        left: 0;
        right: 0;
        z-index: 9999;
        background: rgba(20, 20, 20, 0.97);
        color: #fff;
        padding: 18px 20px;
        box-shadow: 0 -4px 16px rgba(0, 0, 0, 0.3);
        border-top: 2px solid #b8860b;
    }
    .cookie-banner-inner {
        max-width: 1200px;
        margin: 0 auto;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 20px;
        flex-wrap: wrap;
    }
    .cookie-banner-text { flex: 1 1 60%; min-width: 280px; font-size: 0.9rem; line-height: 1.5; }
    .cookie-banner-text strong { display: block; margin-bottom: 4px; font-size: 1rem; }
    .cookie-banner-text a { color: #d4a849; text-decoration: underline; }
    .cookie-banner-actions { display: flex; gap: 10px; flex-wrap: wrap; }
    .btn-cookie {
        padding: 10px 20px;
        border-radius: 4px;
        font-size: 0.88rem;
        font-weight: 600;
        border: none;
        cursor: pointer;
        transition: opacity 0.2s;
    }
    .btn-cookie:hover { opacity: 0.85; }
    .btn-cookie-primary { background: #b8860b; color: #fff; }
    .btn-cookie-secondary { background: transparent; color: #fff; border: 1px solid #fff; }
    @media (max-width: 600px) {
        .cookie-banner-inner { flex-direction: column; align-items: stretch; }
        .cookie-banner-actions { justify-content: stretch; }
        .btn-cookie { flex: 1; }
    }
</style>

<script>
(function () {
    const COOKIE_NAME = 'cookie_consent';
    const banner = document.getElementById('cookieConsentBanner');
    if (!banner) return;

    function getConsent() {
        const match = document.cookie.match(new RegExp('(^| )' + COOKIE_NAME + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function setConsent(value) {
        const oneYear = 365 * 24 * 60 * 60;
        document.cookie = COOKIE_NAME + '=' + value + '; max-age=' + oneYear + '; path=/; SameSite=Lax';
    }

    @if(config('services.clarity.project_id'))
    function loadClarity() {
        if (window.clarityLoaded) return;
        window.clarityLoaded = true;

        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", "{{ config('services.clarity.project_id') }}");
    }
    @endif

    const consent = getConsent();
    if (consent === 'all') {
        @if(config('services.clarity.project_id'))
        loadClarity();
        @endif
    } else if (!consent) {
        banner.style.display = 'block';
    }

    document.getElementById('cookieAcceptAll').addEventListener('click', function () {
        setConsent('all');
        banner.style.display = 'none';
        @if(config('services.clarity.project_id'))
        loadClarity();
        @endif
    });

    document.getElementById('cookieRejectOptional').addEventListener('click', function () {
        setConsent('necessary');
        banner.style.display = 'none';
    });
})();
</script>
