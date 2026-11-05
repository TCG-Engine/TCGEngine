// SWUSim Limited (Sealed) room — the menu entry and the room panel (#lt-root) in setup and building. Chromium + Firefox +
// WebKit, desktop (1440x900) and phone width (390x844). Spec: docs/superpowers/specs/2026-11-04-swusim-limited-sealed-draft-
// design.md §3.1; checklist SWUSim/Tests/Visual/LimitedRoom_NewPetranakiHud.md.
//
// Rooms come from SWUSim/DevTools/tests/limited_fixture.php (claudebot1 hosts, claudebot2 joins). Each person gets their own
// browser context holding only their own room key.
//   · host, setup: every pack mode can be picked; Chaos with one set shows the error and Start is disabled, a second set
//     clears it and enables Start; 🔀 sets that pack to a booster set and the choice survives the next poll;
//   · guest, setup: nothing inside the panel is editable;
//   · building: a m:ss countdown, two seat rows, "Open deckbuilder" leads to the builder;
//   · every state: no horizontal page scroll, every button ≥44px tall, the waiting room's deck bar is hidden;
//   · menu: the Limited tile opens its setup, whose "Create a Limited Room" lands logged-in claudebot1 in a room with
//     #lt-root shown; the PvP setup no longer carries that button.
//
// Usage: node swusim-limited-room-xbrowser.mjs [BASE] [SHOTS_DIR] [engines]
import { chromium, firefox, webkit } from 'playwright';

setTimeout(() => { console.log('WATCHDOG: harness took over 15 minutes'); process.exit(2); }, 900000).unref();
const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const ONLY  = (process.argv[4] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));
const SIZES = [{ name: 'desktop', w: 1440, h: 900 }, { name: 'phone', w: 390, h: 844 }];
const T = 45000;

let failures = 0;
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);
const check = (where, cond, msg, detail = '') => cond ? ok(where, msg) : bad(where, msg + (detail ? ' — ' + detail : ''));

async function fixture(request, phase) {
  const r = await request.get(`${BASE}SWUSim/DevTools/tests/limited_fixture.php?phase=${phase}`);
  const j = await r.json();
  if (!j.lobbyID) throw new Error('fixture failed: ' + JSON.stringify(j));
  return j;
}
async function personContext(browser, size, fx, seat) {
  const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
  await ctx.addInitScript(([lobbyID, key]) => {
    try { localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey: key, ts: Date.now() })); } catch (e) {}
  }, [fx.lobbyID, fx.keys[seat]]);
  return ctx;
}
const overflow = (page) => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
const shot = async (page, name) => { if (SHOTS) await page.screenshot({ path: `${SHOTS}/${name}.png`, fullPage: true }); };
// Everything a state must hold: no sideways scroll, 44px buttons, no deck bar.
async function invariants(page, where) {
  check(where, (await overflow(page)) <= 0, 'no horizontal page scroll', String(await overflow(page)));
  const short = await page.evaluate(() => [...document.querySelectorAll('#lt-root button, #lt-root a.btn')]
    .filter((b) => b.offsetParent !== null).map((b) => [b.textContent.trim(), Math.round(b.getBoundingClientRect().height)]).filter(([, h]) => h < 44));
  check(where, short.length === 0, 'every button is at least 44px tall', JSON.stringify(short));
  const deck = await page.evaluate(() => { const d = document.getElementById('wr-deck'); return !!d && d.offsetParent !== null; });
  check(where, !deck, 'the waiting room deck bar is hidden');
}
const openRoom = async (ctx, fx) => {
  const page = await ctx.newPage();
  await page.goto(`${BASE}${fx.urls.room}`, { waitUntil: 'domcontentloaded', timeout: T });
  await page.waitForSelector('#lt-root .lt-panel', { timeout: T });
  return page;
};
const pickMode = async (page, label) => { await page.locator('#lt-root label.seg__opt', { hasText: label }).first().click(); };

