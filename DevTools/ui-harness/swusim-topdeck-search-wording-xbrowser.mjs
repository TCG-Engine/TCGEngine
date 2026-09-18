// The TOPDECKSEARCH panel must say what the CARD says — in all three engines.
//
// Reported 2026-09-18: ASH_110 Admiral Ackbar ("search for any number of SPACE units with combined cost 5
// or less") opened a panel reading "Select Villainy units (combined cost ≤ 5)". The panel is shared by ~65
// callers and its filter is a PHP closure that cannot cross the request boundary, so the wording had been
// hardcoded to its first caller of the day, SOR_087 Darth Vader. It is now a REQUIRED argument carried as
// decision-param segments 4 (label) and 5 (verb).
//
// This drives the REAL shipped function out of the newest Core/UILibraries<date>.js: the file is read from
// disk, the ShowTopDeckSearchPanel source is sliced out and evaluated in the page, and the panel is opened
// with the exact params the server now builds for each caller. Then the rendered subtitle and confirm
// button are read back out of the DOM. No game or login needed — the panel is self-contained, which is
// what makes a three-engine pass cheap enough to actually run.
//
// Usage: node swusim-topdeck-search-wording-xbrowser.mjs   ENGINES=chromium,firefox,webkit (default: all)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
import path from 'node:path';
import { fileURLToPath } from 'node:url';

const REPO = path.resolve(path.dirname(fileURLToPath(import.meta.url)), '../..');
const bundles = fs.readdirSync(path.join(REPO, 'Core'))
  .filter(f => /^UILibraries\d+\.js$/.test(f)).sort().reverse();
if (!bundles.length) { console.error('no Core/UILibraries<date>.js found'); process.exit(1); }
const BUNDLE = bundles[0];
const src = fs.readFileSync(path.join(REPO, 'Core', BUNDLE), 'utf8');

const start = src.indexOf('function ShowTopDeckSearchPanel(');
if (start < 0) { console.error('ShowTopDeckSearchPanel not found in ' + BUNDLE); process.exit(1); }
const end = src.indexOf('\nfunction ', start + 10);
const PANEL_SRC = src.slice(start, end < 0 ? src.length : end);

// Real params, in the shape _topDeckSearchBegin now emits: allIDs|matchIDs|constraint|costMap|label|verb.
// One per wording shape rather than per caller: a cost budget, a cost budget with a pick cap, a plain
// count, and a non-"Take" verb (the case a fixed button label used to get wrong).
const CASES = [
  { card: 'ASH_110 Admiral Ackbar',      param: 'SOR_225,SOR_237,SOR_046|SOR_225,SOR_237|cost:5|SOR_225:2,SOR_237:2,SOR_046:3|space_units|Play',
    subtitle: 'Select space units (combined cost ≤ 5). Used: 0/5',                  button: 'Play None' },
  { card: 'SOR_087 Darth Vader',         param: 'SOR_225,SOR_237|SOR_225|cost:3|SOR_225:2,SOR_237:2|Villainy_units|Play',
    subtitle: 'Select Villainy units (combined cost ≤ 3). Used: 0/3',               button: 'Play None' },
  { card: 'SOR_104 U-Wing Reinforcement', param: 'SOR_225,SOR_237|SOR_225,SOR_237|cost:7:3|SOR_225:2,SOR_237:2|units|Play',
    subtitle: 'Select units (up to 3, combined cost ≤ 7). Used: 0/7',               button: 'Play None' },
  { card: 'HMW_265 Twi\'lek Kalikori',   param: 'SOR_225|SOR_225|cost:5|SOR_225:2|Twi\'lek_units|Play',
    subtitle: 'Select Twi\'lek units (combined cost ≤ 5). Used: 0/5',               button: 'Play None' },
  { card: 'SOR_123 Recruit',             param: 'SOR_225,SOR_237|SOR_225|count:1|SOR_225:2,SOR_237:2|units|Take',
    subtitle: 'Select units (up to 1). Selected: 0/1',                              button: 'Take None' },
  { card: 'LOF_117 Sifo-Dyas (discards)', param: 'SOR_225,SOR_237|SOR_225|cost:4|SOR_225:2,SOR_237:2|Clone_units|Discard',
    subtitle: 'Select Clone units (combined cost ≤ 4). Used: 0/4',                  button: 'Discard None' },
];

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const results = [];
let allOk = true;
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

for (const [name, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const page = await browser.newPage();
    await page.setViewportSize({ width: 1200, height: 800 });
    await page.setContent('<!doctype html><html><body></body></html>');
    // Art is irrelevant here and every src would 404 offline; stub the resolver the panel calls so a
    // broken image can never be mistaken for a broken assertion.
    await page.evaluate(([panelSrc]) => {
      window.resolveCardImageID = (id) => id;
      window.assetImageFolder = 'about:blank#';
      // eslint-disable-next-line no-eval
      (0, eval)(panelSrc);
    }, [PANEL_SRC]);

    for (const c of CASES) {
      const got = await page.evaluate(([param]) => {
        const old = document.getElementById('topdecksearch-panel');
        if (old) old.remove();
        window.ShowTopDeckSearchPanel({ Param: param }, 0, () => {});
        const panel = document.getElementById('topdecksearch-panel');
        if (!panel) return null;
        const divs = panel.querySelectorAll('div > div > div');
        return {
          subtitle: divs[1] ? divs[1].textContent : '(no subtitle)',
          button: panel.querySelector('button') ? panel.querySelector('button').textContent : '(no button)',
        };
      }, [c.param]);
      if (!got) { ok(name, `${c.card}: panel opens`, false); continue; }
      ok(name, `${c.card}: subtitle`, got.subtitle === c.subtitle, got.subtitle === c.subtitle ? '' : `got "${got.subtitle}" want "${c.subtitle}"`);
      ok(name, `${c.card}: button`, got.button === c.button, got.button === c.button ? '' : `got "${got.button}" want "${c.button}"`);
    }
    // The bug in one line: no caller's panel may mention another caller's filter.
    const leaked = await page.evaluate(([param]) => {
      const old = document.getElementById('topdecksearch-panel');
      if (old) old.remove();
      window.ShowTopDeckSearchPanel({ Param: param }, 0, () => {});
      return document.getElementById('topdecksearch-panel').textContent;
    }, [CASES[0].param]);
    ok(name, 'Ackbar\'s panel never says "Villainy"', !/Villainy/i.test(leaked));
  } catch (e) {
    ok(name, 'engine ran', false, String(e && e.message || e));
  } finally { if (browser) await browser.close(); }
}

console.log(`\nTOPDECKSEARCH panel wording — ${BUNDLE}\n`);
let lastEngine = '';
for (const [engine, name, pass, extra] of results) {
  if (engine !== lastEngine) { console.log(`── ${engine}`); lastEngine = engine; }
  console.log(`  ${pass ? '✓' : '✗'} ${name}${extra ? '  — ' + extra : ''}`);
}
const passed = results.filter(r => r[2]).length;
console.log(`\n${passed}/${results.length} checks passed across ${ENGINES.map(e => e[0]).join(', ')}`);
process.exit(allOk ? 0 : 1);
