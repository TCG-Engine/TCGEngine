// End-to-end check for the SWUSim Bot Practice menu (SharedUI/Sites/SWUSim/MainMenu.php), 2026-09-14.
//
// What it protects:
//  • the dev menu OFFERS Bot Practice (it is 'enabled' => false in AppCore/SWU/Formats.php; MainMenu adds
//    it only when SWUIsLocalDevRequest(), owner ruling "enable only in dev env") — logged out too, like
//    Goldfish / Hotseat
//  • picking it reveals the bot deck link (relabelled, empty = mirror) and the Play Style select, shows
//    "Start Bot Practice", hides Join Queue / Create Private Room, and locks Match Type to Bo1
//  • switching to Hotseat / Open puts every field back the way it was (a control)
//  • the new fields stay inside the card at desktop and phone widths
//  • Start creates a real game, redirects to the board, and the BOT (seat 2) answers its own prompts —
//    every mode-10017 request is timed, because the heuristic stack's lookahead had only ever run headless
//
// Usage:
//   node botpractice-menu-xbrowser.mjs [baseURL]      # chromium + firefox + webkit (stock Playwright)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const OUT = process.env.OUT || '/tmp/botpractice-shots';
fs.mkdirSync(OUT, { recursive: true });

// The human's list: a real tournament fixture (comments stripped). Free text, so no external host is needed.
const FIXTURE = new URL('../../SWUSim/Tests/BotFixtures/meta-2026-09/aggro_vader_yellow.txt', import.meta.url);
const DECK = fs.readFileSync(FIXTURE, 'utf8').split('\n').filter(l => !l.startsWith('#')).join('\n').trim();

const ALL = { chromium, firefox, webkit };
// ENGINES=chromium,firefox to run a subset.
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

const visible = (page, sel) => page.$eval(sel, el => {
  const cs = getComputedStyle(el); const r = el.getBoundingClientRect();
  return cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 0 && r.height > 0;
}).catch(() => false);

async function menuChecks(engine, page, width) {
  await page.setViewportSize({ width, height: 900 });
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  const tag = `${width}px`;
  const opts = await page.$$eval('#swu-format-select option', os => os.map(o => o.value));
  ok(engine, `${tag}: logged-out dev menu offers Bot Practice`, opts.includes('botpractice'), opts.join(','));
  if (!opts.includes('botpractice')) return;

  await page.selectOption('#swu-format-select', 'botpractice');
  ok(engine, `${tag}: bot deck field shown`, await visible(page, '#swu-deck2-group'));
  ok(engine, `${tag}: bot deck label`, (await page.textContent('#swu-deck2-label')).trim() === 'Bot deck link:');
  ok(engine, `${tag}: empty = mirror placeholder`, /empty/i.test(await page.getAttribute('#swu-deck2-input', 'placeholder')));
  ok(engine, `${tag}: play style shown`, await visible(page, '#swu-botstyle-group'));
  const styles = await page.$$eval('#swu-botstyle-select option', os => os.map(o => o.value));
  ok(engine, `${tag}: styles are aggro/normal/control`, styles.join(',') === 'aggro,normal,control', styles.join(','));
  ok(engine, `${tag}: Normal is the default`, (await page.inputValue('#swu-botstyle-select')) === 'normal');
  ok(engine, `${tag}: Start Bot Practice shown`, await visible(page, '#start-solo-btn')
     && (await page.textContent('#start-solo-btn')).trim() === 'Start Bot Practice');
  ok(engine, `${tag}: Create Private Room hidden`, !(await visible(page, '#create-private-game-btn')));
  ok(engine, `${tag}: Join Queue hidden`, !(await visible(page, '#join-queue-btn')));
  ok(engine, `${tag}: Match Type locked to Bo1`, await page.$eval('#swu-queuetype-select', s => s.disabled && s.value === 'bo1'));
  // The new fields stay inside the card (no overflow at this width).
  const fits = await page.evaluate(() => {
    const card = document.querySelector('.swu-queue-card').getBoundingClientRect();
    return ['#swu-deck2-input', '#swu-botstyle-select'].every(s => {
      const r = document.querySelector(s).getBoundingClientRect();
      return r.left >= card.left - 1 && r.right <= card.right + 1;
    });
  });
  ok(engine, `${tag}: fields fit inside the card`, fits);
  await page.$eval('.swu-queue-card', el => el.scrollIntoView());
  await page.screenshot({ path: path.join(OUT, `${engine}-${width}-botpractice.png`), fullPage: false });

  // Controls: Hotseat restores its own label and hides the style; Open hides both.
  await page.selectOption('#swu-format-select', 'hotseat');
  ok(engine, `${tag}: hotseat label restored`, (await page.textContent('#swu-deck2-label')).trim() === 'Player 2 deck link (Hotseat):');
  ok(engine, `${tag}: hotseat hides play style`, !(await visible(page, '#swu-botstyle-group')));
  ok(engine, `${tag}: hotseat button label`, (await page.textContent('#start-solo-btn')).trim() === 'Start Hotseat Game');
  await page.selectOption('#swu-format-select', 'open');
  ok(engine, `${tag}: open hides deck 2 and style`, !(await visible(page, '#swu-deck2-group')) && !(await visible(page, '#swu-botstyle-group')));
  ok(engine, `${tag}: open shows no Start button`, !(await visible(page, '#start-solo-btn')));
}

