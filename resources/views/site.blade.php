<!DOCTYPE html>
<html lang="en-US" class="no-js">
<head>
{!! $head !!}
</head>
<body class="{{ $bodyClass ?? '' }}">
{!! \App\Models\Setting::get('header_html') !!}
@isset($contentHtml){!! $contentHtml !!}@endisset
@isset($slot){!! $slot !!}@endisset
{!! \App\Models\Setting::get('footer_html') !!}
<script>
(function(){
  var path = location.pathname.replace(/\/+$/,'') || '/';
  document.querySelectorAll('.gva-main-menu a, #gva-mobile-menu a, .menu a').forEach(function(a){
    var href = (a.getAttribute('href')||'').replace(/^https?:\/\/[^\/]+/,'').replace(/\/+$/,'') || '/';
    if (href === path) {
      var li = a.closest('li');
      if (li) li.classList.add('current-menu-item','current_page_item','active');
    }
  });

  // Contact form (CF7-compatible) -> Laravel endpoint, with loader + notification
  document.querySelectorAll('form.wpcf7-form').forEach(function(form){
    // A reliable, theme-independent status banner appended to the form.
    var banner = document.createElement('div');
    banner.setAttribute('aria-live', 'polite');
    banner.style.cssText = 'display:none;margin-top:16px;padding:13px 16px;border-radius:8px;font-size:15px;line-height:1.5;';
    form.appendChild(banner);

    function setStatus(kind, text){
      banner.style.display = 'block';
      if (kind === 'loading'){
        banner.style.background = '#eef5ff'; banner.style.color = '#1d4ed8'; banner.style.border = '1px solid #93c5fd';
        banner.innerHTML = '<span style="display:inline-block;width:15px;height:15px;margin-right:9px;vertical-align:-2px;'
          + 'border:2px solid #bcd4fb;border-top-color:#1d4ed8;border-radius:50%;animation:cgg-spin .7s linear infinite;"></span>' + text;
      } else if (kind === 'ok'){
        banner.style.background = '#e7f6ec'; banner.style.color = '#188a42'; banner.style.border = '1px solid #188a42'; banner.textContent = text;
      } else {
        banner.style.background = '#fdeaea'; banner.style.color = '#c0392b'; banner.style.border = '1px solid #c0392b'; banner.textContent = text;
      }
    }

    form.addEventListener('submit', function(e){
      e.preventDefault();
      e.stopImmediatePropagation();
      var btn = form.querySelector('[type=submit]');
      var themeOut = form.querySelector('.wpcf7-response-output');
      if (themeOut) themeOut.style.display = 'none'; // hide the theme's empty box
      if (btn){
        btn.disabled = true;
        btn.dataset.cggLabel = (btn.value !== undefined && btn.value !== '') ? btn.value : btn.textContent;
        if (btn.value !== undefined) btn.value = 'Sending...'; else btn.textContent = 'Sending...';
      }
      setStatus('loading', 'Sending your message...');
      fetch('/contact-submit', {
        method: 'POST',
        headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
        body: new FormData(form)
      }).then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }).catch(function(){ return {ok:r.ok, d:{}}; }); })
        .then(function(res){
          if (res.ok){ setStatus('ok', (res.d && res.d.message) || 'Thank you! Your message has been sent.'); form.reset(); }
          else { setStatus('err', (res.d && res.d.message) || 'Please check the form and try again.'); }
        })
        .catch(function(){ setStatus('err', 'Something went wrong. Please try again.'); })
        .finally(function(){
          if (btn){
            btn.disabled = false;
            if (btn.value !== undefined) btn.value = btn.dataset.cggLabel || 'Send Message';
            else btn.textContent = btn.dataset.cggLabel || 'Send Message';
          }
        });
    }, true);
  });
})();
</script>

