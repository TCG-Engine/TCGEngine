// SWU-PGN replay viewer — open our fixture, step, jump, toggle hands, desktop + mobile, 3 engines;
// then the Replays tab: Open .swupgn, rows, Play SWUPGN, Delete, hostile names.
// Usage: node swupgn-viewer-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all three)
//        PART=viewer|menu to run one half.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const FIX = new URL('../tdd-regression/fixtures/swupgn/', import.meta.url);
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const PART = process.env.PART || 'all';
let allOk = true; const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
setTimeout(() => { console.log('WATCHDOG: 420s'); report(); process.exit(9); }, 420000);
function report() { for (const [e, n, p, x] of results) console.log(`${p ? 'PASS' : 'FAIL'}  ${e.padEnd(18)} ${n}${x ? ' — ' + x : ''}`); }

async function openViewer(file) { return openViewerText(fs.readFileSync(new URL(file, FIX), 'utf8')); }
async function openViewerText(text) {
  const r = await fetch(BASE + 'SWUSim/SwuPgnViewer.php?action=open', { method: 'POST', body: text });
  return r.json();
}
const phNames = (page) => page.$$eval('.swupgn-ph-name', (els) => els.map((e) => e.textContent));
const rel = (u) => u.replace(/^\/TCGEngine\//, '');
// A finished game saved with the in-game "Save Replay" button (Core/MatchReplayClient.js putReplay).
const SAVED = JSON.parse(fs.readFileSync(new URL('./fixtures/replay-optionchoose-goldfish.json', import.meta.url), 'utf8'));
const seedSavedReplay = (page) => page.evaluate((replay) => new Promise((res, rej) => {
  const r = indexedDB.open('tcgengine-match-replays', 1);
  r.onupgradeneeded = () => r.result.createObjectStore('replays', { keyPath: 'id' });
  r.onerror = () => rej(r.error);
  r.onsuccess = () => { const t = r.result.transaction('replays', 'readwrite'); t.objectStore('replays').put({ ...replay, id: 'SWUSim:harness:1', storedAt: new Date().toISOString() }); t.oncomplete = () => { r.result.close(); res(); }; };
}), SAVED);
const caption = (page) => page.$eval('#swupgnCaption', (el) => el.textContent);
const waitStep = (page, n) => page.waitForFunction((n) => window.SwuPgnViewer && window.SwuPgnViewer.state.step === n && !window.SwuPgnViewer.state.busy, n, { timeout: 15000 });

if (PART !== 'menu') for (const [name, type] of ENGINES) {
  const browser = await type.launch();
  for (const layout of ['desktop', 'mobile']) {
    const tag = `${name}/${layout}`;
    const ctx = await browser.newContext({ viewport: layout === 'mobile' ? { width: 390, height: 844 } : { width: 1600, height: 900 } });
    const page = await ctx.newPage();
    const errors = []; page.on('pageerror', (e) => errors.push(String(e)));
    try {
      const v = await openViewer('viewer-game.swupgn');
      await page.goto(BASE + rel(v.viewUrl) + `&swuLayout=${layout}`);
      await page.waitForSelector('#swupgnViewerBar', { timeout: 20000 });
      await waitStep(page, 0);
      ok(tag, 'the viewer bar appears', true);
      ok(tag, 'step 0 caption is Setup', (await caption(page)).startsWith('Setup'));
      // The board's flash-message popup (a dimming full-screen overlay) must not appear when stepping.
      await page.evaluate(() => {
        window.__swupgnPopups = [];
        new MutationObserver((ms) => { for (const m of ms) m.addedNodes.forEach((n) => {
          if (n.nodeType === 1 && n.parentElement === document.body && n.style && n.style.position === 'fixed' && n.style.backgroundColor === 'rgba(0, 0, 0, 0.5)') window.__swupgnPopups.push(n.textContent);
        }); }).observe(document.body, { childList: true });
      });
      await page.click('[data-swupgn="next"]'); await waitStep(page, 1);
      ok(tag, 'Next moves to step 1', (await caption(page)).includes('Round 1'));
      for (let i = 0; i < 5; i++) await page.click('[data-swupgn="next"]');
      await waitStep(page, 6);
      await page.waitForTimeout(500);
      ok(tag, '★ five fast Next presses land on step 6 (latest wins)', (await page.evaluate(() => window.SwuPgnViewer.state.step)) === 6);
      await page.click('[data-swupgn="prev"]'); await waitStep(page, 5);
      ok(tag, 'Prev goes back one step', true);
      await page.waitForTimeout(800);
      const popups = await page.evaluate(() => window.__swupgnPopups);
      ok(tag, '★ stepping pops up no flash message', popups.length === 0, JSON.stringify(popups));
      const chips = await page.$$('.swupgn-round');
      await chips[chips.length - 1].click();
      await page.waitForFunction(() => window.SwuPgnViewer && window.SwuPgnViewer.state.round === 2 && !window.SwuPgnViewer.state.busy, null, { timeout: 15000 });
      ok(tag, 'the last round chip jumps to round 2', true);
      await page.click('[data-swupgn="last"]');
      // The fixture's last step is a 2026-10-28 leader action: an EFFECT_START, a LOOK and a NAME under it.
      await page.waitForFunction(() => document.querySelector('#swupgnCaption').textContent.includes('Player 2 uses'), null, { timeout: 15000 });
      const lastCap = await caption(page);
      ok(tag, 'the last step shows the new records (effect, look, name) in its caption',
        lastCap.includes('Gains Sentinel for this phase on Yoda') && lastCap.includes("looks at 1 card of Player 1's hand") && lastCap.includes('names Vanguard Infantry'), lastCap);
      await page.waitForFunction(() => document.querySelectorAll('.swupgn-ph-name').length > 0, null, { timeout: 15000 }).catch(() => {});
      const ph = await page.$$eval('.swupgn-ph-name', (els) => els.map((e) => ({ text: e.textContent, tags: e.querySelectorAll('*').length })));
      ok(tag, '★ the unknown card renders its name as literal text', ph.some((p) => p.text === 'Mystery <b>Card</b>' && p.tags === 0), JSON.stringify(ph));
      ok(tag, 'the Hands toggle exists for an all-seeing file', await page.$('#swupgnHands') !== null);
      const bar = await page.$eval('#swupgnViewerBar', (el) => { const r = el.getBoundingClientRect(); return { l: r.left, r: r.right, b: r.bottom, vw: innerWidth, vh: innerHeight }; });
      ok(tag, 'the bar sits inside the viewport', bar.l >= 0 && bar.r <= bar.vw + 0.5 && bar.b <= bar.vh + 0.5, JSON.stringify(bar));
      ok(tag, 'no horizontal page scroll', await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
      const clear = await page.evaluate((layout) => {
        const bar = document.getElementById('swupgnViewerBar').getBoundingClientRect();
        if (layout === 'mobile') {
          // The phone layout ends above the bar (a sticky footer could never scroll clear of it), and its
          // last row — the sidebar header with the gear — is reachable: scrolled to the end, it is not under the bar.
          const root = document.getElementById('swuMobileRoot');
          if (!root) return { ok: false, why: 'no #swuMobileRoot' };
          root.scrollTop = root.scrollHeight;
          const rb = root.getBoundingClientRect(), g = document.getElementById('swuGearBtn').getBoundingClientRect();
          const hit = document.elementFromPoint(g.left + g.width / 2, g.top + g.height / 2);
          return { ok: rb.bottom <= bar.top + 0.5 && !!hit && !!hit.closest('#swuGearBtn'), rootBottom: rb.bottom, barTop: bar.top, gearHit: hit && (hit.id || String(hit.className)) };
        }
        const hit = [...document.querySelectorAll('img')].filter((i) => !i.closest('#swupgnViewerBar')).map((i) => i.getBoundingClientRect())
          .filter((r) => r.width > 40 && r.height > 40 && r.left < bar.right && r.right > bar.left && r.top < bar.bottom && r.bottom > bar.top);
        return { ok: hit.length === 0, covered: hit.length };
      }, layout);
      ok(tag, '★ the bar does not cover cards (desktop: docked clear; phone: the layout ends above it, gear reachable)', clear.ok, JSON.stringify(clear));
      await page.waitForTimeout(2500);   // let the last step's damage animation finish
      await page.screenshot({ path: `/tmp/swupgn-viewer-${name}-${layout}.png` });
      if (layout === 'desktop') {
        await page.selectOption('#swupgnHands', 'p2');
        await page.waitForURL(/viewerPerspective=2/, { timeout: 15000 });
        await page.waitForSelector('#swupgnViewerBar', { timeout: 20000 });
        ok(tag, "Hands → P2's view reloads the board from P2's seat", (await page.$eval('#swupgnHands', (s) => s.value)) === 'p2');
        await page.waitForFunction(() => window.SwuPgnViewer && window.SwuPgnViewer.state.meta && !window.SwuPgnViewer.state.busy, null, { timeout: 15000 });
        await page.waitForTimeout(1500);
        const after = await phNames(page);
        ok(tag, '★ after the Hands reload the unknown card still shows its name', after.includes('Mystery <b>Card</b>'), JSON.stringify(after));
      }
      ok(tag, 'no page errors', errors.length === 0, errors.join(' | '));
      // The gear menu offers a way out (a viewer is a spectator, so the Match section stays hidden).
      await page.click('#swuGearBtn');
      const back = await page.waitForSelector('[data-swupgn="menu"]', { state: 'visible', timeout: 5000 }).catch(() => null);
      ok(tag, '★ the gear menu shows Return to Main Menu', back !== null);
      const covered = await page.evaluate(() => {
        const r = document.getElementById('swupgnViewerBar').getBoundingClientRect();
        const hit = document.elementFromPoint(r.left + r.width / 2, r.top + r.height / 2);
        return !!(hit && hit.closest('#swupgnViewerBar'));
      });
      ok(tag, '★ the open settings panel sits above the replay bar', !covered);
      if (back) {
        await back.click();
        await page.waitForURL(/MainMenu\.php/, { timeout: 15000, waitUntil: 'commit' }).catch(() => {});
        ok(tag, '★ Return to Main Menu leaves the replay for the main menu', /MainMenu\.php/.test(page.url()), page.url());
      }
    } catch (e) { ok(tag, 'viewer run completed', false, String(e).slice(0, 300)); }
    await ctx.close();
  }
  const ctx = await browser.newContext({ viewport: { width: 1600, height: 900 } });
  const page = await ctx.newPage();
  try {
    const p1 = await openViewer('viewer-game-p1.swupgn');
    await page.goto(BASE + rel(p1.viewUrl));
    await page.waitForSelector('#swupgnViewerBar', { timeout: 20000 });
    ok(name, 'a perspective file has no Hands toggle', await page.$('#swupgnHands') === null);
  } catch (e) { ok(name, 'perspective run completed', false, String(e).slice(0, 300)); }
  await ctx.close();
  // A file whose P2 leader and base SWUSim does not know, with a keyframe that disagrees with its events.
  const uctx = await browser.newContext({ viewport: { width: 1600, height: 900 } });
  const upage = await uctx.newPage();
  try {
    const text = fs.readFileSync(new URL('viewer-game.swupgn', FIX), 'utf8').replaceAll('SOR#005', 'ZZZ#005').replaceAll('SOR#028', 'ZZZ#028')
      .replace('"baseHp":25,"baseMaxHp":25', '"baseHp":24,"baseMaxHp":25');
    const u = await openViewerText(text);
    // A slow network: the viewer's info answer arrives after the board's first paint.
    await upage.route('**/SwuPgnViewer.php?action=info*', async (route) => { await new Promise((r) => setTimeout(r, 3000)); await route.continue(); });
    await upage.goto(BASE + rel(u.viewUrl));
    await upage.waitForSelector('#swupgnViewerBar', { timeout: 20000 });
    await waitStep(upage, 0);
    await upage.waitForTimeout(1500);
    const names = await phNames(upage);
    ok(name, '★ step 0: an unknown LEADER shows its name on first paint', names.includes('Luke Skywalker, Faithful Friend'), JSON.stringify(names));
    ok(name, '★ step 0: an unknown BASE shows its name on first paint', names.includes('Jedha City'), JSON.stringify(names));
    ok(name, '★ keyframe mismatch: the bar shows a warning count', (await upage.$eval('#swupgnInfo', (b) => b.textContent)).includes('⚠'));
  } catch (e) { ok(name, 'unknown-leader run completed', false, String(e).slice(0, 300)); }
  await uctx.close();
  await browser.close();
}

if (PART !== 'viewer') for (const [name, type] of ENGINES) {
  const browser = await type.launch();
  for (const layout of ['desktop', 'mobile']) {
    const tag = `${name}/menu-${layout}`;
    const ctx = await browser.newContext({ viewport: layout === 'mobile' ? { width: 390, height: 844 } : { width: 1600, height: 900 } });
    const page = await ctx.newPage();
    const errors = []; page.on('pageerror', (e) => errors.push(String(e)));
    const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
    try {
      // A game saved with "Save Replay" is listed, plays, and deletes.
      await page.goto(MENU);
      await seedSavedReplay(page);
      await page.goto(MENU);
      await page.click('#ga-info-tab-replays');
      const saved = await page.waitForSelector(`#swupgnReplayList li[data-saved-replay="SWUSim:harness:1"]`, { timeout: 8000 }).catch(() => null);
      ok(tag, '★ a game saved with Save Replay is listed', saved !== null && (await saved.textContent()).includes(SAVED.gameName), saved ? await saved.textContent() : 'no row');
      ok(tag, '★ with only a saved game, the empty note is not RENDERED', await page.$eval('#swupgnReplayEmpty', (e) => e.getBoundingClientRect().height === 0 || getComputedStyle(e).display === 'none'));
      if (saved) {
        await (await saved.$('[data-saved-play]')).click();
        await page.waitForURL(/NextTurn\.php.*replay=1/, { timeout: 20000, waitUntil: 'commit' }).catch(() => {});
        ok(tag, '★ Play opens the saved game in the replay player', /NextTurn\.php.*replay=1/.test(page.url()), page.url());
        await page.goto(MENU);
        await page.click('#ga-info-tab-replays');
        await page.evaluate(() => { window.StyledConfirm = async () => true; window.confirm = () => true; });
        await page.click('#swupgnReplayList li[data-saved-replay="SWUSim:harness:1"] [data-saved-delete]');
        await page.waitForFunction(() => !document.querySelector('#swupgnReplayList li[data-saved-replay="SWUSim:harness:1"]'), null, { timeout: 8000 }).catch(() => {});
        ok(tag, '★ Delete removes the saved game', await page.$('#swupgnReplayList li[data-saved-replay="SWUSim:harness:1"]') === null);
      }
      await page.goto(MENU);
      await page.click('#ga-info-tab-replays');
      await page.setInputFiles('#swupgnFileInput', new URL('viewer-game.swupgn', FIX).pathname);
      await page.waitForURL(/swupgnViewer=1/, { timeout: 20000, waitUntil: 'commit' });
      ok(tag, 'Open .swupgn opens the viewer', true);
      // A second file whose P1 leader name is hostile markup — opened from an in-memory buffer.
      const hostile = fs.readFileSync(new URL('viewer-game.swupgn', FIX), 'utf8')
        .replace('Darth Vader, Dark Lord of the Sith', '<img src=x onerror=window.__pwned=1>');
      await page.goto(MENU);
      await page.click('#ga-info-tab-replays');
      await page.setInputFiles('#swupgnFileInput', { name: 'hostile.swupgn', mimeType: 'text/plain', buffer: Buffer.from(hostile) });
      await page.waitForURL(/swupgnViewer=1/, { timeout: 20000, waitUntil: 'commit' });
      const hostileGame = new URL(page.url()).searchParams.get('gameName');
      await page.goto(MENU);
      await page.click('#ga-info-tab-replays');
      await page.waitForFunction(() => document.querySelectorAll('#swupgnReplayList li').length >= 2, null, { timeout: 10000 });
      const rows = await page.$$eval('#swupgnReplayList li', (lis) => lis.map((li) => ({ text: li.textContent, imgs: li.querySelectorAll('img').length })));
      ok(tag, 'opened files are listed with their leaders', rows.some((r) => r.text.includes('Darth Vader')));
      ok(tag, '★ a hostile name in a row is literal text, not an element', rows.some((r) => r.text.includes('<img src=x') && r.imgs === 0)
        && await page.evaluate(() => window.__pwned === undefined));
      ok(tag, 'the empty-state note is not RENDERED once rows exist', await page.$eval('#swupgnReplayEmpty', (e) => e.getBoundingClientRect().height === 0 || getComputedStyle(e).display === 'none'));
      const clipped = await page.$$eval('#swupgnReplayList li *', (els) => els.filter((e) => e.childElementCount === 0 && e.scrollWidth > e.clientWidth + 1).map((e) => e.textContent.slice(0, 40)));
      ok(tag, 'no row text is clipped', clipped.length === 0, JSON.stringify(clipped));
      ok(tag, 'no horizontal page scroll', await page.evaluate(() => document.documentElement.scrollWidth <= innerWidth + 1));
      await page.screenshot({ path: `/tmp/swupgn-replays-${name}-${layout}.png` });
      await page.click('#swupgnReplayList li [data-swupgn-play]');
      await page.waitForURL(/swupgnViewer=1/, { timeout: 20000, waitUntil: 'commit' });
      ok(tag, 'Play SWUPGN re-opens a stored file', true);
      ok(tag, '★ Play SWUPGN reuses the live viewer game (no new upload)', new URL(page.url()).searchParams.get('gameName') === hostileGame, `${hostileGame} → ${new URL(page.url()).searchParams.get('gameName')}`);
      await page.goto(MENU);
      await page.click('#ga-info-tab-replays');
      await page.waitForSelector('#swupgnReplayList li', { timeout: 10000 });
      await page.evaluate(() => { window.StyledConfirm = async () => true; window.confirm = () => true; });   // answer "yes" to the delete prompt
      const before = await page.$$eval('#swupgnReplayList li', (lis) => lis.length);
      await page.click('#swupgnReplayList li [data-swupgn-delete]');
      await page.waitForFunction((n) => document.querySelectorAll('#swupgnReplayList li').length === n - 1, before, { timeout: 10000 });
      ok(tag, 'Delete removes the row', true);
      // Leave this browser profile empty for the next layout run.
      await page.evaluate(() => new Promise((res) => { const r = indexedDB.deleteDatabase('petranaki-swupgn'); r.onsuccess = r.onerror = r.onblocked = () => res(); }));
      ok(tag, 'no page errors', errors.length === 0, errors.join(' | '));
    } catch (e) { ok(tag, 'menu run completed', false, String(e).slice(0, 300)); }
    await ctx.close();
  }
  await browser.close();
}

report();
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