async function gameCheck(engine, page) {
  await page.setViewportSize({ width: 1400, height: 900 });
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  await page.selectOption('#swu-format-select', 'botpractice');
  await page.evaluate(() => switchDeckTab('text'));
  await page.fill('#deck-text', DECK);
  await page.selectOption('#swu-botstyle-select', 'control');
  const steps = [];
  // SubmitEngineInput (Core/jsInclude.js) puts the mode in the URL: ProcessInput.php?…&mode=10017.
  page.on('request', r => { if (/ProcessInput\.php\?.*[?&]mode=10017\b/.test(r.url())) r._t0 = Date.now(); });
  page.on('response', async r => {
    const q = r.request();
    if (q._t0) {
      let body = {}; try { body = await r.json(); } catch (e) {}
      steps.push({ ms: Date.now() - q._t0, applied: body.botStepApplied === true, msg: body.message || '' });
    }
  });
  await page.click('#start-solo-btn');
  const reached = await page.waitForURL(/NextTurn\.php/, { timeout: 30000 }).then(() => true).catch(() => false);
  ok(engine, 'Start redirects to the game board', reached, page.url().slice(0, 120));
  if (!reached) { await page.screenshot({ path: path.join(OUT, `${engine}-start-failed.png`) }); return; }
  const gameName = new URL(page.url()).searchParams.get('gameName');
  // A passive human autopilot for seat 1, so the BOT gets to play: keep the opening hand, answer any
  // prompt with its first option (a multi-pick: the first selectable cards, then Confirm), otherwise
  // Pass. The bot's own turns run from the client's mode-10017 poller, as for a real player.
  const shown = async sel => page.locator(sel).first().isVisible().catch(() => false);
  const deadline = Date.now() + Number(process.env.PLAY_MS || 90000);
  while (Date.now() < deadline) {
    if (await shown('.yesno-decision-no')) {
      await page.locator('.yesno-decision-no').first().click().catch(() => {});
    } else if (await shown('#inline-multi-confirm')) {
      const sel = page.locator('.selectable-card');
      for (let i = 0; i < Math.min(2, await sel.count()); i++) await sel.nth(i).click().catch(() => {});
      await page.locator('#inline-multi-confirm').click().catch(() => {});
    } else if (await shown('.selectable-card[onclick^="OnSelectableCardClick"]')) {
      // A decision's pick only — never a free-play card. (A glowing hand card that cannot resolve, e.g.
      // an upgrade with no unit to attach to, logs a "played" line and stays in hand: clicking it again
      // and again stalled the human here, see the Bot Practice spec's ActivateCard residue.)
      await page.locator('.selectable-card[onclick^="OnSelectableCardClick"]').first().click().catch(() => {});
    } else if (await shown('#swuPassBtn')) {
      await page.locator('#swuPassBtn').click().catch(() => {});
    }
    await page.waitForTimeout(1500);
  }
  await page.screenshot({ path: path.join(OUT, `${engine}-board.png`) });
  const applied = steps.filter(s => s.applied);
  ok(engine, 'the bot made at least one move on its own', applied.length > 0, `${applied.length} applied of ${steps.length} bot requests`);
  if (steps.length) {
    const ms = steps.map(s => s.ms).sort((a, b) => a - b);
    ok(engine, 'bot requests answer within 5 s', ms[ms.length - 1] < 5000, `min ${ms[0]} / median ${ms[Math.floor(ms.length / 2)]} / max ${ms[ms.length - 1]} ms`);
  }
  results.push([engine, `game ${gameName}`, true, '']);
}

for (const [name, launcher] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await launcher.launch();
    const page = await browser.newPage();
    for (const w of [1400, 420]) await menuChecks(name, page, w);
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
