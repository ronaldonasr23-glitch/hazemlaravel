<div id="page-content">
    @include('dynamic.partials.banner', ['title' => $post->title, 'crumb' => '<a href="/">Home</a> / <a href="/news">News</a>'])

    <div class="container" style="padding:70px 15px;">
        <div class="row">
            <div class="content-page col-xl-8 col-lg-8 col-md-12 col-sm-12" style="margin:0 auto;">
                <article class="post single-post">
                    @if($post->featured_image)
                        <div class="post-thumbnail" style="margin-bottom:34px;">
                            <img src="{{ $post->featured_image }}" alt="{{ $post->title }}" style="width:100%;border-radius:8px;">
                        </div>
                    @endif

                    <div class="entry-meta" style="color:#f05a28;font-size:13px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:14px;">
                        {{ optional($post->published_at)->format('F d, Y') }}
                        @if($post->reading_time) &middot; {{ $post->reading_time }} min read @endif
                    </div>

                    <h1 class="entry-title" style="font-size:34px;line-height:1.25;color:#0c2340;margin:0 0 26px;">{{ $post->title }}</h1>

                    <div class="entry-content" style="font-size:17px;line-height:1.8;color:#374151;">
                        <div class="content-inner">
                            {!! $post->content !!}
                        </div>
                    </div>
                </article>

                @if($related->count())
                    <div style="margin-top:60px;">
                        <h3 style="color:#0c2340;margin-bottom:24px;">Related Articles</h3>
                        <div class="row">
                            @foreach($related as $r)
                                <div class="col-md-4 col-sm-6" style="margin-bottom:24px;">
                                    <a href="/{{ $r->slug }}" style="color:#0c2340;font-weight:600;">{{ \Illuminate\Support\Str::limit($r->title, 60) }}</a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
