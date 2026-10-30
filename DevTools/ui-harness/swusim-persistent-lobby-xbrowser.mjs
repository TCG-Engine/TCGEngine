// SWUSim persistent 1v1 lobby (Return to Lobby) — the waiting room and the end-game screen, Chromium + Firefox + WebKit,
// desktop (1280x800) and phone width (390x844).
// Spec: docs/superpowers/specs/2026-10-30-persistent-lobby-rematch-design.md
//
// Matches come from SWUSim/DevTools/tests/persistent_lobby_fixture.php (a started 1v1 match from a room or the public
// queue). Each seat gets its own browser context holding only its own seat key — two people, two browsers.
//
// ROOM (waiting room after a match):
//   · seat 1 returned, seat 2 still on the end screen → seat 2's tile reads ON END-GAME SCREEN, Start is disabled and the
//     hint names the end screen;
//   · the host's Best of 1 / Best of 3 toggle switches, and seat 2 (back in the room) reads "Best of 3";
//   · no horizontal page overflow.
//
// END SCREEN (a real game over: seat 2 concedes through the board's own input):
//   · room match: seat 1 asks for a Rematch → seat 2 gets the pop-up with "Accept and Return to Lobby" / "Decline";
//     Decline → seat 1's status line says so; seat 1 asks again, seat 2 accepts → BOTH pages land in the waiting room;
//   · public match: Requeue is offered and Return to Lobby is not; Requeue lands on the main menu with the waiting
//     pop-up up; seat 2's status line says seat 1 requeued, and its Rematch button is gone.
//
// Usage: node swusim-persistent-lobby-xbrowser.mjs [BASE] [SHOTS_DIR] [engines]
import { chromium, firefox, webkit } from 'playwright';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const ONLY  = (process.argv[4] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));
// The phone pass forces the SEPARATE mobile layout (GameLayoutMobile — chosen by user agent or ?swuLayout=mobile); a
// narrow viewport alone renders the desktop layout at 390px.
const SIZES = [{ name: 'desktop', w: 1280, h: 800, layout: '' }, { name: 'phone', w: 390, h: 844, layout: '&swuLayout=mobile' }];

let failures = 0;
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);

async function fixture(request, origin, finish, queue = 'bo1') {
  const r = await request.get(`${BASE}SWUSim/DevTools/tests/persistent_lobby_fixture.php?origin=${origin}${finish ? '&finish=1' : ''}&queue=${queue}`);
  const j = await r.json();
  if (!j.lobbyID) throw new Error('fixture failed: ' + JSON.stringify(j));
  return j;
}
async function endAction(request, fx, seat, action, extra = {}) {
  const r = await request.post(`${BASE}SWUSim/EndScreenAction.php`,
    { form: { gameName: fx.gameName, playerID: String(seat), authKey: fx.keys[seat], action, ...extra } });
  return r.json();
}
// A context that holds ONLY this seat's keys: the room key in localStorage (what the waiting room reads) and the
// lastAuthKey cookie (what the board reads) — exactly what the real hand-offs store.
async function seatContext(browser, size, fx, seat) {
  const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
  await ctx.addInitScript(([lobbyID, key]) => {
    try { localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey: key, ts: Date.now() })); } catch (e) {}
  }, [fx.lobbyID, fx.keys[seat]]);
  const host = new URL(BASE).hostname;
  await ctx.addCookies([{ name: 'lastAuthKey', value: fx.keys[seat], domain: host, path: '/' }]);
  return ctx;
}
// The end-game overlay FADES IN (opacity 0 → 1); headless Firefox runs that at a few frames a second, so a screenshot
// taken as soon as the buttons exist shows the board with no overlay. Wait for the fade before any end-screen shot.
const settledShot = async (page, path) => {
  await page.waitForFunction(() => getComputedStyle(document.getElementById('game-over-overlay')).opacity === '1', null, { timeout: 15000 }).catch(() => {});
  await page.screenshot({ path });
};
const overflow = (page) => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);

