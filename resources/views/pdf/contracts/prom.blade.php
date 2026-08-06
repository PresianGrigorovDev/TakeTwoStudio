@extends('pdf.contracts._layout')

@php
    if (!function_exists('pdfBlank')) { function pdfBlank($value, $class = 'blank') {
        $val = is_string($value) ? trim($value) : '';
        return $val !== '' ? e($val) : '<span class="' . $class . '">&nbsp;</span>';
    } }

    $totalEur   = (float) ($data['total_price'] ?? 0);
    $depositEur = (float) ($data['deposit_amount'] ?? 0);
    $remainEur  = (float) ($data['remaining_amount'] ?? 0);

    function fmtAmt(float $val): string {
        return $val > 0 ? number_format($val, 2, '.', '') : '';
    }
@endphp

@section('content')
    <div class="contract-title">Договор за фото заснемане на абитуриентски бал</div>
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

    <p>се сключи настоящият договор за следното:</p>

    @php $sectionNum = 1; @endphp

    @if(!empty($sections['subject']))
        <div class="section-title">{{ $sectionNum++ }}. Предмет на договора</div>
        <div class="section-text">{{ $sections['subject'] }}</div>
    @endif

    {{-- Дата и локация --}}
    <div class="section-title">{{ $sectionNum++ }}. Дата и локация</div>
    <table>
        <tr>
            <th style="width:180px;">Дата на бала</th>
            <td>{!! !empty($data['event_date']) ? \Carbon\Carbon::parse($data['event_date'])->format('d.m.Y') . ' г.' : pdfBlank('', 'blank') !!}</td>
        </tr>
        <tr>
            <th>Хотел / Локация</th>
            <td>{!! pdfBlank($data['prom_venue'] ?? '', 'blank-long') !!}</td>
        </tr>
        <tr>
            <th>Начален час</th>
            <td>{!! pdfBlank($data['start_time'] ?? '', 'blank') !!}</td>
        </tr>
    </table>

    @if(!empty($sections['scope_photo']))
        <div class="section-title">{{ $sectionNum++ }}. Обхват на услугата</div>
        <div class="section-text">{{ $sections['scope_photo'] }}</div>
    @endif

    @if(!empty($sections['deadlines']))
        <div class="section-title">{{ $sectionNum++ }}. Срокове за предаване</div>
        <div class="section-text">{{ $sections['deadlines'] }}</div>
    @endif

    {{-- Възнаграждение: EUR --}}
    <div class="section-title">{{ $sectionNum++ }}. Възнаграждение и условия за плащане</div>
    <table>
        <tr>
            <th style="width:220px;">Показател</th>
            <th style="width:140px; text-align:center;">EUR (Евро)</th>
        </tr>
        <tr>
            <th>Обща цена на пакета</th>
            <td class="text-bold text-center">{!! $totalEur > 0 ? fmtAmt($totalEur) : pdfBlank('', 'blank') !!}</td>
        </tr>
        <tr>
            <th>Капаро (50%)</th>
            <td class="text-center">{!! $depositEur > 0 ? fmtAmt($depositEur) : pdfBlank('', 'blank') !!}</td>
        </tr>
        <tr>
            <th>Остатък</th>
            <td class="text-center">{!! $remainEur > 0 ? fmtAmt($remainEur) : pdfBlank('', 'blank') !!}</td>
        </tr>
    </table>

    @if(!empty($sections['payment']))
        <div class="section-text">{{ $sections['payment'] }}</div>
    @endif

    @if(!empty($sections['transport']))
        <div class="section-title">{{ $sectionNum++ }}. Транспорт и пътни разходи</div>
        <div class="section-text">{{ $sections['transport'] }}</div>
    @endif

    @if(!empty($sections['obligations_executor']))
        <div class="section-title">{{ $sectionNum++ }}. Задължения на Изпълнителя</div>
        <div class="section-text">{{ $sections['obligations_executor'] }}</div>
    @endif

    @if(!empty($sections['obligations_client']))
        <div class="section-title">{{ $sectionNum++ }}. Задължения на Възложителя</div>
        <div class="section-text">{{ $sections['obligations_client'] }}</div>
    @endif

    @if(!empty($sections['cancellation']))
        <div class="section-title">{{ $sectionNum++ }}. Отказ и неустойки</div>
        <div class="section-text">{{ $sections['cancellation'] }}</div>
    @endif

    @if(!empty($sections['limitations']))
        <div class="section-title">{{ $sectionNum++ }}. Ограничения на отговорността</div>
        <div class="section-text">{{ $sections['limitations'] }}</div>
    @endif

    @if(!empty($sections['copyright']))
        <div class="section-title">{{ $sectionNum++ }}. Права на страните</div>
        <div class="section-text">{{ $sections['copyright'] }}</div>
    @endif

    @if(!empty($sections['force_majeure']))
        <div class="section-title">{{ $sectionNum++ }}. Форсмажорни обстоятелства</div>
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

