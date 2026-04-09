<!DOCTYPE html>
<html lang="bg">
<head>
    <meta charset="UTF-8">
    <style>
        @page {
            margin: 2cm 2.5cm;
        }
        body {
            font-family: 'DejaVu Sans', sans-serif;
            font-size: 11pt;
            line-height: 1.6;
            color: #1a1a1a;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #d97706;
            padding-bottom: 10px;
        }
        .header img {
            max-width: 150px;
            margin-bottom: 5px;
        }
        .header .studio-name {
            font-size: 10pt;
            color: #666;
        }
        .contract-title {
            font-size: 15pt;
            font-weight: bold;
            text-align: center;
            margin: 20px 0 5px;
            text-transform: uppercase;
        }
        .contract-subtitle {
            text-align: center;
            font-size: 10pt;
            color: #666;
            margin-bottom: 20px;
        }
        .section-title {
            font-weight: bold;
            font-size: 11pt;
            margin-top: 18px;
            margin-bottom: 5px;
            border-bottom: 1px solid #d97706;
            padding-bottom: 3px;
            text-transform: uppercase;
        }
        .party-info {
            margin: 8px 0;
            padding: 8px 10px;
            background-color: #fef9ee;
            border-left: 3px solid #d97706;
        }
        .party-info strong {
            display: block;
            margin-bottom: 3px;
        }
        p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 8px 0;
        }
        table td, table th {
            padding: 5px 8px;
            border: 1px solid #ddd;
            font-size: 10pt;
            vertical-align: top;
        }
        table th {
            background-color: #fef9ee;
            text-align: left;
            font-weight: bold;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .text-bold {
            font-weight: bold;
        }
        .section-text {
            white-space: pre-line;
            margin: 5px 0 10px;
        }
        .blank {
            display: inline-block;
            min-width: 200px;
            border-bottom: 1px dotted #333;
            padding-bottom: 1px;
        }
        .blank-short {
            display: inline-block;
            min-width: 120px;
            border-bottom: 1px dotted #333;
            padding-bottom: 1px;
        }
        .blank-long {
            display: inline-block;
            min-width: 320px;
            border-bottom: 1px dotted #333;
            padding-bottom: 1px;
        }
        .signatures {
            margin-top: 50px;
            page-break-inside: avoid;
        }
        .signature-block {
            margin-bottom: 30px;
        }
        .signature-line {
            border-top: 1px solid #333;
            width: 300px;
            margin-top: 40px;
            padding-top: 5px;
            font-size: 10pt;
        }
        .page-break {
            page-break-after: always;
        }
    </style>
</head>
<body>
    <div class="header">
        @php
            $blackLogoPath = public_path('css/img/logo-tts-black.png');
            $logoExists = file_exists($blackLogoPath);
            $logoBase64 = $logoExists ? base64_encode(file_get_contents($blackLogoPath)) : null;
        @endphp
        @if($logoBase64)
            <img src="data:image/png;base64,{{ $logoBase64 }}" alt="Take Two Studio">
            <br>
        @endif
        <span class="studio-name">{{ $data['studio_name'] ?? 'Take Two Studio 1603' }}</span>
    </div>

    @yield('content')

    @include('pdf.contracts._signatures')
</body>
</html>
