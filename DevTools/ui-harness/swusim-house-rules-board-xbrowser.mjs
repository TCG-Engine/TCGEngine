// Cross-browser check for Twin Suns HOUSE RULES on the game board (2026-10-19).
// Spec: docs/superpowers/specs/2026-10-18-twinsuns-house-rules-design.md · by-eye checklist: SWUSim/Tests/Visual/HouseRules_Board.md
//
// Seeds two 4-seat Twin Suns games through TestSchemaSetup.php — one WITH house rules (Extra Base HP +10, Take Credit,
// Plan Scry 2) and a control WITHOUT — and checks, in chromium + firefox + webkit, desktop (1728) and phone layout (430,
// &swuLayout=mobile — GameLayoutMobile has its OWN copies of the buttons):
//   • the House Rules chip shows only with rules, and a click opens its list;
//   • the 4th counter button reads "Credit" and shows only with the rule;
//   • the Plan button's tooltip describes the scry.
// Screenshots: $SHOTS (default <tmpdir>/swusim-house-rules)/house-rules-board-<engine>-<layout>.png (taken with the list open). Read them.
//
// Usage: node swusim-house-rules-board-xbrowser.mjs   (ONLY=chromium,firefox to run a subset)
import { chromium, firefox, webkit } from 'playwright';
import { mkdirSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { tmpdir } from 'node:os';
import { dirname, join } from 'node:path';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const ONLY = (process.env.ONLY || '').split(',').map((s) => s.trim()).filter(Boolean);
const SHOTS = process.env.SHOTS || join(tmpdir(), 'swusim-house-rules');   // outside the repo
mkdirSync(SHOTS, { recursive: true });

const schema = (rules) => ['## GIVEN', 'CommonSetup4P: bbk/bbk/bbk/bbk', 'SkipPreGame: true', 'WithActivePlayer: 1',
  ...(rules ? ['WithHouseRules: ' + rules] : []), 'WithP1Hand: SOR_095', 'WithP1Deck: [SOR_095 SOR_128 SOR_095]',
  '## WHEN', '## EXPECT', 'SEATCOUNT:4'].join('\n');

async function seed(s) {
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ schema: s }).toString(),
  });
  const j = await r.json();
  if (j.error) throw new Error('TestSchemaSetup refused the schema: ' + j.error);
  return String(j.gameName);
}

let allOk = true;
const results = [];
const ok = (tag, name, cond, extra) => { if (!cond) allOk = false; results.push([tag, name, !!cond, extra]); };

const state = (page) => page.evaluate(() => {
  const vis = (el) => !!el && !el.hidden && el.getBoundingClientRect().width > 0;
  const chip = document.getElementById('swuHouseRulesChip');
  const pop = document.getElementById('swuHouseRulesPop');
  const fourth = document.getElementById('swuFourthBtn');
  const plan = document.getElementById('swuPlanBtn');
  return {
    chip: vis(chip), pop: vis(pop), popText: pop ? pop.textContent : '',
    // The chip's WRAPPER must take no space in a game without rules (it is a flex item in the round row).
    wrapShown: !!(chip && chip.parentElement && getComputedStyle(chip.parentElement).display !== 'none'),
    fourth: vis(fourth), fourthText: fourth ? fourth.textContent.trim() : '',
    planTitle: plan ? plan.title : '', hScroll: document.documentElement.scrollWidth > window.innerWidth + 1,
    // The list must be wholly visible. Hit-test its four corners: elementFromPoint honours clip-path (the desktop
    // sidebar is a chamfered clip-path polygon — it ate the first version's left edge, which neither a visibility
    // check nor an overflow walk can see) as well as overflow and anything painted on top.
    popInside: (() => {
      if (!vis(pop)) return null;
      const r = pop.getBoundingClientRect();
      return [[r.left + 2, r.top + 2], [r.right - 2, r.top + 2], [r.left + 2, r.bottom - 2], [r.right - 2, r.bottom - 2]]
        .every(([x, y]) => { const e = document.elementFromPoint(x, y); return !!e && (e === pop || pop.contains(e)); });
    })(),
  };
});

