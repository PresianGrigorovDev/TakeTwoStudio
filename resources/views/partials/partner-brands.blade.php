@if($partners->isNotEmpty())
    <section class="brands-section" aria-labelledby="brands-title">
        <div class="brands-inner">
            <header class="brands-heading">
                <h2 id="brands-title">Довериха <span>ни се</span></h2>
                <a class="brands-link" href="{{ $portfolioUrl }}">
                    Вижте бизнес проектите <span aria-hidden="true">→</span>
                </a>
            </header>

            <ul class="brands-grid" role="list">
                @foreach($partners as $partner)
                    <li class="brand-card">
                        @if($logo = \App\Support\Images::url($partner->logo_path))
                            <div class="brand-mark">
                                <x-picture :src="$logo" alt="" class="brand-logo" />
                            </div>
                        @endif
                        <span class="brand-name">{{ $partner->name }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </section>
@endif
