<?php
// Shared saved-deck-links library renderer. Identity is the deck's `decklink`
// (the favoritedeck dual PK with usersId). Surfaces: SWUSim MainMenu + Profile.
//
// One presentation everywhere: a name-only <select> (favorites first, marked ★) — no card art.
//   - RenderDeckLibrary($uid)                          → selector only (MainMenu: load a deck to queue)
//   - RenderDeckLibrary($uid, ['actionButtons'=>true]) → selector + Favorite/Rename/Delete + wiring (Profile)
require_once __DIR__ . '/../../Database/functions.inc.php';

// Self-contained management wiring, emitted once per page when actionButtons is on. Posts to the
// SavedDecks endpoint anchored at /TCGEngine/ (works regardless of the page's URL depth) and reloads.
function _DeckLibraryScript(): string {
    return "<script>(function(){
  if (window.__deckLibWired) return; window.__deckLibWired = true;
  function appBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0 ? p.slice(0,i+11) : '/TCGEngine/'; }
  var URL = appBase() + 'SWUSim/SavedDecks.php';
  function post(params){ var x=new XMLHttpRequest(); x.open('POST',URL,true);
    x.setRequestHeader('Content-Type','application/x-www-form-urlencoded'); x.onload=function(){location.reload();}; x.send(params); }

  // Custom confirm dialog (no native confirm). Resolves true on confirm, false otherwise.
  // The message is set via textContent so deck names can't inject markup.
  function confirmDialog(title, message, confirmLabel){
    return new Promise(function(resolve){
      var ov = document.createElement('div'); ov.className='dl-modal-overlay';
      ov.innerHTML = \"<div class='dl-modal' role='dialog' aria-modal='true'>\"
        + \"<h3 class='dl-modal-title'></h3><p class='dl-modal-message'></p>\"
        + \"<div class='dl-modal-actions'><button type='button' class='dl-modal-cancel'>Cancel</button>\"
        + \"<button type='button' class='dl-modal-confirm dl-modal-danger'></button></div></div>\";
      ov.querySelector('.dl-modal-title').textContent = title;
      ov.querySelector('.dl-modal-message').textContent = message;
      ov.querySelector('.dl-modal-confirm').textContent = confirmLabel;
      document.body.appendChild(ov);
      function done(v){ ov.remove(); document.removeEventListener('keydown', onKey, true); resolve(v); }
      function onKey(e){ if(e.key==='Escape'){ e.preventDefault(); done(false); } else if(e.key==='Enter'){ e.preventDefault(); done(true); } }
      ov.querySelector('.dl-modal-cancel').onclick = function(){ done(false); };
      ov.querySelector('.dl-modal-confirm').onclick = function(){ done(true); };
      ov.addEventListener('mousedown', function(e){ if(e.target===ov) done(false); });
      document.addEventListener('keydown', onKey, true);
      ov.querySelector('.dl-modal-confirm').focus();
    });
  }

  // Custom prefilled rename dialog (no native prompt). Resolves the trimmed value, or null on cancel.
  function renameDialog(currentName){
    return new Promise(function(resolve){
      var ov = document.createElement('div'); ov.className='dl-modal-overlay';
      ov.innerHTML = \"<div class='dl-modal' role='dialog' aria-modal='true'>\"
        + \"<h3 class='dl-modal-title'>Rename Deck</h3>\"
        + \"<input type='text' class='dl-modal-input' maxlength='128'>\"
        + \"<div class='dl-modal-actions'><button type='button' class='dl-modal-cancel'>Cancel</button>\"
        + \"<button type='button' class='dl-modal-save'>Save</button></div></div>\";
      document.body.appendChild(ov);
      var input = ov.querySelector('.dl-modal-input');
      input.value = currentName || ''; input.focus(); input.select();
      function done(v){ ov.remove(); document.removeEventListener('keydown', onKey, true); resolve(v); }
      function save(){ var v=(input.value||'').trim(); done(v ? v : null); }
      function onKey(e){ if(e.key==='Escape'){ e.preventDefault(); done(null); } else if(e.key==='Enter'){ e.preventDefault(); save(); } }
      ov.querySelector('.dl-modal-cancel').onclick = function(){ done(null); };
      ov.querySelector('.dl-modal-save').onclick = save;
      ov.addEventListener('mousedown', function(e){ if(e.target===ov) done(null); });
      document.addEventListener('keydown', onKey, true);
    });
  }

  document.addEventListener('click', function(e){
    var btn = e.target.closest('.deck-library-dropdown [data-action]'); if(!btn) return;
    var wrap = btn.closest('.deck-library-dropdown'); var sel = wrap ? wrap.querySelector('.dl-select') : null;
    var opt = sel ? sel.options[sel.selectedIndex] : null;
    if(!opt || !opt.getAttribute('data-id')){ return; }
    var link = opt.getAttribute('data-id'), act = btn.getAttribute('data-action');
    if(act==='favorite'){ post('action=favorite&decklink='+encodeURIComponent(link)+'&value='+(opt.getAttribute('data-fav')==='1'?0:1)); }
    else if(act==='delete'){
      var dn = opt.getAttribute('data-name') || 'this deck';
      confirmDialog('Delete Deck', 'Remove \"'+dn+'\" from your saved decks? This cannot be undone.', 'Delete').then(function(ok){
        if(ok) post('action=delete&decklink='+encodeURIComponent(link));
      });
    }
    else if(act==='rename'){
      renameDialog(opt.getAttribute('data-name')||'').then(function(n){
        if(n && n !== (opt.getAttribute('data-name')||'')) post('action=rename&decklink='+encodeURIComponent(link)+'&name='+encodeURIComponent(n));
      });
    }
  });

  document.addEventListener('change', function(e){
    var sel = e.target.closest('.deck-library-dropdown .dl-select'); if(!sel) return;
    var wrap = sel.closest('.deck-library-dropdown');
    var box = wrap ? wrap.querySelector('.dl-stats') : null; if(!box) return;
    var opt = sel.options[sel.selectedIndex];
    if(!opt || !opt.getAttribute('data-id')){ box.textContent = box.getAttribute('data-empty'); return; }
    var w = parseInt(opt.getAttribute('data-wins')||'0',10), l = parseInt(opt.getAttribute('data-losses')||'0',10);
    var total = w + l;
    var head = total===0 ? 'No games yet'
      : (w+' win'+(w===1?'':'s')+' \\u00b7 '+l+' loss'+(l===1?'':'es')+' \\u00b7 '+Math.round(w*100/total)+'%');
    box.innerHTML = '<div class=\"dl-stats-overall\">'+head+'</div><div class=\"dl-stats-matchups\">\\u2026</div>';
    var mu = box.querySelector('.dl-stats-matchups');
    var x = new XMLHttpRequest();
    x.open('POST', URL, true); x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    x.onload = function(){ var r={}; try{ r=JSON.parse(x.responseText); }catch(e){}
      if(!r.success || !r.matchups || !r.matchups.length){ mu.textContent = total===0 ? '' : 'No matchup data yet.'; return; }
      var rows = r.matchups.map(function(m){
        return '<div class=\"dl-mu-row\"><span class=\"dl-mu-vs\">vs '+esc(m.oppLeaderTitle)+' / '+esc(m.oppBaseLabel)
             + '</span><span class=\"dl-mu-rec\">'+m.wins+'\\u2013'+m.losses+'</span></div>'; }).join('');
      mu.innerHTML = '<div class=\"dl-mu-title\">Matchups</div>'+rows;
    };
    x.send('action=matchups&decklink='+encodeURIComponent(opt.getAttribute('data-id')));
  });
  function esc(s){ return String(s==null?'':s).replace(/[&<>\"]/g, function(ch){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;'}[ch]; }); }
})();</script>";
}

