/**
 * NameTraitUI — the "Name a Trait" picker (HMW_108 The First Legion, Vader's Fist).
 *
 * Sibling of NameCardUI, one dictionary over: where that one autocompletes over every card NAME, this
 * one offers every TRAIT printed on any card. Both derive their universe from the generated card
 * dictionaries rather than a hand-kept list, so a new set's traits appear with the next regen and no
 * code change here. `traitData` (and `leaderUnitTraitData`, since a deployed leader side prints its own
 * trait line) are already emitted to the client by zzCardCodeGenerator.
 *
 * The server validates the submitted string against SWUAllTraits() as well — this picker is a
 * convenience, never the enforcement.
 */
(function (global) {
  'use strict';

  var traitsCache = null;
  var overlayEl = null;

  function normalize(s) { return String(s || '').trim().toLowerCase(); }

  // Every distinct trait across both trait dictionaries, alphabetised. Traits are stored per card as a
  // comma-separated string ("Imperial,Trooper"), so split and dedupe case-insensitively while keeping
  // the printed casing for display.
  function getAllTraits() {
    if (Array.isArray(traitsCache)) return traitsCache;
    var sources = [];
    if (typeof traitData !== 'undefined' && traitData) sources.push(traitData);
    if (typeof leaderUnitTraitData !== 'undefined' && leaderUnitTraitData) sources.push(leaderUnitTraitData);
    var seen = Object.create(null);
    sources.forEach(function (src) {
      Object.keys(src).forEach(function (cardId) {
        String(src[cardId] || '').split(',').forEach(function (raw) {
          var t = String(raw || '').trim();
          if (!t) return;
          var key = normalize(t);
          if (!seen[key]) seen[key] = t;
        });
      });
    });
    traitsCache = Object.keys(seen).map(function (k) { return seen[k]; })
      .sort(function (a, b) { return a.localeCompare(b); });
    return traitsCache;
  }

  function findMatchingTraits(query, limit) {
    var q = normalize(query);
    var all = getAllTraits();
    if (!q) return all.slice(0, limit || 12);
    return all.filter(function (t) { return normalize(t).indexOf(q) !== -1; }).slice(0, limit || 12);
  }

  function close() {
    if (overlayEl && overlayEl.parentNode) overlayEl.parentNode.removeChild(overlayEl);
    overlayEl = null;
  }

  function ShowNameTraitUI(param, tooltip, decisionIndex, onSubmit) {
    close();
    var overlay = document.createElement('div');
    overlay.className = 'nametrait-modal-overlay';
    overlay.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.6);z-index:10000;'
      + 'display:flex;align-items:center;justify-content:center;';

    var box = document.createElement('div');
    box.className = 'nametrait-modal';
    box.style.cssText = 'background:#1e1e1e;color:#eee;border:1px solid #555;border-radius:8px;'
      + 'padding:18px;min-width:320px;max-width:90vw;font-family:inherit;';

    var title = document.createElement('div');
    title.textContent = tooltip || 'Name a Trait';
    title.style.cssText = 'font-weight:bold;margin-bottom:10px;font-size:15px;';
    box.appendChild(title);

    var input = document.createElement('input');
    input.type = 'text';
    input.placeholder = 'Type a trait…';
    input.setAttribute('aria-label', tooltip || 'Name a Trait');
    input.style.cssText = 'width:100%;padding:8px;border-radius:4px;border:1px solid #666;'
      + 'background:#111;color:#eee;box-sizing:border-box;';
    box.appendChild(input);

    var list = document.createElement('div');
    list.style.cssText = 'margin-top:10px;max-height:240px;overflow-y:auto;';
    box.appendChild(list);

    function submit(value) {
      var v = String(value || '').trim();
      if (!v) return;
      close();
      if (typeof onSubmit === 'function') onSubmit(v, decisionIndex);
    }

    function render() {
      list.innerHTML = '';
      findMatchingTraits(input.value, 40).forEach(function (t) {
        var row = document.createElement('div');
        row.textContent = t;
        row.setAttribute('role', 'button');
        row.tabIndex = 0;
        row.style.cssText = 'padding:6px 8px;cursor:pointer;border-radius:4px;';
        row.onmouseenter = function () { row.style.background = '#333'; };
        row.onmouseleave = function () { row.style.background = 'transparent'; };
        row.onclick = function () { submit(t); };
        row.onkeydown = function (e) { if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); submit(t); } };
        list.appendChild(row);
      });
    }

    input.addEventListener('input', render);
    input.addEventListener('keydown', function (e) {
      if (e.key !== 'Enter') return;
      // Enter commits an exact (case-insensitive) trait, else the first current match.
      var typed = normalize(input.value);
      var exact = getAllTraits().find(function (t) { return normalize(t) === typed; });
      var matches = findMatchingTraits(input.value, 1);
      if (exact) submit(exact);
      else if (matches.length) submit(matches[0]);
    });

    overlay.appendChild(box);
    document.body.appendChild(overlay);
    overlayEl = overlay;
    render();
    input.focus();
  }

  global.ShowNameTraitUI = ShowNameTraitUI;
  global.NameTraitUI = { getAllTraits: getAllTraits, findMatchingTraits: findMatchingTraits };
})(window);
