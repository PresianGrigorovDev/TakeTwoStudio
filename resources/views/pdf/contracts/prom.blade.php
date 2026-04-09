@extends('pdf.contracts._layout')

@php
    if (!function_exists('pdfBlank')) { function pdfBlank($value, $class = 'blank') {
        $val = is_string($value) ? trim($value) : '';
        return $val !== '' ? e($val) : '<span class="' . $class . '">&nbsp;</span>';
    } }
@endphp

@section('content')
    <div class="contract-title">Договор за фото заснемане на абитуриентски бал</div>
    <div class="contract-subtitle">
        {{ \Carbon\Carbon::parse($contractDate)->format('d.m.Y') }} г.
    </div>

    <p>Днес, {{ \Carbon\Carbon::parse($contractDate)->format('d.m.Y') }} г., между:</p>

    {{-- ИЗПЪЛНИТЕЛ --}}
    <div class="party-info">
        <strong>ИЗПЪЛНИТЕЛ:</strong>
        {{ $data['studio_name'] ?? 'Take Two Studio 1603' }}
        @if(!empty($data['studio_bulstat'])), БУЛСТАТ: {{ $data['studio_bulstat'] }}@endif
        @if(!empty($data['studio_address'])), Адрес: {{ $data['studio_address'] }}@endif
        <br><br>
        <strong>Изпълняващи:</strong>
        @foreach($executors as $executor)
            {{ $executor->name }}<br>
            @if($executor->phone)Тел.: {{ $executor->phone }}<br>@endif
            @if(!$loop->last)<br>@endif
        @endforeach
    </div>

    <p class="text-center">и</p>

    {{-- ВЪЗЛОЖИТЕЛ --}}
    <div class="party-info">
        <strong>ВЪЗЛОЖИТЕЛ:</strong><br>
        Три имена: {!! pdfBlank($data['client1_name'] ?? '', 'blank-long') !!}<br>
        ЕГН: {!! pdfBlank($data['client1_egn'] ?? '', 'blank-short') !!}<br>
        Адрес: {!! pdfBlank($data['client1_address'] ?? '', 'blank-long') !!}<br>
        Тел.: {!! pdfBlank($data['client1_phone'] ?? '') !!}
        Имейл: {!! pdfBlank($data['client1_email'] ?? '') !!}
    </div>

    <p>се сключи настоящият договор за следното:</p>

    @php $sectionNum = 1; @endphp

    @if(!empty($sections['subject']))
        <div class="section-title">{{ $sectionNum++ }}. Предмет на договора</div>
        <div class="section-text">{{ $sections['subject'] }}</div>
    @endif

    {{-- Детайли --}}
    <div class="section-title">{{ $sectionNum++ }}. Дата и локация</div>
    <table>
        <tr><th style="width:180px;">Дата на бала</th><td>{!! !empty($data['event_date']) ? \Carbon\Carbon::parse($data['event_date'])->format('d.m.Y') : pdfBlank('', 'blank') !!}</td></tr>
        <tr><th>Хотел / Локация</th><td>{!! pdfBlank($data['ceremony_location'] ?? '', 'blank-long') !!}</td></tr>
        <tr><th>Начален час</th><td>{!! pdfBlank($data['ceremony_time'] ?? '') !!}</td></tr>
        <tr><th>Краен час</th><td>{!! pdfBlank($data['end_time'] ?? '') !!}</td></tr>
    </table>

    @if(!empty($sections['scope_photo']))
        <div class="section-title">{{ $sectionNum++ }}. Обхват на услугата</div>
        <div class="section-text">{{ $sections['scope_photo'] }}</div>
    @endif

    @if(!empty($sections['deadlines']))
        <div class="section-title">{{ $sectionNum++ }}. Срокове за предаване</div>
        <div class="section-text">{{ $sections['deadlines'] }}</div>
    @endif

    {{-- Цена --}}
    <div class="section-title">{{ $sectionNum++ }}. Възнаграждение</div>
    <table>
        <tr><th style="width:180px;">Цена на пакета</th><td class="text-bold">{!! pdfBlank($data['total_price'] ?? '') !!} {{ $data['currency'] ?? 'EUR' }}</td></tr>
        <tr><th>Капаро (50%)</th><td>{!! pdfBlank($data['deposit_amount'] ?? '') !!} {{ $data['currency'] ?? 'EUR' }}</td></tr>
        <tr><th>Остатък</th><td>{!! pdfBlank($data['remaining_amount'] ?? '') !!} {{ $data['currency'] ?? 'EUR' }}</td></tr>
    </table>

    @if(!empty($sections['payment']))
        <div class="section-title">{{ $sectionNum++ }}. Условия за плащане</div>
        <div class="section-text">{{ $sections['payment'] }}</div>
    @endif

    @if(!empty($sections['obligations']))
        <div class="section-title">{{ $sectionNum++ }}. Права и задължения</div>
        <div class="section-text">{{ $sections['obligations'] }}</div>
    @endif

    @if(!empty($sections['cancellation']))
        <div class="section-title">{{ $sectionNum++ }}. Отговорност и санкции</div>
        <div class="section-text">{{ $sections['cancellation'] }}</div>
    @endif

    @if(!empty($sections['general']))
        <div class="section-title">{{ $sectionNum++ }}. Общи клаузи</div>
        <div class="section-text">{{ $sections['general'] }}</div>
    @endif

    {{-- Банкови данни --}}
    <div class="section-title">Банкови данни на Изпълнителя</div>
    <table>
        <tr><th style="width:180px;">IBAN</th><td>{!! pdfBlank($data['studio_iban'] ?? '', 'blank-long') !!}</td></tr>
        <tr><th>Банка</th><td>{!! pdfBlank($data['studio_bank'] ?? '', 'blank-long') !!}</td></tr>
    </table>

    @if(!empty($data['event_notes']))
    <div class="section-title">Допълнителни бележки</div>
    <div class="section-text">{{ $data['event_notes'] }}</div>
    @endif
@endsection
