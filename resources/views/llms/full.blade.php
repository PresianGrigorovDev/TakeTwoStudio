# {!! $plain($siteName) !!}: пълна информация

> {!! $plain($tagline) !!}. Фото и видео студио във Варна, България. Всички цени са в евро и съвпадат с {{ url('/ceni') }}.

@if($updatedAt)
Актуализирано: {{ $updatedAt->format('Y-m-d') }}
@endif

## 1. Основни данни
- Име: {!! $plain($siteName) !!}
- Град: Варна, България; работим в {{ implode(', ', $areas) }} и в цяла България
- Адрес: {!! $plain($address) !!}
- Телефон: {{ $phone }}
- Имейл: {{ $email }}
- Уебсайт: {{ url('/') }}
- Работно време: {{ $openingHours }}
- Резервация: онлайн на страницата на всяка услуга (калкулатор) или на {{ url('/kontakti') }}

@if($delivery->isNotEmpty())
## 2. Срокове за доставка (работни дни, максимум)
@foreach($delivery as $service)
- {!! $plain($service->name_bg) !!}: до {{ $service->delivery_days }}
@endforeach
@endif

## 3. Услуги и цени
@if($pricesUpdatedAt)
Цените са актуални към {{ $pricesUpdatedAt->format('Y-m-d') }}.
@endif
@foreach($priceGroups as $group)

### {{ $group['title'] }}
Страница: {{ $group['url'] }}
@if($group['note'])
{!! $plain($group['note']) !!}
@endif
@foreach($group['packages'] as $package)
- {!! $plain($package['name']) !!}: {{ rtrim(rtrim(number_format($package['price'], 2, '.', ''), '0'), '.') }} €{{ $group['unit'] ? ' '.$group['unit'] : '' }}{!! $package['description'] ? ' – '.$plain($package['description']) : '' !!}
@foreach($package['features'] as $feature)
  - {!! $plain($feature) !!}
@endforeach
@endforeach
@if($group['extras']->isNotEmpty())
Екстри:
@foreach($group['extras'] as $extra)
- {!! $plain($extra['name']) !!}: {{ rtrim(rtrim(number_format($extra['price'], 2, '.', ''), '0'), '.') }} €
@endforeach
@endif
@endforeach

## 4. Екип
@foreach($team as $member)
- {!! $plain($member->name) !!} ({!! $plain($member->role_bg) !!}): {!! $plain($member->bio_bg) !!}
@endforeach
- Повече: {{ url('/za-nas') }}

## 5. Въпроси и отговори
@foreach($faqs as $slug => $group)

### {{ $group['title'] }}
@foreach($group['items'] as $faq)
- Въпрос: {!! $plain($faq->question) !!}
  Отговор: {!! $plain($faq->answer) !!}
@endforeach
@endforeach
@if($stories->isNotEmpty())

## 6. Сватби, които сме снимали
@foreach($stories as $story)
- {!! $plain($story->title) !!}{!! $story->event_date ? ', '.$story->event_date->format('Y-m-d') : '' !!}{!! $story->venue ? ', '.$plain($story->venue) : '' !!}{!! $story->location ? ', '.$plain($story->location) : '' !!}: {{ $story->url }}
@if($story->description)
  {!! $plain($story->description) !!}
@endif
@endforeach
@endif
@if($testimonials->isNotEmpty())

## 7. Отзиви от клиенти (публикувани на сайта)
@foreach($testimonials as $testimonial)
- {!! $plain($testimonial->client_name_bg ?? $testimonial->client_name) !!}: "{!! $plain($testimonial->content_bg) !!}"
@endforeach
@endif
@if($posts->isNotEmpty())

## 8. Статии
@foreach($posts as $post)
- {!! $plain($post->title) !!} ({{ $post->published_at?->format('Y-m-d') }}): {{ route('blog.show', $post->slug) }}
@if($post->excerpt)
  {!! $plain($post->excerpt) !!}
@endif
@endforeach
@endif
@if($social)

## 9. Профили
@foreach($social as $network => $link)
- {{ ucfirst(str_replace('_', ' ', $network)) }}: {{ $link }}
@endforeach
@endif
