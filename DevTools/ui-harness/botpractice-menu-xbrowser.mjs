// End-to-end check for the SWUSim game-setup menu (SharedUI/Sites/SWUSim/MainMenu.php) — nested dropdowns:
// game type → opponent / players / mode → card pool. Spec: docs/superpowers/specs/2026-09-16-swusim-format-menu-design.md.
// (Rewritten 2026-09-16 from the Bot Practice single-dropdown check, owner-approved; Bot Practice is now "Arenabot".)
//
// What it protects:
//  • logged out = logged in (owner, 2026-09-21: no account needed to play): every game type, every PvP pool, Arenabot
//  • Arenabot is the FIRST Constructed opponent and the default for everyone: Constructed → Arenabot → Premier
//  • Arenabot reveals the bot deck link and Play Style, shows "Start Arenabot", hides Join Queue / Create Private Room,
//    locks Match Type to Bo1, and writes format botpractice + the chosen card pool to the hidden stored fields
//  • 1P Mode hides the card pool; Hotseat / Goldfish restore their own labels (controls)
//  • logged in: Twin Suns → Free-for-all / Teams, each with Standard and Preview; the whole family is Bo1 with no queue
//  • public queues (2026-09-16): Join Queue is shown for every PvP card pool and nowhere else
//  • enforcement: Arenabot → Premier refuses an SOR deck in the page and does not navigate
//  • invite lock: an anonymous visitor following a Premier invite sees Constructed → PvP → Premier, all three locked
//  • Start creates a real Arenabot game and the bot answers its own prompts
//
// Usage: node botpractice-menu-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const OUT = process.env.OUT || '/tmp/arenabot-menu-shots';
fs.mkdirSync(OUT, { recursive: true });
const readFixture = (rel) => fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/' + rel, import.meta.url), 'utf8')
  .split('\n').filter(l => !l.startsWith('#')).join('\n').trim();
const DECK = readFixture('meta-2026-09/vader_yellow.txt');   // Premier-legal (verified 2026-09-16)
const SOR_DECK = readFixture('premier_deck_a.txt');                 // NOT Premier-legal

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
const visible = (page, sel) => page.$eval(sel, el => {
  const cs = getComputedStyle(el); const r = el.getBoundingClientRect();
  return cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 0 && r.height > 0;
}).catch(() => false);
const values = (page, sel) => page.$$eval(sel + ' option', os => os.map(o => o.value));
const stored = (page) => page.evaluate(() => ({
  format: document.getElementById('swu-format-select').value,
  pool: document.getElementById('swu-cardpool-input').value,
}));
async function pick(page, gameType, second, pool) {
  await page.selectOption('#swu-gametype-select', gameType);
  await page.selectOption('#swu-second-select', second);
  if (pool) await page.selectOption('#swu-pool-select', pool);
}
async function login(page, user) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', user);
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

