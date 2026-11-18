@if($partners->isNotEmpty())
    <section class="py-5 px-3 bg-light" aria-labelledby="brands-title">
        <div class="container text-center">
            <h2 id="brands-title" class="mb-3">Довериха ни се</h2>
            <div class="section-divider"></div>

            <ul class="brands-grid list-unstyled small text-muted mb-0" role="list">
                @foreach($partners as $partner)
                    <li class="brand-item">
                        <div class="brand-mark">
                            @if($logo = \App\Support\Images::url($partner->logo_path))
                                <x-picture :src="$logo" alt="" class="brand-logo" />
                            @endif
                        </div>
                        <span class="brand-name">{{ $partner->name }}</span>
                    </li>
                @endforeach
            </ul>

            <a class="btn-custom brands-link mt-4" href="{{ $portfolioUrl }}">
                Вижте бизнес проектите <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>
@endif
