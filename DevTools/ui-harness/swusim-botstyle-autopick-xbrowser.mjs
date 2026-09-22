// Cross-browser check for the Arenabot "Bot play style" AUTO-PICK (spec 2026-09-22).
// Visual case: SWUSim/Tests/Visual/Menu_BotStyleAutoPick.md
//
// What it asserts, which a screenshot cannot: pasting a bot deck sets the select from APIs/SWUBotDeckStyle.php; a
// LATER deck load re-picks even over a manual change (the owner's choice: "always auto-pick on deck change"); and an
// unreadable deck leaves the select exactly as it was.
//
// Usage: node swusim-botstyle-autopick-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const FIX = new URL('../../SWUSim/Tests/BotFixtures/meta-2026-09/', import.meta.url);
const deck = (name) => readFileSync(new URL(name + '.txt', FIX), 'utf8');
const VADER = deck('aggro_vader_yellow');          // labelled hyperaggro
const KRENNIC = deck('control_krennic_splash');    // labelled softcontrol
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

// Type a deck into the bot-deck field and let the lookup settle.
async function enterBotDeck(page, text) {
  await page.evaluate((t) => {
    const el = document.getElementById('swu-deck2-input');
    el.value = t;
    el.dispatchEvent(new Event('change', { bubbles: true }));
  }, text);
  await page.waitForTimeout(1200);
  return page.evaluate(() => document.getElementById('swu-botstyle-select').value);
}

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });
    await page.goto(MENU, { waitUntil: 'load' });
    // Arenabot is the only format with a bot deck and a play style.
    await page.evaluate(() => {
      const f = document.getElementById('swu-format-select');
      f.value = 'botpractice';
      f.dispatchEvent(new Event('change', { bubbles: true }));
    });
    await page.waitForTimeout(300);
    const visible = await page.evaluate(() => {
      const g = document.getElementById('swu-botstyle-group');
      return !!g && getComputedStyle(g).display !== 'none';
    });
    ok(engine, 'Arenabot reveals the play-style field', visible);

    const first = await enterBotDeck(page, VADER);
    ok(engine, 'a stock Vader list picks Hyper Aggro', first === 'hyperaggro', first);

    // A manual change, then ANOTHER deck: the new deck wins (owner's ruling).
    await page.evaluate(() => {
      const s = document.getElementById('swu-botstyle-select');
      s.value = 'hardcontrol';
      s.dispatchEvent(new Event('change', { bubbles: true }));
    });
    const second = await enterBotDeck(page, KRENNIC);
    ok(engine, 'a new deck re-picks over a manual choice', second === 'softcontrol', second);

    // An unreadable deck must not move it.
    const third = await enterBotDeck(page, 'not a deck');
    ok(engine, 'an unreadable deck leaves the select alone', third === second, third);
    await page.close();
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}

for (const [engine, name, pass, extra] of results) {
  console.log(`${pass ? 'ok  ' : 'BAD '} [${engine}] ${name}${pass || !extra ? '' : '  ' + extra}`);
}
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
