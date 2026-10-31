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

  // Delegate to the shared StyledDialog primitive (self-injects its themed CSS, so these
  // now render correctly on every app, not just where the old .dl-modal CSS happened to load).
  function confirmDialog(title, message, confirmLabel){
    return StyledConfirm(message, { title: title, confirmLabel: confirmLabel, danger: true });
  }

  function renameDialog(currentName){
    return StyledPrompt('Enter a name for this deck.', { title: 'Deck Name', initial: currentName || '', confirmLabel: 'Save' });
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
    var d = parseInt(opt.getAttribute('data-draws')||'0',10);   // drawn games (SWUSim); the attribute only exists when > 0
    var total = w + l + d;
    var head = total===0 ? 'No Premier match stats'
      : (w+' win'+(w===1?'':'s')+' - '+l+' loss'+(l===1?'':'es')+(d>0 ? ' - '+d+' draw'+(d===1?'':'s') : '')+' - '+Math.round(w*100/total)+'%');
    box.innerHTML = '<div class=\"dl-stats-overall\">'+head+'</div><div class=\"dl-stats-matchups\">...</div>';
    var mu = box.querySelector('.dl-stats-matchups');
    var x = new XMLHttpRequest();
    x.open('POST', URL, true); x.setRequestHeader('Content-Type','application/x-www-form-urlencoded');
    x.onload = function(){ var r={}; try{ r=JSON.parse(x.responseText); }catch(e){}
      if(!r.success || !r.matchups || !r.matchups.length){ mu.textContent = total===0 ? '' : 'No matchup data yet.'; return; }
      var rows = r.matchups.map(function(m){
        return '<div class=\"dl-mu-row\"><span class=\"dl-mu-vs\">vs '+esc(m.oppLeaderTitle)+' / '+esc(m.oppBaseLabel)
             + '</span><span class=\"dl-mu-rec\">'+m.wins+'-'+m.losses+(m.draws ? '-'+m.draws : '')+'</span></div>'; }).join('');
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
    // keepWhenEmpty: render the (empty) dropdown anyway, for a page that adds decks to it in place (RenderDeckImport).
    $keepWhenEmpty = !empty($config['keepWhenEmpty']);

    if (empty($decks) && $storage !== 'local' && !$keepWhenEmpty) {
        return "<div class='deck-library deck-library-empty'>" . $esc($emptyText) . "</div>";
    }

    $opts = "<option value=\"\">-- Select a saved deck --</option>";
    foreach ($decks as $deck) {
        $name = ($deck['name'] ?? '') !== '' ? $deck['name'] : 'Untitled Deck';
        $fav = !empty($deck['isFavorite']);
        $queueInput = !empty($deck['deckContent']) ? base64_decode($deck['deckContent']) : ($deck['decklink'] ?? '');
        $opts .= "<option data-id=\"{$esc($deck['decklink'] ?? '')}\" data-queue-input=\"{$esc($queueInput)}\" "
               . "data-name=\"{$esc($name)}\" data-fav=\"" . ($fav ? '1' : '0') . "\" "
               . "data-wins=\"" . (int)($deck['wins'] ?? 0) . "\" data-losses=\"" . (int)($deck['losses'] ?? 0) . "\""
               . ((int)($deck['draws'] ?? 0) > 0 ? " data-draws=\"" . (int)$deck['draws'] . "\"" : "") . ">"
               . ($fav ? '★ ' : '') . $esc($name) . "</option>";
    }

    $attrs = "data-storage=\"" . $esc($storage) . "\"";
    if ($storage === 'local') {
        $localKey = $config['localStorageKey'] ?? ('tcgengine:savedDecks:' . ($config['rootName'] ?? 'default'));
        $attrs .= " data-local-key=\"" . $esc($localKey) . "\"";
    }

    $sel = "<select class='dl-select'>$opts</select>";
    $empty = ($storage === 'local' || empty($decks))
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
            : '')
         . $empty
         . "</div>"
         . _DeckLibraryScript($config);
}

// "Import decks": a column of deck-link boxes (one to start, "Add another" up to DECK_IMPORT_MAX), saved to the account
// with one press. Each box posts, one at a time, to the endpoint's existing `save` action, so the links-only rule and
// the name lookup are the menu's own. Saved decks join the panel's dropdown in place; a box that failed keeps its link
// and the reason, so it can be fixed and imported again. Account storage only.
const DECK_IMPORT_MAX = 10;

