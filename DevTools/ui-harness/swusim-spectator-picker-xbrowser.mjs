// The spectator's "Spectator View" seat picker, in all three engines.
//
// Owner, 2026-09-26: "Spectators of a Twin Suns game cannot see as P3 or P4. this is due to the
// legacy view picker."
//
// It was two hardcoded buttons, so the two far seats were unreachable from the UI even though the
// backend already accepted any perspective >= 1 (Core/ViewerIdentity.php NormalizeViewerPerspective).
// The clamp lived entirely in NextTurn.php's markup.
//
// ⚠ WHY THIS IS A BROWSER GATE AND NOT ONLY AN HTTP ONE. The HTTP test
// (DevTools/tdd-regression/test_swusim_spectator_view_and_chat.php) proves the four buttons are in
// the MARKUP. It cannot see that they are actually usable: a fixed-position 4-button row in a box
// sized for 2 is exactly the shape that wraps, clips or overlaps, and it does so differently per
// engine. So this measures GEOMETRY — every button visible, inside the viewport, non-zero, and not
// overlapping its neighbour — and then actually CLICKS the far seat to prove the round trip.
//
//   node DevTools/ui-harness/swusim-spectator-picker-xbrowser.mjs
//
// Needs the four-seat fixture:
//   docker exec -w /var/www/html/TCGEngine <container> php SWUSim/DevTools/make-fourseat-fixture.php --game=9932777
import { chromium, firefox, webkit } from 'playwright';
const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const GAME = process.env.GAME || '9932777';
// ⚠ not `URL` — that shadows the global URL constructor used below.
const PAGE = BASE + 'NextTurn.php?folderPath=SWUSim&gameName=' + GAME + '&playerID=S';
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 1000 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(PAGE, { waitUntil: 'domcontentloaded' });
  await p.waitForSelector('#spectatorControls', { timeout: 20000 });

  const btns = await p.$$eval('#spectatorControls button', els => els.map(e => {
    const r = e.getBoundingClientRect();
    return { text: (e.textContent || '').trim(), x: r.x, y: r.y, w: r.width, h: r.height };
  }));

  // 1. One per seat, in order.
  const labels = btns.map(b2 => b2.text);
  if (labels.join('|') !== 'P1|P2|P3|P4') {
    bad(name, `expected four seat buttons P1..P4, got [${labels.join(', ')}]`);
  } else ok();

  // 2. Every one actually rendered and on screen. A wrapped row that pushes P4 out of its box would
  //    still be in the DOM and still pass the HTTP test.
  for (const b2 of btns) {
    if (b2.w < 30 || b2.h < 16) { bad(name, `"${b2.text}" has no usable size (${b2.w}x${b2.h})`); continue; }
    if (b2.x < 0 || b2.y < 0 || b2.x + b2.w > 1440 || b2.y + b2.h > 1000) {
      bad(name, `"${b2.text}" falls outside the viewport at (${Math.round(b2.x)}, ${Math.round(b2.y)})`);
      continue;
    }
    ok();
  }

  // 3. No two buttons overlap — the failure mode when a fixed-width box is given twice the content.
  for (let i = 0; i < btns.length; i++) {
    for (let j = i + 1; j < btns.length; j++) {
      const a = btns[i], c = btns[j];
      const over = a.x < c.x + c.w && c.x < a.x + a.w && a.y < c.y + c.h && c.y < a.y + a.h;
      if (over) bad(name, `"${a.text}" and "${c.text}" overlap`); else ok();
    }
  }

  // 4. THE ROUND TRIP. Clicking the far seat must actually land on that perspective — the button
  //    existing proves nothing if SetSpectatorPerspective still clamps to 1/2, which is exactly what
  //    the old JS did (`perspective === 2 ? '2' : '1'`).
  await Promise.all([
    p.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }).catch(() => {}),
    p.click('#spectatorControls button:nth-of-type(4)'),
  ]);
  const vp = new URL(p.url()).searchParams.get('viewerPerspective');
  if (vp !== '4') bad(name, `clicking "P4" gave viewerPerspective=${vp}, expected 4`); else ok();

  // 5. And the spectator still has no way to chat on the page they landed on.
  const chatBits = await p.$$eval('#chatText, #chatSendBtn, #chatGuestNote', els => els.length);
  if (chatBits !== 0) bad(name, `spectator sees ${chatBits} chat control(s) — expected none`); else ok();

  // 6. THE HOME PANELS FOLLOW THE PICKED SEAT (owner report 2026-10-07: "when viewing as P1, they still see
  //    P1 in the home panels"). The Twin Suns view builder keyed on the viewer's OWN seat, which is 0 for a
  //    spectator ('S'), so every perspective built the same "seat 0" views: all four seats tiled (the bottom
  //    board's own seat included, gold-highlighted as the home view's opponent) and the strip never
  //    rotated. Expected is DERIVED, not literal: a spectator watching seat N gets exactly player N's views.
  const readViews = async (pg) => pg.evaluate(() => ({
    views: (window.swuViews || []).map(v => `${v.mode}:${v.viewSeat}v${v.oppSeat}`).join(' '),
    tiles: [...document.querySelectorAll('#swuHomeStrips .swu-home-strip[data-seat]')].map(e => e.getAttribute('data-seat')).join(','),
    badge: document.body.classList.contains('swu-spectating'),
  }));
  const settle = (pg) => pg.waitForFunction(() => (window.swuViews || []).length > 0
    && document.querySelectorAll('#swuHomeStrips .swu-home-strip[data-seat]').length > 0, null, { timeout: 20000 }).catch(() => {});
  for (const seat of [1, 2, 3, 4]) {
    const sp = await b.newPage({ viewport: { width: 1440, height: 1000 } });
    const pl = await b.newPage({ viewport: { width: 1440, height: 1000 } });
    await sp.goto(PAGE + '&viewerPerspective=' + seat, { waitUntil: 'domcontentloaded' });
    await pl.goto(BASE + 'NextTurn.php?folderPath=SWUSim&gameName=' + GAME + '&playerID=' + seat, { waitUntil: 'domcontentloaded' });
    await settle(sp); await settle(pl);
    const s = await readViews(sp), q = await readViews(pl);
    if (!q.views || !q.tiles) bad(name, `fixture: player ${seat}'s page built no views/tiles`);
    else if (s.views !== q.views) bad(name, `spectator as P${seat} built views [${s.views}], player ${seat} has [${q.views}]`);
    else ok();
    if (s.tiles !== q.tiles) bad(name, `spectator as P${seat} tiles [${s.tiles}], player ${seat} has [${q.tiles}]`); else ok();
    if (s.tiles.split(',').includes(String(seat))) bad(name, `spectator as P${seat} still has a P${seat} home panel`); else ok();
    // A spectator is read-only, and the badge says whose seat they are watching.
    if (!s.badge) bad(name, `spectator as P${seat} is not marked read-only (body.swu-spectating)`); else ok();
    if (process.env.SHOTS) await sp.screenshot({ path: `${process.env.SHOTS}/spectator-p${seat}-${name}.png` });
    await sp.close(); await pl.close();
  }

  if (errs.length) bad(name, `page errors: ${errs.slice(0, 2).join(' | ')}`); else ok();
  await b.close();
}

console.log(fails === 0 ? `PASS (${checks} checks, 3 engines)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
