<?php
// Shared saved-deck-links library renderer.
//
// The UI is storage-agnostic: account/database decks and browser-local decks use
// the same dropdown/actions markup. The storage mode is selected with config:
//   - storage => account (default): LoadSavedDecks + SavedDecks.php endpoint
//   - storage => local: browser localStorage only, no login required
require_once __DIR__ . '/../../Database/functions.inc.php';

function DeckLibraryConfigFromSiteDef(array $def, array $overrides = []): array {
    $cfg = $def['deckLibrary'] ?? [];
    $cfg['rootName'] = $cfg['rootName'] ?? ($def['identity']['rootName'] ?? 'default');
    return array_merge($cfg, $overrides);
}

function _DeckLibraryScript(array $config = []): string {
    $storage = ($config['storage'] ?? 'account') === 'local' ? 'local' : 'account';
    $endpoint = $config['endpoint'] ?? 'SWUSim/SavedDecks.php';
    $storageKey = $config['localStorageKey'] ?? ('tcgengine:savedDecks:' . ($config['rootName'] ?? 'default'));
    $safeEndpoint = json_encode($endpoint);
    $safeStorageKey = json_encode($storageKey);
    $safeStorage = json_encode($storage);

    return "<script>(function(){
  if (window.__deckLibWired) return; window.__deckLibWired = true;
  function appBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0 ? p.slice(0,i+11) : '/TCGEngine/'; }
  var DEFAULT_STORAGE = $safeStorage;
  var DEFAULT_LOCAL_KEY = $safeStorageKey;
  var URL = appBase() + $safeEndpoint;
  function post(params){ var x=new XMLHttpRequest(); x.open('POST',URL,true);
    x.setRequestHeader('Content-Type','application/x-www-form-urlencoded'); x.onload=function(){location.reload();}; x.send(params); }

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

  function renameDialog(currentName){
    return new Promise(function(resolve){
      var ov = document.createElement('div'); ov.className='dl-modal-overlay';
      ov.innerHTML = \"<div class='dl-modal' role='dialog' aria-modal='true'>\"
        + \"<h3 class='dl-modal-title'>Deck Name</h3>\"
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

  function esc(s){ return String(s==null?'':s).replace(/[&<>\"]/g, function(ch){
    return {'&':'&amp;','<':'&lt;','>':'&gt;','\"':'&quot;'}[ch]; }); }
  function readLocalDecks(key){
    try {
      var raw = localStorage.getItem(key || DEFAULT_LOCAL_KEY);
      var decks = raw ? JSON.parse(raw) : [];
      return Array.isArray(decks) ? decks.filter(function(d){ return d && d.input; }) : [];
    } catch(e) { return []; }
  }
  function writeLocalDecks(key, decks){
    try { localStorage.setItem(key || DEFAULT_LOCAL_KEY, JSON.stringify(decks || [])); return true; }
    catch(e) { return false; }
  }
  function localHash(input){
    var h = 0, s = String(input || '');
    for (var i = 0; i < s.length; i++) { h = ((h << 5) - h + s.charCodeAt(i)) | 0; }
    return 'local:' + Math.abs(h).toString(36) + ':' + s.length;
  }
  function localSort(a, b){
    var af = a.isFavorite ? 1 : 0, bf = b.isFavorite ? 1 : 0;
    if (af !== bf) return bf - af;
    var al = a.lastUsed || '', bl = b.lastUsed || '';
    if (al !== bl) return String(bl).localeCompare(String(al));
    return String(a.name || '').localeCompare(String(b.name || ''));
  }
  function renderLocalWrap(wrap){
    if (!wrap) return;
    var key = wrap.getAttribute('data-local-key') || DEFAULT_LOCAL_KEY;
    var sel = wrap.querySelector('.dl-select');
    if (!sel) return;
    var selected = sel.value || '';
    var decks = readLocalDecks(key).sort(localSort);
    var html = '<option value=\"\">-- Select a saved deck --</option>';
    decks.forEach(function(deck){
      var id = deck.id || localHash(deck.input);
      var name = deck.name || 'Untitled Deck';
      html += '<option value=\"' + esc(id) + '\" data-id=\"' + esc(id) + '\" data-queue-input=\"' + esc(deck.input) + '\"'
        + ' data-name=\"' + esc(name) + '\" data-fav=\"' + (deck.isFavorite ? '1' : '0') + '\">'
        + (deck.isFavorite ? '* ' : '') + esc(name) + '</option>';
    });
    sel.innerHTML = html;
    if (selected) sel.value = selected;
    var empty = wrap.querySelector('.deck-library-empty');
    if (empty) empty.style.display = decks.length ? 'none' : '';
  }
  function renderLocalDeckLibraries(){
    document.querySelectorAll('.deck-library-dropdown[data-storage=\"local\"]').forEach(renderLocalWrap);
  }
  function localSaveCurrent(input, opts){
    opts = opts || {};
    input = String(input || '').trim();
    if (!input) return Promise.resolve(false);
    var key = opts.localStorageKey || DEFAULT_LOCAL_KEY;
    var id = localHash(input);
    var decks = readLocalDecks(key);
    var existing = null;
    for (var i = 0; i < decks.length; i++) {
      if ((decks[i].id || localHash(decks[i].input)) === id) { existing = decks[i]; break; }
    }
    var defaultName = opts.name || (existing && existing.name) || 'Saved Deck';
    if (opts.promptName === false) {
      var nowSilent = new Date().toISOString();
      if (existing) {
        existing.input = input;
        existing.lastUsed = nowSilent;
      } else {
        decks.push({ id: id, input: input, name: defaultName, isFavorite: false, createdAt: nowSilent, lastUsed: nowSilent });
      }
      if (!writeLocalDecks(key, decks)) return Promise.resolve(false);
      renderLocalDeckLibraries();
      return Promise.resolve(true);
    }
    return renameDialog(defaultName).then(function(name){
      if (!name) return false;
      var now = new Date().toISOString();
      if (existing) {
        existing.name = name;
        existing.input = input;
        existing.lastUsed = now;
      } else {
        decks.push({ id: id, input: input, name: name, isFavorite: false, createdAt: now, lastUsed: now });
      }
      if (!writeLocalDecks(key, decks)) return false;
      renderLocalDeckLibraries();
      return true;
    });
  }
  window.TCGDeckLibrarySaveCurrent = localSaveCurrent;
  window.TCGDeckLibraryRenderLocal = renderLocalDeckLibraries;

  document.addEventListener('click', function(e){
    var btn = e.target.closest('.deck-library-dropdown [data-action]'); if(!btn) return;
    var wrap = btn.closest('.deck-library-dropdown'); var sel = wrap ? wrap.querySelector('.dl-select') : null;
    var opt = sel ? sel.options[sel.selectedIndex] : null;
    if(!opt || !opt.getAttribute('data-id')){ return; }
    var link = opt.getAttribute('data-id'), act = btn.getAttribute('data-action');
    if (wrap && wrap.getAttribute('data-storage') === 'local') {
      var key = wrap.getAttribute('data-local-key') || DEFAULT_LOCAL_KEY;
      var decks = readLocalDecks(key);
      var deck = null;
      for (var i = 0; i < decks.length; i++) {
        if ((decks[i].id || localHash(decks[i].input)) === link) { deck = decks[i]; break; }
      }
      if (!deck) return;
      if (act === 'favorite') {
        deck.isFavorite = !deck.isFavorite;
        deck.lastUsed = new Date().toISOString();
        writeLocalDecks(key, decks);
        renderLocalWrap(wrap);
      } else if (act === 'delete') {
        confirmDialog('Delete Deck', 'Remove \"' + (deck.name || 'this deck') + '\" from your saved decks? This cannot be undone.', 'Delete').then(function(ok){
          if (!ok) return;
          decks = decks.filter(function(d){ return (d.id || localHash(d.input)) !== link; });
          writeLocalDecks(key, decks);
          renderLocalWrap(wrap);
        });
      } else if (act === 'rename') {
        renameDialog(deck.name || '').then(function(n){
          if (!n) return;
          deck.name = n;
          deck.lastUsed = new Date().toISOString();
          writeLocalDecks(key, decks);
          renderLocalWrap(wrap);
        });
      }
      return;
    }
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
      : (w+' win'+(w===1?'':'s')+' - '+l+' loss'+(l===1?'':'es')+' - '+Math.round(w*100/total)+'%');
    box.innerHTML = '<div class=\"dl-stats-overall\">'+head+'</div><div class=\"dl-stats-matchups\">...</div>';
    var mu = box.querySelector('.dl-stats-matchups');
    var x = new XMLHttpRequest();
    x.open('POST', URL, true); x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    x.onload = function(){ var r={}; try{ r=JSON.parse(x.responseText); }catch(e){}
      if(!r.success || !r.matchups || !r.matchups.length){ mu.textContent = total===0 ? '' : 'No matchup data yet.'; return; }
      var rows = r.matchups.map(function(m){
        return '<div class=\"dl-mu-row\"><span class=\"dl-mu-vs\">vs '+esc(m.oppLeaderTitle)+' / '+esc(m.oppBaseLabel)
             + '</span><span class=\"dl-mu-rec\">'+m.wins+'-'+m.losses+'</span></div>'; }).join('');
      mu.innerHTML = '<div class=\"dl-mu-title\">Matchups</div>'+rows;
    };
    x.send('action=matchups&decklink='+encodeURIComponent(opt.getAttribute('data-id')));
  });
  if (DEFAULT_STORAGE === 'local') renderLocalDeckLibraries();
})();</script>";
}