const withRules = await seed(schema('extraBaseHp=10,takeCredit,planScry=2'));
const control = await seed(schema(''));
// Seat 1 takes the Credit counter through its button, then seat 2 looks at the chip's list. (Not a WHEN step:
// TestSchemaSetup's WHEN path, seeded right after the other two, left this harness's earlier games serving stale state.)
const takeGames = {};
const ENGINES = Object.entries({ chromium, firefox, webkit }).filter(([n]) => !ONLY.length || ONLY.includes(n));
const LAYOUTS = [['desktop', { width: 1728, height: 1000 }, ''], ['mobile', { width: 430, height: 932 }, '&swuLayout=mobile']];

for (const [engine, driver] of ENGINES) {
  let browser;
  try {
    browser = await driver.launch();
    for (const [layout, viewport, q] of LAYOUTS) {
      const tag = `${engine}/${layout}`;
      const ctx = await browser.newContext({ viewport });
      const page = await ctx.newPage();
      const url = (g) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=1&authKey=testschema${q}`;

      await page.goto(url(withRules), { waitUntil: 'load' });
      await page.waitForTimeout(3000);
      let s = await state(page);
      ok(tag, 'rules: House Rules chip shown', s.chip);
      ok(tag, 'rules: the 4th counter button shows "Credit"', s.fourth && s.fourthText === 'Credit', JSON.stringify(s));
      ok(tag, 'rules: Plan tooltip describes the scry', /top 2 cards/.test(s.planTitle), s.planTitle);
      ok(tag, 'rules: the list is closed until clicked', !s.pop);
      await page.click('#swuHouseRulesChip');
      await page.waitForTimeout(300);
      s = await state(page);
      ok(tag, 'rules: clicking the chip opens the list', s.pop && /Extra Base HP \+10/.test(s.popText) && /Plan Scry 2/.test(s.popText), s.popText);
      ok(tag, 'rules: the list says the 4th counter is available', /Take Credit 4th Action[^]*available/.test(s.popText), s.popText);
      ok(tag, 'rules: no sideways scroll', !s.hScroll);
      ok(tag, 'rules: the list is wholly visible (not clipped)', s.popInside === true);
      await page.screenshot({ path: join(SHOTS, `house-rules-board-${engine}-${layout}.png`) });
      await page.mouse.click(5, viewport.height - 5);
      await page.waitForTimeout(300);
      ok(tag, 'rules: clicking elsewhere closes the list', !(await state(page)).pop);

      await page.goto(url(control), { waitUntil: 'load' });
      await page.waitForTimeout(3000);
      s = await state(page);
      ok(tag, 'control: no chip', !s.chip);
      ok(tag, 'control: the chip wrapper takes no space', !s.wrapShown);
      ok(tag, 'control: no 4th counter button', !s.fourth);
      ok(tag, 'control: Plan tooltip unchanged', /draw 1, bottom 1/.test(s.planTitle), s.planTitle);
      // Who holds the 4th counter is visible to everyone, not just its taker: seat 2's view after seat 1 took it.
      const taken = takeGames[tag] = await seed(schema('takeCredit'));
      await page.goto(url(taken), { waitUntil: 'load' });
      await page.waitForTimeout(3000);
      await page.click('#swuFourthBtn');
      await page.waitForTimeout(2500);
      await page.goto(url(taken).replace('playerID=1', 'playerID=2'), { waitUntil: 'load' });
      await page.waitForTimeout(3000);
      await page.click('#swuHouseRulesChip');
      await page.waitForTimeout(300);
      s = await state(page);
      ok(tag, 'taken: another seat sees "taken by P1"', s.pop && /taken by P1/.test(s.popText), s.popText);
      await ctx.close();
    }
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}

for (const [tag, name, pass, extra] of results) console.log(`${pass ? 'ok  ' : 'BAD '} [${tag}] ${name}${pass || !extra ? '' : '  ' + extra}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
