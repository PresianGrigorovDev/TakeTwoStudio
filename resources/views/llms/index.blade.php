# {!! $plain($siteName) !!}

> {!! $plain($tagline) !!}. Фото и видео студио във Варна, България: абитуриентски балове, сватби (фото + 4K видео), кръщенета, рекламна, продуктова, автомобилна и архитектурна фотография, събития, дрон. Цените са в евро и са публикувани на {{ url('/ceni') }}.

@if($updatedAt)
Актуализирано: {{ $updatedAt->format('Y-m-d') }}
@endif

## Контакти
- Адрес: {!! $plain($address) !!}
- Телефон: {{ $phone }}
- Имейл: {{ $email }}
- Работно време: {{ $openingHours }}
- Район: {{ implode(', ', $areas) }} и цяла България

## Основни страници
- [Абитуриентски бал Варна {{ $season }}: пълен гид за класа]({{ url('/abiturientski-bal-varna') }}): срокове, цени на ученик, какво да изберете
- [Абитуриентски балове: фото и видео за класа]({{ url('/proms') }})
- [Сватбена фотография и видеозаснемане]({{ url('/weddings') }})
- [Кръщене]({{ url('/baptism') }})
- [Рекламна, продуктова и бизнес фотография]({{ url('/commercial') }})
- [Събития]({{ url('/events') }})
- [Автомобилна фотография]({{ url('/automotive') }})
- [Архитектурна и интериорна фотография]({{ url('/architectural') }})
- [Семейни фотосесии]({{ url('/family') }})
- [Портретна фотография]({{ url('/portrait') }})
- [Цени на всички услуги]({{ url('/ceni') }})
- [За нас и екипът]({{ url('/za-nas') }})
- [Контакти]({{ url('/kontakti') }})

@if($delivery->isNotEmpty())
## Срокове за доставка (работни дни, максимум)
@foreach($delivery as $service)
- {!! $plain($service->name_bg) !!}: до {{ $service->delivery_days }}
@endforeach
@endif
@if($stories->isNotEmpty())

## Сватби, които сме снимали
@foreach($stories as $story)
- [{!! $plain($story->title) !!}]({{ $story->url }}){!! $story->venue ? ' – '.$plain($story->venue) : '' !!}
@endforeach
@endif
@if($posts->isNotEmpty())

## Последни статии
@foreach($posts as $post)
- [{!! $plain($post->title) !!}]({{ route('blog.show', $post->slug) }})
@endforeach
@endif

## Още
- [Пълна информация: пакети, цени, екип, въпроси и отговори]({{ url('/llms-full.txt') }})
- [Блог]({{ url('/blog') }})
@if($social)

## Профили
@foreach($social as $network => $link)
- {{ ucfirst(str_replace('_', ' ', $network)) }}: {{ $link }}
@endforeach
@endif
