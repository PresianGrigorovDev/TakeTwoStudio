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

    @if(config('services.google_analytics.measurement_id'))
    // Google Analytics is loaded ONLY after the visitor accepts optional cookies
    // (Consent Mode v2, "basic" implementation: nothing is sent before consent).
    function grantAnalyticsConsent() {
        if (window.gaLoaded) return;
        window.gaLoaded = true;

        var gaId = @json(config('services.google_analytics.measurement_id'));
        window.dataLayer = window.dataLayer || [];
        window.gtag = window.gtag || function () { window.dataLayer.push(arguments); };
        gtag('consent', 'default', {
            'ad_storage': 'granted',
            'ad_user_data': 'granted',
            'ad_personalization': 'granted',
            'analytics_storage': 'granted'
        });
        gtag('js', new Date());
        gtag('config', gaId, { 'anonymize_ip': true });

        var tag = document.createElement('script');
        tag.async = true;
        tag.src = 'https://www.googletagmanager.com/gtag/js?id=' + encodeURIComponent(gaId);
        document.head.appendChild(tag);
    }
    @endif

    const consent = getConsent();
    if (consent === 'all') {
        @if(config('services.clarity.project_id'))
        loadClarity();
        @endif
        @if(config('services.google_analytics.measurement_id'))
        grantAnalyticsConsent();
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
        @if(config('services.google_analytics.measurement_id'))
        grantAnalyticsConsent();
        @endif
    });

    document.getElementById('cookieRejectOptional').addEventListener('click', function () {
        setConsent('necessary');
        banner.style.display = 'none';
    });
})();
</script>
