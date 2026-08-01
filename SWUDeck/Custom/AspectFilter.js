// SWUDeck aspect-filter matching.
//
// PURE and dependency-free on purpose: this file is inlined verbatim into the generated card
// dictionary (see zzCardCodeGenerator.php) so the browser gets it without a new <script> tag,
// AND it is require()d directly by node for unit tests. Do not add DOM access, globals, or
// imports here, and do not use syntax newer than ES5 — the generated bundle targets browsers
// broadly.
//
// Semantics (see docs/superpowers/specs/2026-07-20-swudeck-aspect-filter-design.md):
//   :  >=   contains all of   (Q subset-of A)  -- COUNTS duplicate copies
//   =        exactly these    (A equals Q)     -- COUNTS duplicate copies
//   >        strictly more    (A superset Q)   -- COUNTS duplicate copies
//   <=       these or fewer   (A subset Q)     -- DEDUPES
//   <        strictly fewer   (A proper-subset Q) -- DEDUPES
//   !=       contains none of (A disjoint Q)   -- DEDUPES
//
// The subset family MUST dedupe or `c<=gbk` would reject double-Command, which is a specified
// requirement. The superset family counts copies so `c:rr` can mean "double-Aggression".
(function (root) {
  'use strict';

  var LETTER_TO_ASPECT = {
    b: 'Vigilance', g: 'Command', r: 'Aggression',
    y: 'Cunning',   w: 'Heroism', k: 'Villainy'
  };

  // Full aspect names AND colour names both resolve to the canonical aspect name.
  var NAME_TO_ASPECT = {
    vigilance: 'Vigilance', command: 'Command', aggression: 'Aggression',
    cunning:   'Cunning',   heroism: 'Heroism', villainy:   'Villainy',
    blue:      'Vigilance', green:   'Command', red:        'Aggression',
    yellow:    'Cunning',   white:   'Heroism', black:      'Villainy'
  };

  // A multiset is a plain object of aspectName -> count.
  function toMultiset(names) {
    var m = {};
    for (var i = 0; i < names.length; i++) {
      var n = names[i];
      if (!n) continue;
      m[n] = (m[n] || 0) + 1;
    }
    return m;
  }

  function keys(m) { var out = []; for (var k in m) if (m.hasOwnProperty(k)) out.push(k); return out; }
  function total(m) { var t = 0; for (var k in m) if (m.hasOwnProperty(k)) t += m[k]; return t; }

  // "Vigilance,Vigilance" -> {Vigilance: 2}. null/'' -> {} (Neutral).
  function parseCardAspects(csv) {
    if (csv == null) return {};
    var parts = String(csv).split(',');
    var names = [];
    for (var i = 0; i < parts.length; i++) {
      var p = parts[i].trim().toLowerCase();
      if (p && NAME_TO_ASPECT[p]) names.push(NAME_TO_ASPECT[p]);
    }
    return toMultiset(names);
  }

  // Returns {neutral:true}, a multiset, or null when the value is not an aspect query.
  function parseQuery(value) {
    if (value == null) return null;
    var v = String(value).trim().toLowerCase();
    if (v === '') return null;

    // 1. Neutral sentinel — standalone only, checked before the letter alphabet.
    if (v === 'n' || v === 'neutral') return { neutral: true };

    // 2 & 3. A full aspect name or colour name resolves to a single aspect.
    if (NAME_TO_ASPECT[v]) return toMultiset([NAME_TO_ASPECT[v]]);

    // 4. Letter string — every character must be a known colour letter. Repeats are significant.
    var names = [];
    for (var i = 0; i < v.length; i++) {
      var a = LETTER_TO_ASPECT[v.charAt(i)];
      if (!a) return null;   // 5. unrecognised -> caller falls back to substring matching
      names.push(a);
    }
    return toMultiset(names);
  }

  // Q subset-of A, counting copies.
  function containsAll(A, Q) {
    var ks = keys(Q);
    for (var i = 0; i < ks.length; i++) {
      if ((A[ks[i]] || 0) < Q[ks[i]]) return false;
    }
    return true;
  }

  // Set-level subset: ignores counts entirely.
  function setSubset(A, Q) {
    var ks = keys(A);
    for (var i = 0; i < ks.length; i++) {
      if (!Q.hasOwnProperty(ks[i])) return false;
    }
    return true;
  }

  function setEqual(A, Q) { return setSubset(A, Q) && setSubset(Q, A); }

  function multisetEqual(A, Q) {
    return keys(A).length === keys(Q).length && containsAll(A, Q) && containsAll(Q, A);
  }

  function disjoint(A, Q) {
    var ks = keys(A);
    for (var i = 0; i < ks.length; i++) {
      if (Q.hasOwnProperty(ks[i])) return false;
    }
    return true;
  }

  function SWUAspectMatch(cardAspectCsv, operator, queryValue) {
    var Q = parseQuery(queryValue);
    if (Q === null) return null;                 // not an aspect query — fall back to substring
    var A = parseCardAspects(cardAspectCsv);
    var isEmpty = total(A) === 0;

    // Neutral is standalone: every operator collapses to "has no aspects", except != which
    // inverts to "has at least one aspect".
    if (Q.neutral) return operator === '!=' ? !isEmpty : isEmpty;

    switch (operator) {
      case ':':
      case '>=': return containsAll(A, Q);
      case '=':  return multisetEqual(A, Q);
      case '>':  return containsAll(A, Q) && total(A) > total(Q);
      case '<=': return setSubset(A, Q);
      case '<':  return setSubset(A, Q) && !setEqual(A, Q);
      case '!=': return disjoint(A, Q);
      default:   return containsAll(A, Q);       // unknown operator behaves like ':'
    }
  }

  root.SWUAspectMatch = SWUAspectMatch;
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = { SWUAspectMatch: SWUAspectMatch };
  }
})(typeof globalThis !== 'undefined' ? globalThis : this);
