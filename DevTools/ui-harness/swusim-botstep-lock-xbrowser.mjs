// Live check for the per-game WRITE LOCK and the bot step's STALE TOKEN (Core/GameWriteLock.php).
//
// Every human browser in a game steps the bot (mode 10017). The client now sends the update it rendered
// (lastUpdate); a step whose token is behind the game is refused as stale instead of making a second,
// unpaced move. This drives a real Arenabot game in each engine:
//   • ONE tab: every step carries a token, none is stale, and the bot plays.
//   • TWO tabs on the same game (both stepping the bot): the bot still plays, the server never errors,
//     and every refused step is a STALE one (the second browser was behind), never a crash.
// The server half is pinned by DevTools/tdd-regression/test_core_game_write_lock.php.
//
// Usage: node swusim-botstep-lock-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit  PLAY_MS=45000
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const PLAY_MS = Number(process.env.PLAY_MS || 45000);
const fs = await import('node:fs');
const readFixture = (rel) => fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/' + rel, import.meta.url), 'utf8')
  .split('\n').filter(l => !l.startsWith('#')).join('\n').trim();
const DECK_A = readFixture('premier_deck_a.txt');
const DECK_B = readFixture('premier_deck_b.txt');

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

async function createArenabotGame(request) {
  const r = await request.post(BASE + 'APIs/Lobbies/JoinQueue.php', {
    form: { rootName: 'SWUSim', format: 'botpractice', queueType: 'bo1',
            botStyle: 'hyperaggro', cardPool: 'open', deckLink: DECK_A, deckLink2: DECK_B },
  });
  return await r.json();
}

function watchSteps(page, steps, errors) {
  page.on('request', r => { if (/ProcessInput\.php\?.*[?&]mode=10017\b/.test(r.url())) r._t0 = Date.now(); });
  page.on('response', async r => {
    const q = r.request();
    if (!q._t0) return;
    let body = null; try { body = await r.json(); } catch (e) {}
    steps.push({
      ms: Date.now() - q._t0, status: r.status(), json: body !== null,
      applied: body?.botStepApplied === true, stale: body?.botStepStale === true,
      success: body?.success === true, message: String(body?.message || ''),
      token: /[?&]lastUpdate=\d+/.test(q.url()),
    });
  });
  page.on('pageerror', e => errors.push(String(e).slice(0, 160)));
}

// Passive human: answer whatever the board asks with the cheapest choice, so the game keeps moving.
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

async function run(engine, browser, tabs) {
  const ctx = await browser.newContext();
  const g = await createArenabotGame(ctx.request);
  ok(engine, `${tabs} tab(s): created an Arenabot game`, !!g.success && !!g.gameName, JSON.stringify(g.message || ''));
  if (!g.gameName) { await ctx.close(); return; }
  await ctx.addCookies([{ name: 'lastAuthKey', value: g.authKey, url: BASE }]);
  const steps = [], errors = [], pages = [];
  for (let i = 0; i < tabs; i++) {
    const page = await ctx.newPage();
    watchSteps(page, steps, errors);
    await page.goto(`${BASE}NextTurn.php?gameName=${g.gameName}&playerID=1&folderPath=SWUSim`, { waitUntil: 'domcontentloaded' });
    pages.push(page);
  }
  const deadline = Date.now() + PLAY_MS;
  while (Date.now() < deadline) {
    await autopilot(pages[0]);           // only the first tab plays the human; every tab steps the bot
    await pages[0].waitForTimeout(1500);
  }
  const applied = steps.filter(s => s.applied).length;
  const stale = steps.filter(s => s.stale).length;
  const bad = steps.filter(s => s.status !== 200 || !s.json || (!s.success && !/busy/i.test(s.message)));
  const summary = `${steps.length} steps, ${applied} applied, ${stale} stale, median ${steps.map(s => s.ms).sort((a, b) => a - b)[Math.floor(steps.length / 2)] ?? '-'}ms`;
  ok(engine, `${tabs} tab(s): the bot made moves on its own`, applied > 0, summary);
  ok(engine, `${tabs} tab(s): every step sends its lastUpdate token`, steps.length > 0 && steps.every(s => s.token), `${steps.filter(s => s.token).length}/${steps.length}`);
  ok(engine, `${tabs} tab(s): no step errored`, bad.length === 0, bad.slice(0, 3).map(s => `${s.status} ${s.message}`).join(' | '));
  ok(engine, `${tabs} tab(s): no page errors`, errors.length === 0, errors.slice(0, 2).join(' | '));
  if (tabs === 1) ok(engine, '1 tab: no step refused as stale', stale === 0, `${stale} stale`);
  await ctx.close();
}

for (const [engine, driver] of Object.entries(ENGINES)) {
  const browser = await driver.launch();
  try {
    await run(engine, browser, 1);
    await run(engine, browser, 2);
  } catch (e) {
    ok(engine, 'engine ran', false, String(e).slice(0, 200));
  }
  await browser.close();
}

for (const [engine, name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${engine.padEnd(8)} ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
