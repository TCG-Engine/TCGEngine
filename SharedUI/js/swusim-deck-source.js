/* Petranaki ↔ SWUStats deck-source toggle (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md §2).
   Shared by the main menu's setup modals and the Waiting Room. Owns the remembered choice, which panel is
   shown, ONE fetch of the hearted-deck list per page, and the copy for the panel's empty/error states.
   Each page builds its own pickers from the list (onRefresh / its own load().then). */
(function () {
  'use strict';
  var KEY = 'swusim:deckSourceTab';

  function base() { var p = location.pathname, i = p.indexOf('/TCGEngine/'); return i >= 0 ? p.slice(0, i + 11) : '/TCGEngine/'; }
  function esc(s) {
    return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
      return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' })[c];
    });
  }
  function preferred() { try { return localStorage.getItem(KEY) === 'saved' ? 'saved' : 'swustats'; } catch (e) { return 'swustats'; } }
  function remember(src) { try { localStorage.setItem(KEY, src); } catch (e) { /* private mode: the toggle still works, it just won't be remembered */ } }

  function show(root, src) {
    if (!root) return;
    root.querySelectorAll('[data-decksrc-opt]').forEach(function (r) { r.checked = (r.value === src); });
    root.querySelectorAll('.decksrc__panel').forEach(function (p) { p.hidden = (p.getAttribute('data-src') !== src); });
  }
  /* one preference for every picker on the page */
  function applyAll(src) { document.querySelectorAll('[data-decksrc]').forEach(function (r) { show(r, src); }); }

  var pending = null;
  function load(refresh) {
    if (pending && !refresh) return pending;
    pending = fetch(base() + 'SWUSim/SWUStatsDecks.php' + (refresh ? '?refresh=1' : ''), { credentials: 'same-origin' })
      .then(function (r) { return r.json(); })
      .catch(function () { return null; })
      .then(function (j) {
        if (!j || !j.status) j = { status: 'unavailable', decks: [] };
        if (!Array.isArray(j.decks)) j.decks = [];
        return j;
      });
    return pending;
  }

  /* What the SWUStats panel says when there is no list to show; '' when there is one. */
  function stateHtml(res) {
    if (res.status === 'ok' && res.decks.length) return '';
    if (res.status === 'ok') {
      return 'No hearted decks on SWUStats yet. <a href="' + esc(res.swustatsUrl || 'https://swustats.net') +
             '" target="_blank" rel="noopener">Heart a deck on SWUStats</a>';
    }
    if (res.status === 'relink' || res.status === 'unlinked') {
      return 'Your SWUStats link has expired. <a href="' + esc(base() + 'SharedUI/Sites/SWUSim/Profile.php') + '">Reconnect SWUStats</a>';
    }
    return 'Couldn’t reach SWUStats. <button type="button" class="decksrc__retry" data-decksrc-refresh>Retry</button>';
  }

  /* ⚠ JS twin of SWUDeckSourceToggle() (SWUSim/Custom/DeckSource.php) — same markup, byte for byte.
     Used where the toggle is built in the browser (the Waiting Room's fill-bot dialog). */
  function toggleHtml(idBase, swustatsInner, savedInner, labelledBy) {
    var b = esc(idBase);
    var label = labelledBy ? ' aria-labelledby="' + esc(labelledBy) + '"' : ' aria-label="Deck source"';
    return '<div class="decksrc" data-decksrc>' +
      '<div class="seg decksrc__seg" role="radiogroup"' + label + '>' +
      '<input class="seg__in u-vh" type="radio" name="' + b + '-src" id="' + b + '-src-ss" value="swustats" data-decksrc-opt checked>' +
      '<label class="seg__opt ch" for="' + b + '-src-ss">SWUStats Decks</label>' +
      '<input class="seg__in u-vh" type="radio" name="' + b + '-src" id="' + b + '-src-saved" value="saved" data-decksrc-opt>' +
      '<label class="seg__opt ch" for="' + b + '-src-saved">Saved Decks</label>' +
      '</div>' +
      '<div class="decksrc__panel" data-src="swustats"><div class="decksrc__host">' + swustatsInner + '</div>' +
      '<button type="button" class="decksrc__refresh" data-decksrc-refresh>Refresh SWUStats decks</button></div>' +
      '<div class="decksrc__panel" data-src="saved" hidden>' + savedInner + '</div>' +
      '</div>';
  }

  var switchers = [], refreshers = [];
  function onSwitch(fn) { switchers.push(fn); }
  function onRefresh(fn) { refreshers.push(fn); }

  document.addEventListener('change', function (ev) {
    var r = ev.target;
    if (!r || !r.matches || !r.matches('[data-decksrc-opt]') || !r.checked) return;
    var root = r.closest('[data-decksrc]');
    remember(r.value);
    applyAll(r.value);
    switchers.forEach(function (fn) { fn(root, r.value); });
  });
  document.addEventListener('click', function (ev) {
    var b = ev.target && ev.target.closest ? ev.target.closest('[data-decksrc-refresh]') : null;
    if (!b) return;
    ev.preventDefault();
    load(true).then(function (res) { refreshers.forEach(function (fn) { fn(res); }); });
  });

  window.SWUDeckSource = { preferred: preferred, remember: remember, show: show, applyAll: applyAll, load: load,
                           stateHtml: stateHtml, onSwitch: onSwitch, onRefresh: onRefresh, base: base, esc: esc,
                           toggleHtml: toggleHtml };

  /* Apply the remembered choice as soon as the markup exists. No change event: nothing is re-picked on load. */
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { applyAll(preferred()); });
  else applyAll(preferred());
})();
