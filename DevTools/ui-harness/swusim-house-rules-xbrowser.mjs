// Cross-browser check for the Twin Suns HOUSE RULES panel in the waiting room (2026-10-19).
// Spec: docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md
//
// Drives REAL rooms against REAL endpoints in chromium + firefox + webkit:
//   PUBLIC  — three accounts in one public Twin Suns room. A vote unreadies the voter; 2 of 3 lights the rule for
//             everyone; the host's value change unreadies the other two and they see the new number; once Take Credit
//             is lit, Take Restore is disabled for the seat that has not voted.
//   PRIVATE — the host's checkbox IS the rule: the guest sees it lit and read-only, and is unreadied.
// Then the host's page is shrunk to 390px: the panel must not cause sideways scroll. Screenshots go to
// $SHOTS (default <tmpdir>/swusim-house-rules)/house-rules-<engine>-<width>.png — look at them, a geometry check cannot see the glow.
//
// Usage: node swusim-house-rules-xbrowser.mjs [baseUrl]
// ⚠ WEBKIT: launched directly — the pinned local Playwright is 1.61.1 (see waiting-room-xbrowser.mjs for >= 1.62).
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync, mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const CREDS = [{ user: 'claudebot1', pass: 'pass' }, { user: 'claudebot2', pass: 'pass' }, { user: 'claudebot3', pass: 'pass' }];
const HERE = dirname(fileURLToPath(import.meta.url));
const SHOTS = process.env.SHOTS || join(tmpdir(), 'swusim-house-rules');   // outside the repo
mkdirSync(SHOTS, { recursive: true });
// A Twin Suns-legal list as FREE TEXT, built from the tracked JSON fixture (2 leaders + base + deck) so the harness
// never depends on an external deck host.
const DECK = (() => {
  const j = JSON.parse(readFileSync(join(HERE, '../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json'), 'utf8'));
  return ['Leader', `1 ${j.leader.id}`, `1 ${j.secondleader.id}`, 'Base', `1 ${j.base.id}`, 'Deck',
    ...j.deck.map((c) => `${c.count} ${c.id}`)].join('\n');
})();
const POLL_WAIT = 2600;   // the room polls every 1.5s
// The PUBLIC part joins whatever open public room of this format exists — set HR_FORMAT=twinsuns-preview (or any
// Twin Suns-family format) when someone is already sitting in a public 'twinsuns' room on this server.
const FORMAT = process.env.HR_FORMAT || 'twinsuns';

const ENGINES = { chromium, firefox, webkit };
// HR_ENGINES=chromium,firefox to run a subset.
if (process.env.HR_ENGINES) for (const k of Object.keys(ENGINES)) if (!process.env.HR_ENGINES.split(',').includes(k)) delete ENGINES[k];
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra) => {
  if (!cond) allOk = false;
  results.push([engine, name, !!cond, extra]);
};

async function login(page, cred) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', cred.user);
  await page.fill('input[name="password"]', cred.pass);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
}

