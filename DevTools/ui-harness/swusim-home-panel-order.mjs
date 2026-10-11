// Twin Suns: the Home panel / matchup order must be RELATIVE TO THE VIEWER — start at the seat to your
// right and wrap (owner request 2026-09-26):
//     P1 → P2 P3 P4 · P2 → P3 P4 P1 · P3 → P4 P1 P2 · P4 → P1 P2 P3
// It used to be plain ascending order with yourself removed, so P2 read "P1 P3 P4".
//
// ⚠ This evaluates the RANKING LINES LIFTED OUT OF THE SHIPPED FILE, not a copy of the algorithm. A
// re-implementation here would pass happily while swuBuildViews did something else entirely; the
// extraction fails loudly if the source stops matching, which is the signal that this needs re-reading.
// The browser half (does the strip actually paint in that order, in all three engines) is
// swusim-home-panel-order-xbrowser.mjs — this half only pins the arithmetic, including the cases that
// are awkward to stage live: elimination and spectators.
import { readFileSync } from 'node:fs';

const SRC = new URL('../../SWUSim/Custom/GameLayoutShared.php', import.meta.url);
const src = readFileSync(SRC, 'utf8');

// Lift `var myIdx = …` through `var byRight = …;` verbatim.
const m = src.match(/var myIdx = seatedList\.indexOf\(me\);[\s\S]*?var byRight = function \(a, b\) \{ return rank\(a\) - rank\(b\); \};/);
if (!m) {
  console.log('BAD  could not extract the ranking block from GameLayoutShared.php — has swuBuildViews changed?');
  process.exit(1);
}

let fails = 0, checks = 0;
const ok = (name, got, want) => {
  checks++;
  const pass = JSON.stringify(got) === JSON.stringify(want);
  if (!pass) fails++;
  console.log(`${pass ? 'ok  ' : 'BAD '} ${name}  got ${JSON.stringify(got)}${pass ? '' : ` want ${JSON.stringify(want)}`}`);
};

// Run the shipped block against a table. `seated` = every seat ever seated; `live` = still in it.
const order = (seatedStr, me, liveStr = seatedStr) => {
  const seatedList = seatedStr.split('').map(Number);
  const seats = liveStr.split('').map(Number);
  const body = `${m[0]}
    return {
      opps:  seats.filter(function (s) { return s !== me; }).sort(byRight),
      tiles: seatedList.filter(function (s) { return s !== me; }).sort(byRight)
    };`;
  return new Function('seatedList', 'seats', 'me', body)(seatedList, seats, me);
};

// ── The rule itself, at four seats ────────────────────────────────────────────────────────────
ok('4 seats, viewer P1', order('1234', 1).tiles, [2, 3, 4]);
ok('4 seats, viewer P2', order('1234', 2).tiles, [3, 4, 1]);
ok('4 seats, viewer P3', order('1234', 3).tiles, [4, 1, 2]);
ok('4 seats, viewer P4', order('1234', 4).tiles, [1, 2, 3]);

// ── Three seats ───────────────────────────────────────────────────────────────────────────────
ok('3 seats, viewer P1', order('123', 1).tiles, [2, 3]);
ok('3 seats, viewer P2', order('123', 2).tiles, [3, 1]);
ok('3 seats, viewer P3', order('123', 3).tiles, [1, 2]);

// ── opps and tiles must never disagree on who leads ───────────────────────────────────────────
// opps[0] is the home view's oppSeat. If the strip led with one seat and the board treated another as
// `their`, targeting and the tile order would point at different players.
for (const me of [1, 2, 3, 4]) {
  const o = order('1234', me);
  ok(`viewer P${me}: opps[0] === tiles[0]`, o.opps[0], o.tiles[0]);
}

// ── A DEAD SEAT still tiles, and does not shift the rotation ──────────────────────────────────
// tiles keeps every seated seat (owner ruling 2026-09-25); opps is live-only. Both stay in the same
// clockwise walk, so the dead seat holds its place in the strip rather than jumping to the end.
ok('P3 dead: viewer P2 tiles keep it in place', order('1234', 2, '124').tiles, [3, 4, 1]);
ok('P3 dead: viewer P2 opps skip it',           order('1234', 2, '124').opps,  [4, 1]);

// ── An ELIMINATED VIEWER is still watching, and is absent from `seats` ────────────────────────
// The anchor is the SEATED order, so their rotation is unchanged rather than collapsing to ascending.
ok('viewer P2 eliminated: tiles still start at P3', order('1234', 2, '134').tiles, [3, 4, 1]);

// ── A SPECTATOR has no "you" ──────────────────────────────────────────────────────────────────
// Seat 0 is not in seatedList, so rank() falls back to the seat number: plain table order, unchanged.
ok('spectator keeps plain table order', order('1234', 0).tiles, [1, 2, 3, 4]);

console.log(`\n${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
