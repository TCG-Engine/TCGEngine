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
        // Owner 2026-11-05: IC27 Icons packs are a supplemental set — offered in Host's Choice only.
        const sel = { "Host’s Choice": 'select[data-lt="lineup"]', Chaos: 'input[data-lt="chaos"]', 'Single Set': 'select[data-lt="singleSet"]' }[m];
        await page.waitForSelector('#lt-root ' + sel, { timeout: T }).catch(() => {});
        const offers = await page.evaluate(() => ({
          lineup: [...document.querySelectorAll('#lt-root select[data-lt="lineup"] option')].map((o) => o.value),
          single: [...document.querySelectorAll('#lt-root select[data-lt="singleSet"] option')].map((o) => o.value),
          chaos: [...document.querySelectorAll('#lt-root input[data-lt="chaos"]')].map((i) => i.value),
        }));
        if (m === "Host’s Choice") check(where, offers.lineup.includes('IC27') && offers.lineup.includes('HMW'), "Host's Choice offers IC27 (and the main sets)", JSON.stringify(offers.lineup));
        if (m === 'Chaos') check(where, offers.chaos.length > 0 && !offers.chaos.includes('IC27'), 'Chaos never offers IC27', JSON.stringify(offers.chaos));
        if (m === 'Single Set') check(where, offers.single.length > 0 && !offers.single.includes('IC27'), 'Single Set never offers IC27', JSON.stringify(offers.single));
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
      // Final review #1: once the host's edit is saved, the form follows the SERVER again — a change made elsewhere (a
      // second tab here) shows up within a couple of polls instead of the host's stale local copy.
      await page.evaluate(async ([lobbyID]) => {
        const k = JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + lobbyID)).authKey;
        await fetch('/TCGEngine/SWUSim/LimitedAction.php', { method: 'POST', body: new URLSearchParams({ lobbyID, authKey: k, action: 'settings',
          settings: JSON.stringify({ packMode: 'single', singleSet: 'TWI', packCount: 7 }) }) });
      }, [fx.lobbyID]);
      const followed = await page.waitForFunction(() => {
        const sel = document.querySelector('#lt-root select[data-lt="singleSet"]'), cnt = document.querySelector('#lt-root select[data-lt="packCount"]');
        return !!sel && sel.value === 'TWI' && cnt && cnt.value === '7';
      }, null, { timeout: 8000 }).then(() => true).catch(() => false);
      check(where, followed, 'after the host\'s own edit is saved, the form follows a change made from another tab');
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

    // ── Draft: drafting and built (Plan 3) ──────────────────────────────────────────────────────────────────────
    where = `${tag} draft`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'drafting&mode=draft&players=3');
      const ctx = await personContext(browser, size, fx, 1);
      const page = await openRoom(ctx, fx);
      check(where, (await page.textContent('#lt-root .lt-title')).trim() === 'Draft', 'the panel is titled Draft');
      check(where, (await page.locator('#lt-root .lt-podseat').count()) === 3, 'the pod shows 3 seats in seating order');
      const href = await page.$eval('#lt-root .lt-open-draft', (a) => a.getAttribute('href'));
      check(where, href.includes('SWUSim/LimitedDraft.php?lobbyID=' + fx.lobbyID), '"Open the draft" leads to the pick screen', href);
      check(where, /\/8$/.test((await page.textContent('#wr-count-n')).trim()), 'the seat count is out of 8');
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-drafting`);
      // Final review #4: leaving mid-draft drops the seat for good — it asks first; Cancel keeps it.
      await page.locator('#wr-leave').click();
      const askedD = await page.locator('.sd-cancel').waitFor({ state: 'visible', timeout: 5000 }).then(() => true).catch(() => false);
      if (askedD) await page.locator('.sd-cancel').click();
      await page.waitForTimeout(1500);
      check(where, askedD && (await page.locator('#lt-root .lt-podseat').count()) === 3, 'Leave asks before dropping a drafter, and Cancel keeps the seat');
      // Plan 4: a built pod's room starts Swiss round 1 by itself (the page ticks the room); a seat with a table is sent on.
      const fb = await fixture(api.request, 'built&mode=draft&players=3');
      const ctx2 = await personContext(browser, size, fb, 1);
      const p2 = await openRoom(ctx2, fb);
      const sent = await Promise.race([
        p2.waitForURL(/NextTurn\.php/, { timeout: T }).then(() => true),
        p2.waitForSelector('#lt-root .lt-tables', { timeout: T }).then(() => true),
      ]).catch(() => false);
      check(where, sent, 'built: round 1 starts by itself (to the board, or the bye shown)');
      await ctx.close(); await ctx2.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── Swiss rounds (Plan 4) ───────────────────────────────────────────────────────────────────────────────────
    where = `${tag} swiss round`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'round1&mode=draft&players=5');
      const bye = fx.tables.find((t) => t.bye).a;
      const table = fx.tables.find((t) => !t.bye);
      // A seat with a table is sent to its own board.
      const ctxA = await personContext(browser, size, fx, table.a);
      const pA = await ctxA.newPage();
      await pA.goto(`${BASE}${fx.urls.room}`, { waitUntil: 'domcontentloaded', timeout: T });
      await pA.waitForURL(/NextTurn\.php/, { timeout: T }).catch(() => {});
      check(where, pA.url().includes('NextTurn.php') && pA.url().includes('gameName=' + table.gameName), 'a seat with a table is sent to its own game', pA.url());
      await ctxA.close();
      // The bye seat stays and sees the round.
      const ctx = await personContext(browser, size, fx, bye);
      const page = await openRoom(ctx, fx);
      await page.waitForSelector('#lt-root .lt-tables', { timeout: T });
      check(where, /Round 1 of 3/.test(await page.textContent('#lt-root')), 'the panel says "Round 1 of 3"');
      check(where, (await page.locator('#lt-root .lt-table').count()) === 3, 'three rows: two tables and the bye');
      check(where, (await page.locator('#lt-root .lt-table--me').count()) === 1 && /bye/i.test(await page.textContent('#lt-root .lt-table--me')), 'my own row (the bye) is highlighted');
      check(where, /You have a bye this round\./.test(await page.textContent('#lt-root')) && (await page.locator('#lt-root .lt-back').count()) === 0, '"You have a bye this round." and no game button');
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-swiss-round`);
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    where = `${tag} swiss between`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'between&mode=draft&players=4');
      const ctx = await personContext(browser, size, fx, 1);
      const page = await openRoom(ctx, fx);
      await page.waitForSelector('#lt-root .lt-standings', { timeout: T });
      check(where, /Round 2 starts in/.test(await page.textContent('#lt-root')) && /^\d+:\d\d$/.test((await page.textContent('#lt-root .lt-clock')).trim()), 'the break: "Round 2 starts in" and a m:ss clock');
      const edit = await page.$eval('#lt-root .lt-edit', (a) => a.getAttribute('href'));
      check(where, edit.includes('SWUSim/LimitedBuilder.php?lobbyID=' + fx.lobbyID), 'Edit deck opens the builder', edit);
      const ranks = await page.$$eval('#lt-root .lt-standings tbody tr', (rs) => rs.map((r) => r.cells[0].textContent.trim()));
      check(where, JSON.stringify(ranks) === '["1","2","3","4"]', 'standings: four rows ranked 1–4', JSON.stringify(ranks));
      const pts = await page.$$eval('#lt-root .lt-standings tbody tr', (rs) => rs.map((r) => r.querySelector('.lt-pts').textContent.trim()));
      check(where, pts.every((x) => /^\d+$/.test(x)), 'the Pts column holds numbers', JSON.stringify(pts));
      check(where, await page.locator('#lt-root .lt-start-round').isDisabled(), 'Start round is disabled until everyone is Ready');
      await page.locator('#lt-root .lt-ready').click();
      await page.waitForFunction(() => { const b = document.querySelector('#lt-root .lt-ready'); return b && b.disabled && !!document.querySelector('#lt-root .lt-seat--me .lt-chip--on'); }, null, { timeout: T }).catch(() => {});
      check(where, await page.locator('#lt-root .lt-ready').isDisabled() && (await page.locator('#lt-root .lt-seat--me .lt-chip--on').count()) === 1, 'Ready from the room: my chip turns Ready and the button disables');
      for (const s of [2, 3, 4]) await api.request.post(`${BASE}SWUSim/LimitedAction.php`, { form: { lobbyID: fx.lobbyID, authKey: fx.keys[s], action: 'ready' } });
      await page.waitForFunction(() => { const b = document.querySelector('#lt-root .lt-start-round'); return b && !b.disabled; }, null, { timeout: T }).catch(() => {});
      check(where, !(await page.locator('#lt-root .lt-start-round').isDisabled()), 'once everyone is Ready the host may start the round');
      if (size.name === 'phone') {
        const fold = await page.evaluate(() => {
          const tb = document.querySelector('#lt-root .lt-standings .lt-tb'), omw = document.querySelector('#lt-root .lt-standings th.lt-omw');
          return { tb: !!tb && tb.offsetParent !== null, omw: !!omw && omw.offsetParent !== null };
        });
        check(where, fold.tb && !fold.omw, '390px: the tiebreaks fold into a line under the name, the OMW column is hidden', JSON.stringify(fold));
      }
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-swiss-between`);
      await page.locator('#lt-root .lt-start-round').click();
      await page.waitForURL(/NextTurn\.php/, { timeout: T }).catch(() => {});
      check(where, page.url().includes('NextTurn.php'), 'Start round: round 2 begins and the host is sent to their game', page.url());
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    where = `${tag} swiss removed`;   // owner fix (2026-11-05): a kicked player sees why, not the break's controls
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'between&mode=draft&players=4&dropSeat=4');
      const ctx = await personContext(browser, size, fx, 4);
      const page = await openRoom(ctx, fx);
      await page.waitForSelector('#lt-root .lt-standings', { timeout: T });
      check(where, /You were removed from this event\./.test(await page.textContent('#lt-root')), '"You were removed from this event." is shown');
      check(where, (await page.locator('#lt-root .lt-ready, #lt-root .lt-edit, #lt-root .lt-start-round').count()) === 0, 'no Edit deck, Ready or Start round');
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-swiss-removed`);
      await ctx.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    where = `${tag} bots`;   // Plan 5 (spec §3.7): the host fills seats with bots in setup
    try {
      const api = await browser.newContext();
      // Sealed: host + a bot is full; ✕ frees the seat, "Add a bot" fills it again.
      const fs = await fixture(api.request, 'setup&mode=sealed&bots=1');
      const ctx = await personContext(browser, size, fs, 1);
      const page = await openRoom(ctx, fs);
      const botRow = page.locator('#lt-root .lt-seat', { hasText: 'Arenabot' });
      check(where, (await botRow.count()) === 1 && /Bot/.test(await botRow.first().textContent()), 'the bot seat shows a "Bot" chip');
      check(where, (await page.locator('#lt-root .lt-add-bot').count()) === 1 && await page.locator('#lt-root .lt-add-bot').isDisabled(), 'Sealed with a bot is full: "Add a bot" is disabled');
      await page.locator('#lt-root .lt-remove-bot').first().click();
      await page.waitForFunction(() => document.querySelectorAll('#lt-root .lt-seat').length === 1, null, { timeout: T }).catch(() => {});
      check(where, (await page.locator('#lt-root .lt-seat').count()) === 1, '✕ removes the bot');
      await page.waitForFunction(() => { const b = document.querySelector('#lt-root .lt-add-bot'); return b && !b.disabled; }, null, { timeout: T }).catch(() => {});
      await page.locator('#lt-root .lt-add-bot').click();
      await page.waitForFunction(() => [...document.querySelectorAll('#lt-root .lt-seat')].some((x) => /Arenabot/.test(x.textContent)), null, { timeout: T }).catch(() => {});
      check(where, (await page.locator('#lt-root .lt-seat', { hasText: 'Arenabot' }).count()) === 1, '"Add a bot" fills the seat again');
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-bots-sealed`);
      await ctx.close();
      // Draft: host + 2 bots can start.
      const fd = await fixture(api.request, 'setup&mode=draft&players=3&bots=2');
      const ctxD = await personContext(browser, size, fd, 1);
      const pd = await openRoom(ctxD, fd);
      check(where, (await pd.locator('#lt-root .lt-remove-bot').count()) === 2 && !(await pd.locator('#lt-root .lt-start').isDisabled()), 'Draft: host + 2 bots — two ✕, Start Draft enabled');
      await shot(pd, `limited-room-${ename}-${size.name}-bots-draft`);
      await ctxD.close();
      // A guest sees the bots but no bot controls.
      const fg = await fixture(api.request, 'setup&mode=draft&players=3&bots=1');
      const ctxG = await personContext(browser, size, fg, 2);
      const pg = await openRoom(ctxG, fg);
      check(where, (await pg.locator('#lt-root .lt-seat', { hasText: 'Arenabot' }).count()) === 1 && (await pg.locator('#lt-root .lt-add-bot, #lt-root .lt-remove-bot').count()) === 0,
        'a guest sees the bot, but no "Add a bot" or ✕');
      await ctxG.close();
      // The pick screen marks bot seats in the pod row.
      const fp = await fixture(api.request, 'drafting&mode=draft&players=3&bots=2');
      const ctxP = await personContext(browser, size, fp, 1);
      const pp = await ctxP.newPage();
      await pp.goto(`${BASE}${fp.urls.draft}`, { waitUntil: 'domcontentloaded', timeout: T });
      await pp.waitForSelector('#ldPod .ld-seat', { timeout: T });
      check(where, (await pp.locator('#ldPod .ld-seat .ld-bot').count()) === 2, 'the pick screen tags the 2 bot seats in the pod row');
      await ctxP.close(); await api.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    where = `${tag} swiss done`;
    try {
      const api = await browser.newContext();
      const fx = await fixture(api.request, 'done&mode=draft&players=4');
      const ctx = await personContext(browser, size, fx, 2);
      const page = await openRoom(ctx, fx);
      await page.waitForSelector('#lt-root .lt-standings', { timeout: T });
      const top = (await page.textContent('#lt-root .lt-standings tbody tr:first-child .lt-name')).trim();
      check(where, /Final standings/.test(await page.textContent('#lt-root')) && (await page.textContent('#lt-root .lt-result')).includes(top + ' wins the event.'),
        'final standings, and the winner line names rank 1', top);
      await invariants(page, where);
      await shot(page, `limited-room-${ename}-${size.name}-swiss-done`);
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
      // Draft from the same dialog: a room of 8 whose Start waits for 3 players.
      await page.goto(`${BASE}SharedUI/Sites/SWUSim/MainMenu.php`, { waitUntil: 'domcontentloaded', timeout: T });
      await page.locator('a.mode[href="#setup-limited"]').click();
      await page.locator('#setup-limited label[for="lt-mode-2"]').click();
      await page.locator('#setup-limited button[data-act="limited"]').click();
      await page.waitForURL(/WaitingRoom\.php/, { timeout: T });
      await page.waitForSelector('#lt-root .lt-panel', { timeout: T });
      check(where, (await page.textContent('#lt-root .lt-title')).trim() === 'Draft' && (await page.textContent('#wr-count-n')).trim() === '1/8',
        'choosing Draft makes a Draft room of 8');
      check(where, await page.locator('#lt-root .lt-start').isDisabled() && /at least 3 players/.test(await page.textContent('#lt-root')),
        'with one player, Start Draft is disabled and says why');
      await ctx.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
  }
  await browser.close();
}
console.log(failures === 0 ? 'ALL PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
