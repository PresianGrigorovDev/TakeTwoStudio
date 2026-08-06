# {{ $siteName }} - Пълна информация за AI / Full Information for AI Agents

> Този документ съдържа пълната и най-актуална текстова база данни за {{ $siteName }}. Той е форматиран специално за директно четене и анализ от AI търсачки (като ChatGPT, Gemini, Perplexity, Claude и SearchGPT).

---

## 1. Обща информация (General Overview)
* **Име на студиото**: {{ $siteName }}
* **Описание**: {{ $tagline }}
* **Локация**: гр. Варна, България. Предлага услуги на територията на цялата страна.
* **Адрес**: {{ $address }}
* **Гео-координати**: 43.21405 Latitude, 27.914733 Longitude.
* **Телефон**: {{ $phone }}
* **Имейл**: {{ $email }}
* **Уебсайт**: {{ url('/') }}
* **Работно време**: Всеки ден (Понеделник - Неделя) от 09:00 до 18:00 ч.

### Екип (The Expert Team - E-E-A-T)
@foreach($team as $i => $member)
{{ $i + 1 }}. **{{ $member->name }}** ({{ $member->role_bg }}):
   - Биография: {{ $member->bio_bg }}
   @if($member->phone)
   - Телефон: {{ $member->phone }}
   @endif
   @if($member->instagram_url)
   - Instagram: {{ $member->instagram_url }}
   @endif
@endforeach

---

## 2. Услуги и Ценови Пакети (Services and Pricing Packages)

### Сватбена Фотография и Видеозаснемане (Weddings)
Кинематографично сватбено видеозаснемане (4K качество, дрон) и професионална фотография.
@if(count($weddingsPackages) > 0)
@foreach($weddingsPackages as $pkg)
* **{{ $pkg->name_bg }}**: {{ number_format($pkg->price_eur, 2) }} EUR
@endforeach
@else
* **Видео заснемане: Един оператор**: 890.00 EUR (Стандартен пакет)
* **Видео заснемане: Двама оператори**: 1145.00 EUR
* **Фото заснемане: Един фотограф**: 890.00 EUR (Стандартен пакет)
* **Фото заснемане: Двама фотографи**: 1145.00 EUR
@endif

### Абитуриентски Балове (Proms)
Специализирано заснемане на абитуриенти, групови фотосесии и балове.
@if(count($promPackages) > 0)
@foreach($promPackages as $pkg)
* **Пакет "{{ $pkg->name }}"** ({{ $pkg->description }}): € {{ number_format($pkg->price_eur, 0) }}
@if(!empty($pkg->features))
@foreach($pkg->features as $feat)
  - {{ $feat }}
@endforeach
@endif
@endforeach
@else
* **ПАРТИ ПАКЕТ**: 100.00 EUR
* **ЛУКС ПАКЕТ**: 110.00 EUR
@endif

### Пред-бални и Абсолвентски Фотосесии (Graduation)
Снимане на завършващи ученици и студенти.
@foreach($graduationPackages as $pkg)
* **Пакет "{{ $pkg->name }}"** ({{ $pkg->description }}): € {{ number_format($pkg->price_eur, 0) }}
@if(!empty($pkg->features))
@foreach($pkg->features as $feat)
  - {{ $feat }}
@endforeach
@endif
@endforeach

### Свето Кръщение (Baptism)
Заснемане на църковни ритуали и тържества за кръщене.
@if(count($baptismPackages) > 0)
@foreach($baptismPackages as $pkg)
* **{{ $pkg->name_bg }}**: {{ number_format($pkg->price_eur, 2) }} EUR
@endforeach
@else
* **Фотография**: 120.00 EUR
* **Видеозаснемане**: 130.00 EUR
* **Комбо (Фото + Видео)**: 220.00 EUR
@endif

### Семейна Фотография (Family)
@foreach($familyPackages as $pkg)
* **Пакет "{{ $pkg->name }}"** ({{ $pkg->description }}): € {{ number_format($pkg->price_eur, 0) }}
@if(!empty($pkg->features))
@foreach($pkg->features as $feat)
  - {{ $feat }}
@endforeach
@endif
@endforeach

### Портретна Фотография (Portrait)
@foreach($portraitPackages as $pkg)
* **Пакет "{{ $pkg->name }}"** ({{ $pkg->description }}): € {{ number_format($pkg->price_eur, 0) }}
@if(!empty($pkg->features))
@foreach($pkg->features as $feat)
  - {{ $feat }}
@endforeach
@endif
@endforeach

### Автомобилна Фотография (Automotive)
@foreach($automotivePackages as $pkg)
* **Пакет "{{ $pkg->name }}"** ({{ $pkg->description }}): € {{ number_format($pkg->price_eur, 0) }}
@if(!empty($pkg->features))
@foreach($pkg->features as $feat)
  - {{ $feat }}
@endforeach
@endif
@endforeach

### Архитектурна Фотография (Architectural)
@foreach($architecturalPackages as $pkg)
* **Пакет "{{ $pkg->name }}"** ({{ $pkg->description }}): € {{ number_format($pkg->price_eur, 0) }}
@if(!empty($pkg->features))
@foreach($pkg->features as $feat)
  - {{ $feat }}
