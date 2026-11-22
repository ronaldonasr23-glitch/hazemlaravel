@php
    $rtl = $rtl ?? false;
    $L = fn ($en, $ar) => $rtl ? $ar : $en;
    $blogUrl = $rtl ? '/ar/blogs/' : '/blogs/';
@endphp

<div class="blog-sidebar" style="{{ $rtl ? 'text-align:right;' : '' }}">

    {{-- Search --}}
    <div class="widget" style="margin-bottom:34px;">
        <form action="{{ $blogUrl }}" method="get" style="display:flex;border:1px solid #e3e7ee;border-radius:8px;overflow:hidden;">
            <input type="text" name="s" value="{{ request('s') }}" placeholder="{{ $L('Search articles…', 'ابحث في المقالات…') }}"
                   style="flex:1;border:0;padding:12px 14px;font-size:14px;outline:none;color:#333;{{ $rtl ? 'text-align:right;' : '' }}">
            <button type="submit" style="border:0;background:#0c2340;color:#fff;padding:0 18px;cursor:pointer;font-size:15px;">⌕</button>
        </form>
    </div>

    {{-- Recent / Popular articles (real data) --}}
    @if(!empty($recent) && count($recent))
        <div class="widget" style="margin-bottom:34px;">
            <h4 style="font-size:15px;text-transform:uppercase;letter-spacing:.6px;color:#0c2340;margin:0 0 18px;padding-bottom:10px;border-bottom:2px solid #f0f2f5;">
                {{ $L('Recent Articles', 'أحدث المقالات') }}
            </h4>
            @foreach($recent as $r)
                <a href="{{ $r->url() }}" style="display:flex;gap:12px;align-items:center;margin-bottom:16px;text-decoration:none;">
                    <span style="flex:0 0 62px;width:62px;height:62px;border-radius:7px;background:#eef1f5 url('{{ $r->featured_image }}') center/cover;"></span>
                    <span style="flex:1;">
                        <span style="display:block;color:#0c2340;font-weight:600;font-size:14px;line-height:1.4;">{{ \Illuminate\Support\Str::limit($r->title, 58) }}</span>
                        <span style="display:block;color:#9aa3af;font-size:12px;margin-top:4px;">{{ optional($r->published_at)->locale($rtl ? 'ar' : 'en')->translatedFormat('j F Y') }}</span>
                    </span>
                </a>
            @endforeach
        </div>
    @endif

    {{-- Contact / Follow --}}
    <div class="widget" style="margin-bottom:10px;">
        <h4 style="font-size:15px;text-transform:uppercase;letter-spacing:.6px;color:#0c2340;margin:0 0 18px;padding-bottom:10px;border-bottom:2px solid #f0f2f5;">
            {{ $L('Follow / Contact', 'تواصل معنا') }}
        </h4>
        <div style="display:flex;gap:10px;flex-wrap:wrap;">
            <a href="https://wa.me/201000018804" target="_blank" rel="noopener" style="flex:1;min-width:120px;display:flex;align-items:center;gap:8px;justify-content:center;background:#25d366;color:#fff;padding:11px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">WhatsApp</a>
            <a href="tel:+201000018804" style="flex:1;min-width:120px;display:flex;align-items:center;gap:8px;justify-content:center;background:#0c2340;color:#fff;padding:11px;border-radius:8px;text-decoration:none;font-size:14px;font-weight:600;">{{ $L('Call', 'اتصل') }}</a>
        </div>
    </div>
</div>
