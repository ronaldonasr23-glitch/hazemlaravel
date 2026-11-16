@php
    use App\Support\SeoAnalyzer;
    $dot = fn ($s) => $s === SeoAnalyzer::GOOD ? '#4caf50' : ($s === SeoAnalyzer::OK ? '#ee7c1b' : '#dc3232');
@endphp

<div style="font-size:13px;line-height:1.5;">
    {{-- Google snippet preview --}}
    <div style="border:1px solid #e5e7eb;border-radius:8px;padding:14px 16px;margin-bottom:18px;background:#fff;">
        <div style="color:#1a0dab;font-size:18px;line-height:1.3;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">
            {{ $snippet['title'] ?: 'Your SEO title preview' }}
        </div>
        <div style="color:#006621;font-size:13px;margin:2px 0 4px;">{{ $snippet['url'] }}</div>
        <div style="color:#545454;font-size:13px;">
            {{ $snippet['desc'] ?: 'Your meta description preview will appear here. Write a compelling summary that includes your focus keyphrase.' }}
        </div>
    </div>

    {{-- Scores --}}
    <div style="display:flex;gap:24px;margin-bottom:16px;">
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="width:14px;height:14px;border-radius:50%;background:{{ SeoAnalyzer::color($result['seo_score']) }};display:inline-block;"></span>
            <strong>SEO:</strong> {{ SeoAnalyzer::label($result['seo_score']) }} ({{ $result['seo_score'] }}/100)
        </div>
        <div style="display:flex;align-items:center;gap:8px;">
            <span style="width:14px;height:14px;border-radius:50%;background:{{ SeoAnalyzer::color($result['readability_score']) }};display:inline-block;"></span>
            <strong>Readability:</strong> {{ SeoAnalyzer::label($result['readability_score']) }} ({{ $result['readability_score'] }}/100)
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:24px;">
        <div>
            <div style="font-weight:600;margin-bottom:8px;color:#111827;">SEO analysis</div>
            @foreach($result['seo'] as $c)
                <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:7px;">
                    <span style="flex:0 0 10px;width:10px;height:10px;border-radius:50%;background:{{ $dot($c['status']) }};margin-top:4px;"></span>
                    <span style="color:#374151;">{{ $c['text'] }}</span>
                </div>
            @endforeach
        </div>
        <div>
            <div style="font-weight:600;margin-bottom:8px;color:#111827;">Readability analysis</div>
            @foreach($result['readability'] as $c)
                <div style="display:flex;gap:8px;align-items:flex-start;margin-bottom:7px;">
                    <span style="flex:0 0 10px;width:10px;height:10px;border-radius:50%;background:{{ $dot($c['status']) }};margin-top:4px;"></span>
                    <span style="color:#374151;">{{ $c['text'] }}</span>
                </div>
            @endforeach
        </div>
    </div>
</div>