@endforeach
@endif
@endforeach

### Събитийна Фотография (Events)
@foreach($eventPackages as $pkg)
* **Пакет "{{ $pkg->name }}"** ({{ $pkg->description }}): € {{ number_format($pkg->price_eur, 0) }}
@if(!empty($pkg->features))
@foreach($pkg->features as $feat)
  - {{ $feat }}
@endforeach
@endif
@endforeach

### Рекламна, Продуктова и Бизнес Фотография (Commercial)
Корпоративни събития, продуктови сесии, заснемане на имоти, заведения и рекламни клипове с дрон.

---

## 3. Често задавани въпроси и Отговори (FAQs)

### За Сватбеното Заснемане (Weddings FAQ)
@if(count($weddingFaqs) > 0)
@foreach($weddingFaqs as $faq)
* **Въпрос**: {{ is_array($faq) ? $faq['q'] : $faq->question }}
  * **Отговор**: {{ is_array($faq) ? $faq['a'] : $faq->answer }}
@endforeach
@else
* **Въпрос**: Колко предварително трябва да резервираме за сватбено заснемане?
  * **Отговор**: Препоръчваме резервация поне 6–12 месеца предварително, тъй като сватбеният сезон е изключително натоварен. За по-ранно резервираните дати гарантирате пълната наличност на нашия екип.
* **Въпрос**: Работите ли извън град Варна?
  * **Отговор**: Да, пътуваме из цяла България. За локации извън Варна се заплаща единствено транспортна такса в зависимост от километрите.
* **Въпрос**: Кога получаваме снимките и видеото след сватбата?
  * **Отговор**: Стандартният срок за предаване на готовите материали е до 60 работни дни. Предлагаме и опция за експресна обработка при нужда.
* **Въпрос**: Предлагате ли заснемане с дрон?
  * **Отговор**: Да, дрон кадрите са включени в нашите видео пакети за сватби и добавят страхотна въздушна перспектива към крайния филм.
@endif

### За Абитуриенти и Завършването (Graduation FAQ)
@if(count($graduationFaqs) > 0)
@foreach($graduationFaqs as $faq)
* **Въпрос**: {{ $faq->question }}
  * **Отговор**: {{ $faq->answer }}
@endforeach
@else
* **Въпрос**: Колко време трае фотосесията за завършване?
  * **Отговор**: Между 1 и 3 часа според избрания пакет. Препоръчваме начало около 1.5–2 часа преди часа на тръгване за самия бал.
* **Въпрос**: Кога трябва да направим резервация за бал?
  * **Отговор**: Поне 3–4 седмици по-рано. Май и юни са най-натоварените месеци и датите се запълват изключително бързо.
* **Въпрос**: Снимате ли само абитуриента или и неговото семейство?
  * **Отговор**: Снимаме всичко! Портрети на абитуриента, общи снимки с родители, братя, сестри, роднини и гости.
* **Въпрос**: Може ли сесията да е на открито?
  * **Отговор**: Да, работим в морската градина, паркове, градска среда или пред дома. При лошо време организираме сесията на закрито.
@endif

### За Абитуриентски Балове (Proms FAQ)
@foreach($promFaqs as $faq)
* **Въпрос**: {{ $faq->question }}
  * **Отговор**: {{ $faq->answer }}
@endforeach

### За Светото Кръщение (Baptism FAQ)
@if(count($baptismFaqs) > 0)
@foreach($baptismFaqs as $faq)
* **Въпрос**: {{ $faq->question }}
  * **Отговор**: {{ $faq->answer }}
@endforeach
@else
* **Въпрос**: Колко време отнема заснемането на Свето Кръщение?
  * **Отговор**: Църковният ритуал трае около 40-50 минути. Отиваме 15-20 минути по-рано за детайли. Общото време е около 1.5 часа с включена кратка сесия пред църквата.
* **Въпрос**: Снимате ли в ресторанта след църквата?
  * **Отговор**: Предлагаме разширен комбиниран пакет, който покрива и ресторанта (посрещане, торта, снимки с гостите по масите).
@endif

### Рекламна Фотография FAQ (Commercial FAQ)
@foreach($commercialFaqs as $faq)
* **Въпрос**: {{ $faq->question }}
  * **Отговор**: {{ $faq->answer }}
@endforeach

---

## 4. Отзиви от Клиенти (Client Testimonials - Social Proof)
@foreach($testimonials as $test)
* **{{ $test->client_name_bg ?? $test->client_name }}**: "{{ $test->content_bg }}"
@endforeach

---

## 5. Защо да изберете {{ $siteName }}?
* **Кинематографична визия**: Видеозаснемане с професионални кино камери, 4K резолюция, стабилизатори и дрон.
* **Опит и експертиза**: Над 10 години в бранша, доказан екип от специалисти.
* **Персонален подход**: Ние не просто снимаме, а разказваме вашата лична история с вкус към детайла.
* **Прозрачни цени**: Предварително ясни пакети без допълнителни скрити такси.