async function loggedOutChecks(engine, page, width) {
  await page.setViewportSize({ width, height: 900 });
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  const tag = `${width}px`;
  ok(engine, `${tag}: the stored format field is hidden`, !(await visible(page, '#swu-format-select')));
  ok(engine, `${tag}: logged out: the default is Constructed → Arenabot → Premier`, JSON.stringify(await stored(page)) === JSON.stringify({ format: 'botpractice', pool: 'premier' }), JSON.stringify(await stored(page)));
  ok(engine, `${tag}: logged out: every game type is offered`, (await values(page, '#swu-gametype-select')).join(',') === 'constructed,twinsuns,solo', (await values(page, '#swu-gametype-select')).join(','));
  ok(engine, `${tag}: second dropdown is labelled Opponent`, (await page.textContent('#swu-second-label')).trim() === 'Opponent:');
  const opponents = await values(page, '#swu-second-select');
  ok(engine, `${tag}: Arenabot is listed first, then PvP`, opponents.join(',') === 'arenabot,pvp', opponents.join(','));
  await pick(page, 'constructed', 'pvp');
  const pvpPools = await values(page, '#swu-pool-select');
  ok(engine, `${tag}: logged out: PvP offers every pool, Premier first`, pvpPools[0] === 'premier' && ['eternal', 'padawan', 'open'].every(p => pvpPools.includes(p)), pvpPools.join(','));
  if (!opponents.includes('arenabot')) return;

  await pick(page, 'constructed', 'arenabot', 'premier');
  const arenaPools = await values(page, '#swu-pool-select');
  ok(engine, `${tag}: Arenabot offers Premier, Eternal, Padawan and Open`, ['premier', 'eternal', 'padawan', 'open'].every(p => arenaPools.includes(p)), arenaPools.join(','));
  ok(engine, `${tag}: Arenabot stores botpractice + premier`, JSON.stringify(await stored(page)) === JSON.stringify({ format: 'botpractice', pool: 'premier' }));
  ok(engine, `${tag}: bot deck field shown`, await visible(page, '#swu-deck2-group'));
  ok(engine, `${tag}: bot deck label`, (await page.textContent('#swu-deck2-label')).trim() === 'Bot deck link:');
  ok(engine, `${tag}: empty = mirror placeholder`, /empty/i.test(await page.getAttribute('#swu-deck2-input', 'placeholder')));
  ok(engine, `${tag}: play style shown`, await visible(page, '#swu-botstyle-group'));
  ok(engine, `${tag}: styles are the five archetypes`,
     (await values(page, '#swu-botstyle-select')).join(',') === 'hyperaggro,softaggro,midrange,softcontrol,hardcontrol');
  ok(engine, `${tag}: Midrange is the default`, (await page.inputValue('#swu-botstyle-select')) === 'midrange');
  ok(engine, `${tag}: Start Arenabot shown`, await visible(page, '#start-solo-btn') && (await page.textContent('#start-solo-btn')).trim() === 'Start Arenabot');
  ok(engine, `${tag}: Create Private Room hidden`, !(await visible(page, '#create-private-game-btn')));
  ok(engine, `${tag}: Join Queue hidden`, !(await visible(page, '#join-queue-btn')));
  ok(engine, `${tag}: Match Type locked to Bo1`, await page.$eval('#swu-queuetype-select', s => s.disabled && s.value === 'bo1'));
  await page.selectOption('#swu-pool-select', 'eternal');
  ok(engine, `${tag}: changing the pool updates the stored pool`, (await stored(page)).pool === 'eternal');
  await page.selectOption('#swu-second-select', 'pvp');
  ok(engine, `${tag}: switching Arenabot → PvP keeps the chosen pool`, (await stored(page)).format === 'eternal', (await stored(page)).format);
  const fits = await page.evaluate(() => {
    const card = document.querySelector('.swu-queue-card').getBoundingClientRect();
    return ['#swu-gametype-select', '#swu-second-select', '#swu-pool-select', '#swu-deck2-input'].every(s => {
      const r = document.querySelector(s).getBoundingClientRect();
      return r.width === 0 || (r.left >= card.left - 1 && r.right <= card.right + 1);
    });
  });
  ok(engine, `${tag}: the dropdowns fit inside the card`, fits);
  await pick(page, 'constructed', 'arenabot', 'premier');
  await page.$eval('.swu-queue-card', el => el.scrollIntoView());
  await page.screenshot({ path: path.join(OUT, `${engine}-${width}-arenabot.png`) });

  // Controls: 1P Mode.
  await pick(page, 'solo', 'hotseat');
  ok(engine, `${tag}: 1P Mode hides the card pool`, !(await visible(page, '#swu-pool-group')));
  ok(engine, `${tag}: second dropdown is labelled Mode`, (await page.textContent('#swu-second-label')).trim() === 'Mode:');
  ok(engine, `${tag}: Hotseat stores hotseat + open`, JSON.stringify(await stored(page)) === JSON.stringify({ format: 'hotseat', pool: 'open' }));
  ok(engine, `${tag}: Hotseat label restored`, (await page.textContent('#swu-deck2-label')).trim() === 'Player 2 deck link (Hotseat):');
  ok(engine, `${tag}: Hotseat hides play style`, !(await visible(page, '#swu-botstyle-group')));
  ok(engine, `${tag}: Hotseat button label`, (await page.textContent('#start-solo-btn')).trim() === 'Start Hotseat Game');
  await page.selectOption('#swu-second-select', 'goldfish');
  ok(engine, `${tag}: Goldfish button label`, (await page.textContent('#start-solo-btn')).trim() === 'Start 1P Game');
  await pick(page, 'constructed', 'pvp', 'open');
  ok(engine, `${tag}: PvP Open hides deck 2, style and Start`, !(await visible(page, '#swu-deck2-group')) && !(await visible(page, '#swu-botstyle-group')) && !(await visible(page, '#start-solo-btn')));
  ok(engine, `${tag}: PvP Open shows Join Queue`, await visible(page, '#join-queue-btn'));
}

