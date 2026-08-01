import { test } from 'node:test';
import assert from 'node:assert/strict';
import { createRequire } from 'node:module';

const require = createRequire(import.meta.url);
const { SWUAspectMatch } = require('../../SWUDeck/Custom/AspectFilter.js');

// Shorthand card fixtures. Aspect CSV is what Cardaspect() returns; duplicates are repeated.
const NEUTRAL = '';
const B  = 'Vigilance';
const BB = 'Vigilance,Vigilance';
const G  = 'Command';
const GG = 'Command,Command';
const R  = 'Aggression';
const RR = 'Aggression,Aggression';
const BK = 'Vigilance,Villainy';
const GBK = 'Command,Vigilance,Villainy';
const BBK = 'Vigilance,Vigilance,Villainy';
const YBK = 'Cunning,Vigilance,Villainy';

// ---- Ex 1: c<=gbk -- subset family DEDUPES, and Neutral is included -------------
test('<= is subset, dedupes, includes Neutral', () => {
  assert.equal(SWUAspectMatch(NEUTRAL, '<=', 'gbk'), true,  'Neutral is a subset of everything');
  assert.equal(SWUAspectMatch(GG,      '<=', 'gbk'), true,  'double-Command dedupes to {g}');
  assert.equal(SWUAspectMatch(GBK,     '<=', 'gbk'), true,  'improper subset counts');
  assert.equal(SWUAspectMatch(R,       '<=', 'gbk'), false, 'Aggression is not in {g,b,k}');
});

// ---- Ex 2: c=gbk -- exact, counts copies ---------------------------------------
test('= is exact and counts copies', () => {
  assert.equal(SWUAspectMatch(GBK, '=', 'gbk'), true);
  assert.equal(SWUAspectMatch(BBK, '=', 'gbk'), false, 'BBK is not exactly one each');
  assert.equal(SWUAspectMatch(BK,  '=', 'gbk'), false, 'missing Command');
  assert.equal(SWUAspectMatch(RR,  '=', 'rr'),  true,  'double-Aggression is exactly rr');
  assert.equal(SWUAspectMatch(R,   '=', 'rr'),  false, 'single != double');
});

// ---- Ex 3: aspect>=bk -- contains all, counts copies ---------------------------
test('>= and : mean contains-all', () => {
  for (const op of ['>=', ':']) {
    assert.equal(SWUAspectMatch(BK,  op, 'bk'), true,  `${op} improper superset`);
    assert.equal(SWUAspectMatch(GBK, op, 'bk'), true);
    assert.equal(SWUAspectMatch(YBK, op, 'bk'), true);
    assert.equal(SWUAspectMatch(BBK, op, 'bk'), true);
    assert.equal(SWUAspectMatch(B,   op, 'bk'), false, `${op} missing Villainy`);
  }
});

// ---- The doubles case the user specifically asked about ------------------------
test(': counts copies so c:rr finds only double-Aggression', () => {
  assert.equal(SWUAspectMatch(RR, ':', 'rr'), true);
  assert.equal(SWUAspectMatch(R,  ':', 'rr'), false, 'single-Aggression must NOT match rr');
  assert.equal(SWUAspectMatch(R,  ':', 'r'),  true,  'single letter still matches single card');
  assert.equal(SWUAspectMatch(RR, ':', 'r'),  true,  'double contains at least one');
});

// ---- Ex 4: c<bk -- proper subset, dedupes, includes Neutral --------------------
test('< is proper subset', () => {
  assert.equal(SWUAspectMatch(NEUTRAL, '<', 'bk'), true);
  assert.equal(SWUAspectMatch(B,       '<', 'bk'), true);
  assert.equal(SWUAspectMatch(BB,      '<', 'bk'), true, 'double-Vigilance dedupes to {b}');
  assert.equal(SWUAspectMatch(BK,      '<', 'bk'), false, 'BK is not a PROPER subset of itself');
  assert.equal(SWUAspectMatch(GBK,     '<', 'bk'), false);
});

// ---- Ex 5: c>bk -- proper superset --------------------------------------------
test('> is proper superset', () => {
  assert.equal(SWUAspectMatch(GBK, '>', 'bk'), true);
  assert.equal(SWUAspectMatch(YBK, '>', 'bk'), true);
  assert.equal(SWUAspectMatch(BBK, '>', 'bk'), true, 'extra copy counts as more');
  assert.equal(SWUAspectMatch(BK,  '>', 'bk'), false, 'not strictly more');
  assert.equal(SWUAspectMatch(B,   '>', 'bk'), false);
});