function RenderDeckLibrary(int $userId, array $config = []): string {
    $storage = ($config['storage'] ?? 'account') === 'local' ? 'local' : 'account';
    $actionButtons = !empty($config['actionButtons']);
    $decks = $config['decks'] ?? ($storage === 'account' ? LoadSavedDecks($userId) : []);
    $emptyText = $config['emptyText'] ?? 'No saved decks yet - paste a deck link and save it.';
    $esc = fn($s) => htmlspecialchars(strval($s ?? ''), ENT_QUOTES);

    if (empty($decks) && $storage !== 'local') {
        return "<div class='deck-library deck-library-empty'>" . $esc($emptyText) . "</div>";
    }

    $opts = "<option value=\"\">-- Select a saved deck --</option>";
    foreach ($decks as $deck) {
        $name = ($deck['name'] ?? '') !== '' ? $deck['name'] : 'Untitled Deck';
        $fav = !empty($deck['isFavorite']);
        $queueInput = !empty($deck['deckContent']) ? base64_decode($deck['deckContent']) : ($deck['decklink'] ?? '');
        $opts .= "<option data-id=\"{$esc($deck['decklink'] ?? '')}\" data-queue-input=\"{$esc($queueInput)}\" "
               . "data-name=\"{$esc($name)}\" data-fav=\"" . ($fav ? '1' : '0') . "\" "
               . "data-wins=\"" . (int)($deck['wins'] ?? 0) . "\" data-losses=\"" . (int)($deck['losses'] ?? 0) . "\">"
               . ($fav ? '★ ' : '') . $esc($name) . "</option>";
    }

    $attrs = "data-storage=\"" . $esc($storage) . "\"";
    if ($storage === 'local') {
        $localKey = $config['localStorageKey'] ?? ('tcgengine:savedDecks:' . ($config['rootName'] ?? 'default'));
        $attrs .= " data-local-key=\"" . $esc($localKey) . "\"";
    }

    $sel = "<select class='dl-select'>$opts</select>";
    $empty = $storage === 'local'
        ? "<div class='deck-library-empty'>" . $esc($emptyText) . "</div>"
        : "";

    if (!$actionButtons) {
        return "<div class='deck-library deck-library-dropdown' $attrs>$sel$empty</div>"
             . ($storage === 'local' ? _DeckLibraryScript($config) : "");
    }

    $btns = "<button class='dl-act' data-action=\"favorite\">* Favorite</button>"
          . "<button class='dl-act' data-action=\"rename\">Rename</button>"
          . "<button class='dl-act' data-action=\"delete\">Delete</button>";
    return "<div class='deck-library deck-library-dropdown' $attrs>$sel"
         . "<div class='dl-dropdown-actions'>$btns</div>"
         . ($storage === 'account'
            ? "<div class='dl-stats' data-empty='Select a deck to see its stats.'>Select a deck to see its stats.</div>"
            : $empty)
         . "</div>"
         . _DeckLibraryScript($config);
}