async function loggedInChecks(engine, browser) {
  const ctx = await browser.newContext();
  const page = await ctx.newPage();
  await page.setViewportSize({ width: 1400, height: 900 });
  await login(page, 'claudebot1');
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  ok(engine, 'logged in: the default is Constructed → Arenabot → Premier', JSON.stringify(await stored(page)) === JSON.stringify({ format: 'botpractice', pool: 'premier' }), JSON.stringify(await stored(page)));
  ok(engine, 'logged in: Twin Suns is offered', (await values(page, '#swu-gametype-select')).includes('twinsuns'));
  await pick(page, 'constructed', 'pvp', 'premier');   // the page opens on Arenabot, which locks Match Type to Bo1
  ok(engine, 'logged in: PvP Match Type is selectable', await page.$eval('#swu-queuetype-select', s => !s.disabled));
  for (const pool of ['premier', 'preview', 'eternal', 'eternal-preview', 'padawan', 'padawan-preview', 'open']) {
    await pick(page, 'constructed', 'pvp', pool);
    ok(engine, `PvP ${pool}: Join Queue shown`, await visible(page, '#join-queue-btn'));
  }
  await pick(page, 'twinsuns', 'ffa', 'twinsuns');
  ok(engine, 'Twin Suns Standard: no public queue', !(await visible(page, '#join-queue-btn')));
  await pick(page, 'twinsuns', 'ffa');
  ok(engine, 'Twin Suns → Players is labelled Players', (await page.textContent('#swu-second-label')).trim() === 'Players:');
  ok(engine, 'Twin Suns players are Free-for-all and Teams', (await values(page, '#swu-second-select')).join(',') === 'ffa,teams');
  ok(engine, 'Free-for-all pools are twinsuns and twinsuns-preview', (await values(page, '#swu-pool-select')).join(',') === 'twinsuns,twinsuns-preview');
  const labels = await page.$$eval('#swu-pool-select option', os => os.map(o => o.textContent.trim()));
  // Shape, not literal: every preview label now names the set it opens ("Preview (IC27)"), derived from
  // AppCore/SWU/PreviewSets.php, so a literal would break on the next preview rollover and again the day
  // the set releases. startsWith keeps the intent — Standard first, a Preview pool second — across both.
  ok(engine, 'Twin Suns pools are Standard and a Preview',
     labels.length === 2 && labels[0] === 'Standard' && labels[1].startsWith('Preview'), labels.join(','));
  await pick(page, 'twinsuns', 'teams', 'teamsuns-preview');
  ok(engine, 'Teams offers teamsuns and teamsuns-preview', (await values(page, '#swu-pool-select')).join(',') === 'teamsuns,teamsuns-preview');
  ok(engine, 'Teams Preview stores teamsuns-preview', (await stored(page)).format === 'teamsuns-preview');
  ok(engine, 'Team Suns Preview: Match Type locked to Bo1', await page.$eval('#swu-queuetype-select', s => s.disabled && s.value === 'bo1'));
  ok(engine, 'Team Suns Preview: no public queue', !(await visible(page, '#join-queue-btn')));
  ok(engine, 'Team Suns Preview: Create Private Room shown', await visible(page, '#create-private-game-btn'));
  await page.screenshot({ path: path.join(OUT, `${engine}-teamsuns-preview.png`) });

  // Invite lock: host a Premier room, then follow its invite as an ANONYMOUS visitor (whose own menu defaults to Arenabot).
  await pick(page, 'constructed', 'pvp', 'premier');
  await page.click('#tab-text');
  await page.fill('#deck-text', DECK);
  await Promise.all([page.waitForURL(/WaitingRoom\.php/, { timeout: 20000 }), page.click('#create-private-game-btn')]).catch(() => {});
  await page.waitForTimeout(1600);
  const invite = await page.evaluate(() => {
    const el = document.querySelector('#wr-invite, [data-invite]');
    const m = el ? (el.getAttribute('data-invite') || el.textContent || '').match(/[0-9a-f]{16,}/i) : null;
    return m ? m[0] : null;
  });
  ok(engine, 'invite: the host created a Premier room', !!invite, invite || page.url());
  if (invite) {
    const anon = await (await browser.newContext()).newPage();
    await anon.setViewportSize({ width: 1400, height: 900 });
    await anon.goto(BASE + 'SharedUI/MainMenu.php?privateInvite=' + encodeURIComponent(invite), { waitUntil: 'load' });
    await anon.waitForFunction(() => window.PrivateInviteUI && window.PrivateInviteUI.lobby, null, { timeout: 10000 }).catch(() => {});
    await anon.waitForTimeout(500);
    const path3 = await anon.evaluate(() => ['swu-gametype-select', 'swu-second-select', 'swu-pool-select'].map(id => document.getElementById(id).value).join('/'));
    ok(engine, 'invite: the dropdowns show the host’s Constructed/PvP/Premier', path3 === 'constructed/pvp/premier', path3);
    ok(engine, 'invite: all three dropdowns are locked', await anon.evaluate(() => ['swu-gametype-select', 'swu-second-select', 'swu-pool-select'].every(id => document.getElementById(id).disabled)));
    ok(engine, 'invite: Join Private Invite shown', await visible(anon, '#join-private-invite-btn'));
    await anon.screenshot({ path: path.join(OUT, `${engine}-invite-locked.png`) });
    await anon.context().close();
  }
  await ctx.close();
}