@section('annex')
<div class="page-break"></div>

{{-- Annex header --}}
<div style="text-align:center; margin-bottom:15px;">
    <div style="font-size:13pt; font-weight:bold; text-transform:uppercase; border-bottom:2px solid #d97706; padding-bottom:6px;">
        ЦЕНОРАЗПИС С ИЗВЪНРЕДНИ И ДОПЪЛНИТЕЛНИ РАЗХОДИ
    </div>
    <div style="font-size:9pt; color:#666; margin-top:4px;">
        Приложение към Договор за фото заснемане на абитуриентски бал
    </div>
</div>

{{-- Reference row --}}
<table style="margin-bottom:12px;">
    <tr>
        <th style="width:140px;">Дата на договора</th>
        <td>{{ \Carbon\Carbon::parse($contractDate)->format('d.m.Y') }} г.</td>
        <th style="width:120px;">Изпълнители</th>
        <td>{{ $executors->pluck('name')->implode(', ') }}</td>
    </tr>
    <tr>
        <th>Възложител</th>
        <td colspan="3">{!! pdfBlank($data['client1_name'] ?? '', 'blank-long') !!}</td>
    </tr>
</table>

{{-- Pricing table --}}
<table>
    <tr>
        <th style="width:280px;">Описание</th>
        <th style="width:120px; text-align:center;">EUR (Евро)</th>
        <th>Бележки</th>
    </tr>
    <tr>
        <th>Капаро (50% от цената на пакета)</th>
        <td class="text-center">{!! $depositEur > 0 ? fmtAmt($depositEur) : pdfBlank('', 'blank') !!}</td>
        <td style="font-size:9pt;">платено при подписване</td>
    </tr>
    <tr>
        <th>Базова цена на фотоалбум</th>
        <td class="text-center">&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    <tr>
        <th>Разходи за гориво (0.50 EUR на км)</th>
        <td class="text-center">&nbsp;</td>
        <td style="font-size:9pt;">изчислява се след събитието</td>
    </tr>
    {{-- 5 empty rows for additional expenses --}}
    @for($i = 0; $i < 5; $i++)
    <tr>
        <td>&nbsp;</td>
        <td class="text-center">&nbsp;</td>
        <td>&nbsp;</td>
    </tr>
    @endfor
</table>

{{-- Compact signatures for annex --}}
<table style="width: 100%; margin-top: 40px; border: none; border-collapse: collapse;">
    <tr>
        <td style="width: 50%; vertical-align: top; border: none; padding: 0 20px 0 0;">
            <strong>ВЪЗЛОЖИТЕЛ:</strong>
            <div style="margin-top: 6px;">
                {{ !empty($data['client1_name']) ? $data['client1_name'] : '' }}
                <div style="border-top: 1px solid #333; width: 220px; margin-top: 30px; padding-top: 4px; font-size: 9pt;">подпис</div>
            </div>
        </td>
        <td style="width: 50%; vertical-align: top; border: none; padding: 0 0 0 20px;">
            <strong>{{ count($executors) > 1 ? 'ИЗПЪЛНИТЕЛИ' : 'ИЗПЪЛНИТЕЛ' }}:</strong>
            @foreach($executors as $executor)
            <div style="margin-top: 6px; {{ !$loop->first ? 'margin-top: 25px;' : '' }}">
                {{ $executor->name }}
                <div style="border-top: 1px solid #333; width: 220px; margin-top: 30px; padding-top: 4px; font-size: 9pt;">подпис</div>
            </div>
            @endforeach
        </td>
    </tr>
</table>
@endsection