{{-- Floating chat-head contact widget --}}
<style>
  #cgg-chat, #cgg-chat * { box-sizing: border-box; }
  #cgg-chat { position: fixed; right: 22px; bottom: 22px; z-index: 2147483000;
    font-family: Arial, Helvetica, sans-serif; }
  #cgg-chat-btn { width: 60px; height: 60px; border-radius: 50%; border: 0; cursor: pointer;
    background: #ff5316; color: #fff; box-shadow: 0 8px 24px rgba(0,0,0,.25);
    display: flex; align-items: center; justify-content: center; transition: transform .15s ease, background .2s; }
  #cgg-chat-btn:hover { background: #e8440c; transform: translateY(-2px); }
  #cgg-chat-btn svg { width: 28px; height: 28px; fill: #fff; }
  #cgg-chat-panel { position: absolute; right: 0; bottom: 74px; width: 340px; max-width: calc(100vw - 32px);
    max-height: calc(100vh - 110px); overflow-y: auto; overflow-x: hidden;
    background: #fff; border-radius: 14px; box-shadow: 0 16px 48px rgba(0,0,0,.28);
    display: none; animation: cgg-pop .18s ease; }
  #cgg-chat.open #cgg-chat-panel { display: block; }
  @keyframes cgg-pop { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
  #cgg-chat-head { background: #192437; color: #fff; padding: 16px 18px; }
  #cgg-chat-head h4 { margin: 0; font-size: 16px; font-weight: 700; }
  #cgg-chat-head p { margin: 4px 0 0; font-size: 12.5px; opacity: .9; }
  #cgg-chat-form { padding: 14px 16px 16px; }
  #cgg-chat-form label { display: block; font-size: 12px; color: #444; margin: 8px 0 4px; font-weight: 600; }
  #cgg-chat-form input, #cgg-chat-form textarea { width: 100%; border: 1px solid #d7d7d7; border-radius: 8px;
    padding: 9px 11px; font-size: 13px; font-family: inherit; color: #222; }
  #cgg-chat-form input:focus, #cgg-chat-form textarea:focus { outline: none; border-color: #ff5316; box-shadow: 0 0 0 2px rgba(255,83,22,.15); }
  #cgg-chat-form textarea { resize: vertical; min-height: 84px; }
  #cgg-chat-send { margin-top: 12px; width: 100%; border: 0; border-radius: 8px; background: #ff5316; color: #fff;
    font-size: 14px; font-weight: 700; padding: 11px; cursor: pointer; transition: background .2s; }
  #cgg-chat-send:hover { background: #e8440c; }
  #cgg-chat-send:disabled { opacity: .6; cursor: default; }
  #cgg-chat-msg { display: none; margin-top: 11px; font-size: 12.5px; padding: 9px 11px; border-radius: 8px; }
  #cgg-chat-close { position: absolute; top: 12px; right: 12px; background: transparent; border: 0; color: #fff;
    font-size: 20px; line-height: 1; cursor: pointer; opacity: .85; }
  #cgg-chat-close:hover { opacity: 1; }
  @keyframes cgg-spin { to { transform: rotate(360deg); } }
  /* Hide the captured theme's empty CF7 spinner (we show our own loader). */
  .wpcf7-spinner { display: none !important; }
  #cgg-chat-alt { padding: 0 16px 16px; }
  #cgg-chat-alt .cgg-alt-sep { height: 1px; background: #eceef1; margin: 2px 0 12px; }
  #cgg-chat-alt .cgg-alt-title { font-size: 11px; text-transform: uppercase; letter-spacing: .04em;
    color: #8a8f98; font-weight: 700; margin: 0 0 11px; text-align: center; }
  #cgg-chat-alt .cgg-alt-row { display: flex; gap: 10px; justify-content: center; }
  #cgg-chat-alt a.cgg-alt { display: flex; flex-direction: column; align-items: center; gap: 6px;
    width: 72px; text-decoration: none; }
  #cgg-chat-alt .cgg-alt-ic { width: 46px; height: 46px; border-radius: 50%;
    display: flex; align-items: center; justify-content: center; box-shadow: 0 3px 9px rgba(0,0,0,.16);
    transition: transform .15s ease; }
  #cgg-chat-alt a.cgg-alt:hover .cgg-alt-ic { transform: translateY(-2px); }
  #cgg-chat-alt .cgg-alt-ic svg { width: 21px; height: 21px; fill: #fff; }
  #cgg-chat-alt a.cgg-alt span { font-size: 11.5px; font-weight: 600; color: #555; }
