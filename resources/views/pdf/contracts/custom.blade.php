@extends('pdf.contracts._layout')

@php
    if (!function_exists('pdfBlank')) { function pdfBlank($value, $class = 'blank') {
        $val = is_string($value) ? trim($value) : '';
        return $val !== '' ? e($val) : '<span class="' . $class . '">&nbsp;</span>';
    } }
@endphp

@section('content')
    <div class="contract-title">Договор</div>
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

    {{-- ВЪЗЛОЖИТЕЛ --}}
    <div class="party-info">
        <strong>ВЪЗЛОЖИТЕЛ:</strong><br>
        Три имена: {!! pdfBlank($data['client1_name'] ?? '', 'blank-long') !!}<br>
        ЕГН: {!! pdfBlank($data['client1_egn'] ?? '', 'blank-short') !!}<br>
        Адрес: {!! pdfBlank($data['client1_address'] ?? '', 'blank-long') !!}<br>
        Тел.: {!! pdfBlank($data['client1_phone'] ?? '') !!}
    </div>

    {{-- Фирмени данни (ако има) --}}
    @if(!empty($data['company_name']) || !empty($data['company_bulstat']))
    <div class="party-info">
        <strong>Фирма / Организация:</strong><br>
        Наименование: {!! pdfBlank($data['company_name'] ?? '', 'blank-long') !!}<br>
        БУЛСТАТ/ЕИК: {!! pdfBlank($data['company_bulstat'] ?? '', 'blank-short') !!}<br>
        Адрес: {!! pdfBlank($data['company_address'] ?? '', 'blank-long') !!}<br>
        МОЛ: {!! pdfBlank($data['company_mol'] ?? '', 'blank-long') !!}
    </div>
    @endif

    <p>се сключи настоящият договор за следното:</p>

    @php $sectionNum = 1; @endphp

    @if(!empty($sections['subject']))
        <div class="section-title">{{ $sectionNum++ }}. Предмет на договора</div>
        <div class="section-text">{{ $sections['subject'] }}</div>
    @endif

    {{-- Детайли --}}
    <div class="section-title">{{ $sectionNum++ }}. Дата и локация</div>
    <table>
        <tr><th style="width:180px;">Дата на събитието</th><td>{!! !empty($data['event_date']) ? \Carbon\Carbon::parse($data['event_date'])->format('d.m.Y') : pdfBlank('', 'blank') !!}</td></tr>
        <tr><th>Локация</th><td>{!! pdfBlank($data['ceremony_location'] ?? '', 'blank-long') !!}</td></tr>
        <tr><th>Начален час</th><td>{!! pdfBlank($data['ceremony_time'] ?? '') !!}</td></tr>
        <tr><th>Краен час</th><td>{!! pdfBlank($data['end_time'] ?? '') !!}</td></tr>
    </table>

    @if(!empty($sections['scope']))
        <div class="section-title">{{ $sectionNum++ }}. Обхват на услугата</div>
        <div class="section-text">{{ $sections['scope'] }}</div>
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
    </table>

    @php
        $customSectionLabels = [
            'payment' => 'Условия за плащане',
            'transport' => 'Транспорт и пътни разходи',
            'overtime' => 'Овъртайм',
            'obligations_executor' => 'Задължения на Изпълнителя',
            'obligations_client' => 'Задължения на Възложителя',
            'cancellation' => 'Отказ и неустойки',
            'copyright' => 'Права и авторство',
            'limitations' => 'Ограничения на отговорността',
            'force_majeure' => 'Форсмажорни обстоятелства',
            'general' => 'Общи клаузи',
        ];
    @endphp
    @foreach($customSectionLabels as $sKey => $sLabel)
        @if(!empty($sections[$sKey]))
            <div class="section-title">{{ $sectionNum++ }}. {{ $sLabel }}</div>
            <div class="section-text">{{ $sections[$sKey] }}</div>
        @endif
    @endforeach

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
