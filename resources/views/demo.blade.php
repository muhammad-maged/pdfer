<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>PDFer — Quotation preview</title>
<style>
*{box-sizing:border-box}body{margin:0;background:#f3f5f7;color:#19243a;font:16px/1.55 system-ui,sans-serif}
main{max-width:820px;margin:0 auto;padding:36px 20px}header{padding:24px 0}
.brand{font-size:14px;font-weight:750;letter-spacing:.09em;color:#255182}h1{margin:.3em 0;font-size:clamp(28px,5vw,42px);line-height:1.15}p{color:#485a70}
form{border:1px solid #dce3eb;background:#fff;padding:20px;border-radius:12px;box-shadow:0 10px 30px #20334b0a}label{display:block;font-weight:700;margin-bottom:8px}
textarea{display:block;width:100%;min-height:380px;border:1px solid #aabacc;border-radius:7px;background:#f9fbfd;padding:12px;font:13px/1.55 ui-monospace,monospace;color:#19243a;resize:vertical}
button{margin-top:16px;background:#173d68;color:#fff;border:0;border-radius:7px;padding:10px 16px;font:inherit;font-weight:650;cursor:pointer}
.help{margin:12px 0 0;font-size:14px}.errors{color:#8f2731;background:#fff1f2;border-radius:6px;padding:12px 12px 12px 32px}
</style>
</head>
<body>
<main>
    <header>
        <div class="brand">PDFer</div>
        <h1>Arabic and English quotations</h1>
        <p>Edit the sample JSON, preview your quotation, and use your browser to print or save it as a PDF.</p>
    </header>
    <form method="post" action="{{ route('preview') }}">
        @csrf
        <label for="payload">Quotation JSON</label>
        @if($errors->any())
            <ul class="errors" role="alert">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        @endif
        <textarea id="payload" name="payload" maxlength="30000" spellcheck="false" aria-describedby="demo-help" required>{{ old('payload', $sample) }}</textarea>
        <p class="help" id="demo-help">Use fictional data here. The preview uses browser printing. The pilot API generates PDFs on the server.</p>
        <button type="submit">Preview quotation</button>
    </form>
</main>
</body>
</html>
