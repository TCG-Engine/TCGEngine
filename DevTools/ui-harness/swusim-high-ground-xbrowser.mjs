// SWUSim High Ground room — the menu entry, the room screen, watching and chatting from the board. Chromium + Firefox +
// WebKit, desktop (1280x800) and phone width (390x844). Spec: docs/superpowers/specs/2026-10-30-swusim-high-ground-design.md
//
// Rooms come from SWUSim/DevTools/tests/high_ground_fixture.php: claudebot1 (host), claudebot2, claudebot3 and two guests,
// a Tonight table (Guest 1 went 1-2-1 against the host's two lists on the same leader + base), and a live hill match
// between the first two in line (claudebot1 vs claudebot2). Each person gets their own browser context holding only their
// own room key; claudebot3 is logged in for real (chat needs an account).
//
//   · menu: "Create a High Ground Lobby" shows for Premier and hides for Meta Premier (rated);
//   · host: title, End match (live), no Start while live, ↑ ↓ ✕ on each line entry, the rule under the line;
//   · Tonight: guest row 1-2-1; deck names only on the host's two same-leader rows;
//   · host moves Guest 1 down: the name stays "Guest 1";
//   · claudebot3 (in line): the live banner, no host controls; Watch live opens the board as a spectator, and the board
//     gives this logged-in member a message box;
//   · claudebot2 (playing): the room sends them to their own board once, then shows "Back to your game";
//   · no horizontal overflow; the hill banner and the line don't overlap; a screenshot per engine × size × view.
//
// Usage: node swusim-high-ground-xbrowser.mjs [BASE] [SHOTS_DIR] [engines]
import { chromium, firefox, webkit } from 'playwright';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const ONLY  = (process.argv[4] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));
const SIZES = [{ name: 'desktop', w: 1280, h: 800, layout: '' }, { name: 'phone', w: 390, h: 844, layout: '&swuLayout=mobile' }];
const T = 45000;

let failures = 0;
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);
const check = (where, cond, msg, detail = '') => cond ? ok(where, msg) : bad(where, msg + (detail ? ' — ' + detail : ''));

async function fixture(request) {
  const r = await request.get(`${BASE}SWUSim/DevTools/tests/high_ground_fixture.php?accounts=claudebot1,claudebot2,claudebot3&guests=2&tonight=1&live=1`);
  const j = await r.json();
  if (!j.lobbyID) throw new Error('fixture failed: ' + JSON.stringify(j));
  return j;
}
// A browser for one person: their own room key (what the room page reads), optionally logged in to an account.
async function personContext(browser, size, fx, pid, login = '') {
  const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
  await ctx.addInitScript(([lobbyID, key]) => {
    try { localStorage.setItem('tcg:lobbyAuth:' + lobbyID, JSON.stringify({ authKey: key, ts: Date.now() })); } catch (e) {}
  }, [fx.lobbyID, fx.keys[pid]]);
  if (login) await ctx.request.post(`${BASE}AccountFiles/AttemptPasswordLogin.php`, { form: { submit: '1', userID: login, password: 'pass' } });
  return ctx;
}
const room = (fx) => `${BASE}SharedUI/WaitingRoom.php?lobby=${fx.lobbyID}`;
const overflow = (page) => page.evaluate(() => document.documentElement.scrollWidth - window.innerWidth);
const rects = (page, a, b) => page.evaluate(([a, b]) => {
  const r = (s) => { const e = document.querySelector(s); if (!e) return null; const x = e.getBoundingClientRect(); return { t: x.top, b: x.bottom, l: x.left, r: x.right }; };
  return [r(a), r(b)];
}, [a, b]);
const overlaps = (x, y) => !!(x && y && x.l < y.r && y.l < x.r && x.t < y.b && y.t < x.b);
const shot = async (page, name) => { if (SHOTS) await page.screenshot({ path: `${SHOTS}/${name}.png`, fullPage: true }); };

