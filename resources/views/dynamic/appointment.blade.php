@php
    $booked = request('booked');
    $hasSlots = $slots->count() > 0;
@endphp
<style>
    .cms-appointment .appt-wrap { max-width: 1040px; margin: 0 auto; padding: 0 15px; }
    .appt-hero { background: linear-gradient(135deg,#0c2340,#13457a); color:#fff; text-align:center; padding:54px 15px; }
    .appt-hero h1 { color:#fff; font-size:clamp(26px,4vw,40px); margin:0 0 10px; font-weight:700; }
    .appt-hero p { margin:0; opacity:.9; font-size:16px; }
    .appt-banner { border-radius:10px; padding:16px 20px; font-size:15px; margin:22px auto; max-width:1040px; }
    .appt-banner.ok { background:#e7f6ec; color:#188a42; border:1px solid #188a42; }
    .appt-banner.err { background:#fdeaea; color:#c0392b; border:1px solid #c0392b; }
    .appt-intro { text-align:center; color:#46536a; font-size:15px; margin:30px 0 8px; }
    .appt-intro b { color:#0c2340; }
    .appt-grid { display:grid; grid-template-columns:1fr 1fr; gap:34px; margin:28px 0 60px; align-items:start; }
    /* Calendar */
    .appt-cal { border:1px solid #e7ebf1; border-radius:14px; padding:20px; }
    .cal-head { display:flex; align-items:center; justify-content:space-between; margin-bottom:14px; }
    .cal-head button { border:1px solid #e0e5ec; background:#fff; width:38px; height:38px; border-radius:9px; cursor:pointer; font-size:18px; color:#0c2340; }
    .cal-head button:disabled { opacity:.35; cursor:default; }
    .cal-title { font-weight:700; color:#0c2340; font-size:16px; }
    .cal-weekdays, .cal-days { display:grid; grid-template-columns:repeat(7,1fr); gap:6px; }
    .cal-weekdays span { text-align:center; font-size:11.5px; color:#9aa3af; text-transform:uppercase; letter-spacing:.4px; padding:6px 0; }
    .cal-day { aspect-ratio:1; display:flex; align-items:center; justify-content:center; border-radius:9px; font-size:14px; color:#c3c9d3; }
    .cal-day.avail { background:#eef5ff; color:#13457a; font-weight:600; cursor:pointer; border:1px solid #d6e6fb; }
    .cal-day.avail:hover { background:#dbeafe; }
    .cal-day.selected { background:#0c2340; color:#fff; border-color:#0c2340; }
    .cal-day.empty { visibility:hidden; }
    /* Side: times + form */
    .appt-side .panel-title { font-size:16px; color:#0c2340; font-weight:700; margin:0 0 14px; }
    .time-chips { display:flex; flex-wrap:wrap; gap:10px; margin-bottom:8px; }
    .time-chip { border:1.5px solid #dbe1ea; border-radius:8px; padding:10px 16px; cursor:pointer; font-size:14px; color:#0c2340; background:#fff; }
    .time-chip:hover { border-color:#13457a; }
    .time-chip.selected { background:#0c2340; color:#fff; border-color:#0c2340; }
    .appt-form { background:#f7f9fc; border:1px solid #e7ebf1; border-radius:12px; padding:24px; margin-top:22px; }
    .appt-form label { display:block; font-size:13px; font-weight:600; color:#334155; margin:12px 0 6px; }
    .appt-form input, .appt-form textarea { width:100%; border:1px solid #d7dde6; border-radius:8px; padding:11px 13px; font-size:15px; font-family:inherit; }
    .appt-form input:focus, .appt-form textarea:focus { outline:none; border-color:#13457a; box-shadow:0 0 0 2px rgba(19,69,122,.12); }
    .appt-summary { background:#eef5ff; border:1px solid #bcd4fb; color:#1d4ed8; border-radius:8px; padding:12px 15px; font-size:14px; margin-bottom:4px; }
    .appt-price { font-size:15px; color:#0c2340; margin:10px 0 2px; }
    .appt-price b { color:#f05a28; font-size:18px; }
    .appt-submit { margin-top:18px; width:100%; border:0; border-radius:8px; background:#f05a28; color:#fff; font-size:16px; font-weight:700; padding:14px; cursor:pointer; }
    .appt-submit:hover { background:#d8481b; }
    .appt-submit:disabled { opacity:.5; cursor:not-allowed; }
    .appt-paynote { font-size:13px; color:#6b7280; margin-top:12px; text-align:center; }
    .appt-hidden { display:none; }
    .appt-empty { color:#6b7280; font-size:16px; text-align:center; padding:40px 0; }
    @media(max-width:820px){ .appt-grid{ grid-template-columns:1fr; } }
</style>

<div id="page-content" class="cms-appointment">
    <section class="appt-hero">
        <h1>Book an Appointment</h1>
        <p>Pick a day and time from the calendar with Prof. Dr. Hazem El Fezary.</p>
    </section>

    @if($booked)
        <div class="appt-banner ok">✅ Thank you! Your appointment request has been received. We will call you to confirm. If you have not paid yet, the time is still open until confirmed.</div>
    @endif
    @if(session('booking_error'))
        <div class="appt-banner err">{{ session('booking_error') }}</div>
    @endif

    <div class="appt-wrap">
        @if(! $hasSlots)
            <p class="appt-empty">Online booking is currently closed. Please check back soon or contact us directly.</p>
        @else
            @if($activeClinics->count())
                <p class="appt-intro">
                    Consultation fee:
                    @foreach($activeClinics as $c)<b>{{ rtrim(rtrim(number_format((float)$c->price,2),'0'),'.') }} {{ $c->currency }}</b>@if(!$loop->last) · @endif @endforeach
                </p>
            @endif

            <div class="appt-grid">
                <div class="appt-cal">
                    <div class="cal-head">
                        <button type="button" id="cal-prev" aria-label="Previous month">‹</button>
                        <span class="cal-title" id="cal-title"></span>
                        <button type="button" id="cal-next" aria-label="Next month">›</button>
                    </div>
                    <div class="cal-weekdays">
                        <span>Sun</span><span>Mon</span><span>Tue</span><span>Wed</span><span>Thu</span><span>Fri</span><span>Sat</span>
                    </div>
                    <div class="cal-days" id="cal-days"></div>
                </div>

                <div class="appt-side">
                    <div id="appt-times" class="appt-hidden">
                        <div class="panel-title" id="times-title">Available times</div>
                        <div class="time-chips" id="time-chips"></div>
                    </div>

                    <form id="appt-form" class="appt-form appt-hidden" method="post" action="/online-appointment/book">
                        @csrf
                        <div class="appt-summary" id="appt-summary"></div>
                        <div class="appt-price" id="appt-price"></div>
                        <input type="hidden" name="slot_id" id="appt-slot-id" value="">
                        <label for="appt-name">Full name</label>
                        <input type="text" id="appt-name" name="name" required placeholder="Your name">
                        <label for="appt-phone">Phone number <span style="color:#c0392b">*</span></label>
                        <input type="tel" id="appt-phone" name="phone" required placeholder="e.g. 010 0000 0000">
                        <label for="appt-note">Notes (optional)</label>
                        <textarea id="appt-note" name="note" rows="2" placeholder="Anything the doctor should know"></textarea>
                        <button type="submit" class="appt-submit" id="appt-submit" disabled>Confirm booking</button>
                        <p class="appt-paynote">Payment is optional — you can book now and pay later. Unpaid times stay open until confirmed.</p>
                    </form>
                </div>
            </div>
        @endif
    </div>
</div>

<script>
(function(){
    var SLOTS = @json($slots);
    if (!SLOTS || !SLOTS.length) return;

    var MONTHS = ['January','February','March','April','May','June','July','August','September','October','November','December'];
    var byDate = {};
    SLOTS.forEach(function(s){ (byDate[s.date] = byDate[s.date] || []).push(s); });
    var dates = Object.keys(byDate).sort();
    var minD = new Date(dates[0] + 'T00:00:00');
    var maxD = new Date(dates[dates.length-1] + 'T00:00:00');

    var view = new Date(minD.getFullYear(), minD.getMonth(), 1);
    var firstMonth = new Date(minD.getFullYear(), minD.getMonth(), 1);
    var lastMonth  = new Date(maxD.getFullYear(), maxD.getMonth(), 1);

    var elDays = document.getElementById('cal-days');
    var elTitle = document.getElementById('cal-title');
    var elPrev = document.getElementById('cal-prev');
    var elNext = document.getElementById('cal-next');
    var elTimes = document.getElementById('appt-times');
    var elTimesTitle = document.getElementById('times-title');
    var elChips = document.getElementById('time-chips');
    var elForm = document.getElementById('appt-form');
    var elSlotId = document.getElementById('appt-slot-id');
    var elSummary = document.getElementById('appt-summary');
    var elPrice = document.getElementById('appt-price');
    var elSubmit = document.getElementById('appt-submit');

    function pad(n){ return (n<10?'0':'') + n; }
    function key(y,m,d){ return y + '-' + pad(m+1) + '-' + pad(d); }

    function render(){
        var y = view.getFullYear(), m = view.getMonth();
        elTitle.textContent = MONTHS[m] + ' ' + y;
        elPrev.disabled = (view <= firstMonth);
        elNext.disabled = (view >= lastMonth);
        elDays.innerHTML = '';
        var firstDow = new Date(y, m, 1).getDay();
        var daysInMonth = new Date(y, m+1, 0).getDate();
        for (var i=0; i<firstDow; i++){ var e=document.createElement('div'); e.className='cal-day empty'; elDays.appendChild(e); }
        for (var d=1; d<=daysInMonth; d++){
            var cell = document.createElement('div');
            cell.className = 'cal-day';
            cell.textContent = d;
            var k = key(y,m,d);
            if (byDate[k]){
                cell.classList.add('avail');
                cell.setAttribute('data-date', k);
                cell.addEventListener('click', function(){ selectDate(this.getAttribute('data-date'), this); });
            }
            elDays.appendChild(cell);
        }
    }

    function selectDate(dateStr, cell){
        Array.prototype.forEach.call(elDays.querySelectorAll('.cal-day'), function(c){ c.classList.remove('selected'); });
        if (cell) cell.classList.add('selected');
        var list = (byDate[dateStr] || []).slice().sort(function(a,b){ return a.time < b.time ? -1 : 1; });
        var dObj = new Date(dateStr + 'T00:00:00');
        elTimesTitle.textContent = 'Available times — ' + dObj.toLocaleDateString(undefined, {weekday:'long', day:'numeric', month:'long'});
        elChips.innerHTML = '';
        list.forEach(function(s){
            var chip = document.createElement('div');
            chip.className = 'time-chip';
            chip.textContent = s.time;
            chip.addEventListener('click', function(){ selectSlot(s, chip); });
            elChips.appendChild(chip);
        });
        elTimes.classList.remove('appt-hidden');
        elForm.classList.add('appt-hidden');
        elSlotId.value=''; elSubmit.disabled=true;
    }

    function selectSlot(s, chip){
        Array.prototype.forEach.call(elChips.querySelectorAll('.time-chip'), function(c){ c.classList.remove('selected'); });
        chip.classList.add('selected');
        elSlotId.value = s.id;
        elSummary.textContent = '✔ ' + s.label + (s.clinic ? ' · ' + s.clinic : '');
        elPrice.innerHTML = 'Fee: <b>' + s.price + ' ' + s.currency + '</b>';
        elSubmit.disabled = false;
        elForm.classList.remove('appt-hidden');
        elForm.scrollIntoView({behavior:'smooth', block:'nearest'});
    }

    elPrev.addEventListener('click', function(){ view = new Date(view.getFullYear(), view.getMonth()-1, 1); render(); });
    elNext.addEventListener('click', function(){ view = new Date(view.getFullYear(), view.getMonth()+1, 1); render(); });
    render();
})();
</script>
