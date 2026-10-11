// A replay must not leave an answered OPTIONCHOOSE banner on screen.
//
// Reported 2026-09-17: replaying a Goldfish game, the "Choose a player to deal indirect damage — YOU / OPPONENT"
// banner stayed up after the replay had stepped past the answer, so it looked as if "Opponent" was never picked.
// The server replayed it correctly (the goldfish base took the damage); the CLIENT never hid the banner, because
// ClearSelectionMode() (Core/UILibraries*.js) swept every decision UI except OPTIONCHOOSE and TWOSIDEDSLIDER. A
// live game hides it on the answering click, so only a click-less replay exposed it.
//
// Fixture (fixtures/replay-optionchoose-goldfish.json): Goldfish, Trap Field (HMW_171) on P1's base, First Order
// Stormtrooper (JTL_132) in hand. Actions: play it -> YES (defeat Trap Field, 3 damage kills the trooper) ->
// "Opponent" for its When Defeated indirect damage.
//
// Usage: node replay-optionchoose-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const REPLAY = JSON.parse(fs.readFileSync(new URL('./fixtures/replay-optionchoose-goldfish.json', import.meta.url), 'utf8'));
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
const bannerVisible = (page) => page.evaluate(() => {
  const el = document.querySelector('.optchoose-banner');
  if (!el) return false;
  const r = el.getBoundingClientRect(); const cs = getComputedStyle(el);
  return cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 0 && r.height > 0;
});

async function importReplay() {
  const res = await fetch(BASE + 'APIs/MatchReplay.php?action=import', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ replay: REPLAY }),
  });
  const j = await res.json();
  if (!j.success) throw new Error('import failed: ' + JSON.stringify(j));
  return j;
}

async function step(page, imp) {
  // The same request the replay panel's "Next" button sends (mode 11101), then let the board poll the update.
  const url = new URL(BASE + 'ProcessInput.php');
  for (const [k, v] of Object.entries({ gameName: imp.gameName, playerID: '1', authKey: imp.authKey, folderPath: imp.rootName, mode: '11101', responseFormat: 'json' })) url.searchParams.set(k, v);
  const payload = await page.evaluate(async (u) => (await fetch(u)).json().catch(() => ({})), url.toString());
  await page.waitForTimeout(2500);
  return payload;
}

for (const [name, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const page = await browser.newPage();
    await page.setViewportSize({ width: 1400, height: 900 });
    const imp = await importReplay();
    await page.goto(BASE + imp.nextTurnUrl.replace(/^\.\//, ''), { waitUntil: 'load' });
    await page.waitForTimeout(2500);
    await step(page, imp);   // play First Order Stormtrooper -> Trap Field's YES/NO
    await step(page, imp);   // YES -> the trooper dies -> "Choose a player to deal indirect damage"
    ok(name, 'the You/Opponent banner is shown while that decision is pending', await bannerVisible(page));
    await step(page, imp);   // "Opponent" -> the decision is answered
    ok(name, 'after the replay steps past the answer, the banner is gone', !(await bannerVisible(page)));
    await page.screenshot({ path: `/tmp/replay-optionchoose-${name}.png` });
  } catch (e) {
    ok(name, 'engine ran', false, String(e).slice(0, 200));
  } finally {
    if (browser) await browser.close();
  }
}
for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