// ---- Ex 6: aspect!=bk -- contains NONE of (not logical negation of =) ----------
test('!= means contains none of', () => {
  assert.equal(SWUAspectMatch(R,       '!=', 'bk'), true,  'Aggression has neither');
  assert.equal(SWUAspectMatch(NEUTRAL, '!=', 'bk'), true,  'Neutral has neither');
  assert.equal(SWUAspectMatch(BK,      '!=', 'bk'), false);
  assert.equal(SWUAspectMatch(B,       '!=', 'bk'), false, 'has Vigilance, so excluded');
  assert.equal(SWUAspectMatch(GBK,     '!=', 'bk'), false);
});

// ---- Neutral shortcut ----------------------------------------------------------
test('n/neutral is standalone and collapses every operator to is-empty', () => {
  for (const v of ['n', 'N', 'neutral', 'NEUTRAL']) {
    for (const op of [':', '=', '<=', '<', '>', '>=']) {
      assert.equal(SWUAspectMatch(NEUTRAL, op, v), true,  `${op}${v} matches Neutral`);
      assert.equal(SWUAspectMatch(R,       op, v), false, `${op}${v} rejects a card with aspects`);
    }
    assert.equal(SWUAspectMatch(NEUTRAL, '!=', v), false, '!=n excludes Neutral');
    assert.equal(SWUAspectMatch(R,       '!=', v), true,  '!=n is has-any-aspect');
  }
});

// ---- Value forms ---------------------------------------------------------------
test('accepts full aspect names and colour names, case-insensitively', () => {
  assert.equal(SWUAspectMatch(B, ':', 'vigilance'), true);
  assert.equal(SWUAspectMatch(B, ':', 'Vigilance'), true);
  assert.equal(SWUAspectMatch(B, ':', 'blue'),      true);
  assert.equal(SWUAspectMatch(B, ':', 'BLUE'),      true);
  assert.equal(SWUAspectMatch(GBK, ':', 'black'),   true, 'black = Villainy');
  assert.equal(SWUAspectMatch(R, ':', 'red'),       true);
  assert.equal(SWUAspectMatch(R, ':', 'blue'),      false);
});

test('letter strings are case-insensitive', () => {
  assert.equal(SWUAspectMatch(GBK, ':', 'GBK'), true);
  assert.equal(SWUAspectMatch(GBK, ':', 'gBk'), true);
});

// ---- Fallback contract (backward compatibility) --------------------------------
test('returns null for values that are not aspect queries', () => {
  assert.equal(SWUAspectMatch(B, ':', 'vig'), null, 'partial name -> caller falls back to substring');
  assert.equal(SWUAspectMatch(B, ':', 'xyz'), null, 'invalid letters');
  assert.equal(SWUAspectMatch(B, ':', 'gn'),  null, 'N cannot combine with letters');
  assert.equal(SWUAspectMatch(B, ':', ''),    null, 'empty value');
});

// ---- Malformed / unknown card data --------------------------------------------
test('handles null aspect data as Neutral-like without throwing', () => {
  assert.equal(SWUAspectMatch(null, ':', 'r'), false, 'unknown card cannot contain Aggression');
  assert.equal(SWUAspectMatch(null, ':', 'n'), true,  'null is treated as no aspects');
});

// ---- Coherence: = IMPLIES (<= AND >=), one-directional -------------------------
// The full biconditional `= <=> (<= AND >=)` does NOT hold under the hybrid semantics, and
// that is by design, not a bug: `<=` deduplicates while `=`/`>=` count copies, so a doubled
// card (e.g. double-Vigilance vs query `b`) satisfies BOTH bounds without being multiset-equal.
// The forward direction is still a true, useful invariant — an exact match must satisfy both
// bounds — so we guard that. See the design spec's "Coherence check" note.
test('= implies (<= AND >=) for every fixture (forward direction only)', () => {
  const cards = [NEUTRAL, B, BB, G, GG, R, RR, BK, GBK, BBK, YBK];
  const queries = ['r', 'rr', 'bk', 'gbk', 'b', 'n'];
  for (const c of cards) {
    for (const q of queries) {
      if (SWUAspectMatch(c, '=', q)) {
        assert.equal(SWUAspectMatch(c, '<=', q), true, `= but not <= for card "${c}" query "${q}"`);
        assert.equal(SWUAspectMatch(c, '>=', q), true, `= but not >= for card "${c}" query "${q}"`);
      }
    }
  }
});
