<div id="page-content">
    @include('dynamic.partials.banner', ['title' => 'Our Projects', 'crumb' => '<a href="/">Home</a> / Projects'])

    <div class="container" style="padding:70px 15px;">
        <div class="row">
            @foreach($portfolios as $portfolio)
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12" style="margin-bottom:30px;">
                    <article class="portfolio-item" style="border-radius:8px;overflow:hidden;background:#fff;box-shadow:0 10px 30px rgba(0,0,0,.06);height:100%;">
                        <a href="/portfolio/{{ $portfolio->slug }}" style="display:block;">
                            <img src="{{ $portfolio->featured_image ?: '/wp-content/uploads/2019/05/grow-gas-03.png' }}"
                                 alt="{{ $portfolio->title }}" style="width:100%;height:230px;object-fit:cover;">
                        </a>
                        <div style="padding:24px;">
                            @if($portfolio->category)<div style="color:#f05a28;font-size:12px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:8px;">{{ $portfolio->category }}</div>@endif
                            <h3 style="font-size:19px;margin:0;"><a href="/portfolio/{{ $portfolio->slug }}" style="color:#0c2340;">{{ $portfolio->title }}</a></h3>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</div>
