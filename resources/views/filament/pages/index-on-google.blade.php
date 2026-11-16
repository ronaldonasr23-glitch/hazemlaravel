<x-filament-panels::page>
    <style>
        .gi-wrap { --brand:#ff5316; --brandDk:#e8440c; --navy:#192437; }
        /* Stepper */
        .gi-steps { display:flex; gap:8px; margin-bottom:22px; flex-wrap:wrap; }
        .gi-steps .st { flex:1 1 150px; display:flex; align-items:center; gap:10px; background:#f3f5f7;
            border:1px solid #e5e7eb; border-radius:10px; padding:11px 14px; color:#6b7280; font-size:13.5px; font-weight:600; }
        .dark .gi-steps .st { background:#1f2937; border-color:#374151; color:#9aa2ad; }
        .gi-steps .st .c { flex:0 0 24px; width:24px; height:24px; border-radius:50%; background:#cbd5e1; color:#fff;
            display:flex; align-items:center; justify-content:center; font-size:13px; font-weight:800; }
        .dark .gi-steps .st .c { background:#4b5563; }
        .gi-steps .st.active { background:#fff3ee; border-color:var(--brand); color:#9a3412; }
        .dark .gi-steps .st.active { background:rgba(255,83,22,.14); border-color:var(--brand); color:#fdb892; }
        .gi-steps .st.active .c { background:var(--brand); }
        /* Cards */
        .gi-card { border:1px solid #e5e7eb; border-radius:14px; padding:18px 20px; margin-bottom:16px; background:#fff; }
        .dark .gi-card { border-color:#374151; background:#141b26; }
        .gi-card.locked { opacity:.55; }
        .gi-card h3 { display:flex; align-items:center; gap:10px; font-size:16px; font-weight:800; margin:0 0 6px; color:var(--navy); }
        .dark .gi-card h3 { color:#e5e7eb; }
        .gi-card h3 .c { flex:0 0 26px; width:26px; height:26px; border-radius:50%; background:var(--brand); color:#fff;
            display:flex; align-items:center; justify-content:center; font-size:14px; font-weight:800; }
        .gi-card p { font-size:13.5px; color:#6b7280; margin:0 0 14px; line-height:1.6; }
        .dark .gi-card p { color:#9aa2ad; }
        .gi-btn { background:var(--brand); color:#fff; border:0; border-radius:9px; padding:11px 20px;
            font-size:14px; font-weight:700; cursor:pointer; transition:background .15s; }
        .gi-btn:hover { background:var(--brandDk); }
        .gi-btn:disabled { background:#cbd5e1; cursor:not-allowed; }
        .dark .gi-btn:disabled { background:#374151; }
        .gi-prog { margin-left:14px; font-size:13px; color:#6b7280; font-weight:600; }
        .dark .gi-prog { color:#cbd5e1; }
        .gi-lock { margin-top:12px; font-size:12.5px; color:#9a8f00; background:#fffbe6; border:1px solid #fde68a;
            border-radius:8px; padding:8px 12px; display:inline-block; }
        .dark .gi-lock { background:rgba(234,179,8,.12); border-color:rgba(234,179,8,.4); color:#e6c84f; }
        .gi-warn { background:#fdeaea; border:1px solid #f5b5b0; color:#b23b32; border-radius:10px; padding:13px 16px;
            margin-bottom:18px; font-size:13.5px; line-height:1.6; }
        .dark .gi-warn { background:rgba(192,57,43,.14); border-color:rgba(192,57,43,.4); color:#f0a8a1; }
        /* Summary chips */
        .gi-sum { display:flex; gap:16px; flex-wrap:wrap; margin-bottom:14px; font-size:14px; }
        .gi-sum span { font-weight:600; color:#4b5563; }
        .dark .gi-sum span { color:#cbd5e1; }
        .gi-sum b { font-weight:800; }
        /* List */
        .gi-list { border:1px solid #e5e7eb; border-radius:14px; overflow:hidden; margin-top:6px; }
        .dark .gi-list { border-color:#374151; }
        .gi-list-h { background:var(--navy); color:#fff; padding:12px 16px; font-size:14px; font-weight:700; }
        .gi-row { display:flex; align-items:center; gap:10px; padding:9px 14px; border-top:1px solid #eef0f2; }
        .dark .gi-row { border-top-color:#2a3342; }
        .gi-row:first-child { border-top:0; }
        .gi-row.busy { outline:2px solid var(--brand); outline-offset:-2px; }
        .gi-row .n { flex:0 0 26px; font-size:12px; color:#9aa2ad; font-weight:700; }
        .gi-row .meta { flex:1 1 auto; min-width:0; }
        .gi-row .ti { font-size:13px; font-weight:600; color:#1f2937; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
        .dark .gi-row .ti { color:#e5e7eb; }
        .gi-row .u { font-size:11.5px; color:#8a8f98; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; direction:ltr; }
        .gi-cov { flex:0 0 auto; font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; white-space:nowrap; }
        .gi-cov.none { background:transparent; color:transparent; }
        .gi-cov.indexed { background:#e7f6ec; color:#188a42; }
        .gi-cov.not_indexed { background:#fff3e0; color:#c2690a; }
        .gi-cov.unknown { background:#eef1f4; color:#6b7280; }
        .gi-cov.checking { background:#eef5ff; color:#1d4ed8; }
        .gi-cov.error { background:#fdeaea; color:#c0392b; }
        .dark .gi-cov.indexed { background:rgba(24,138,66,.2); }
        .dark .gi-cov.not_indexed { background:rgba(194,105,10,.22); color:#f0b06a; }
        .dark .gi-cov.unknown { background:#2a3342; color:#9aa2ad; }
        .dark .gi-cov.checking { background:rgba(37,99,235,.22); color:#93c5fd; }
        .dark .gi-cov.error { background:rgba(192,57,43,.25); color:#f3a29a; }
        .gi-sent { flex:0 0 auto; font-size:11px; font-weight:700; padding:3px 8px; border-radius:999px; white-space:nowrap; }
        .gi-sent.sending { background:#eef5ff; color:#1d4ed8; }
        .gi-sent.ok { background:#e7f6ec; color:#188a42; }
        .gi-sent.err { background:#fdeaea; color:#c0392b; }
        .gi-gsc { flex:0 0 auto; font-size:11px; font-weight:700; color:var(--brand); text-decoration:none; white-space:nowrap; }
        .gi-gsc:hover { text-decoration:underline; }
    </style>

    @php($cfg = $this->indexingConfigured())
    @php($sum = $this->summary())
    @php($anyChecked = $this->anyChecked())

    <div class="gi-wrap">

        {{-- Stepper --}}
        <div class="gi-steps">
            <div class="st active"><span class="c">1</span> Check status</div>
            <div class="st {{ $anyChecked ? 'active' : '' }}"><span class="c">2</span> Submit un-indexed</div>
            <div class="st"><span class="c">3</span> Wait &amp; re-check</div>
        </div>

        @unless($cfg)
            <div class="gi-warn">
                ⚠️ <b>Google Indexing isn't set up yet.</b> Go to <b>Google &amp; Marketing</b>, paste your service
                account JSON and turn on auto-submit — then come back here.
            </div>
        @endunless

        {{-- STEP 1 --}}
        <div class="gi-card">
            <h3><span class="c">1</span> Check which articles are indexed</h3>
            <p>Asks Google (URL Inspection) about every published article, one at a time — you'll see each result appear below. Safe, never times out.</p>
            <div class="gi-sum" id="gi-sum">
                🟢 <span>indexed <b id="s-idx">{{ $sum['indexed'] }}</b></span>
                🟠 <span>not indexed <b id="s-no">{{ $sum['notIndexed'] }}</b></span>
                ⚪ <span>unknown <b id="s-unk">{{ $sum['unknown'] }}</b></span>
                ⬜ <span>not checked <b id="s-unc">{{ $sum['unchecked'] }}</b></span>
            </div>
            <button type="button" class="gi-btn" id="btn-check" onclick="cggCheckAll(this)" @disabled(! $cfg)>▶ Check all (live)</button>
            <span class="gi-prog" id="prog-check"></span>
        </div>

        {{-- STEP 2 --}}
        <div class="gi-card {{ $anyChecked ? '' : 'locked' }}" id="step2">
            <h3><span class="c">2</span> Submit the ones not indexed</h3>
            <p>Sends to Google only the 🟠 not-indexed and ⚪ unknown articles — one at a time. The 🟢 indexed ones are skipped.</p>
            <button type="button" class="gi-btn" id="btn-submit" onclick="cggSubmitUnindexed(this)" @disabled(! ($cfg && $anyChecked))>🚀 Submit un-indexed (live)</button>
            <span class="gi-prog" id="prog-submit"></span>
            @unless($anyChecked)
                <div class="gi-lock">🔒 Finish <b>Step 1</b> first so we know which articles need submitting.</div>
            @endunless
        </div>

        {{-- STEP 3 --}}
        <div class="gi-card">
            <h3><span class="c">3</span> Wait, then re-check</h3>
            <p>Indexing takes a few days. The not-indexed articles are <b>re-checked automatically every day</b>, so the numbers above update on their own — you can also run <b>Step 1</b> anytime for an instant refresh. If an article stays not-indexed for weeks, the issue is its content, not this tool.</p>
        </div>

        {{-- ARTICLE LIST --}}
        <div wire:ignore>
            @if($this->totalCount() === 0)
                <p style="font-size:14px;color:#6b7280;">No published articles yet.</p>
            @else
                <div class="gi-list">
                    <div class="gi-list-h">All published articles ({{ $this->totalCount() }})</div>
                    @foreach($this->articles() as $k => $row)
                        @php($cov = $row['state'] ?: 'none')
                        <div class="gi-row" data-check-id="{{ $row['id'] }}" data-state="{{ $row['state'] }}">
                            <div class="n">{{ $k + 1 }}</div>
                            <div class="meta">
                                <div class="ti">{{ $row['title'] }}</div>
                                <div class="u">{{ $row['url'] }}</div>
                            </div>
                            <span class="gi-cov {{ $cov }}" data-cov title="{{ $row['coverage'] }}">
                                @if($row['state'] === 'indexed') Indexed ✓
                                @elseif($row['state'] === 'not_indexed') Not indexed
                                @elseif($row['state'] === 'unknown') Unknown
                                @endif
                            </span>
                            <span class="gi-sent {{ $row['submitted'] ? 'ok' : '' }}" data-sent style="{{ $row['submitted'] ? '' : 'display:none' }}">{{ $row['submitted'] ? '✓ Sent' : '' }}</span>
                            <a class="gi-gsc" href="{{ $row['inspect'] }}" target="_blank" rel="noopener" title="Open in Search Console">GSC ↗</a>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>

    <script>
        function cggWire(){ return window.Livewire.find(@js($this->getId())); }

        // STEP 1 — live check, one article per request.
        async function cggCheckAll(btn){
            if (btn.dataset.running) return;
            btn.dataset.running = '1'; btn.disabled = true;
            var rows = document.querySelectorAll('[data-check-id]');
            var prog = document.getElementById('prog-check');
            var total = rows.length, done = 0, idx = 0, no = 0, unk = 0, err = 0;
            var wire = cggWire();
            for (var i = 0; i < rows.length; i++){
                var row = rows[i], id = parseInt(row.getAttribute('data-check-id'));
                var badge = row.querySelector('[data-cov]');
                row.classList.add('busy');
                if (badge){ badge.className = 'gi-cov checking'; badge.textContent = 'Checking…'; }
                row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                try {
                    var res = await wire.checkOne(id);
                    row.setAttribute('data-state', res.state || '');
                    if (badge){ badge.className = 'gi-cov ' + (res.state || 'unknown'); badge.textContent = res.label; badge.title = res.coverage || ''; }
                    if (res.state === 'indexed') idx++; else if (res.state === 'not_indexed') no++; else if (res.state === 'error') err++; else unk++;
                } catch (e) {
                    row.setAttribute('data-state', 'error');
                    if (badge){ badge.className = 'gi-cov error'; badge.textContent = 'Error'; }
                    err++;
                }
                row.classList.remove('busy'); done++;
                document.getElementById('s-idx').textContent = idx;
                document.getElementById('s-no').textContent = no;
                document.getElementById('s-unk').textContent = unk;
                document.getElementById('s-unc').textContent = (total - done);
                prog.textContent = 'Checked ' + done + '/' + total + (err ? ('  •  ⚠️ ' + err + ' error') : '');
            }
            btn.disabled = false; delete btn.dataset.running; btn.textContent = '✓ Re-check';
            // Unlock Step 2.
            var s2 = document.getElementById('step2'); s2.classList.remove('locked');
            var sub = document.getElementById('btn-submit'); if (sub) sub.disabled = false;
            var lock = s2.querySelector('.gi-lock'); if (lock) lock.remove();
            document.querySelectorAll('.gi-steps .st')[1].classList.add('active');
        }

        // STEP 2 — live submit of only the NOT-indexed articles.
        async function cggSubmitUnindexed(btn){
            if (btn.dataset.running) return;
            btn.dataset.running = '1'; btn.disabled = true;
            var rows = Array.prototype.slice.call(document.querySelectorAll('[data-check-id]'))
                .filter(function(r){ return r.getAttribute('data-state') !== 'indexed'; });
            var prog = document.getElementById('prog-submit');
            var total = rows.length, done = 0, ok = 0, err = 0;
            if (total === 0){ prog.textContent = 'Nothing to submit — everything is indexed 🎉'; btn.disabled = false; delete btn.dataset.running; return; }
            var wire = cggWire();
            for (var i = 0; i < rows.length; i++){
                var row = rows[i], id = parseInt(row.getAttribute('data-check-id'));
                var sent = row.querySelector('[data-sent]');
                row.classList.add('busy');
                if (sent){ sent.style.display = ''; sent.className = 'gi-sent sending'; sent.textContent = 'Sending…'; }
                row.scrollIntoView({ block: 'center', behavior: 'smooth' });
                try {
                    var res = await wire.submitOne(id);
                    if (sent){ sent.className = 'gi-sent ' + (res.ok ? 'ok' : 'err'); sent.textContent = res.ok ? '✓ Sent' : 'Error'; sent.title = res.message || ''; }
                    res.ok ? ok++ : err++;
                } catch (e) {
                    if (sent){ sent.className = 'gi-sent err'; sent.textContent = 'Error'; }
                    err++;
                }
                row.classList.remove('busy'); done++;
                prog.textContent = 'Sent ' + done + '/' + total + '  •  ✓ ' + ok + (err ? ('  ⚠️ ' + err) : '');
            }
            btn.disabled = false; delete btn.dataset.running; btn.textContent = '✓ Done';
        }
    </script>
</x-filament-panels::page>
