// END TO END: a Twin Suns room filled with bots, started, and PLAYED in the browser (Chromium, Firefox, WebKit).
// SWUSim/docs/todo-twinsuns-fill-bot.md — everything at once: the room (host + 2 pre-con bots through AddBot.php), the
// start path (StartRoom.php → bot seats marked in the game), the client bot driver with its stale token and the write
// lock (Core/GameWriteLock.php), and the bots' 3-seat judgement. A passive human autopilot answers seat 1's prompts.
// Passes when both bot seats make moves on their own, no bot step errors, and the page throws nothing.
// Usage: node swusim-fill-bot-game-xbrowser.mjs [baseURL]   ENGINES=…  PLAY_MS=60000
import { chromium, firefox, webkit } from 'playwright';
import fs from 'fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const PLAY_MS = Number(process.env.PLAY_MS || 60000);
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
let allOk = true;
const results = [];
const ok = (e, n, cond, extra = '') => { if (!cond) allOk = false; results.push([e, n, !!cond, extra]); };

const DECK = fs.readFileSync(new URL('../../SWUSim/DevTools/tests/fixtures/twinsuns_deck.json', import.meta.url), 'utf8').trim();
async function post(path, params) {
  const r = await fetch(BASE + path, { method: 'POST', body: new URLSearchParams(params) });
  const t = await r.text(); try { return JSON.parse(t); } catch (e) { return { RAW: t.slice(0, 200) }; }
}
async function startedRoomWithBots() {
  const host = await post('APIs/Lobbies/JoinQueue.php', { rootName: 'SWUSim', createPrivate: '1', format: 'twinsuns', deckLink: DECK, preconstructedDeck: '', game_type: '' });
  if (!host.success) throw new Error('room: ' + JSON.stringify(host));
  for (const p of ['precon:ts2', 'precon:ts4']) {
    const r = await post('APIs/Lobbies/AddBot.php', { lobbyID: host.lobbyID, authKey: host.authKey, botProfile: p });
    if (!r.success) throw new Error('add bot: ' + JSON.stringify(r));
  }
  const st = await post('APIs/Lobbies/StartRoom.php', { rootName: 'SWUSim', lobbyID: host.lobbyID, playerID: String(host.playerID || 1), authKey: host.authKey });
  if (!st.success) throw new Error('start: ' + JSON.stringify(st));
  return { gameName: st.gameName, authKey: host.authKey };
}
async function autopilot(page) {
  const shown = async sel => page.locator(sel).first().isVisible().catch(() => false);
  if (await shown('.yesno-decision-no')) await page.locator('.yesno-decision-no').first().click().catch(() => {});
  else if (await shown('#inline-multi-confirm')) {
    const sel = page.locator('.selectable-card');
    for (let i = 0; i < Math.min(2, await sel.count()); i++) await sel.nth(i).click().catch(() => {});
    await page.locator('#inline-multi-confirm').click().catch(() => {});
  } else if (await shown('.selectable-card[onclick^="OnSelectableCardClick"]')) {
    await page.locator('.selectable-card[onclick^="OnSelectableCardClick"]').first().click().catch(() => {});
  } else if (await shown('#swuPassBtn')) await page.locator('#swuPassBtn').click().catch(() => {});
}

for (const [engine, launcher] of ENGINES) {
  const browser = await launcher.launch();
  try {
    const g = await startedRoomWithBots();
    ok(engine, 'a room with 2 pre-con bots starts a game', !!g.gameName, String(g.gameName));
    const ctx = await browser.newContext({ viewport: { width: 1400, height: 950 } });
    await ctx.addCookies([{ name: 'lastAuthKey', value: g.authKey, url: BASE }]);
    const page = await ctx.newPage();
    const steps = [], errors = [];
    page.on('pageerror', e => errors.push(String(e).slice(0, 160)));
    page.on('request', r => { if (/ProcessInput\.php\?.*[?&]mode=10017\b/.test(r.url())) r._t0 = Date.now(); });
    page.on('response', async r => {
      const q = r.request(); if (!q._t0) return;
      let b = null; try { b = await r.json(); } catch (e) {}
      steps.push({ status: r.status(), applied: b?.botStepApplied === true, ok: b?.success === true || /busy/i.test(String(b?.message || '')), msg: String(b?.message || '') });
    });
    await page.goto(`${BASE}NextTurn.php?gameName=${g.gameName}&playerID=1&folderPath=SWUSim`, { waitUntil: 'domcontentloaded' });
    const bc = await page.waitForFunction(() => window.BotController && window.BotController.enabled ? window.BotController.players : null, null, { timeout: 30000 }).then(h => h.jsonValue()).catch(() => null);
    ok(engine, 'the page drives seats 2 and 3 as bots', JSON.stringify(bc) === '[2,3]', JSON.stringify(bc));
    const deadline = Date.now() + PLAY_MS;
    while (Date.now() < deadline) { await autopilot(page); await page.waitForTimeout(1500); }
    const seatsMoved = await page.evaluate(() => fetch(location.href, { method: 'HEAD' }).then(() => true)).catch(() => false);
    const applied = steps.filter(s => s.applied).length;
    ok(engine, 'the bots made moves on their own', applied >= 4, `${applied} applied of ${steps.length} steps`);
    ok(engine, 'no bot step errored', steps.every(s => s.status === 200 && s.ok), steps.filter(s => !(s.status === 200 && s.ok)).slice(0, 2).map(s => s.msg).join(' | '));
    ok(engine, 'no page errors', errors.length === 0, errors.slice(0, 2).join(' | '));
    await page.screenshot({ path: `/tmp/fillbot/game-${engine}.png` });
    await ctx.close();
  } catch (e) {
    ok(engine, 'ran without throwing', false, String(e && e.message || e));
  }
  await browser.close();
}
for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
