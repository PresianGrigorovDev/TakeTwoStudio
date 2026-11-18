document.addEventListener('DOMContentLoaded', function () {
    const menu = document.getElementById('navbarNav');
    menu.addEventListener('click', function (event) {
        const link = event.target.closest('a');
        if (link?.hash && link.pathname === location.pathname && menu.classList.contains('show')) {
            bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false }).hide();
        }
    });

    const inquiryService = document.querySelector('[data-inquiry-service]');
    inquiryService?.addEventListener('click', function () {
        document.getElementById('home-service').value = inquiryService.dataset.inquiryService;
    });

    const feedback = document.querySelector('.home-feedback');
    if (feedback) {
        window.addEventListener('pageshow', function () {
            requestAnimationFrame(() => feedback.focus({ preventScroll: true }));
        }, { once: true });
    }
});
