@php
    $rtl = $post->isRtl();
    $img = $post->featured_image;
    $date = optional($post->published_at)->locale($rtl ? 'ar' : 'en')->translatedFormat('j F Y');
    $shareUrl = rawurlencode(url($post->url()));
    $shareTxt = rawurlencode($post->title);
    $L = fn ($en, $ar) => $rtl ? $ar : $en;
@endphp

@include('dynamic.partials.blog-css')
<div id="page-content" class="cms-article" @if($rtl) dir="rtl" @endif style="background:#fff;{{ $rtl ? 'text-align:right;' : '' }}">

    {{-- Blurred-thumbnail hero with the article title --}}
    <section class="article-hero" style="position:relative;overflow:hidden;min-height:320px;display:flex;align-items:center;">
        @if($img)
            <div style="position:absolute;inset:0;background:url('{{ $img }}') center/cover no-repeat;filter:blur(16px) brightness(.5);transform:scale(1.12);"></div>
        @else
            <div style="position:absolute;inset:0;background:linear-gradient(135deg,#0c2340,#13457a);"></div>
        @endif
        <div style="position:absolute;inset:0;background:linear-gradient(180deg,rgba(9,20,40,.55),rgba(9,20,40,.80));"></div>
        <div class="container" style="position:relative;z-index:2;padding:62px 15px;text-align:center;color:#fff;">
            <nav style="font-size:13px;letter-spacing:.3px;opacity:.92;margin-bottom:16px;">
                <a href="/" style="color:#fff;text-decoration:none;">{{ $L('Home', 'الرئيسية') }}</a>
                <span style="margin:0 8px;opacity:.55;">/</span>
                <a href="{{ $rtl ? '/ar/blogs/' : '/blogs/' }}" style="color:#fff;text-decoration:none;">{{ $L('Blog', 'المقالات') }}</a>
            </nav>
            <h1 style="font-size:clamp(25px,4vw,41px);line-height:1.28;margin:0 auto;max-width:900px;font-weight:700;color:#fff;">{{ $post->title }}</h1>
            <div style="margin-top:16px;font-size:13px;opacity:.92;text-transform:uppercase;letter-spacing:.6px;">
                {{ $date }}@if($post->reading_time) &nbsp;&middot;&nbsp; {{ $post->reading_time }} {{ $L('min read', 'دقيقة قراءة') }}@endif
            </div>
        </div>
    </section>

    {{-- Main column + sidebar --}}
    <div class="cms-wrap" style="padding:58px 15px;">
        <div class="article-body">
            <article class="article-main">
                @if($img)
                    <div style="margin-bottom:30px;">
                        <img src="{{ $img }}" alt="{{ $post->title }}" style="width:100%;border-radius:10px;display:block;">
                    </div>
                @endif

                <div class="entry-content" style="font-size:17px;line-height:1.9;color:#374151;{{ $rtl ? 'text-align:right;direction:rtl;' : '' }}">
                    {!! $post->content !!}
                </div>

                {{-- Share row --}}
                <div style="margin-top:42px;border-top:1px solid #eef0f3;padding-top:22px;display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
                    <span style="font-weight:700;color:#0c2340;">{{ $L('Share', 'شارك') }}</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="Facebook" style="width:38px;height:38px;border-radius:50%;background:#1877f2;color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">f</a>
                    <a href="https://twitter.com/intent/tweet?url={{ $shareUrl }}&text={{ $shareTxt }}" target="_blank" rel="noopener" aria-label="X" style="width:38px;height:38px;border-radius:50%;background:#0f1419;color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">X</a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" target="_blank" rel="noopener" aria-label="LinkedIn" style="width:38px;height:38px;border-radius:50%;background:#0a66c2;color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">in</a>
                    <a href="https://wa.me/?text={{ $shareTxt }}%20{{ $shareUrl }}" target="_blank" rel="noopener" aria-label="WhatsApp" style="width:38px;height:38px;border-radius:50%;background:#25d366;color:#fff;display:inline-flex;align-items:center;justify-content:center;text-decoration:none;">w</a>
                </div>

                @if($related->count())
                    <div style="margin-top:56px;">
                        <h3 style="color:#0c2340;margin-bottom:22px;font-size:22px;">{{ $L('Related Articles', 'مقالات ذات صلة') }}</h3>
                        <div class="cms-related">
                            @foreach($related as $r)
                                <a href="{{ $r->url() }}" style="display:block;text-decoration:none;">
                                    <div style="height:140px;border-radius:8px;overflow:hidden;background:#eef1f5 url('{{ $r->featured_image }}') center/cover;"></div>
                                    <span style="display:block;margin-top:12px;color:#0c2340;font-weight:600;font-size:15px;line-height:1.4;">{{ \Illuminate\Support\Str::limit($r->title, 70) }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </article>

            <aside class="article-aside">
                @include('dynamic.partials.blog-sidebar', ['recent' => $recent, 'rtl' => $rtl])
            </aside>
        </div>
    </div>
</div>