function RenderDeckLibrary(int $userId, array $config = []): string {
    $actionButtons = !empty($config['actionButtons']);   // default false; true on the Profile page
    $decks = $config['decks'] ?? LoadSavedDecks($userId);
    if (empty($decks)) {
        $msg = $config['emptyText'] ?? 'No saved decks yet — paste a deck link and save it.';
        return "<div class='deck-library deck-library-empty'>" . htmlspecialchars($msg, ENT_QUOTES) . "</div>";
    }
    $esc = fn($s) => htmlspecialchars(strval($s ?? ''), ENT_QUOTES);

    $opts = "<option value=\"\">— Select a saved deck —</option>";
    foreach ($decks as $deck) {
        $name = ($deck['name'] ?? '') !== '' ? $deck['name'] : 'Untitled Deck';
        $fav  = !empty($deck['isFavorite']);
        // Queue input: URL decks queue by link; raw decks queue by their decoded JSON (decklink is a 'raw:'+hash sentinel).
        $queueInput = !empty($deck['deckContent']) ? base64_decode($deck['deckContent']) : ($deck['decklink'] ?? '');
        $opts .= "<option data-id=\"{$esc($deck['decklink'] ?? '')}\" data-queue-input=\"{$esc($queueInput)}\" "
               . "data-name=\"{$esc($name)}\" data-fav=\"" . ($fav ? '1' : '0') . "\" "
               . "data-wins=\"" . (int)($deck['wins'] ?? 0) . "\" data-losses=\"" . (int)($deck['losses'] ?? 0) . "\">"
               . ($fav ? '★ ' : '') . $esc($name) . "</option>";
    }
    $sel = "<select class='dl-select'>$opts</select>";
    if (!$actionButtons) {
        return "<div class='deck-library deck-library-dropdown'>$sel</div>";
    }
    $btns = "<button class='dl-act' data-action=\"favorite\">★ Favorite</button>"
          . "<button class='dl-act' data-action=\"rename\">Rename</button>"
          . "<button class='dl-act' data-action=\"delete\">Delete</button>";
    return "<div class='deck-library deck-library-dropdown'>$sel"
         . "<div class='dl-dropdown-actions'>$btns</div>"
         . "<div class='dl-stats' data-empty='Select a deck to see its stats.'>Select a deck to see its stats.</div>"
         . "</div>"
         . _DeckLibraryScript();
}
