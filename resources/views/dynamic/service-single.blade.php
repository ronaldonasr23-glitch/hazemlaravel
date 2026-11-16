<div id="page-content">
    @include('dynamic.partials.banner', ['title' => $service->title, 'crumb' => '<a href="/">Home</a> / <a href="/service-3-2-2">Services</a>'])

    <div class="container" style="padding:70px 15px;">
        <div class="row">
            <div class="col-xl-8 col-lg-8 col-md-12" style="margin:0 auto;">
                @if($service->featured_image)
                    <div class="post-thumbnail" style="margin-bottom:34px;">
                        <img src="{{ $service->featured_image }}" alt="{{ $service->title }}" style="width:100%;border-radius:8px;">
                    </div>
                @endif
                <h1 class="entry-title" style="font-size:32px;color:#0c2340;margin:0 0 24px;">{{ $service->title }}</h1>
                <div class="entry-content" style="font-size:17px;line-height:1.8;color:#374151;">
                    {!! $service->content !!}
                </div>
            </div>
        </div>

        @if($others->count())
            <div style="margin-top:60px;">
                <h3 style="color:#0c2340;margin-bottom:24px;">Other Services</h3>
                <div class="row">
                    @foreach($others as $o)
                        <div class="col-md-3 col-sm-6" style="margin-bottom:16px;">
                            <a href="/service/{{ $o->slug }}" style="color:#0c2340;font-weight:600;">{{ \Illuminate\Support\Str::limit($o->title, 50) }}</a>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</div>
