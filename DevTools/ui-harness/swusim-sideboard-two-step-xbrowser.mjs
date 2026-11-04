// SWUSim sideboard — Submit Sideboard, Ready for Game N and Cancel as separate steps (owner, 2026-11-03), Chromium +
// Firefox + WebKit, desktop (1280x800) and phone (390x844). Both seats on their own sideboard screen:
//   · seat 1 swaps a card (deck → sideboard, sideboard → deck): "Changes not submitted"; Submit saves it;
//   · Ready locks the list (a click moves nothing) and seat 2's screen says the opponent is ready;
//   · Cancel goes back to editing: not ready, nothing submitted, the swap still on screen — and seat 2 sees it;
//   · Ready with the swap unsubmitted asks first, then shows the deck that is actually played (last game's);
//   · seat 2's Ready starts game 2 and both screens go to its board;
//   · the action row fits: no horizontal overflow, every button inside the viewport.
// The match comes from SWUSim/DevTools/tests/draw_endscreen_fixture.php?queue=bo3&scenario=p2won.
//
// Usage: node swusim-sideboard-two-step-xbrowser.mjs [BASE] [SHOTS_DIR] [engines]
import { chromium, firefox, webkit } from 'playwright';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const ONLY  = (process.argv[4] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));
const SIZES = [{ name: 'desktop', w: 1280, h: 800 }, { name: 'phone', w: 390, h: 844 }];

let failures = 0;
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);
const expect = (where, cond, msg, detail) => cond ? ok(where, msg) : bad(where, msg + ' — ' + JSON.stringify(detail));

