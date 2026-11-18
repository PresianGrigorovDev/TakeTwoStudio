@if($partners->isNotEmpty())
    <section class="brands-section" aria-labelledby="brands-title">
        <div class="brands-inner">
            <h2 id="brands-title">Довериха ни се</h2>

            <ul class="brands-grid" role="list">
                @foreach($partners as $partner)
                    <li class="brand-item">
                        @if($logo = \App\Support\Images::url($partner->logo_path))
                            <div class="brand-mark">
                                <x-picture :src="$logo" alt="" class="brand-logo" />
                            </div>
                        @endif
                        <span class="brand-name">{{ $partner->name }}</span>
                    </li>
                @endforeach
            </ul>

            <a class="brands-link" href="{{ $portfolioUrl }}">
                Вижте бизнес проектите <span aria-hidden="true">→</span>
            </a>
        </div>
    </section>
@endif