function RenderDeckImport(array $config = []): string {
    $max = DECK_IMPORT_MAX;
    $row = "<div class='dl-import-row'>"
         . "<input type='url' class='dl-import-input' placeholder=\"Paste a deck link\" aria-label='Deck link 1'"
         . " autocomplete='off' spellcheck='false'>"
         . "<button type='button' class='dl-import-remove' aria-label='Remove this link' hidden>&times;</button>"
         . "<span class='dl-import-status'></span>"
         . "</div>";
    return "<div class='dl-import' data-max='$max'>"
         . "<h3 class='dl-import-title'>Import decks</h3>"
         . "<p class='dl-import-hint'>Paste a link from SWUDB, SWUStats, SWU Forge or another deck builder. Up to $max at a time.</p>"
         . "<div class='dl-import-rows'>$row</div>"
         . "<div class='dl-import-controls'>"
         . "<button type='button' class='dl-import-add'><span class='dl-import-plus' aria-hidden='true'>+</span> Add another</button>"
         . "<button type='button' class='dl-import-save'>Import &amp; Save</button>"
         . "</div>"
         . "<div class='dl-import-summary' aria-live='polite'></div>"
         . "</div>"
         . _DeckImportScript($config);
}

function _DeckImportScript(array $config = []): string {
    $js = <<<'JS'
<script>(function(){
  if (window.__deckImportWired) return; window.__deckImportWired = true;
  function appBase(){ var p=location.pathname, i=p.indexOf('/TCGEngine/'); return i>=0 ? p.slice(0,i+11) : '/TCGEngine/'; }
  var URL = appBase() + __ENDPOINT__;
  var REASONS = { not_a_link: 'Not a deck link', empty: 'Paste a deck link', not_logged_in: 'Log in again to save decks',
                  db_insert_failed: 'Could not save it. Try again', unresolvable: 'Could not load that deck' };

  function rows(box){ return Array.prototype.slice.call(box.querySelectorAll('.dl-import-row')); }
  function maxRows(box){ return parseInt(box.getAttribute('data-max') || '10', 10); }
  function input(row){ return row.querySelector('.dl-import-input'); }
  function setStatus(row, state, text){
    var s = row.querySelector('.dl-import-status');
    s.textContent = text || '';
    if (state) row.setAttribute('data-state', state); else row.removeAttribute('data-state');
  }
  // Numbers the boxes for screen readers, hides the ✕ while only one box is left, and stops "Add another" at the cap.
  function refresh(box){
    var list = rows(box);
    list.forEach(function(r, i){
      input(r).setAttribute('aria-label', 'Deck link ' + (i + 1));
      r.querySelector('.dl-import-remove').hidden = list.length === 1;
    });
    box.querySelector('.dl-import-add').disabled = list.length >= maxRows(box);
  }
  function addRow(box, value){
    var list = rows(box);
    if (list.length >= maxRows(box)) return null;
    var row = list[list.length - 1].cloneNode(true);
    input(row).value = value || '';
    input(row).readOnly = false;
    setStatus(row, '', '');
    box.querySelector('.dl-import-rows').appendChild(row);
    refresh(box);
    return row;
  }
  function reason(r){
    if (!r) return 'Could not reach the server';
    var e = String(r.error || '');
    if (REASONS[e]) return REASONS[e];
    return e.indexOf(' ') > 0 ? e.replace(/\.$/, '') : 'Could not load that deck';   // the resolver's own sentence
  }
  function post(params, done){
    var x = new XMLHttpRequest();
    x.open('POST', URL, true);
    x.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    x.timeout = 45000;
    x.onload = function(){ var r = null; try { r = JSON.parse(x.responseText); } catch (e) {} done(r); };
    x.onerror = x.ontimeout = function(){ done(null); };
    x.send(params);
  }
  // Adds (or renames) the deck in this panel's Saved Decks dropdown, after the favourites, as the page would on load.
  function addToDropdown(box, decklink, link, name){
    var panel = box.closest('.savedDecks') || document;
    var sel = panel.querySelector('.deck-library-dropdown[data-storage="account"] .dl-select');
    if (!sel || !decklink) return;
    var opts = Array.prototype.slice.call(sel.options), before = null;
    for (var i = 0; i < opts.length; i++) {
      if (opts[i].getAttribute('data-id') === decklink) {
        opts[i].setAttribute('data-name', name);
        opts[i].textContent = (opts[i].getAttribute('data-fav') === '1' ? '★ ' : '') + name;
        return;
      }
      if (!before && opts[i].getAttribute('data-id') && opts[i].getAttribute('data-fav') !== '1') before = opts[i];
    }
    var o = document.createElement('option');
    o.setAttribute('data-id', decklink);
    o.setAttribute('data-queue-input', link);
    o.setAttribute('data-name', name);
    o.setAttribute('data-fav', '0');
    o.setAttribute('data-wins', '0');
    o.setAttribute('data-losses', '0');
    o.textContent = name;
    sel.insertBefore(o, before);
    var empty = panel.querySelector('.deck-library-empty');
    if (empty) empty.style.display = 'none';
  }

  function runImport(box){
    var btn = box.querySelector('.dl-import-save'), summary = box.querySelector('.dl-import-summary');
    if (btn.disabled) return;
    var seen = {}, jobs = [], extra = [];
    rows(box).forEach(function(r){
      var v = input(r).value.trim();
      if (!v) { setStatus(r, '', ''); extra.push(r); return; }
      if (seen[v]) { setStatus(r, '', ''); extra.push(r); return; }   // the same link twice is saved once
      seen[v] = true; jobs.push({ row: r, link: v });
    });
    if (!jobs.length) { summary.textContent = 'Paste a deck link first.'; input(rows(box)[0]).focus(); return; }
    btn.disabled = true; box.setAttribute('aria-busy', 'true');
    summary.textContent = 'Saving ' + jobs.length + ' deck' + (jobs.length === 1 ? '' : 's') + '…';
    var saved = [], failed = [], i = 0;
    (function next(){
      if (i >= jobs.length) return finish();
      var j = jobs[i++];
      setStatus(j.row, 'saving', 'Saving…');
      input(j.row).readOnly = true;
      post('action=save&deckInput=' + encodeURIComponent(j.link), function(r){
        input(j.row).readOnly = false;
        if (r && r.success) {
          var name = String(r.name || '').trim() || 'Untitled Deck';
          setStatus(j.row, 'ok', '✓ ' + name);
          addToDropdown(box, r.decklink, j.link, name);
          saved.push(j);
          j.name = name;
        } else {
          setStatus(j.row, 'error', '✗ ' + reason(r));
          failed.push(j);
        }
        next();
      });
    })();
    // Saved and blank boxes go; failed ones stay with their reason. At least one box is always left.
    // (The last box is cleared rather than removed: new boxes are copies of an existing one.)
    function finish(){
      saved.map(function(j){ return j.row; }).concat(extra).forEach(function(r){
        if (rows(box).length > 1) r.remove(); else { input(r).value = ''; setStatus(r, '', ''); }
      });
      refresh(box);
      btn.disabled = false; box.removeAttribute('aria-busy');
      var names = saved.map(function(j){ return j.name; }).join(', ');
      if (!failed.length) summary.textContent = 'Saved ' + saved.length + ' deck' + (saved.length === 1 ? '' : 's') + ': ' + names + '.';
      else if (saved.length) summary.textContent = 'Saved ' + saved.length + ' of ' + (saved.length + failed.length) + ': ' + names
        + '. Fix the links marked ✗ and press Import & Save again.';
      else summary.textContent = 'Nothing was saved. Fix the links marked ✗ and try again.';
      if (failed.length) input(failed[0].row).focus();
    }
  }

  document.addEventListener('click', function(e){
    var box = e.target.closest('.dl-import'); if (!box) return;
    if (e.target.closest('.dl-import-add')) { var r = addRow(box, ''); if (r) input(r).focus(); }
    else if (e.target.closest('.dl-import-remove')) {
      var row = e.target.closest('.dl-import-row');
      if (rows(box).length > 1) { var list = rows(box), at = list.indexOf(row); row.remove(); refresh(box);
        var left = rows(box); input(left[Math.min(at, left.length - 1)]).focus(); }
    }
    else if (e.target.closest('.dl-import-save')) runImport(box);
  });
  document.addEventListener('keydown', function(e){
    if (e.key !== 'Enter' || !e.target.closest('.dl-import-input')) return;
    e.preventDefault(); runImport(e.target.closest('.dl-import'));
  });
  // A pasted LIST of links (one per line, or space separated) fills one box each. A single-line box would otherwise
  // drop the line breaks and glue every link into one.
  document.addEventListener('paste', function(e){
    var inp = e.target.closest && e.target.closest('.dl-import-input'); if (!inp) return;
    var text = (e.clipboardData || window.clipboardData) ? (e.clipboardData || window.clipboardData).getData('text') : '';
    var links = String(text || '').split(/\s+/).filter(Boolean);
    if (links.length < 2) return;
    e.preventDefault();
    var box = inp.closest('.dl-import'), list = rows(box), at = list.indexOf(inp.closest('.dl-import-row')), left = 0;
    inp.value = links.shift();
    list.slice(at + 1).forEach(function(r){ if (links.length && !input(r).value.trim()) input(r).value = links.shift(); });
    while (links.length && addRow(box, links[0])) links.shift();
    left = links.length;
    box.querySelector('.dl-import-summary').textContent = left
      ? 'Only ' + maxRows(box) + ' at a time — ' + left + ' link' + (left === 1 ? ' was' : 's were') + ' left out. Import these, then paste the rest.'
      : '';
  });
})();</script>
JS;
    return strtr($js, ['__ENDPOINT__' => json_encode($config['endpoint'] ?? 'SWUSim/SavedDecks.php', JSON_UNESCAPED_SLASHES)]);
}
