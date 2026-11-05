@php
    $ar = $quote['language'] !== 'en';
    $en = $quote['language'] !== 'ar';
    $label = static function (string $english, string $arabic) use ($en, $ar): string {
        return $en && $ar ? "$english / $arabic" : ($ar ? $arabic : $english);
    };
@endphp
<!doctype html>
<html lang="{{ $en ? 'en' : 'ar' }}" dir="{{ $en ? 'ltr' : 'rtl' }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>{{ $label('Quotation', 'عرض سعر') }} {{ $quote['reference'] }}</title>
<style>
@font-face{font-family:QuoteArabic;src:url(data:font/ttf;base64,{{ $fontBase64 }}) format('truetype');unicode-range:U+0600-06FF,U+0750-077F,U+08A0-08FF,U+FB50-FDFF,U+FE70-FEFF}
*{box-sizing:border-box}body{margin:0;background:#eef0f3;color:#182235;font:14px/1.55 QuoteArabic,Arial,sans-serif}
.sheet{max-width:820px;margin:22px auto;background:white;padding:44px 48px;overflow-wrap:anywhere}
.top{display:flex;justify-content:space-between;gap:20px;align-items:flex-start;border-top:5px solid #264c72;padding-top:24px;margin-bottom:26px}
.logo{display:block;max-width:130px;max-height:55px;margin-bottom:10px;object-fit:contain}h1{margin:0;font-size:34px;line-height:1.28;letter-spacing:-.5px}p{margin:4px 0}.sub{color:#50627a}.parties{display:grid;grid-template-columns:1fr 1fr;gap:16px;margin:0 0 25px}.party{border:1px solid #dce2e9;padding:14px 16px}
table{width:100%;border-collapse:collapse}th,td{padding:11px 9px;border-bottom:1px solid #dce2e9;text-align:start;vertical-align:top}
th{background:#eaf0f7;font-size:12px}.num{text-align:end;white-space:nowrap;font-variant-numeric:tabular-nums}.total{margin:16px 0 24px auto;width:max-content;padding:12px 16px;border:1px solid #264c72;break-inside:avoid}.notes{margin-top:24px;white-space:pre-wrap}
.toolbar{max-width:820px;margin:16px auto 0;padding:0 16px;display:flex;justify-content:space-between;align-items:center;gap:12px}.toolbar a,.toolbar button{color:#173c64;font:inherit}.toolbar button{border:1px solid #173c64;background:#fff;padding:7px 12px;border-radius:5px;cursor:pointer}
@page{size:A4;margin:13mm}
@media(max-width:600px){.sheet{padding:20px 14px}.top{flex-wrap:wrap}h1{font-size:26px}th,td{padding:7px 4px;font-size:11px}}
@media print{body{background:white;-webkit-print-color-adjust:exact;print-color-adjust:exact}.toolbar{display:none}.sheet{margin:0;padding:0;max-width:none}thead{display:table-header-group}tr,.top,.parties{break-inside:avoid}}
</style>
</head>
<body>
@if($preview ?? false)
<nav class="toolbar" dir="ltr" aria-label="Preview actions">
    <a href="{{ route('demo') }}">← Back to demo</a>
    <button type="button" onclick="window.print()">Print or save as PDF</button>
</nav>
@endif
<main class="sheet">
    <header class="top">
        <div>
            @if($logoBase64)
                <img class="logo" src="data:image/png;base64,{{ $logoBase64 }}" alt="">
            @endif
            <h1>{{ $label('Quotation', 'عرض سعر') }}</h1>
            <p class="sub" dir="ltr">{{ $quote['reference'] }}</p>
        </div>
        <div>
            <p>{{ $label('Issued', 'تاريخ الإصدار') }} <bdi>{{ $quote['issued_on'] }}</bdi></p>
            @if(!empty($quote['valid_until']))
                <p>{{ $label('Valid until', 'صالح حتى') }} <bdi>{{ $quote['valid_until'] }}</bdi></p>
            @endif
        </div>
    </header>
    <section class="parties">
        <div class="party"><div class="sub">{{ $label('Prepared by', 'مقدم العرض') }}</div><strong dir="auto">{{ $quote['seller'] }}</strong></div>
        <div class="party"><div class="sub">{{ $label('Prepared for', 'مقدم إلى') }}</div><strong dir="auto">{{ $quote['customer'] }}</strong></div>
    </section>
    <table>
        <thead><tr>
            <th scope="col">{{ $label('Description', 'الوصف') }}</th>
            <th scope="col" class="num">{{ $label('Qty', 'الكمية') }}</th>
            <th scope="col" class="num">{{ $label('Unit price', 'سعر الوحدة') }}</th>
            <th scope="col" class="num">{{ $label('Amount', 'المبلغ') }}</th>
        </tr></thead>
        <tbody>
        @foreach($quote['items'] as $item)
            <tr>
                <td><bdi>{{ $item['description'] }}</bdi></td>
                <td class="num"><bdi>{{ $item['quantity'] }}</bdi></td>
                <td class="num"><bdi>{{ \App\Services\Quotation::money($item['unit_minor']) }}</bdi></td>
                <td class="num"><bdi>{{ \App\Services\Quotation::money($item['amount_minor']) }}</bdi></td>
            </tr>
        @endforeach
        </tbody>
    </table>
    <p class="total">{{ $label('Total', 'الإجمالي') }} <strong dir="ltr">{{ $quote['currency'] }} {{ \App\Services\Quotation::money($quote['total_minor']) }}</strong></p>
    @if(!empty($quote['notes']))
        <div class="notes" dir="auto">{{ $quote['notes'] }}</div>
    @endif
</main>
</body>
</html>
