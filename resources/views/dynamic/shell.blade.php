{{-- Dynamic page wrapped in the site's real captured theme chrome (header/footer/CSS). --}}
<!DOCTYPE html>
<html lang="{{ $lang }}" @if($dir) dir="{{ $dir }}" @endif class="no-js">
<head>
{!! $head !!}
</head>
<body class="{{ $bodyClass }}">
{!! $top !!}
{!! $content !!}
{!! $bottom !!}
</body>
</html>