async function enforcementCheck(engine, page) {
  await page.setViewportSize({ width: 1400, height: 900 });
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  if (!(await values(page, '#swu-second-select')).includes('arenabot')) { ok(engine, 'enforcement: Arenabot offered', false); return; }
  await pick(page, 'constructed', 'arenabot', 'premier');
  await page.evaluate(() => switchDeckTab('text'));
  await page.fill('#deck-text', SOR_DECK);
  const before = page.url();
  await page.click('#start-solo-btn');
  await page.waitForTimeout(3000);
  const err = (await page.textContent('#queue-inline-error').catch(() => '')) || '';
  ok(engine, 'enforcement: Arenabot → Premier refuses an SOR deck in the page', /format error|not legal/i.test(err), err.slice(0, 160));
  ok(engine, 'enforcement: no game was started', page.url() === before);
}

async function gameCheck(engine, page) {
  await page.setViewportSize({ width: 1400, height: 900 });
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  await pick(page, 'constructed', 'arenabot', 'premier');
  await page.evaluate(() => switchDeckTab('text'));
  await page.fill('#deck-text', DECK);
  // hardcontrol, not the old 'control' alias: this drives a real game to watch a control bot take turns,
  // and hard control is the archetype whose conversion failure the archetype work targets (owner, 2026-09-18).
  await page.selectOption('#swu-botstyle-select', 'hardcontrol');
  const steps = [];
  page.on('request', r => { if (/ProcessInput\.php\?.*[?&]mode=10017\b/.test(r.url())) r._t0 = Date.now(); });
  page.on('response', async r => {
    const q = r.request();
    if (q._t0) { let body = {}; try { body = await r.json(); } catch (e) {} steps.push({ ms: Date.now() - q._t0, applied: body.botStepApplied === true }); }
  });
  await page.click('#start-solo-btn');
  const reached = await page.waitForURL(/NextTurn\.php/, { timeout: 30000 }).then(() => true).catch(() => false);
  ok(engine, 'Start Arenabot redirects to the game board', reached, page.url().slice(0, 120));
  if (!reached) { await page.screenshot({ path: path.join(OUT, `${engine}-start-failed.png`) }); return; }
  const shown = async sel => page.locator(sel).first().isVisible().catch(() => false);
  const deadline = Date.now() + Number(process.env.PLAY_MS || 90000);
  while (Date.now() < deadline) {
    if (await shown('.yesno-decision-no')) await page.locator('.yesno-decision-no').first().click().catch(() => {});
    else if (await shown('#inline-multi-confirm')) {
      const sel = page.locator('.selectable-card');
      for (let i = 0; i < Math.min(2, await sel.count()); i++) await sel.nth(i).click().catch(() => {});
      await page.locator('#inline-multi-confirm').click().catch(() => {});
    } else if (await shown('.selectable-card[onclick^="OnSelectableCardClick"]')) {
      await page.locator('.selectable-card[onclick^="OnSelectableCardClick"]').first().click().catch(() => {});
    } else if (await shown('#swuPassBtn')) await page.locator('#swuPassBtn').click().catch(() => {});
    await page.waitForTimeout(1500);
  }
  await page.screenshot({ path: path.join(OUT, `${engine}-board.png`) });
  ok(engine, 'the bot made at least one move on its own', steps.some(s => s.applied), `${steps.filter(s => s.applied).length} applied of ${steps.length}`);
}

for (const [name, launcher] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await launcher.launch();
    const page = await browser.newPage();
    for (const w of [1400, 420]) await loggedOutChecks(name, page, w);
    await enforcementCheck(name, page);
    await loggedInChecks(name, browser);
    await gameCheck(name, page);
  } catch (e) {
    ok(name, 'engine ran', false, String(e).slice(0, 200));
  } finally {
    if (browser) await browser.close();
  }
}
for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
console.log('screenshots: ' + OUT);
process.exit(allOk ? 0 : 1);
