<!DOCTYPE html>
<html lang="{{ $htmlLang ?? 'en-US' }}"@if(!empty($htmlDir)) dir="{{ $htmlDir }}"@endif>
<head>
{!! $head !!}
</head>
<body class="{{ $bodyClass ?? '' }}">
{!! $contentHtml !!}
</body>
</html>
