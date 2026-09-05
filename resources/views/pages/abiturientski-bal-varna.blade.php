@extends('layouts.app')

@section('title', 'Абитуриентски бал Варна ' . $season . ': гид за класа | Take Two Studio')
@section('meta_description', 'Абитуриентски бал Варна ' . $season . ': кога са баловете и изпращането, срокове за зала, фотограф и DJ, бюджет на ученик и какво да питате фотографа.')
@section('og_title', 'Абитуриентски бал Варна ' . $season . ' – пълен гид за класа')
@section('og_description', 'Дати, срокове, бюджет на ученик и как да изберете фотограф и видео за бала във Варна.')
@section('og_image', asset('css/img/Бал.jpeg'))

@section('content')
    <section class="page-hero">
        <div class="container">
            <h1>{{ $text->get('hero', 'title', "Абитуриентски бал Варна {$season}: пълен гид за класа") }}</h1>
            <p class="page-hero__subtitle">{{ $text->get('hero', 'subtitle', 'Дати, срокове, бюджет на ученик и как да изберете фотограф и видео') }}</p>
        </div>
    </section>
    @include('partials.breadcrumbs')

    <article class="py-5 px-1">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-9 guide-body">
                    <p class="lead answer-capsule">{{ $text->get('intro', 'capsule', "Баловете на випуск {$season} във Варна са в края на май и през юни, а изпращането от училище е обикновено около 24 май. Класовете, които искат добра зала и добър екип за снимки и видео, резервират между септември и февруари. Този гид събира на едно място какво да планирате, в какъв ред и с какъв бюджет на ученик, с примери от практиката на Take Two Studio 1603, фото и видео студио във Варна, което заснема балове за цели класове.") }}</p>

                    <h2 id="dati">{{ $text->get('dates', 'title', "Кога е балът и изпращането във Варна през {$season}") }}</h2>
                    <p>{{ $text->get('dates', 'text', 'Датата на бала се определя от училището и класа, обикновено след като залата потвърди свободна вечер. Във Варна баловете са концентрирани в последната седмица на май и първите две седмици на юни, което означава, че най-търсените зали, фотографи и DJ имат по една свободна дата за десетки класове. Изпращането (последният учебен ден за 12 клас) е традиционно около 24 май, с канене на класния ръководител и събиране пред училището, което също се заснема.') }}</p>

                    <h2 id="srokove">{{ $text->get('timeline', 'title', 'Срокове: в какъв ред да резервирате') }}</h2>
                    <ol class="process-list">
                        <li><strong>Септември – октомври:</strong> {{ $text->get('timeline', 'step1', 'зала и дата. Без потвърдена дата не могат да се резервират останалите доставчици.') }}</li>
                        <li><strong>Септември – февруари:</strong> {{ $text->get('timeline', 'step2', 'фотограф и видео. Екипите, които работят с цели класове, запълват датите в края на май рано; през есента е и първата фотосесия на класа.') }}</li>
                        <li><strong>Ноември – март:</strong> {{ $text->get('timeline', 'step3', 'DJ или музика, декорация, автомобили за автошествието, тоалети.') }}</li>
                        <li><strong>Април – май:</strong> {{ $text->get('timeline', 'step4', 'пролетна фотосесия на класа, финален сценарий на вечерта, списък с моментите, които трябва да бъдат заснети.') }}</li>
                    </ol>

                    <h2 id="byudzhet">{{ $text->get('budget', 'title', 'Бюджет за фото и видео на ученик') }}</h2>
                    @if($packages->isNotEmpty())
                        <p>{{ $text->get('budget', 'intro', 'Най-удобният модел за класовете е фиксирана цена на ученик, която покрива целия випуск: канене на класния, изпращане, балната вечер и фотосесиите на класа. Актуалните пакети на Take Two Studio 1603:') }}</p>
                        <div class="table-responsive">
                            <table class="table price-table align-middle">
                                <thead><tr><th scope="col">Пакет</th><th scope="col" class="text-end">Цена на ученик</th><th scope="col">Какво включва</th></tr></thead>
                                <tbody>
                                    @foreach($packages as $package)
                                        <tr @if($package->is_featured) class="price-table__featured" @endif>
                                            <th scope="row">{{ $package->name }}</th>
                                            <td class="text-end text-nowrap"><strong>{{ number_format($package->price_eur, 0, ',', ' ') }} €</strong></td>
                                            <td class="small">{{ trim(strip_tags($package->description ?? '')) }}
                                                @if(is_array($package->features) && $package->features)
                                                    <ul class="mb-0 ps-3 mt-1">@foreach($package->features as $f)<li>{{ is_array($f) ? implode(' ', $f) : $f }}</li>@endforeach</ul>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <p class="small text-muted">Пълните цени и екстрите са на страницата <a href="{{ url('/ceni#abiturienti') }}">Цени</a>; калкулаторът на <a href="{{ url('/proms#calculator') }}">страницата за балове</a> дава сума за конкретния клас.</p>
                    @else
                        <p>{{ $text->get('budget', 'fallback', 'Класовете най-често плащат фиксирана сума на ученик за целия випуск (канене, изпращане, бал и фотосесии). Актуалните ни пакети са на страницата за балове и в раздел Цени.') }}</p>
                    @endif
                    @if($sessions->isNotEmpty())
                        <p>{{ $text->get('budget', 'sessions', 'Отделно от класа, много абитуриенти правят индивидуална или семейна фотосесия в деня на бала или дни преди него. Тези сесии са с отделни цени и се резервират лично.') }}</p>
                    @endif

                    <h2 id="vaprosi">{{ $text->get('checklist', 'title', 'Какво да питате фотографа и видеооператора') }}</h2>
                    <ul class="trust-list">
                        <li>{{ $text->get('checklist', 'q1', 'Един екип ли снимате и видео, и снимки, или ще има две отделни фирми в ресторанта?') }}</li>
                        <li>{{ $text->get('checklist', 'q2', 'Включени ли са каненето на класния и изпращането, или са допълнително?') }}</li>
                        <li>{{ $text->get('checklist', 'q3', 'Колко фотосесии на класа са включени и на кои локации във Варна?') }}</li>
                        <li>{{ $text->get('checklist', 'q4', 'Има ли дрон и има ли пилотът право да лети над училище и град?') }}</li>
                        <li>{{ $text->get('checklist', 'q5', 'Кога и как се доставят снимките и клипът: онлайн галерия, флашка, срок?') }}</li>
                        <li>{{ $text->get('checklist', 'q6', 'Има ли договор, депозит и какво става при промяна на датата?') }}</li>
                        <li>{{ $text->get('checklist', 'q7', 'Може ли да покажете пълен клип и галерия от бал на цял клас, а не само избрани кадри?') }}</li>
                        <li>{{ $text->get('checklist', 'q8', 'Кой е контактът в деня на бала и колко души от екипа ще бъдат в залата?') }}</li>
                    </ul>

                    <h2 id="denyat">{{ $text->get('day', 'title', 'Как протича балната вечер с нас') }}</h2>
                    <p>{{ $text->get('day', 'text', 'Започваме преди тръгването: индивидуални и семейни кадри у дома или на избрана локация, после автошествието и пристигането пред залата, официалната част, първия танц и партито до уговорения час. Дронът лети при позволено време и зона, най-често при събирането пред залата и по крайбрежието. След вечерта селектираме и обработваме кадрите, монтираме клипа и даваме достъп на класа до онлайн галерия.') }}</p>

                    <p class="mt-4"><a href="{{ url('/proms') }}" class="btn-custom-full me-2">Пакети и калкулатор за бал</a> <a href="{{ url('/booking') }}" class="btn-custom">Запази дата за {{ $season }}</a></p>
                </div>
            </div>
        </div>
    </article>

    @include('partials.faq-section', ['faqs' => $faqs, 'title' => 'Често задавани въпроси за бал ' . $season])
@endsection