for (const [ename, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    const tag = `${ename} ${size.name}`;

    // ── host, setup ─────────────────────────────────────────────────────────────────────────────────────────────
    let where = `${tag} host setup`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'setup');
      const ctx = await personContext(browser, size, fx, 1);
      const page = await openRoom(ctx, fx);
      for (const m of ["Host’s Choice", 'Chaos', 'Single Set']) {
        await pickMode(page, m);
        const on = await page.evaluate(() => { const i = [...document.querySelectorAll('#lt-root input[data-lt="packMode"]')].find((x) => x.checked); return i ? i.value : ''; });
        check(where, on !== '', `pack mode "${m}" can be picked`, on);
      }
      await pickMode(page, 'Chaos');
      await page.locator('#lt-root input[data-lt="chaos"][value="SOR"]').check();
      await page.waitForFunction(() => /needs at least 2/.test((document.querySelector('#lt-root .lt-err') || {}).textContent || ''), null, { timeout: T });
      check(where, await page.locator('#lt-root .lt-start').isDisabled(), 'Chaos with one set shows the error and Start is disabled');
      await shot(page, `limited-room-${ename}-${size.name}-chaos-error`);
      await page.locator('#lt-root input[data-lt="chaos"][value="HMW"]').check();
      await page.waitForFunction(() => !document.querySelector('#lt-root .lt-err') && !document.querySelector('#lt-root .lt-start').disabled, null, { timeout: T });
      ok(where, 'a second set clears the error and enables Start');
      await pickMode(page, "Host’s Choice");
      await page.waitForSelector('#lt-root .lt-shuffle', { timeout: T });
      await page.waitForTimeout(2500);   // let the mode change land before the shuffle
      const before = await page.$$eval('#lt-root select[data-lt="lineup"]', (s) => s.map((x) => x.value));
      let changed = false, after = before;
      for (let i = 0; i < 6 && !changed; i++) {   // a random pick can land on the same set: try a few times
        await page.locator('#lt-root .lt-shuffle').first().click();
        after = await page.$$eval('#lt-root select[data-lt="lineup"]', (s) => s.map((x) => x.value));
        changed = after[0] !== before[0];
      }
      check(where, changed && /^[A-Z]{3}$/.test(after[0]), '🔀 sets pack 1 to a booster set', JSON.stringify([before[0], after[0]]));
      await page.waitForTimeout(3500);   // the debounced save, then at least one poll
      const kept = await page.$eval('#lt-root select[data-lt="lineup"]', (s) => s.value);
      check(where, kept === after[0], 'the shuffled pack survives the next poll', JSON.stringify([after[0], kept]));
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-host-setup`);
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── guest, setup ────────────────────────────────────────────────────────────────────────────────────────────
    where = `${tag} guest setup`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'setup');
      const ctx = await personContext(browser, size, fx, 2);
      const page = await openRoom(ctx, fx);
      const editable = await page.$$eval('#lt-root input, #lt-root select', (n) => n.filter((x) => !x.disabled).length);
      check(where, editable === 0, 'nothing in the panel is editable for the guest', String(editable));
      check(where, (await page.locator('#lt-root .lt-start').count()) === 0, 'the guest has no Start');
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-guest-setup`);
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── building ────────────────────────────────────────────────────────────────────────────────────────────────
    where = `${tag} building`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'building');
      const ctx = await personContext(browser, size, fx, 1);
      const page = await openRoom(ctx, fx);
      const clock = await page.$eval('#lt-root .lt-clock', (n) => n.textContent.trim());
      check(where, /^\d+:\d\d$/.test(clock), 'a m:ss countdown', clock);
      check(where, (await page.locator('#lt-root .lt-seat').count()) === 2, 'two seat rows');
      const href = await page.$eval('#lt-root .lt-open', (a) => a.getAttribute('href'));
      check(where, href.includes('SWUSim/LimitedBuilder.php?lobbyID=' + fx.lobbyID), 'Open deckbuilder leads to the builder', href);
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-building`);
      // Leaving during the build forfeits the event, so it asks first; Cancel keeps the seat (final review #5).
      await page.locator('#wr-leave').click();
      await page.locator('.sd-cancel').waitFor({ state: 'visible', timeout: 5000 }).catch(() => {});
      const asked = await page.locator('.sd-cancel').isVisible().catch(() => false);
      if (asked) await page.locator('.sd-cancel').click();
      await page.waitForTimeout(2000);
      check(where, asked && (await page.locator('#lt-root .lt-seat').count()) === 2, 'Leave asks before forfeiting, and Cancel keeps the seat');
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── menu ────────────────────────────────────────────────────────────────────────────────────────────────────
    where = `${tag} menu`;
    try {
      const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
      await ctx.request.post(`${BASE}AccountFiles/AttemptPasswordLogin.php`, { form: { submit: '1', userID: 'claudebot1', password: 'pass' } });
      const page = await ctx.newPage();
      await page.goto(`${BASE}SharedUI/Sites/SWUSim/MainMenu.php`, { waitUntil: 'domcontentloaded', timeout: T });
      check(where, (await page.locator('#setup-pvp button[data-act="limited"]').count()) === 0, 'the PvP setup no longer carries the Limited button');
      // A format-detection switch INTO PvP (window.SETUP_OPEN) opens plain PvP even after the High Ground tile used it.
      const head = await page.evaluate(() => {
        const d = document.getElementById('setup-pvp');
        window.SWU_SET_PVP_MODE(d, 'highground');
        window.SETUP_OPEN(d, null);
        const t = d.querySelector('.setup__title').textContent;
        const join = d.querySelector('button[data-act="join"]').style.display !== 'none';
        d.close();
        return { t, join };
      });
      check(where, head.t === 'PvP' && head.join, 'a switch into PvP always opens plain PvP', JSON.stringify(head));
      await page.locator('a.mode[href="#setup-limited"]').click();
      const btn = page.locator('#setup-limited button[data-act="limited"]');
      await btn.waitFor({ state: 'visible', timeout: T });
      check(where, (await btn.textContent()).trim() === 'Create a Limited Room', 'the Limited tile opens a setup offering Create a Limited Room');
      await btn.click();
      await page.waitForURL(/WaitingRoom\.php/, { timeout: T });
      await page.waitForSelector('#lt-root .lt-panel', { timeout: T });
      check(where, await page.locator('#lt-root .lt-panel').isVisible(), 'it lands in a Limited room');
      await ctx.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
  }
  await browser.close();
}
console.log(failures === 0 ? 'ALL PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