for (const [ename, engine] of Object.entries(ENGINES)) {
  const browser = await engine.launch();
  for (const size of SIZES) {
    const tag = `${ename} ${size.name}`;

    // ── menu ──────────────────────────────────────────────────────────────────────────────────────────────────────
    let where = `${tag} menu`;
    try {
      const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
      const page = await ctx.newPage();
      await page.goto(`${BASE}SharedUI/Sites/SWUSim/MainMenu.php`, { waitUntil: 'domcontentloaded', timeout: T });
      const vis = await page.evaluate(() => {
        const dlg = document.getElementById('setup-pvp'); const sel = document.getElementById('pvp-pool');
        const btn = dlg && dlg.querySelector('button[data-act="highground"]');
        if (!dlg || !sel || !btn) return { missing: true };
        const show = (v) => { sel.value = v; window.SWU_POOL_RULES_SYNC(dlg); return btn.style.display !== 'none'; };
        return { text: btn.textContent.trim(), premier: show('premier'),
                 metapremier: [...sel.options].some((o) => o.value === 'metapremier') ? show('metapremier') : null };
      });
      check(where, vis.text === 'Create a High Ground Lobby' && vis.premier === true, 'the button shows for Premier', JSON.stringify(vis));
      check(where, vis.metapremier === false, 'and hides for Meta Premier', JSON.stringify(vis));
      await ctx.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── the room ──────────────────────────────────────────────────────────────────────────────────────────────────
    const api = await browser.newContext();
    let fx;
    try { fx = await fixture(api.request); } catch (e) { bad(tag, e.message); await api.close(); continue; }
    const pidOf = (name) => Object.keys(fx.names).find((k) => fx.names[k] === name);

    where = `${tag} host`;
    const hc = await personContext(browser, size, fx, pidOf('claudebot1'));
    const hp = await hc.newPage();
    try {
      // The host is playing (claudebot1 vs claudebot2): the room sends them to their board once; back in the room, it stays.
      await hp.goto(room(fx), { waitUntil: 'domcontentloaded', timeout: T });
      await hp.waitForURL(/NextTurn\.php.*playerID=1/, { timeout: T });
      await hp.goto(room(fx), { waitUntil: 'domcontentloaded', timeout: T });
      await hp.waitForSelector('#hg-root .hg-line-row', { timeout: T });
      const info = await hp.evaluate(() => ({
        title: (document.querySelector('.hg-title') || {}).textContent || '',
        end: !!document.getElementById('hg-end'), start: !!document.getElementById('hg-start'),
        rows: [...document.querySelectorAll('.hg-line-row')].map((r) => ({ name: r.querySelector('.hg-name').textContent,
          up: !!r.querySelector('.hg-up'), down: !!r.querySelector('.hg-down'), kick: !!r.querySelector('.hg-kick') })),
        rule: (document.querySelector('.hg-rule') || {}).textContent || '',
        tonight: [...document.querySelectorAll('#hg-tonight tbody tr')].map((r) => ({ name: r.querySelector('.hg-t-name').textContent,
          deck: (r.querySelector('.hg-t-deck') || {}).textContent || '', wld: r.querySelector('.hg-t-wld').textContent })),
      }));
      check(where, info.title === 'High Ground', 'the title reads High Ground', info.title);
      check(where, info.end && !info.start, 'a match is on: End match, no Start');
      check(where, info.rows.length === 3 && info.rows.every((r) => r.up && r.down && r.kick), 'three in line, each with ↑ ↓ ✕', JSON.stringify(info.rows));
      check(where, info.rule.includes('Winner stays; loser goes to the back.'), 'the rule sits under the line');
      const guest = info.tonight.find((r) => r.name === 'Guest 1');
      const hostRows = info.tonight.filter((r) => r.name === 'claudebot1');
      check(where, guest && guest.wld === '1-2-1' && guest.deck === '', 'Tonight: Guest 1 1-2-1, no deck name', JSON.stringify(guest));
      check(where, hostRows.length === 2 && hostRows.every((r) => r.deck !== ''), 'the host\'s two same-leader rows show their deck names', JSON.stringify(hostRows));
      check(where, await overflow(hp) <= 0, 'no horizontal overflow');
      const [banner, line] = await rects(hp, '#hg-banner', '#hg-line');
      check(where, banner && line && !overlaps(banner, line), 'the hill banner and the line don\'t overlap', JSON.stringify([banner, line]));
      await shot(hp, `hg-${ename}-${size.name}-host`);

      // Guest 1 moves down the line; the name doesn't drift.
      const before = info.rows.map((r) => r.name).join(',');
      await hp.click('.hg-line-row:has(.hg-name:text-is("Guest 1")) .hg-down');
      await hp.waitForFunction((b) => [...document.querySelectorAll('.hg-line-row .hg-name')].map((x) => x.textContent).join(',') !== b, before, { timeout: T });
      const after = await hp.$$eval('.hg-line-row .hg-name', (e) => e.map((x) => x.textContent));
      check(where, after.join(',') === 'claudebot3,Guest 2,Guest 1', 'Guest 1 moved down and is still Guest 1', after.join(','));
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── a member in line: banner, watch, chat from the board ──────────────────────────────────────────────────────
    where = `${tag} watcher`;
    const wc = await personContext(browser, size, fx, pidOf('claudebot3'), 'claudebot3');
    const wp = await wc.newPage();
    try {
      await wp.goto(room(fx), { waitUntil: 'domcontentloaded', timeout: T });
      await wp.waitForSelector('#hg-watch', { timeout: T });
      const v = await wp.evaluate(() => ({
        banner: (document.querySelector('.hg-banner-live') || {}).textContent || '',
        hostCtl: !!document.querySelector('.hg-up, .hg-kick, #hg-end, #hg-start'),
        bo: (document.querySelector('.hg-bestof-text') || {}).textContent || '',
        me: (document.querySelector('.hg-line-row.hg-me .hg-you') || {}).textContent || '',
      }));
      check(where, v.banner.includes('On the hill now · Game 1 of 1 · 0–0'), 'the live banner', v.banner);
      check(where, !v.hostCtl && v.bo.includes('Best of 1'), 'no host controls; the length is shown as text', JSON.stringify(v));
      check(where, v.me === '(you)', '"(you)" marks their place in line');
      check(where, await overflow(wp) <= 0, 'no horizontal overflow');
      await shot(wp, `hg-${ename}-${size.name}-watcher`);
      await wp.click('#hg-watch');
      await wp.waitForURL(/NextTurn\.php.*playerID=S/, { timeout: T });
      check(where, true, 'Watch live opens the board as a spectator');
      await wp.waitForSelector('#chatComposer', { state: 'attached', timeout: T }).then(
        () => ok(where, 'the board gives this logged-in member a message box'),
        () => bad(where, 'no message box on the board for a member watcher'));
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── a hill player: sent to their game once, then "Back to your game" ─────────────────────────────────────────
    where = `${tag} hill player`;
    const pc = await personContext(browser, size, fx, pidOf('claudebot2'));
    const pp = await pc.newPage();
    try {
      await pp.goto(room(fx), { waitUntil: 'domcontentloaded', timeout: T });
      await pp.waitForURL(/NextTurn\.php.*playerID=2/, { timeout: T });
      check(where, true, 'the room sends the player to their own board (seat 2)');
      await pp.goto(room(fx), { waitUntil: 'domcontentloaded', timeout: T });
      await pp.waitForSelector('#hg-back', { timeout: T });
      await pp.waitForTimeout(4000);   // a few polls: it must NOT bounce again
      check(where, pp.url().includes('WaitingRoom.php'), 'coming back to the room stays in the room, with "Back to your game"');
      await shot(pp, `hg-${ename}-${size.name}-player`);
    } catch (e) { bad(where, e.message.split('\n')[0]); }

    // ── between matches: "Next match", Start waits for both Ready ──────────────────────────────────────────────────
    where = `${tag} between`;
    const bc0 = await browser.newContext();
    try {
      const r = await bc0.request.get(`${BASE}SWUSim/DevTools/tests/high_ground_fixture.php?accounts=claudebot1,claudebot2&guests=1&tonight=1`);
      const fb = await r.json();
      const bc = await personContext(browser, size, fb, Object.keys(fb.names).find((k) => fb.names[k] === 'claudebot1'));
      const bp = await bc.newPage();
      await bp.goto(room(fb), { waitUntil: 'domcontentloaded', timeout: T });
      await bp.waitForSelector('.hg-banner-next', { timeout: T });
      const v = await bp.evaluate(() => ({
        label: document.querySelector('.hg-banner-next').textContent,
        who: [...document.querySelectorAll('.hg-contender .hg-who')].map((x) => x.textContent),
        start: (() => { const b = document.getElementById('hg-start'); return b ? { text: b.textContent, disabled: b.disabled } : null; })(),
        ready: !!document.getElementById('hg-ready'),
      }));
      check(where, v.label === 'Next match' && v.who.join(',') === 'claudebot1,claudebot2', 'the banner names the next match', JSON.stringify(v));
      check(where, v.start && v.start.text === 'Start next match' && v.start.disabled, 'Start next match waits for both Ready', JSON.stringify(v.start));
      check(where, v.ready, 'the host, called up, has a Ready button');
      // Someone called up who walked away: the host can send them to the back or remove them from the banner itself.
      const ctl = await bp.$$eval('.hg-contender', (cs) => cs.map((c) => ({ who: c.querySelector('.hg-who').textContent,
        back: !!c.querySelector('.hg-toback'), kick: !!c.querySelector('.hg-kick') })));
      const other = ctl.find((c) => c.who === 'claudebot2'), self = ctl.find((c) => c.who === 'claudebot1');
      check(where, other && other.back && other.kick && self && !self.kick, 'the banner gives the host "back of the line" and ✕ for the other player', JSON.stringify(ctl));
      check(where, await overflow(bp) <= 0, 'no horizontal overflow');
      await shot(bp, `hg-${ename}-${size.name}-between`);
      await bc.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    await bc0.close();

    // ── the end of a hill match: "Back to the room", and it goes there by itself ───────────────────────────────────
    where = `${tag} end screen`;
    const ec0 = await browser.newContext();
    try {
      const r = await ec0.request.get(`${BASE}SWUSim/DevTools/tests/high_ground_fixture.php?accounts=claudebot1,claudebot2&live=1`);
      const fe = await r.json();
      const p2 = Object.keys(fe.names).find((k) => fe.names[k] === 'claudebot2');
      const ec = await personContext(browser, size, fe, p2);
      await ec.addCookies([{ name: 'lastAuthKey', value: fe.keys[p2], domain: new URL(BASE).hostname, path: '/' }]);
      const ep = await ec.newPage();
      await ep.goto(`${BASE}NextTurn.php?gameName=${fe.gameName}&playerID=2&folderPath=SWUSim${size.layout}`, { waitUntil: 'domcontentloaded', timeout: T });
      await ep.waitForFunction(() => typeof window.SubmitInput === 'function', null, { timeout: T });
      await ep.evaluate(() => window.SubmitInput('10006', ''));   // seat 2 concedes: the Bo1 hill match is over
      const btn = await ep.waitForSelector('#game-over-overlay button:has-text("Back to the room")', { timeout: T }).then(() => true, () => false);
      check(where, btn, 'the end screen offers "Back to the room"');
      const others = await ep.$$eval('#game-over-overlay button', (b) => b.map((x) => x.textContent.trim()).filter((t) => /rematch|requeue|convert/i.test(t)));
      check(where, others.length === 0, 'and no rematch / requeue / convert', JSON.stringify(others));
      await ep.waitForURL(/WaitingRoom\.php\?lobby=/, { timeout: 30000 }).then(
        () => ok(where, 'it returns to the room by itself'), () => bad(where, 'it did not return to the room'));
      await ec.close();
    } catch (e) { bad(where, e.message.split('\n')[0]); }
    await ec0.close();

    for (const c of [hc, wc, pc, api]) await c.close();
  }
  await browser.close();
}
console.log(failures === 0 ? 'ALL PASS' : `${failures} FAILED`);
process.exit(failures === 0 ? 0 : 1);