async function fixture(request) {
  const j = await (await request.get(`${BASE}SWUSim/DevTools/tests/draw_endscreen_fixture.php?queue=bo3&scenario=p2won`)).json();
  if (!j.matchId || j.match !== 'sideboarding') throw new Error('fixture failed: ' + JSON.stringify(j));
  return j;
}
async function screen(browser, size, fx, seat) {
  const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
  const page = await ctx.newPage();
  await page.goto(`${BASE}SWUSim/Sideboard.php?matchId=${fx.matchId}&playerID=${seat}&authKey=${fx.keys[seat]}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  await page.waitForSelector('#deckGrid .card', { timeout: 60000 });
  return { ctx, page };
}
const view = (page) => page.evaluate(() => {
  const el = (id) => document.getElementById(id);
  const btn = (id) => ({ shown: !el(id).hidden && el(id).offsetParent !== null, disabled: el(id).disabled });
  const out = [...document.querySelectorAll('.sb-actions button')].filter((b) => b.offsetParent !== null).map((b) => b.getBoundingClientRect())
    .filter((r) => r.left < 0 || r.right > window.innerWidth);
  const grid = (id) => [...el(id).querySelectorAll('.card')].map((c) => c.title + ':' + ((c.querySelector('.qty') || {}).textContent || '1')).join(',');
  return { submit: btn('submit'), ready: btn('ready'), cancel: btn('cancel'), status: el('status').textContent, opp: el('oppStatus').textContent,
           deck: grid('deckGrid'), side: grid('sideGrid'), deckCount: el('deckCount').textContent, sideCount: el('sideCount').textContent,
           overflow: document.documentElement.scrollWidth - window.innerWidth, buttonsOutside: out.length };
});
async function until(page, pred, timeout = 20000) {
  const t0 = Date.now(); let v = null;
  while (Date.now() - t0 < timeout) { v = await view(page).catch(() => null); if (v && pred(v)) return { v, ok: true }; await page.waitForTimeout(400); }
  return { v, ok: false };
}

for (const [ename, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    const where = `${ename} ${size.name}`;
    const api = await browser.newContext();
    try {
      const fx = await fixture(api.request);
      const s1 = await screen(browser, size, fx, '1'), s2 = await screen(browser, size, fx, '2');
      const p1 = s1.page, p2 = s2.page;
      let r = await until(p1, (v) => /still sideboarding/.test(v.opp));
      const start = r.v;
      expect(where, r.ok && start.submit.shown && !start.submit.disabled && start.ready.shown && !start.cancel.shown && start.status === '',
        'it opens editable: Submit + Ready, no Cancel, the opponent still sideboarding', start);
      expect(where, start.overflow <= 0 && start.buttonsOutside === 0, 'the action row fits the screen', { o: start.overflow, out: start.buttonsOutside });

      // A legal swap: one copy out of the deck, a different card in from the sideboard.
      await p1.locator('#deckGrid .card').first().click();
      const outTitle = await p1.locator('#deckGrid .card').first().getAttribute('title');
      const inCard = p1.locator('#sideGrid .card').filter({ hasNot: p1.locator(`[alt="${(outTitle || '').replace(/"/g, '\\"')}"]`) }).first();
      await inCard.click();
      const swapped = await view(p1);
      expect(where, swapped.status === 'Changes not submitted.' && !swapped.submit.disabled && swapped.deck !== start.deck, 'a swap shows as not submitted', swapped);

      await p1.locator('#submit').click();
      r = await until(p1, (v) => /Sideboard submitted/.test(v.status));
      expect(where, r.ok && r.v.submit.disabled && r.v.cancel.shown && r.v.ready.shown, 'Submit saves it: Submit greys out, Cancel appears, still not ready', r.v);
      if (SHOTS) await p1.screenshot({ path: `${SHOTS}/sb2-${ename}-${size.name}-submitted.png`, fullPage: true });

      await p1.locator('#ready').click();
      r = await until(p1, (v) => /waiting for your opponent/.test(v.status));
      expect(where, r.ok && !r.v.ready.shown && r.v.cancel.shown && r.v.submit.disabled, 'Ready: waiting for the opponent, only Cancel left', r.v);
      await p1.locator('#deckGrid .card').first().click({ force: true });
      const locked = await view(p1);
      expect(where, locked.deck === swapped.deck && locked.side === swapped.side, 'a ready list is locked — a click moves nothing', { before: swapped.deck, after: locked.deck });
      r = await until(p2, (v) => v.opp === 'Your opponent is ready.');
      expect(where, r.ok, 'seat 2 is told the opponent is ready', r.v);
      if (SHOTS) await p1.screenshot({ path: `${SHOTS}/sb2-${ename}-${size.name}-ready.png`, fullPage: true });

      await p1.locator('#cancel').click();
      r = await until(p1, (v) => v.status === 'Changes not submitted.');
      expect(where, r.ok && r.v.ready.shown && !r.v.cancel.shown && !r.v.submit.disabled && r.v.deck === swapped.deck,
        'Cancel: back to editing, nothing submitted, the swap still on screen', r.v);
      r = await until(p2, (v) => /still sideboarding/.test(v.opp));
      expect(where, r.ok, 'and seat 2 sees the opponent sideboarding again', r.v);

      await p1.locator('#ready').click();
      await p1.waitForSelector('.sd-modal .sd-ok', { timeout: 10000 });
      const asked = await p1.locator('.sd-modal .sd-message').textContent();
      expect(where, /the deck from last game/.test(asked || ''), 'Ready with unsubmitted changes asks first', asked);
      await p1.locator('.sd-modal .sd-ok').click();
      r = await until(p1, (v) => /waiting for your opponent/.test(v.status));
      expect(where, r.ok && r.v.deck === start.deck && r.v.side === start.side, 'then shows the deck actually played: last game\'s', { deck: r.v && r.v.deck, start: start.deck });

      await p2.locator('#ready').click();
      // The navigation itself, not the board's full load (that can take 30s+ on a loaded dev box).
      await Promise.all([p1.waitForURL(/NextTurn\.php\?gameName=/, { timeout: 60000, waitUntil: 'commit' }), p2.waitForURL(/NextTurn\.php\?gameName=/, { timeout: 60000, waitUntil: 'commit' })]);
      const g1 = new URL(p1.url()).searchParams.get('gameName'), g2 = new URL(p2.url()).searchParams.get('gameName');
      expect(where, g1 && g1 === g2 && g1 !== fx.gameName, `seat 2's Ready starts game 2 and both screens go to its board (${g1})`, { g1, g2 });
      await s1.ctx.close(); await s2.ctx.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    await api.close();
  }
  await browser.close();
}
console.log(failures === 0 ? 'PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
