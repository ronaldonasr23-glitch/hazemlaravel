<div id="page-content">
    @include('dynamic.partials.banner', ['title' => 'News', 'crumb' => '<a href="/">Home</a> / News'])

    <div class="container" style="padding:70px 15px;">
        <div class="row">
            @foreach($posts as $post)
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12" style="margin-bottom:40px;">
                    <article class="blog-item" style="box-shadow:0 10px 30px rgba(0,0,0,.06);border-radius:8px;overflow:hidden;background:#fff;height:100%;">
                        <a href="/{{ $post->slug }}" class="blog-thumb" style="display:block;">
                            <img src="{{ $post->featured_image ?: '/wp-content/uploads/2019/05/grow-gas-03.png' }}"
                                 alt="{{ $post->title }}" style="width:100%;height:220px;object-fit:cover;">
                        </a>
                        <div style="padding:26px 24px;">
                            <div class="entry-meta" style="color:#f05a28;font-size:13px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
                                {{ optional($post->published_at)->format('F d, Y') }}
                            </div>
                            <h3 class="entry-title" style="font-size:20px;line-height:1.35;margin:0 0 14px;">
                                <a href="/{{ $post->slug }}" style="color:#0c2340;">{{ $post->title }}</a>
                            </h3>
                            <p style="color:#6b7280;font-size:15px;">{{ \Illuminate\Support\Str::limit($post->excerpt, 120) }}</p>
                            <a href="/{{ $post->slug }}" style="color:#f05a28;font-weight:600;">Read More &rarr;</a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>

        <div style="margin-top:20px;">
            {!! $posts->links() !!}
        </div>
    </div>
</div>
