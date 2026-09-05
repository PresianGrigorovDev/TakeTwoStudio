@php $seoCrumbs = app(\App\Support\Seo\Seo::class)->breadcrumbs(); @endphp
@if(count($seoCrumbs) > 1)
    <nav aria-label="breadcrumb" class="breadcrumb-bar">
        <div class="container">
            <ol class="breadcrumb mb-0">
                @foreach($seoCrumbs as $crumb)
                    @if(!$loop->last && !empty($crumb['url']))
                        <li class="breadcrumb-item"><a href="{{ $crumb['url'] }}">{{ $crumb['name'] }}</a></li>
                    @else
                        <li class="breadcrumb-item active" aria-current="page">{{ $crumb['name'] }}</li>
                    @endif
                @endforeach
            </ol>
        </div>
    </nav>
@endif
