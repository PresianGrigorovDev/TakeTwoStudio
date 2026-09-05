{{-- Google Maps facade: no third-party request until the visitor asks for the map. --}}
@php
    $mapQuery = $mapQuery ?? 'Take+Two+Studio+1603+Varna';
    $mapSrc = 'https://maps.google.com/maps?q=' . $mapQuery . '&t=&z=15&ie=UTF8&iwloc=&output=embed';
    $mapId = 'map-' . substr(md5($mapQuery . ($mapLabel ?? '')), 0, 8);
@endphp
<div class="map-container map-facade" id="{{ $mapId }}" data-map-src="{{ $mapSrc }}">
    <div class="map-facade__inner">
        <i class="fas fa-map-marker-alt fa-2x mb-2" aria-hidden="true"></i>
        <p class="mb-1 fw-bold">{{ $mapLabel ?? \App\Support\Settings::address() }}</p>
        <button type="button" class="btn btn-outline-dark btn-sm rounded-0 map-facade__btn">Покажи картата</button>
        <p class="small text-muted mt-2 mb-0"><a href="https://www.google.com/maps/search/?api=1&query={{ $mapQuery }}" target="_blank" rel="noopener">Отвори в Google Maps</a></p>
    </div>
</div>
@once
    @push('scripts')
    <script>
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.map-facade__btn');
            if (!btn) return;
            var box = btn.closest('.map-facade');
            var iframe = document.createElement('iframe');
            iframe.src = box.getAttribute('data-map-src');
            iframe.title = 'Take Two Studio 1603 на картата';
            iframe.setAttribute('allowfullscreen', '');
            iframe.loading = 'lazy';
            box.innerHTML = '';
            box.appendChild(iframe);
        });
    </script>
    @endpush
@endonce