for (const [ename, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    const where = `${ename} ${size.name} room`;
    const api = await browser.newContext();
    const fx = await fixture(api.request, 'room', true);
    await endAction(api.request, fx, 1, 'lobby');
    const c1 = await seatContext(browser, size, fx, 1);
    const p1 = await c1.newPage();
    await p1.goto(`${BASE}SharedUI/WaitingRoom.php?lobby=${fx.lobbyID}`, { waitUntil: 'domcontentloaded' });
    try {
      await p1.waitForSelector('.wr-pill-endscreen', { timeout: 15000 });
      const pills = await p1.$$eval('.wr-pill-endscreen', (e) => e.map((x) => x.textContent));
      pills.length === 1 && pills[0] === 'ON END-GAME SCREEN' ? ok(where, 'seat 2 reads ON END-GAME SCREEN') : bad(where, 'end-screen pills: ' + JSON.stringify(pills));
      const start = await p1.$('#wr-start');
      (start && await start.isDisabled()) ? ok(where, 'Start is disabled') : bad(where, 'Start is not disabled');
      const hint = await p1.textContent('#wr-hint');
      /still on the end-game screen/.test(hint || '') ? ok(where, 'hint: ' + hint) : bad(where, 'hint: ' + hint);
      if (SHOTS) await p1.screenshot({ path: `${SHOTS}/lobby-${ename}-${size.name}-endscreen.png`, fullPage: true });

      // The host switches to Best of 3; seat 2 comes back and reads it.
      await p1.click('label[for="wr-bestof-bo3"]');
      await p1.waitForFunction(() => document.querySelector('#wr-bestof-bo3')?.checked, null, { timeout: 15000 });
      await endAction(api.request, fx, 2, 'lobby');
      const c2 = await seatContext(browser, size, fx, 2);
      const p2 = await c2.newPage();
      await p2.goto(`${BASE}SharedUI/WaitingRoom.php?lobby=${fx.lobbyID}`, { waitUntil: 'domcontentloaded' });
      await p2.waitForSelector('#wr-bestof-text', { timeout: 15000 });
      await p2.waitForFunction(() => document.querySelector('#wr-bestof-text')?.textContent === 'Best of 3', null, { timeout: 15000 })
        .then(() => ok(where, 'seat 2 reads Best of 3'), async () => bad(where, 'seat 2 reads ' + await p2.textContent('#wr-bestof-text')));
      (await p2.$('input[name="wr-bestof"]')) === null ? ok(where, 'seat 2 has no toggle') : bad(where, 'seat 2 got the host toggle');
      for (const [pg, who] of [[p1, 'host'], [p2, 'guest']]) {
        const o = await overflow(pg);
        o <= 0 ? ok(where, `${who}: no horizontal overflow`) : bad(where, `${who}: page overflows by ${o}px`);
      }
      if (SHOTS) {
        await p1.screenshot({ path: `${SHOTS}/lobby-${ename}-${size.name}-host-bo3.png`, fullPage: true });
        await p2.screenshot({ path: `${SHOTS}/lobby-${ename}-${size.name}-guest-bo3.png`, fullPage: true });
      }
      await c2.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    await c1.close(); await api.close();
  }

  // ── END SCREEN ─────────────────────────────────────────────────────────────────────────────────────────────────
  for (const size of SIZES) {
    const api = await browser.newContext();
    const board = (fx, seat) => `${BASE}NextTurn.php?gameName=${fx.gameName}&playerID=${seat}&folderPath=SWUSim${size.layout}`;
    const openBoards = async (fx, concede = '10006') => {
      const c1 = await seatContext(browser, size, fx, 1), c2 = await seatContext(browser, size, fx, 2);
      const p1 = await c1.newPage(), p2 = await c2.newPage();
      await p1.goto(board(fx, 1), { waitUntil: 'domcontentloaded' });
      await p2.goto(board(fx, 2), { waitUntil: 'domcontentloaded' });
      await p2.waitForFunction(() => typeof SubmitInput === 'function', null, { timeout: 30000 });
      await p2.evaluate((mode) => SubmitInput(mode, ''), concede);   // seat 2 concedes: a real game over on both boards
      await p1.waitForSelector('#game-over-overlay #game-over-buttons button', { timeout: 30000 });
      await p2.waitForSelector('#game-over-overlay #game-over-buttons button', { timeout: 30000 });
      return { c1, c2, p1, p2 };
    };
    const buttons = (pg) => pg.$$eval('#game-over-buttons button', (bs) => bs.filter((b) => b.offsetParent !== null).map((b) => b.textContent.trim()));
    const clickButton = (pg, text) => pg.locator('#game-over-buttons button', { hasText: text }).first().click();

    // Room: request, decline, request again, accept → both in the room.
    let where = `${ename} ${size.name} end-room`;
    try {
      const fx = await fixture(api.request, 'room', false);
      const { c1, c2, p1, p2 } = await openBoards(fx);
      const b1 = await buttons(p1);
      ['Return to Lobby', 'Rematch', 'Quick Rematch', 'Convert to Best of 3', 'Return to Main Menu'].every((t) => b1.includes(t))
        ? ok(where, 'room buttons: ' + b1.join(' · ')) : bad(where, 'room buttons: ' + b1.join(' · '));
      b1.includes('Requeue') ? bad(where, 'a room match offers Requeue') : ok(where, 'no Requeue after a room match');
      await clickButton(p1, /^Rematch$/);
      await p2.waitForSelector('#swu-end-request-modal', { timeout: 15000 });
      const modalText = await p2.textContent('#swu-end-request-modal');
      /Accept and Return to Lobby/.test(modalText) && /Decline/.test(modalText) ? ok(where, 'seat 2 pop-up: ' + modalText.replace(/\s+/g, ' ')) : bad(where, 'pop-up: ' + modalText);
      if (SHOTS) await settledShot(p2, `${SHOTS}/end-${ename}-${size.name}-popup.png`);
      await p2.click('#swu-end-request-modal button:has-text("Decline")');
      await p1.waitForFunction(() => /declined/.test(document.querySelector('#swu-end-status')?.textContent || ''), null, { timeout: 15000 })
        .then(async () => ok(where, 'seat 1 status: ' + await p1.textContent('#swu-end-status')), async () => bad(where, 'seat 1 status: ' + await p1.textContent('#swu-end-status').catch(() => '-')));
      await clickButton(p1, /^Rematch$/);
      await p2.waitForSelector('#swu-end-request-modal', { timeout: 15000 });
      await p2.click('#swu-end-request-modal button:has-text("Accept")');
      await Promise.all([p1.waitForURL(/WaitingRoom\.php\?lobby=/, { timeout: 20000 }), p2.waitForURL(/WaitingRoom\.php\?lobby=/, { timeout: 20000 })])
        .then(() => ok(where, 'both land in the waiting room'), () => bad(where, 'URLs: ' + p1.url() + ' | ' + p2.url()));
      await p1.waitForSelector('#wr-roster .wr-seat', { timeout: 15000 }).catch(() => {});
      const seated = await p1.$$eval('.wr-seat-mine', (e) => e.length).catch(() => 0);
      seated === 1 ? ok(where, 'seat 1 is seated in the room') : bad(where, 'seat 1 not seated in the room');
      await c1.close(); await c2.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // Public: Requeue.
    where = `${ename} ${size.name} end-public`;
    try {
      const fx = await fixture(api.request, 'public', false);
      const { c1, c2, p1, p2 } = await openBoards(fx);
      const b1 = await buttons(p1);
      b1.includes('Requeue') && !b1.includes('Return to Lobby') ? ok(where, 'public buttons: ' + b1.join(' · ')) : bad(where, 'public buttons: ' + b1.join(' · '));
      // The Bo1/Bo3 chip sits on Quick Rematch's row, to its right, inside the button column (it used to wrap onto a
      // row of its own).
      // Also with the longest label the button takes ("Quick Rematch requested…", while a request waits).
      const geo = await p1.evaluate(() => {
        const r = (id) => document.getElementById(id)?.getBoundingClientRect();
        const measure = () => {
          const q = r('swu-end-quick'), c = r('swu-bestof-btn'), col = r('game-over-buttons');
          return q && c && col ? { qTop: q.top, qBottom: q.bottom, qRight: q.right, cTop: c.top, cLeft: c.left, cRight: c.right, colRight: col.right } : null;
        };
        const btn = document.getElementById('swu-end-quick'), label = btn ? btn.textContent : '';
        const plain = measure();
        if (btn) btn.textContent = 'Quick Rematch requested…';
        const waiting = measure();
        if (btn) btn.textContent = label;
        return { plain, waiting };
      });
      const sameRow = (g) => g && Math.abs(g.qTop - g.cTop) <= 2 && g.cLeft >= g.qRight && g.cRight <= g.colRight + 1;
      for (const k of ['plain', 'waiting']) sameRow(geo[k])
        ? ok(where, `the Bo chip shares the Quick Rematch row (${k} label)`) : bad(where, `Bo chip geometry (${k} label): ` + JSON.stringify(geo[k]));
      for (const [pg, who] of [[p1, 'seat 1'], [p2, 'seat 2']]) {
        const o = await overflow(pg);
        o <= 0 ? ok(where, `${who}: no horizontal overflow`) : bad(where, `${who}: page overflows by ${o}px`);
      }
      if (SHOTS) await settledShot(p1, `${SHOTS}/end-${ename}-${size.name}-public.png`);
      await clickButton(p1, /^Requeue$/);
      await p1.waitForURL(/MainMenu/, { timeout: 20000 }).catch(() => {});
      await p1.waitForSelector('#waiting-popup', { timeout: 20000 })
        .then(() => ok(where, 'Requeue lands on the menu, waiting for an opponent'), async () => bad(where, 'no waiting pop-up; url ' + p1.url() + ' status ' + await p1.textContent('#swu-end-status').catch(() => '-')));
      await p1.keyboard.press('Escape').catch(() => {});   // leave the queue (no stray public lobby)
      await p2.waitForFunction(() => /requeued/.test(document.querySelector('#swu-end-status')?.textContent || ''), null, { timeout: 15000 })
        .then(async () => ok(where, 'seat 2 status: ' + await p2.textContent('#swu-end-status')), async () => bad(where, 'seat 2 status: ' + await p2.textContent('#swu-end-status').catch(() => '-')));
      const b2 = await buttons(p2);
      b2.includes('Rematch') ? bad(where, 'seat 2 still offered Rematch: ' + b2.join(' · ')) : ok(where, 'seat 2 has no Rematch: ' + b2.join(' · '));
      await c1.close(); await c2.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    // A finished Bo3: Quick Rematch's length starts at Bo3, as it always has after a Bo3.
    where = `${ename} ${size.name} end-bo3`;
    try {
      const fx = await fixture(api.request, 'public', false, 'bo3');
      const { c1, c2, p1 } = await openBoards(fx, '10007');   // concede the whole match
      const chip = await p1.textContent('#swu-bestof-btn').catch(() => '');
      chip === 'Bo3' ? ok(where, 'the Quick Rematch length starts at Bo3') : bad(where, 'length chip reads ' + JSON.stringify(chip));
      await c1.close(); await c2.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    await api.close();
  }
  await browser.close();
}
console.log(failures === 0 ? 'ALL PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
