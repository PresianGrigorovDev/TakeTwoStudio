@extends('pdf.contracts._layout')

@php
    // Helper: show value or blank line for handwriting
    if (!function_exists('pdfBlank')) {
        function pdfBlank($value, $class = 'blank') {
            $val = is_string($value) ? trim($value) : '';
            return $val !== '' ? e($val) : '<span class="' . $class . '">&nbsp;</span>';
        }
    }
@endphp

@section('content')
    <div class="contract-title">Договор за сватбено фото и видео заснемане</div>
    <div class="contract-subtitle">
        {{ \Carbon\Carbon::parse($contractDate)->format('d.m.Y') }} г.
    </div>

    <p>Днес, {{ \Carbon\Carbon::parse($contractDate)->format('d.m.Y') }} г., между:</p>

    {{-- ИЗПЪЛНИТЕЛ --}}
    <div class="party-info">
        <strong>{{ count($executors) > 1 ? 'ИЗПЪЛНИТЕЛИ' : 'ИЗПЪЛНИТЕЛ' }}:</strong>
        {{ $data['studio_name'] ?? 'Take Two Studio 1603' }}
        @if(!empty($data['studio_bulstat'])), БУЛСТАТ: {{ $data['studio_bulstat'] }}@endif
        @if(!empty($data['studio_address'])), Адрес: {{ $data['studio_address'] }}@endif
        @if(!empty($data['studio_mol'])), представлявано от {{ $data['studio_mol'] }}@endif
        <br><br>
        <strong>Изпълняващи:</strong>
        @foreach($executors as $executor)
            {{ $executor->name }}<br>
            @if($executor->egn)ЕГН: {{ $executor->egn }}<br>@endif
            @if($executor->phone)Тел.: {{ $executor->phone }}<br>@endif
            @if($executor->email)Имейл: {{ $executor->email }}<br>@endif
            @if($executor->address)Адрес: {{ $executor->address }}<br>@endif
            @if(!$loop->last)<br>@endif
        @endforeach
    </div>

    <p class="text-center">и</p>

    {{-- ВЪЗЛОЖИТЕЛ 1 --}}
    <div class="party-info">
        <strong>ВЪЗЛОЖИТЕЛ 1:</strong><br>
        Три имена: {!! pdfBlank($data['client1_name'] ?? '', 'blank-long') !!}<br>
        ЕГН: {!! pdfBlank($data['client1_egn'] ?? '', 'blank-short') !!}<br>
        Адрес: {!! pdfBlank($data['client1_address'] ?? '', 'blank-long') !!}<br>
        Тел.: {!! pdfBlank($data['client1_phone'] ?? '') !!}
    </div>

    {{-- ВЪЗЛОЖИТЕЛ 2 --}}
    <div class="party-info">
        <strong>ВЪЗЛОЖИТЕЛ 2:</strong><br>
        Три имена: {!! pdfBlank($data['client2_name'] ?? '', 'blank-long') !!}<br>
        ЕГН: {!! pdfBlank($data['client2_egn'] ?? '', 'blank-short') !!}<br>
        Адрес: {!! pdfBlank($data['client2_address'] ?? '', 'blank-long') !!}<br>
        Тел.: {!! pdfBlank($data['client2_phone'] ?? '') !!}
    </div>

    <p>се сключи настоящият договор за следното:</p>

    {{-- Секции --}}
    @php $sectionNum = 1; @endphp

    @if(!empty($sections['subject']))
        <div class="section-title">{{ $sectionNum++ }}. Предмет на договора</div>
        <div class="section-text">{{ $sections['subject'] }}</div>
    @endif

    {{-- Детайли на събитието --}}
    <div class="section-title">{{ $sectionNum++ }}. Дата, локации и програма</div>
    <table>
        <tr><th style="width:180px;">Дата на събитието</th><td>{!! !empty($data['event_date']) ? \Carbon\Carbon::parse($data['event_date'])->format('d.m.Y') : pdfBlank('', 'blank') !!}</td></tr>
        <tr><th>Подготовка</th><td>{!! pdfBlank($data['preparation_time'] ?? '') !!}, {!! pdfBlank($data['preparation_location'] ?? '', 'blank-long') !!}</td></tr>
        <tr><th>Церемония</th><td>{!! pdfBlank($data['ceremony_time'] ?? '') !!}, {!! pdfBlank($data['ceremony_location'] ?? '', 'blank-long') !!}</td></tr>
        <tr><th>Фотосесия</th><td>{!! pdfBlank($data['photoshoot_time'] ?? '') !!}, {!! pdfBlank($data['photoshoot_location'] ?? '', 'blank-long') !!}</td></tr>
        <tr><th>Ресторант / Тържество</th><td>{!! pdfBlank($data['reception_time'] ?? '') !!}, {!! pdfBlank($data['reception_location'] ?? '', 'blank-long') !!}</td></tr>
        <tr><th>Краен час</th><td>{!! pdfBlank($data['end_time'] ?? '') !!}</td></tr>
    </table>

    @if(!empty($sections['scope_photo']))
        <div class="section-title">{{ $sectionNum++ }}. Обхват Фото</div>
        <div class="section-text">{{ $sections['scope_photo'] }}</div>
    @endif

    @if(!empty($sections['scope_video']))
        <div class="section-title">{{ $sectionNum++ }}. Обхват Видео</div>
        <div class="section-text">{{ $sections['scope_video'] }}</div>
    @endif

    @if(!empty($sections['deadlines']))
        <div class="section-title">{{ $sectionNum++ }}. Срокове за предаване</div>
        <div class="section-text">{{ $sections['deadlines'] }}</div>
    @endif

    {{-- Цена --}}
    <div class="section-title">{{ $sectionNum++ }}. Възнаграждение</div>
    <table>
        <tr><th style="width:180px;">Обща цена</th><td class="text-bold">{!! pdfBlank($data['total_price'] ?? '') !!} {{ $data['currency'] ?? 'EUR' }}</td></tr>
        <tr><th>Капаро</th><td>{!! pdfBlank($data['deposit_amount'] ?? '') !!} {{ $data['currency'] ?? 'EUR' }}</td></tr>
        <tr><th>Остатък</th><td>{!! pdfBlank($data['remaining_amount'] ?? '') !!} {{ $data['currency'] ?? 'EUR' }}</td></tr>
        @if(!empty($data['payment_method']))
        <tr><th>Начин на плащане</th><td>{{ match($data['payment_method']) { 'cash' => 'В брой', 'bank_transfer' => 'По банков път', 'mixed' => 'Смесено', default => $data['payment_method'] } }}</td></tr>
        @endif
    </table>

    @if(!empty($data['services']))
        <table>
            <tr><th>Услуга</th><th style="width:100px;" class="text-right">Цена</th></tr>
            @foreach($data['services'] as $service)
                @if(!empty($service['description']))
                <tr>
                    <td>{{ $service['description'] }}</td>
                    <td class="text-right">{{ $service['price'] ?? '' }} {{ $data['currency'] ?? 'EUR' }}</td>
                </tr>
                @endif
            @endforeach
        </table>
    @endif

    @if(!empty($sections['payment']))
        <div class="section-title">{{ $sectionNum++ }}. Условия за плащане</div>
        <div class="section-text">{{ $sections['payment'] }}</div>
    @endif

    @if(!empty($sections['transport']))
        <div class="section-title">{{ $sectionNum++ }}. Транспорт и пътни разходи</div>
        <div class="section-text">{{ $sections['transport'] }}</div>
    @endif

    @if(!empty($sections['overtime']))
        <div class="section-title">{{ $sectionNum++ }}. Овъртайм</div>
        <div class="section-text">{{ $sections['overtime'] }}</div>
    @endif

    @if(!empty($sections['cancellation']))
        <div class="section-title">{{ $sectionNum++ }}. Отказ и неустойки</div>
        <div class="section-text">{{ $sections['cancellation'] }}</div>
    @endif

    @if(!empty($sections['delays']))
        <div class="section-title">{{ $sectionNum++ }}. Закъснения</div>
        <div class="section-text">{{ $sections['delays'] }}</div>
    @endif

    @if(!empty($sections['raw_materials']))
        <div class="section-title">{{ $sectionNum++ }}. RAW материали</div>
        <div class="section-text">{{ $sections['raw_materials'] }}</div>
    @endif

    @if(!empty($sections['copyright']))
        <div class="section-title">{{ $sectionNum++ }}. Права и авторство</div>
        <div class="section-text">{{ $sections['copyright'] }}</div>
    @endif

    @if(!empty($sections['limitations']))
        <div class="section-title">{{ $sectionNum++ }}. Ограничения</div>
        <div class="section-text">{{ $sections['limitations'] }}</div>
    @endif

    @if(!empty($sections['work_conditions']))
        <div class="section-title">{{ $sectionNum++ }}. Условия на работа</div>
        <div class="section-text">{{ $sections['work_conditions'] }}</div>
    @endif

    @if(!empty($sections['force_majeure']))
        <div class="section-title">{{ $sectionNum++ }}. Форсмажор</div>
        <div class="section-text">{{ $sections['force_majeure'] }}</div>
    @endif

    @if(!empty($sections['general']))
        <div class="section-title">{{ $sectionNum++ }}. Общи клаузи</div>
        <div class="section-text">{{ $sections['general'] }}</div>
    @endif

    {{-- Банкови данни --}}
    @if(!empty($data['studio_iban']))
    <div class="section-title">Банкови данни на Изпълнителя</div>
    <table>
        <tr><th style="width:180px;">IBAN</th><td>{{ $data['studio_iban'] }}</td></tr>
        @if(!empty($data['studio_bank']))
        <tr><th>Банка</th><td>{{ $data['studio_bank'] }}</td></tr>
        @endif
    </table>
    @endif

    @if(!empty($data['event_notes']))
    <div class="section-title">Допълнителни бележки</div>
    <div class="section-text">{{ $data['event_notes'] }}</div>
    @endif
@endsection
