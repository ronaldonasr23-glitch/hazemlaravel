@php
    $rtl = $rtl ?? false;
    $L = fn ($en, $ar) => $rtl ? $ar : $en;
    $q = trim((string) request('s'));
@endphp

@include('dynamic.partials.blog-css')
<div id="page-content" class="cms-article cms-blog-archive" @if($rtl) dir="rtl" @endif style="background:#fff;{{ $rtl ? 'text-align:right;' : '' }}">

    {{-- Archive header --}}
    <section style="position:relative;background:linear-gradient(135deg,#0c2340,#13457a);color:#fff;">
        <div class="container" style="padding:56px 15px;text-align:center;">
            <nav style="font-size:13px;opacity:.9;margin-bottom:14px;">
                <a href="/" style="color:#fff;text-decoration:none;">{{ $L('Home', 'الرئيسية') }}</a>
                <span style="margin:0 8px;opacity:.55;">/</span>{{ $L('Blog', 'المقالات') }}
            </nav>
            <h1 style="font-size:clamp(26px,4vw,40px);margin:0;color:#fff;font-weight:700;">
                {{ $q !== '' ? $L('Search: ', 'نتائج البحث: ') . $q : $L('Latest Articles', 'أحدث المقالات') }}
            </h1>
        </div>
    </section>

    <div class="cms-wrap" style="padding:56px 15px;">
        @if($posts->count())
            <div class="cms-grid">
                @foreach($posts as $post)
                    @php $cardRtl = $post->isRtl(); @endphp
                    <article style="box-shadow:0 10px 30px rgba(12,35,64,.08);border-radius:10px;overflow:hidden;background:#fff;display:flex;flex-direction:column;">
                        <a href="{{ $post->url() }}" style="display:block;height:210px;background:#eef1f5 url('{{ $post->featured_image }}') center/cover;"></a>
                        <div @if($cardRtl) dir="rtl" @endif style="padding:24px 22px;display:flex;flex-direction:column;flex:1;{{ $cardRtl ? 'text-align:right;' : '' }}">
                            <div style="color:#f05a28;font-size:12.5px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:10px;">
                                {{ optional($post->published_at)->locale($rtl ? 'ar' : 'en')->translatedFormat('j F Y') }}
                            </div>
                            <h3 style="font-size:19px;line-height:1.4;margin:0 0 12px;">
                                <a href="{{ $post->url() }}" style="color:#0c2340;text-decoration:none;">{{ \Illuminate\Support\Str::limit($post->title, 72) }}</a>
                            </h3>
                            <p style="color:#6b7280;font-size:14.5px;line-height:1.7;margin:0 0 16px;flex:1;">{{ \Illuminate\Support\Str::limit(strip_tags($post->excerpt), 115) }}</p>
                            <a href="{{ $post->url() }}" style="color:#f05a28;font-weight:600;text-decoration:none;font-size:14px;">{{ $L('Read more', 'اقرأ المزيد') }} {{ $rtl ? '←' : '→' }}</a>
                        </div>
                    </article>
                @endforeach
            </div>

            @if($posts->hasPages())
                <div class="cms-pagination">
                    @if($posts->onFirstPage())
                        <span class="disabled"><span>{{ $rtl ? '→' : '←' }}</span></span>
                    @else
                        <a href="{{ $posts->previousPageUrl() }}">{{ $rtl ? '→' : '←' }}</a>
                    @endif

                    @foreach($posts->getUrlRange(max(1, $posts->currentPage() - 2), min($posts->lastPage(), $posts->currentPage() + 2)) as $page => $url)
                        @if($page == $posts->currentPage())
                            <span class="current"><span>{{ $page }}</span></span>
                        @else
                            <a href="{{ $url }}">{{ $page }}</a>
                        @endif
                    @endforeach

                    @if($posts->hasMorePages())
                        <a href="{{ $posts->nextPageUrl() }}">{{ $rtl ? '←' : '→' }}</a>
                    @else
                        <span class="disabled"><span>{{ $rtl ? '←' : '→' }}</span></span>
                    @endif
                </div>
            @endif
        @else
            <p style="text-align:center;color:#6b7280;font-size:17px;padding:40px 0;">{{ $L('No articles found.', 'لا توجد مقالات.') }}</p>
        @endif
    </div>
</div>
