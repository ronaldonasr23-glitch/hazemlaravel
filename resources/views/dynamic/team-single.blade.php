<div id="page-content">
    @include('dynamic.partials.banner', ['title' => $member->name, 'crumb' => '<a href="/">Home</a> / <a href="/volunteer">Team</a>'])

    <div class="container" style="padding:70px 15px;">
        <div class="row">
            <div class="col-xl-4 col-lg-4 col-md-5">
                <img src="{{ $member->photo ?: '/wp-content/uploads/2019/05/grow-gas-03.png' }}" alt="{{ $member->name }}" style="width:100%;border-radius:8px;">
            </div>
            <div class="col-xl-8 col-lg-8 col-md-7">
                <h1 style="font-size:32px;color:#0c2340;margin:0 0 6px;">{{ $member->name }}</h1>
                @if($member->position)<div style="color:#f05a28;font-weight:600;margin-bottom:20px;">{{ $member->position }}</div>@endif
                <div style="font-size:17px;line-height:1.8;color:#374151;">{!! $member->bio !!}</div>
                @php($socials = array_filter($member->socials ?? []))
                @if($socials)
                    <div style="margin-top:22px;">
                        @foreach($socials as $net => $url)
                            <a href="{{ $url }}" target="_blank" rel="noopener" style="color:#0c2340;margin-right:16px;text-transform:capitalize;">{{ $net }}</a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>
