{{-- Inner page title banner (indutri style) --}}
<div class="page-title-wrapper" style="background:#0c2340 url('/wp-content/uploads/2019/05/grow-gas-03.png') center/cover no-repeat;position:relative;">
    <div style="position:absolute;inset:0;background:rgba(12,35,64,.75);"></div>
    <div class="container" style="position:relative;padding:70px 15px;">
        <h1 class="heading-title" style="color:#fff;margin:0;">{{ $title }}</h1>
        @isset($crumb)
            <div class="breadcrumbs" style="color:#cfd8e3;margin-top:8px;">{!! $crumb !!}</div>
        @endisset
    </div>
</div>
