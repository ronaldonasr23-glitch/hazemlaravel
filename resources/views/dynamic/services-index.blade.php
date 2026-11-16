<div id="page-content">
    @include('dynamic.partials.banner', ['title' => 'Our Services', 'crumb' => '<a href="/">Home</a> / Services'])

    <div class="container" style="padding:70px 15px;">
        <div class="row">
            @foreach($services as $service)
                <div class="col-xl-4 col-lg-4 col-md-6 col-sm-12" style="margin-bottom:30px;">
                    <article class="service-item" style="border:1px solid #eef1f5;border-radius:8px;overflow:hidden;background:#fff;height:100%;transition:.3s;">
                        <a href="/service/{{ $service->slug }}" style="display:block;">
                            <img src="{{ $service->featured_image ?: '/wp-content/uploads/2019/05/grow-gas-03.png' }}"
                                 alt="{{ $service->title }}" style="width:100%;height:200px;object-fit:cover;">
                        </a>
                        <div style="padding:26px 24px;">
                            <h3 style="font-size:19px;margin:0 0 12px;"><a href="/service/{{ $service->slug }}" style="color:#0c2340;">{{ $service->title }}</a></h3>
                            <p style="color:#6b7280;font-size:15px;">{{ \Illuminate\Support\Str::limit(strip_tags($service->excerpt), 110) }}</p>
                            <a href="/service/{{ $service->slug }}" style="color:#f05a28;font-weight:600;">Read More &rarr;</a>
                        </div>
                    </article>
                </div>
            @endforeach
        </div>
    </div>
</div>