// Open a public (Join Queue) or private (Create) Twin Suns room the way the menu does: POST JoinQueue.php from the
// logged-in page, hand the seat's authKey over through localStorage under the waiting room's key, then navigate.
// ⚠ Not by clicking the menu: the 2026-10 menu revamp removed the ids the older harnesses drive (#tab-text,
// #join-queue-btn …), and this harness is about the waiting room, not the menu. Returns the lobby id.
async function openRoom(page, privateRoom) {
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  const r = await page.evaluate(async ({ deck, priv, format }) => {
    const body = new URLSearchParams({ deckLink: deck, game_type: 'casual', preconstructedDeck: '', rootName: 'SWUSim',
                                       format, queueType: 'bo1' });
    if (priv) body.set('createPrivate', '1');
    const res = await fetch('../APIs/Lobbies/JoinQueue.php', { method: 'POST', body });
    return res.json();
  }, { deck: DECK, priv: privateRoom, format: FORMAT });
  if (!r.success || !r.lobbyID) throw new Error('JoinQueue refused: ' + JSON.stringify(r));
  await page.evaluate(({ id, key }) => localStorage.setItem('tcg:lobbyAuth:' + id, JSON.stringify({ authKey: key, ts: Date.now() })),
                      { id: r.lobbyID, key: r.authKey });
  await page.goto(BASE + 'SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(r.lobbyID), { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1800);
  return r.lobbyID;
}

async function joinRoom(page, lobby) {
  await page.goto(BASE + 'SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(lobby), { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1800);   // the first poll adopts the room's invite code, which doJoin posts
  await page.fill('#wr-deck-input', DECK);
  await page.click('#wr-deck-btn');
  await page.waitForTimeout(POLL_WAIT);
}

async function openPanel(page) {
  const open = await page.evaluate(() => { const d = document.querySelector('#wr-hr'); return !!(d && d.open); });
  if (!open) await page.click('#wr-hr > summary');
}

const view = (page) => page.evaluate(() => {
  const box = document.querySelector('#wr-hr');
  const rows = {};
  document.querySelectorAll('#wr-hr-rows .wr-hr-row').forEach((r) => {
    const cb = r.querySelector('.wr-hr-check');
    const num = r.querySelector('.wr-hr-value');
    const dot = r.querySelector('.wr-hr-dot');
    rows[r.getAttribute('data-rule')] = {
      active: r.classList.contains('is-active'), locked: r.classList.contains('is-locked'),
      checked: !!(cb && cb.checked), disabled: !!(cb && cb.disabled),
      value: num ? num.value : null, valueDisabled: num ? num.disabled : null,
      tally: (r.querySelector('.wr-hr-tally') || {}).textContent || '',
      dotVisible: !!dot && dot.getBoundingClientRect().width > 0,
      dotColor: dot ? getComputedStyle(dot).backgroundColor : '',
    };
  });
  return {
    shown: !!box && getComputedStyle(box).display !== 'none',
    rows,
    myNotReady: !!document.querySelector('.wr-seat-mine .wr-pill-notready'),
    notReady: document.querySelectorAll('.wr-pill-notready').length,
    hScroll: document.documentElement.scrollWidth > window.innerWidth + 1,
  };
});

async function leaveAll(pages) {
  for (const p of pages) { await p.click('#wr-leave').catch(() => {}); }
  await pages[0].waitForTimeout(800);
}

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  const ctxs = [];
  try {
    browser = await driver.launch();
    const pages = [];
    for (const cred of CREDS) {
      const ctx = await browser.newContext({ viewport: { width: 1728, height: 1100 } });
      ctxs.push(ctx);
      const page = await ctx.newPage();
      await login(page, cred);
      pages.push(page);
    }
    const [host, p2, p3] = pages;

    // ── PUBLIC ────────────────────────────────────────────────────────────────────────────────
    const lobby = await openRoom(host, false);
    // A public Join Queue lands in any open public Twin Suns room — a stray one left by an aborted run would give this
    // harness a room it is not host of. Refuse it rather than test the wrong room.
    const fresh = await host.evaluate(() => ({ n: (document.querySelector('#wr-count-n') || {}).textContent || '', start: !!document.querySelector('#wr-start') }));
    if (!/^1\//.test(fresh.n) || !fresh.start) {
      await leaveAll([host]);
      throw new Error('joined a stray public room (' + fresh.n + ') — it expires ~15 min after its last write; rerun then');
    }
    await joinRoom(p2, lobby);
    await joinRoom(p3, lobby);
    await host.waitForTimeout(POLL_WAIT);
    for (const p of pages) await openPanel(p);
    let v = await view(host);
    ok(engine, 'public: the House Rules panel is shown', v.shown);
    ok(engine, 'public: four rules listed', Object.keys(v.rows).length === 4, Object.keys(v.rows).join(','));
    ok(engine, 'public: everyone starts ready', v.notReady === 0, v.notReady);
    ok(engine, 'public: Extra Base HP shows its default 10', v.rows.extraBaseHp && v.rows.extraBaseHp.value === '10');

    await host.check('#wr-hr-rows [data-rule="extraBaseHp"] .wr-hr-check');
    await host.waitForTimeout(POLL_WAIT);
    v = await view(host);
    ok(engine, 'public: voting unreadies the voter', v.myNotReady);
    ok(engine, 'public: 1 vote is not enough (1/2, dark)', v.rows.extraBaseHp.tally === '1/2' && !v.rows.extraBaseHp.active, JSON.stringify(v.rows.extraBaseHp));

    await p2.check('#wr-hr-rows [data-rule="extraBaseHp"] .wr-hr-check');
    await p3.waitForTimeout(POLL_WAIT);
    for (const [i, p] of pages.entries()) {
      const pv = await view(p);
      ok(engine, `public: seat ${i + 1} sees Extra Base HP lit at 2 of 3`, pv.rows.extraBaseHp.active && pv.rows.extraBaseHp.dotColor.includes('61, 220, 132'),
         JSON.stringify(pv.rows.extraBaseHp));
    }
    // Re-ready the two voters so the host's value change has someone to unready.
    for (const p of [p2, p3]) await p.evaluate(() => { const b = document.querySelector('#wr-ready'); if (b && /^ready$/i.test(b.textContent.trim())) b.click(); });
    await p3.waitForTimeout(POLL_WAIT);

    ok(engine, 'public: a guest cannot edit the number', (await view(p2)).rows.extraBaseHp.valueDisabled === true);
    await host.fill('#wr-hr-rows [data-rule="extraBaseHp"] .wr-hr-value', '20');
    await host.press('#wr-hr-rows [data-rule="extraBaseHp"] .wr-hr-value', 'Tab');
    await p3.waitForTimeout(POLL_WAIT + 400);
    ok(engine, "public: the host's own box shows the 20 it committed", (await view(host)).rows.extraBaseHp.value === '20',
       (await view(host)).rows.extraBaseHp.value);
    for (const [i, p] of [[2, p2], [3, p3]]) {
      const pv = await view(p);
      ok(engine, `public: seat ${i} sees the host's new value 20`, pv.rows.extraBaseHp.value === '20', pv.rows.extraBaseHp.value);
      ok(engine, `public: the host's value change unreadied seat ${i}`, pv.myNotReady);
    }

    // Minor fixes (2026-10-19 follow-up).
    const VAL = '#wr-hr-rows [data-rule="extraBaseHp"] .wr-hr-value';
    ok(engine, 'public: the number is drawn bold (valid font longhands)',
       (await host.evaluate((sel) => getComputedStyle(document.querySelector(sel)).fontWeight, VAL)) === '600');
    for (const p of [p2, p3]) await p.evaluate(() => { const b = document.querySelector('#wr-ready'); if (b && /^ready$/i.test(b.textContent.trim())) b.click(); });
    await p3.waitForTimeout(POLL_WAIT);
    // A CLEARED box is not a value: it must not snap to the minimum and unready everyone.
    await host.fill(VAL, '');
    await host.press(VAL, 'Tab');
    await p3.waitForTimeout(POLL_WAIT);
    const hv = (await view(host)).rows.extraBaseHp;
    ok(engine, 'public: clearing the box keeps the value (host sees 20)', hv.value === '20',
       JSON.stringify({ value: hv.value, focused: await host.evaluate(() => document.activeElement && document.activeElement.className) }));
    v = await view(p2);
    ok(engine, 'public: clearing the box changed nothing for seat 2', v.rows.extraBaseHp.value === '20' && !v.myNotReady, JSON.stringify({ val: v.rows.extraBaseHp.value, notReady: v.myNotReady }));
    // While the host's cursor is in the box, the polls must not rebuild the room (roster art flickers).
    await host.focus(VAL);
    // The old loop only starts once something CHANGES while the cursor is in the box — so make a change.
    await p2.check('#wr-hr-rows [data-rule="takeRestore"] .wr-hr-check').catch(() => {});
    await host.waitForTimeout(POLL_WAIT);
    const rebuilds = await host.evaluate(() => new Promise((res) => {
      // A full redraw rebuilds the control rows (Start / Ready / Leave are innerHTML'd by render()). The roster's own
      // "Remove (Ns)" countdown ticks in place every second by design, so it is deliberately not watched.
      let n = 0; const mo = new MutationObserver((ms) => { n += ms.filter((m) => m.type === 'childList').length; });
      for (const id of ['wr-actions-left', 'wr-actions-right', 'wr-head-actions']) {
        const e = document.getElementById(id); if (e) mo.observe(e, { childList: true, subtree: true });
      }
      setTimeout(() => { mo.disconnect(); res(n); }, 4500);
    }));
    ok(engine, 'public: no room redraws while the host is typing', rebuilds === 0, rebuilds);
    await host.evaluate(() => document.activeElement && document.activeElement.blur());
    // A poll that was already in flight when you clicked must not flip your checkbox back.
    // Delay the RESPONSE, not the request: the server must answer with the room as it was BEFORE the click.
    await p3.route('**/PollLobbyUpdates.php', async (route) => {
      const resp = await route.fetch().catch(() => null);
      await new Promise((r) => setTimeout(r, 2000));
      if (resp) await route.fulfill({ response: resp }).catch(() => {});   // .catch: may outlive unroute/page close
      else await route.abort().catch(() => {});
    });
    await p3.waitForRequest('**/PollLobbyUpdates.php', { timeout: 10000 });   // a slow poll is now in flight
    await p3.check('#wr-hr-rows [data-rule="planScry"] .wr-hr-check');
    const flips = await p3.evaluate(() => new Promise((res) => {
      let unchecked = 0, t = 0;
      const iv = setInterval(() => {
        const cb = document.querySelector('#wr-hr-rows [data-rule="planScry"] .wr-hr-check');
        if (cb && !cb.checked) unchecked++;
        if (++t >= 40) { clearInterval(iv); res(unchecked); }
      }, 100);
    }));
    ok(engine, 'public: a slow in-flight poll never un-checks a fresh vote', flips === 0, flips);
    await p3.unroute('**/PollLobbyUpdates.php');
    await p3.waitForTimeout(POLL_WAIT);

    await host.check('#wr-hr-rows [data-rule="takeCredit"] .wr-hr-check');
    await p2.check('#wr-hr-rows [data-rule="takeCredit"] .wr-hr-check');
    await p3.waitForTimeout(POLL_WAIT);
    v = await view(p3);
    ok(engine, 'public: Take Credit lit', v.rows.takeCredit.active);
    ok(engine, 'public: Take Restore locked and disabled for a non-voter', v.rows.takeRestore.locked && v.rows.takeRestore.disabled, JSON.stringify(v.rows.takeRestore));

    await host.screenshot({ path: join(SHOTS, `house-rules-${engine}-1728.png`), fullPage: true });
    await host.setViewportSize({ width: 390, height: 844 });
    await host.waitForTimeout(POLL_WAIT);
    v = await view(host);
    ok(engine, 'public 390px: no sideways scroll', !v.hScroll);
    await host.locator('#wr-hr').screenshot({ path: join(SHOTS, `house-rules-${engine}-390.png`) });
    await host.setViewportSize({ width: 1728, height: 1100 });
    await leaveAll([p3, p2, host]);

    // ── PRIVATE ───────────────────────────────────────────────────────────────────────────────
    const plobby = await openRoom(host, true);
    await joinRoom(p2, plobby);
    await host.waitForTimeout(POLL_WAIT);
    await openPanel(host); await openPanel(p2);
    v = await view(p2);
    ok(engine, 'private: the guest\'s checkboxes are read-only', Object.values(v.rows).every((r) => r.disabled), JSON.stringify(v.rows));
    ok(engine, 'private: no vote tallies', Object.values(v.rows).every((r) => r.tally === ''));
    await host.check('#wr-hr-rows [data-rule="planScry"] .wr-hr-check');
    await p2.waitForTimeout(POLL_WAIT);
    v = await view(p2);
    ok(engine, 'private: the host\'s toggle lights Plan Scry for the guest', v.rows.planScry.active && v.rows.planScry.checked, JSON.stringify(v.rows.planScry));
    ok(engine, 'private: the host\'s toggle unreadied the guest', v.myNotReady);
    await p2.screenshot({ path: join(SHOTS, `house-rules-${engine}-private-guest.png`), fullPage: true });
    await leaveAll([p2, host]);
  } catch (e) {
    // Never strand a room: a stray PUBLIC room captures the next run's Join Queue.
    for (const c of ctxs) for (const pg of c.pages()) await pg.click('#wr-leave', { timeout: 2000 }).catch(() => {});
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    for (const c of ctxs) await c.close().catch(() => {});
    if (browser) await browser.close().catch(() => {});
  }
}

for (const [engine, name, pass, extra] of results) {
  console.log(`${pass ? 'ok  ' : 'BAD '} [${engine}] ${name}${pass || !extra ? '' : '  ' + extra}`);
}
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
