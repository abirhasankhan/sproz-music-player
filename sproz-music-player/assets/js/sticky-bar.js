/**
 * Sproz Music Player — Persistent Sticky Bar
 *
 * ARCHITECTURE:
 * - Sticky bar + WaveSurfer live in #sproz-shell (fixed, outside everything)
 * - Page navigation uses history.pushState + fetch + innerHTML swap
 * - On ANY error the audio is NEVER interrupted (no location.href fallback)
 */
(function () {
    'use strict';

    var STORE = 'sproz_state';
    var SP = window.SprozPlayer = {
        ws:null, tracks:[], index:0,
        isPlaying:false, isRepeat:false, isShuffle:false,
        shuffleOrder:[], volume:1, ready:false, position:0,
    };
    var _rId=null, _rPlay=null, _busy=false, _ajaxNav=false;
    var $bar,$art,$artImg,$title,$artist,$play,$prev,$next,$shuf,$rep,$seek,$fill,$cur,$dur,$vol,$queue,$qList;

    document.addEventListener('DOMContentLoaded', boot);

    function boot() {
        buildShell();       // 1. Move bar+wave into fixed shell — NEVER touched by nav
        wrapContent();      // 2. Wrap all page content in #sproz-content — the ONLY swap target
        cacheRefs();        // 3. Cache bar DOM refs
        initWave();         // 4. Single WaveSurfer instance, lives in shell forever
        barEvents();        // 5. Sticky bar button handlers
        bindPlayers(document); // 6. Bind inline players on current page
        initNav();          // 7. Intercept all link clicks for AJAX nav
        restoreState();     // 8. Restore AFTER nav init — so SP.tracks is set before any link click
        setInterval(syncRows, 250);
    }

    /* ══ WRAP CONTENT — creates the single stable swap target ════════ */
    function wrapContent() {
        if (document.getElementById('sproz-content')) return;
        var shell = document.getElementById('sproz-shell');
        var wrap = document.createElement('div');
        wrap.id = 'sproz-content';
        // Move every body child EXCEPT our shell into the wrap
        var nodes = Array.from(document.body.childNodes).filter(function(n){ return n !== shell; });
        nodes.forEach(function(n){ wrap.appendChild(n); });
        document.body.insertBefore(wrap, shell || null);
    }

    /* ══ SHELL — bar lives here, never touched by nav ════════════════ */
    function buildShell() {
        var shell = document.getElementById('sproz-shell');
        if (!shell) {
            shell = document.createElement('div');
            shell.id = 'sproz-shell';
            shell.style.cssText = 'position:fixed;bottom:0;left:0;right:0;z-index:2147483647;pointer-events:none;';
            document.body.appendChild(shell);
        }
        var wave = document.getElementById('sproz-global-wave');
        var bar  = document.getElementById('sproz-sticky-bar');
        if (wave) { wave.style.cssText='position:fixed;bottom:-200px;left:0;width:200px;height:20px;overflow:hidden;opacity:0;pointer-events:none;visibility:hidden;'; shell.appendChild(wave); }
        if (bar)  { bar.style.pointerEvents='all'; shell.appendChild(bar); }
    }

    /* ══ DOM refs ════════════════════════════════════════════════════ */
    function cacheRefs() {
        $bar=$bar||document.getElementById('sproz-sticky-bar'); if(!$bar) return;
        $art    = $bar.querySelector('.sproz-sticky-art');
        $artImg = $bar.querySelector('#sproz-sticky-art-img');
        $title  = $bar.querySelector('#sproz-sticky-title');
        $artist = $bar.querySelector('#sproz-sticky-artist');
        $play   = $bar.querySelector('.sproz-sb-play');
        $prev   = $bar.querySelector('.sproz-sb-prev');
        $next   = $bar.querySelector('.sproz-sb-next');
        $shuf   = $bar.querySelector('.sproz-sb-shuffle');
        $rep    = $bar.querySelector('.sproz-sb-repeat');
        $seek   = $bar.querySelector('.sproz-sticky-seek-inner,#sproz-sb-seek');
        $fill   = $bar.querySelector('.sproz-sticky-progress-fill-inner,#sproz-sb-fill');
        $cur    = $bar.querySelector('.sproz-sb-cur');
        $dur    = $bar.querySelector('.sproz-sb-dur');
        $vol    = $bar.querySelector('#sproz-sb-volume');
        $queue  = $bar.querySelector('#sproz-sb-queue');
        $qList  = $bar.querySelector('#sproz-sb-queue-list');
        var x   = $bar.querySelector('#sproz-sb-close');
        if (x) x.addEventListener('click', closeBar);
    }

    /* ══ WaveSurfer ══════════════════════════════════════════════════ */
    function initWave() {
        var c = document.getElementById('sproz-global-wave');
        if (!c||!window.WaveSurfer) return;
        SP.ws = WaveSurfer.create({ container:c, waveColor:'#b4ff6e', progressColor:'#6fff00', height:1, barWidth:2, interact:false, normalize:true, minPxPerSec:1, fillParent:false, width:200 });
        SP.ws.on('ready', function(){
            SP.ready=true;
            if($dur) $dur.textContent=fmt(SP.ws.getDuration());
            if(SP.position>0){ var d=SP.ws.getDuration(); if(d>0) SP.ws.seekTo(Math.min(SP.position/d,0.99)); }
            if(SP.isPlaying){ var p=SP.ws.play(); if(p&&p.catch) p.catch(function(){ SP.isPlaying=false; syncBtn(); renderRows(); }); }
            else SP.ws.pause();
        });
        SP.ws.on('timeupdate', function(cur){
            SP.position=cur; var d=SP.ws.getDuration()||1, pct=(cur/d)*100;
            if($cur) $cur.textContent=fmt(cur); if($fill) $fill.style.width=pct+'%'; if($seek) $seek.value=Math.round((cur/d)*1000);
        });
        SP.ws.on('finish', function(){ SP.isRepeat?(SP.ws.seekTo(0),SP.ws.play()):goNext(); });
        SP.ws.on('error',  function(e){ console.warn('[Sproz]',e); SP._loadedUrl=null; });
        SP.ws.setVolume(SP.volume);
    }

    /* ══ Storage ═════════════════════════════════════════════════════ */
    function save(){
        if(!SP.tracks.length) return;
        try{ sessionStorage.setItem(STORE,JSON.stringify({tracks:SP.tracks,index:SP.index,volume:SP.volume,isRepeat:SP.isRepeat,isShuffle:SP.isShuffle,position:SP.position,isPlaying:SP.isPlaying})); }catch(e){}
    }
    function restoreState(){
        try{
            var raw=sessionStorage.getItem(STORE); if(!raw) return;
            var s=JSON.parse(raw); if(!s||!s.tracks||!s.tracks.length) return;

            SP.tracks=s.tracks; SP.index=s.index||0; SP.volume=s.volume!=null?s.volume:1;
            SP.isRepeat=!!s.isRepeat; SP.isShuffle=!!s.isShuffle; SP.position=s.position||0;
            SP.isPlaying=!!s.isPlaying;
            SP.shuffleOrder=rng(SP.tracks.length);

            if($vol) $vol.value=SP.volume;
            if($rep) $rep.classList.toggle('active',SP.isRepeat);
            if($shuf) $shuf.classList.toggle('active',SP.isShuffle);
            if(SP.ws) SP.ws.setVolume(SP.volume);

            updateBar(); showBar(); renderRows();

            // AJAX nav: shell is alive, WaveSurfer never stopped — touch nothing
            if(_ajaxNav){ _ajaxNav=false; return; }

            // Real page reload — need to reload audio
            if(SP.isPlaying){
                loadTrack(SP.index, true);
            } else {
                loadTrack(SP.index, false);
            }
        }catch(e){ console.warn('[Sproz] restoreState error:',e); }
    }
    window.addEventListener('pagehide',save);
    window.addEventListener('beforeunload',save);

    /* ══ Play API ════════════════════════════════════════════════════ */
    window.sprozPlay = function(tracks,idx){
        SP.tracks=tracks; SP.index=idx; SP.position=0; SP.isPlaying=true; SP.ready=false;
        SP._loadedUrl=null; // force reload for new track
        SP.shuffleOrder=rng(tracks.length);
        updateBar(); loadTrack(idx,true); showBar(); save(); renderRows();
    };
    function updateBar(){
        var t=SP.tracks[SP.index]; if(!t) return;
        if($title) $title.textContent=t.title||'—'; if($artist) $artist.textContent=t.artist||'';
        if(t.art){ if($artImg) $artImg.src=t.art; if($art) $art.classList.add('has-art'); }
        else     { if($artImg) $artImg.src='';   if($art) $art.classList.remove('has-art'); }
        if($art) $art.classList.toggle('is-spinning',SP.isPlaying);
        if($cur) $cur.textContent=fmt(SP.position); if($fill) $fill.style.width='0%'; if($seek) $seek.value=0;
        syncBtn(); buildQueue();
    }
    function loadTrack(idx,play){
        var t=SP.tracks[idx]; if(!t||!t.url||!SP.ws) return;
        SP.isPlaying=!!play;
        // Same track already loaded — skip ws.load() entirely, no delay
        if(SP._loadedUrl===t.url && SP.ready){
            if(play){
                var p=SP.ws.play();
                if(p&&p.catch) p.catch(function(){ SP.isPlaying=false; syncBtn(); renderRows(); });
            } else { SP.ws.pause(); }
            syncBtn(); renderRows(); return;
        }
        // New track — load it
        SP.ready=false; SP._loadedUrl=t.url;
        SP.ws.load(t.url);
        if(!play) SP.ws.once('ready',function(){ SP.ws.pause(); });
    }
    function setPlay(state){
        SP.isPlaying=state;
        if(SP.ws&&SP.ready){ if(state){ var p=SP.ws.play(); if(p&&p.catch) p.catch(function(){ SP.isPlaying=false; syncBtn(); }); } else SP.ws.pause(); }
        syncBtn(); if($art) $art.classList.toggle('is-spinning',SP.isPlaying); renderRows(); save();
    }
    function goNext(){ var n=SP.isShuffle?sNext():SP.index+1; if(n>=SP.tracks.length){ if(SP.isRepeat)n=0; else{setPlay(false);return;} } SP.index=n;SP.position=0;updateBar();loadTrack(n,true);save(); }
    function goPrev(){ if(SP.ws&&SP.ws.getCurrentTime()>3){SP.ws.seekTo(0);return;} var n=SP.isShuffle?sPrev():SP.index-1; if(n<0)n=SP.tracks.length-1; SP.index=n;SP.position=0;updateBar();loadTrack(n,true);save(); }
    function sNext(){ var i=SP.shuffleOrder.indexOf(SP.index); return SP.shuffleOrder[(i+1)%SP.shuffleOrder.length]; }
    function sPrev(){ var i=SP.shuffleOrder.indexOf(SP.index); return SP.shuffleOrder[(i-1+SP.shuffleOrder.length)%SP.shuffleOrder.length]; }
    function syncBtn(){
        if(!$play) return;
        var ip=$play.querySelector('.sproz-sb-icon-play'),ipa=$play.querySelector('.sproz-sb-icon-pause');
        if(ip) ip.style.display=SP.isPlaying?'none':''; if(ipa) ipa.style.display=SP.isPlaying?'':'none';
    }
    function showBar(){ if(!$bar) return; $bar.classList.remove('sproz-sticky-hidden'); $bar.classList.add('sproz-sticky-visible'); document.body.style.paddingBottom='72px'; }
    function closeBar(){ if(!$bar) return; $bar.classList.add('sproz-sticky-hidden'); $bar.classList.remove('sproz-sticky-visible'); document.body.style.paddingBottom=''; if(SP.ws) SP.ws.pause(); SP.isPlaying=false; SP.tracks=[]; try{sessionStorage.removeItem(STORE);}catch(e){} renderRows(); }

    /* ══ Row highlighting ════════════════════════════════════════════ */
    function renderRows(){
        var cId=String((SP.tracks[SP.index]||{}).id||''),play=SP.isPlaying; _rId=cId; _rPlay=play;
        var seen=new Set();
        document.querySelectorAll('.sproz-row,.sproz-v2-row,.sproz-playlist-item').forEach(function(row){
            if(seen.has(row)) return; seen.add(row);
            var p=row.closest('.sproz-player,.sproz-player'); if(!p){setInactive(row);return;}
            var list=JSON.parse(p.dataset.tracks||'[]'),idx=parseInt(row.dataset.index,10);
            if(isNaN(idx)||!list[idx]){setInactive(row);return;}
            String(list[idx].id||'')===cId?setActive(row,play):setInactive(row);
        });
        syncBtn();
    }
    function setActive(r,pl){
        r.classList.add('is-playing'); r.classList.toggle('is-paused',!pl);
        r.style.setProperty('background','rgba(180,255,110,.10)','important'); r.style.setProperty('border-left','3px solid #b4ff6e','important');
        var num=r.querySelector('.sproz-track-num,.sproz-v2-num'),icon=r.querySelector('.sproz-row-play-icon,.sproz-v2-row-play-icon');
        var bars=r.querySelector('.sproz-bars,.sproz-v2-playing-bars'),title=r.querySelector('.sproz-row-title,.sproz-v2-row-title');
        var bEls=bars?bars.querySelectorAll('.sproz-bar,.sproz-v2-bar'):[];
        if(num) num.style.setProperty('display','none','important'); if(icon) icon.style.setProperty('display','none','important');
        if(bars) bars.style.setProperty('display','flex','important'); if(title) title.style.setProperty('color','#b4ff6e','important');
        bEls.forEach(function(b){ b.style.setProperty('display','block','important'); b.style.setProperty('animation-play-state',pl?'running':'paused','important'); });
    }
    function setInactive(r){
        r.classList.remove('is-playing','is-paused'); r.style.removeProperty('background'); r.style.removeProperty('border-left');
        var num=r.querySelector('.sproz-track-num,.sproz-v2-num'),icon=r.querySelector('.sproz-row-play-icon,.sproz-v2-row-play-icon');
        var bars=r.querySelector('.sproz-bars,.sproz-v2-playing-bars'),title=r.querySelector('.sproz-row-title,.sproz-v2-row-title');
        var bEls=bars?bars.querySelectorAll('.sproz-bar,.sproz-v2-bar'):[];
        if(num) num.style.removeProperty('display'); if(icon) icon.style.setProperty('display','none','important');
        if(bars) bars.style.setProperty('display','none','important'); if(title) title.style.removeProperty('color');
        bEls.forEach(function(b){ b.style.setProperty('display','none','important'); });
    }
    function syncRows(){ var cId=String((SP.tracks[SP.index]||{}).id||''); if(cId!==String(_rId)||SP.isPlaying!==_rPlay) renderRows(); }

    /* ══ Queue ═══════════════════════════════════════════════════════ */
    function buildQueue(){
        if(!$qList) return; $qList.innerHTML='';
        SP.tracks.forEach(function(t,i){
            var d=document.createElement('div');
            d.className='sproz-sb-queue-item'+(i===SP.index?' is-active':'');
            d.innerHTML='<span class="sproz-sb-queue-num">'+(i+1)+'</span>'+(t.art?'<img class="sproz-sb-q-art" src="'+esc(t.art)+'" alt="" />':'<div class="sproz-sb-q-art" style="background:#1a1a26;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#555">♪</div>')+'<div class="sproz-sb-q-info"><span class="sproz-sb-q-title">'+esc(t.title)+'</span><span class="sproz-sb-q-artist">'+esc(t.artist||'')+'</span></div><span class="sproz-sb-q-dur">'+esc(t.duration||'')+'</span>';
            d.addEventListener('click',function(){ SP.index=i;SP.position=0;updateBar();loadTrack(i,true);save(); });
            $qList.appendChild(d);
        });
    }

    /* ══ Bar events ══════════════════════════════════════════════════ */
    function barEvents(){
        if(!$bar) return;
        if($play) $play.addEventListener('click',function(){ setPlay(!SP.isPlaying); });
        if($prev) $prev.addEventListener('click',goPrev); if($next) $next.addEventListener('click',goNext);
        if($shuf) $shuf.addEventListener('click',function(){ SP.isShuffle=!SP.isShuffle; $shuf.classList.toggle('active',SP.isShuffle); save(); });
        if($rep)  $rep.addEventListener('click',function(){  SP.isRepeat=!SP.isRepeat;   $rep.classList.toggle('active',SP.isRepeat);   save(); });
        if($seek) $seek.addEventListener('input',function(){ if(!SP.ws||!SP.ready) return; var r=$seek.value/1000; SP.ws.seekTo(r); if($fill) $fill.style.width=(r*100)+'%'; });
        if($vol)  $vol.addEventListener('input',function(){  SP.volume=parseFloat($vol.value); if(SP.ws) SP.ws.setVolume(SP.volume); save(); });
        var qBtn=$bar.querySelector('#sproz-sb-queue-toggle'),qClose=$bar.querySelector('#sproz-sb-queue-close');
        if(qBtn)   qBtn.addEventListener('click',  function(){ if($queue) $queue.classList.toggle('open'); });
        if(qClose) qClose.addEventListener('click',function(){ if($queue) $queue.classList.remove('open'); });
    }

    /* ══ Bind inline players ══════════════════════════════════════════ */
    function bindPlayers(scope){
        scope.querySelectorAll('.sproz-player:not([data-b]),.sproz-player:not([data-b])').forEach(function(el){
            el.setAttribute('data-b','1');
            var tracks=JSON.parse(el.dataset.tracks||'[]'); if(!tracks.length) return;
            el.querySelectorAll('.sproz-row,.sproz-v2-row').forEach(setInactive);
            el.querySelectorAll('.sproz-row,.sproz-v2-row').forEach(function(row){
                row.addEventListener('click',function(e){ e.stopPropagation(); var idx=parseInt(row.dataset.index,10); if(!isNaN(idx)) window.sprozPlay(tracks,idx); });
            });
            el.querySelectorAll('.sproz-btn-play-all,.sproz-v2-play-all,.spz-btn-play').forEach(function(btn){
                btn.addEventListener('click',function(e){ e.stopPropagation(); var same=SP.tracks.length&&tracks.length&&String((SP.tracks[0]||{}).id)===String((tracks[0]||{}).id); same?setPlay(!SP.isPlaying):window.sprozPlay(tracks,0); });
            });
        });
    }

    /* ══════════════════════════════════════════════════════════════
       AJAX NAVIGATION
       The shell is FIXED and outside the document flow.
       We only swap the page content. Audio never stops.

       Strategy:
       1. Intercept link click
       2. fetch() the new URL as text
       3. Regex-extract <body> content
       4. Find .site-main (Hello Elementor) or fallback
       5. Swap innerHTML of that element only
       6. Re-run page-specific inline scripts
       7. NEVER call location.href — that reloads the page
    ══════════════════════════════════════════════════════════════ */
    function initNav(){
        document.addEventListener('click', onLink, true);
        window.addEventListener('popstate',function(e){
            if(e.state&&e.state.sproz) navigate(e.state.url,false);
        });
        history.replaceState({sproz:true,url:location.href},'',location.href);
    }

    function onLink(e){
        var a=e.target.closest('a[href]'); if(!a) return;
        var href=a.getAttribute('href')||'';
        if(!href||/^(#|mailto:|tel:|javascript:)/i.test(href)) return;
        var url; try{ url=new URL(href,location.href); }catch(x){ return; }
        if(url.origin!==location.origin)                              return;
        if(/\/(wp-admin|wp-login|wp-json)/i.test(url.pathname))      return;
        if(/\.(pdf|zip|mp3|mp4|docx?|xlsx?|png|jpe?g|gif|svg|ico|woff2?)$/i.test(url.pathname)) return;
        if(a.hasAttribute('download'))                                return;
        if(a.target&&a.target!=='_self')                              return;
        if(e.ctrlKey||e.metaKey||e.shiftKey||e.altKey)               return;
        if(url.href===location.href)                                  return;
        e.preventDefault();
        e.stopPropagation();
        save();
        _ajaxNav = true; // flag: next boot() call (if any) should skip audio restore
        navigate(url.href,true);
    }

    function navigate(url, push){
        if(_busy) return; _busy=true;

        // Fade the content area
        var contentEl = getContentEl();
        if(contentEl){ contentEl.style.transition='opacity .12s'; contentEl.style.opacity='0.2'; }

        fetch(url, {headers:{'X-Requested-With':'XMLHttpRequest'}, credentials:'same-origin', cache:'no-store'})
        .then(function(r){
            if(!r.ok) throw new Error('HTTP '+r.status);
            return r.text();
        })
        .then(function(html){
            // Update title
            var tm=html.match(/<title[^>]*>([\s\S]*?)<\/title>/i);
            if(tm) document.title=decodeEntities(tm[1].trim());

            // Extract body content via regex — works across all themes
            var bm=html.match(/<body[^>]*>([\s\S]*)<\/body>/i);
            var bodyHtml=bm?bm[1]:html;

            // Strip plugin shell elements via regex BEFORE parsing into DOM
            // Prevents browser from initialising audio/media elements in the temp div
            bodyHtml = bodyHtml
                .replace(/<div[^>]+id=["']sproz-shell["'][^>]*>[\s\S]*?<\/div>/gi,'')
                .replace(/<div[^>]+id=["']sproz-shell["'][^>]*>[\s\S]*?<\/div>/gi,'')
                .replace(/<div[^>]+id=["']sproz-content["'][^>]*>/gi,'')
                .replace(/<div[^>]+id=["']sproz-sticky-bar["'][^>]*>[\s\S]*?<\/div>/gi,'')
                .replace(/<div[^>]+id=["']sproz-global-wave["'][^>]*>[\s\S]*?<\/div>/gi,'');

            // Parse into temp element
            var tmp=document.createElement('div');
            tmp.innerHTML=bodyHtml;

            // Also strip via DOM in case regex missed anything
            ['#sproz-shell','#sproz-shell','#sproz-content',
             '#sproz-sticky-bar','#sproz-global-wave'].forEach(function(s){
                tmp.querySelectorAll(s).forEach(function(el){ el.parentNode && el.parentNode.removeChild(el); });
            });

            // Find the best matching element to swap
            // Priority: Hello Elementor (.site-main) → generic content selectors
            var swapSels=['.site-main','#content','.site-content','.entry-content',
                          'main','#main','.main-content','#primary','.content-area'];
            var newEl=null, oldEl=null;
            for(var i=0;i<swapSels.length;i++){
                newEl=tmp.querySelector(swapSels[i]);
                if(newEl){
                    oldEl=document.querySelector(swapSels[i]);
                    if(oldEl&&!oldEl.closest('#sproz-shell')) break;
                    newEl=null; oldEl=null;
                }
            }

            // Always prefer #sproz-content — created by wrapContent() on boot
            var contentWrap = document.getElementById('sproz-content');

            if(newEl && oldEl && !oldEl.closest('#sproz-shell')){
                // Swap the matching inner element (e.g. .site-main)
                oldEl.innerHTML = newEl.innerHTML;
                afterSwap(oldEl);
            } else if(contentWrap) {
                // Fallback: swap everything inside our wrap div
                // Strip plugin shell elements from tmp first
                contentWrap.innerHTML = tmp.innerHTML;
                afterSwap(contentWrap);
            } else {
                // Last resort: ensureWrap (should never happen after wrapContent())
                var wrap = ensureWrap();
                if(wrap){ wrap.innerHTML = tmp.innerHTML; afterSwap(wrap); }
            }

            if(push) history.pushState({sproz:true,url:url},'',url);

            _rId=null; renderRows();
            if(SP.tracks.length){
                document.body.style.paddingBottom='72px';
                // Music is already playing in the shell — don't touch it
                // Just update bar display and row highlights
                if(SP.isPlaying){ updateBar(); }
            }
            window.scrollTo(0,0);

            // Fade back in
            var ce=getContentEl();
            if(ce) requestAnimationFrame(function(){
                ce.style.opacity='1';
                setTimeout(function(){ ce.style.transition=''; },150);
            });
        })
        .catch(function(err){
            // NEVER reload the page — audio would stop.
            // Just restore opacity and log.
            console.warn('[Sproz] nav error (audio continues):', err);
            var ce=getContentEl();
            if(ce){ ce.style.opacity='1'; ce.style.transition=''; }
            // Push URL so back button works even if content didn't swap
            if(push) history.pushState({sproz:true,url:url},'',url);
        })
        .finally(function(){ _busy=false; });
    }

    function afterSwap(scope){
        // Re-run only inline (no src) non-player scripts — page widgets etc
        scope.querySelectorAll('script:not([src])').forEach(function(old){
            var c=old.textContent||'';
            if(/SprozPlayer|WaveSurfer|sproz-shell/i.test(c)) return;
            try{
                var s=document.createElement('script');
                s.textContent=c;
                old.parentNode.replaceChild(s,old);
            }catch(e){}
        });
        bindPlayers(scope);
        // Elementor re-init
        try{
            if(window.elementorFrontend&&window.elementorFrontend.init){
                setTimeout(function(){ try{ window.elementorFrontend.init(); }catch(e){} },100);
            }
        }catch(e){}
    }

    function getContentEl(){
        // #sproz-content is our guaranteed wrap — always prefer it for fade transitions
        var wrap = document.getElementById('sproz-content');
        if(wrap) return wrap;
        var sels=['.site-main','#content','.site-content','main','#main','#primary'];
        for(var i=0;i<sels.length;i++){
            var el=document.querySelector(sels[i]);
            if(el&&!el.closest('#sproz-shell')) return el;
        }
        return document.body;
    }

    function ensureWrap(){
        var w=document.getElementById('sproz-content');
        if(w) return w;
        var shell=document.getElementById('sproz-shell');
        w=document.createElement('div'); w.id='sproz-content';
        var nodes=Array.from(document.body.childNodes).filter(function(n){ return n!==shell; });
        nodes.forEach(function(n){ w.appendChild(n); });
        document.body.insertBefore(w,shell);
        return w;
    }

    function decodeEntities(s){
        return s.replace(/&amp;/g,'&').replace(/&#039;/g,"'").replace(/&quot;/g,'"').replace(/&lt;/g,'<').replace(/&gt;/g,'>');
    }

    /* ══ Helpers ══════════════════════════════════════════════════════ */
    function fmt(s){ if(!s||isNaN(s)) return '0:00'; return Math.floor(s/60)+':'+String(Math.floor(s%60)).padStart(2,'0'); }
    function esc(s){ var d=document.createElement('div'); d.textContent=String(s||''); return d.innerHTML; }
    function rng(n){ return Array.from({length:n},function(_,i){return i;}); }

})();
