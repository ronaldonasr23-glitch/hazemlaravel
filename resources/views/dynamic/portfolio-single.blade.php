<div id="page-content">
    @include('dynamic.partials.banner', ['title' => $portfolio->title, 'crumb' => '<a href="/">Home</a> / <a href="/portfolio">Projects</a>'])

    <div class="container" style="padding:70px 15px;">
        <div class="row">
            <div class="col-xl-10 col-lg-10" style="margin:0 auto;">
                @if($portfolio->featured_image)
                    <div class="post-thumbnail" style="margin-bottom:34px;">
                        <img src="{{ $portfolio->featured_image }}" alt="{{ $portfolio->title }}" style="width:100%;border-radius:8px;">
                    </div>
                @endif
                <h1 class="entry-title" style="font-size:32px;color:#0c2340;margin:0 0 24px;">{{ $portfolio->title }}</h1>
                @if($portfolio->client || $portfolio->category)
                    <div style="color:#6b7280;margin-bottom:20px;">
                        @if($portfolio->client) <strong>Client:</strong> {{ $portfolio->client }} @endif
                        @if($portfolio->category) &nbsp; <strong>Category:</strong> {{ $portfolio->category }} @endif
                    </div>
                @endif
                <div class="entry-content" style="font-size:17px;line-height:1.8;color:#374151;">
                    {!! $portfolio->content !!}
                </div>
            </div>
        </div>
    </div>
</div>