</style>
<div id="cgg-chat" aria-live="polite">
  <div id="cgg-chat-panel" role="dialog" aria-label="Contact us">
    <button type="button" id="cgg-chat-close" aria-label="Close">&times;</button>
    <div id="cgg-chat-head">
      <h4>Contact us</h4>
      <p>Send us a message and we'll reply by email.</p>
    </div>
    <form id="cgg-chat-form" novalidate>
      <label for="cgg-email">Your email</label>
      <input type="email" id="cgg-email" name="your-email" placeholder="you@example.com" required>
      <label for="cgg-subject">Subject</label>
      <input type="text" id="cgg-subject" name="your-subject" placeholder="How can we help?">
      <label for="cgg-message">Message</label>
      <textarea id="cgg-message" name="your-message" placeholder="Type your message..." required></textarea>
      <button type="submit" id="cgg-chat-send">Send message</button>
      <div id="cgg-chat-msg"></div>
    </form>
    <div id="cgg-chat-alt">
      <div class="cgg-alt-sep"></div>
      <div class="cgg-alt-title">Or reach us directly</div>
      <div class="cgg-alt-row">
        <a class="cgg-alt" href="https://wa.me/201000018804" target="_blank" rel="noopener" title="WhatsApp +20 100 0018804">
          <span class="cgg-alt-ic" style="background:#25d366;">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2a10 10 0 0 0-8.5 15.2L2 22l4.9-1.5A10 10 0 1 0 12 2zm0 18.2c-1.5 0-3-.4-4.3-1.2l-.3-.2-2.9.9.9-2.8-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.7-6.1c-.3-.1-1.6-.8-1.8-.9-.3-.1-.4-.1-.6.1l-.8 1c-.2.2-.3.2-.5.1-1.1-.4-1.8-.8-2.6-1.8-.2-.3 0-.4.1-.6l.4-.5c.1-.2 0-.3 0-.5l-.8-1.9c-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.4.1-.7.3-.2.3-.9.9-.9 2.1s.9 2.5 1.1 2.7c.1.2 1.8 2.8 4.4 3.9 1.6.7 2.2.7 3 .6.5-.1 1.6-.6 1.8-1.3.2-.6.2-1.2.2-1.3-.1-.1-.3-.2-.6-.3z"/></svg>
          </span>
          <span>WhatsApp</span>
        </a>
        <a class="cgg-alt" href="tel:+201000018804" title="Call +20 100 0018804">
          <span class="cgg-alt-ic" style="background:#192437;">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.4.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.6 21 3 13.4 3 4c0-.6.4-1 1-1h3.5c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.4 0 .8-.2 1l-2.3 2.2z"/></svg>
          </span>
          <span>Call</span>
        </a>
        <a class="cgg-alt" href="https://maps.app.goo.gl/c2H1rft3W9wTHeYv5" target="_blank" rel="noopener" title="9 St 314, Smouha, Alexandria">
          <span class="cgg-alt-ic" style="background:#ff5316;">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M12 2C8.1 2 5 5.1 5 9c0 5.2 7 13 7 13s7-7.8 7-13c0-3.9-3.1-7-7-7zm0 9.5A2.5 2.5 0 1 1 12 6.5a2.5 2.5 0 0 1 0 5z"/></svg>
          </span>
          <span>Location</span>
        </a>
      </div>
    </div>
  </div>
  <button type="button" id="cgg-chat-btn" aria-label="Open contact form" aria-expanded="false">
    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M20 2H4a2 2 0 0 0-2 2v18l4-4h14a2 2 0 0 0 2-2V4a2 2 0 0 0-2-2zM7 9h10v2H7V9zm0 4h7v2H7v-2z"/></svg>
  </button>
</div>
<script>
(function(){
  var wrap = document.getElementById('cgg-chat');
  var btn = document.getElementById('cgg-chat-btn');
  var close = document.getElementById('cgg-chat-close');
  var form = document.getElementById('cgg-chat-form');
  var send = document.getElementById('cgg-chat-send');
  var msg = document.getElementById('cgg-chat-msg');
  if (!wrap || !btn || !form) return;

  function open(){ wrap.classList.add('open'); btn.setAttribute('aria-expanded','true');
    setTimeout(function(){ var f = document.getElementById('cgg-email'); if (f) f.focus(); }, 50); }
  function shut(){ wrap.classList.remove('open'); btn.setAttribute('aria-expanded','false'); }

  btn.addEventListener('click', function(){ wrap.classList.contains('open') ? shut() : open(); });
  close.addEventListener('click', shut);
  document.addEventListener('keydown', function(e){ if (e.key === 'Escape') shut(); });

  function note(ok, text){ msg.style.display='block'; msg.textContent=text;
    msg.style.background = ok ? '#e7f6ec' : '#fdeaea';
    msg.style.color = ok ? '#188a42' : '#c0392b';
    msg.style.border = '1px solid ' + (ok ? '#188a42' : '#c0392b'); }

  form.addEventListener('submit', function(e){
    e.preventDefault();
    var email = document.getElementById('cgg-email').value.trim();
    var body = document.getElementById('cgg-message').value.trim();
    if (!email || !body){ note(false, 'Please enter your email and a message.'); return; }
    send.disabled = true; send.textContent = 'Sending...';
    fetch('/contact-submit', {
      method: 'POST',
      headers: {'X-Requested-With':'XMLHttpRequest','Accept':'application/json'},
      body: new FormData(form)
    }).then(function(r){ return r.json().then(function(d){ return {ok:r.ok, d:d}; }); })
      .then(function(res){
        if (res.ok){ note(true, res.d.message || 'Thank you! Your message has been sent.'); form.reset(); }
        else { note(false, (res.d && res.d.message) || 'Please check your details and try again.'); }
      })
      .catch(function(){ note(false, 'Something went wrong. Please try again.'); })
      .finally(function(){ send.disabled = false; send.textContent = 'Send message'; });
  });
})();
</script>
</body>
</html>
