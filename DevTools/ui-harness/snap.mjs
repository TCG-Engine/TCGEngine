#!/usr/bin/env node
// Cross-browser UI snapshot + measure harness. One command instead of a bespoke Playwright
// script per session. Renders a page (or a SWUDeck deck by gameName) in Chromium, Firefox, and
// WebKit, screenshots an element, and prints its box + key computed styles from each engine so
// layout divergences (the classic being height:100% not resolving through a flex-stretched parent
// in Firefox/WebKit) show up immediately.
//
// Setup (once):   cd DevTools/ui-harness && npm install   (postinstall pulls the 3 browser engines)
//
// Examples:
//   # A SWUDeck deck's identity banner across all 3 engines (logs in as Drixx automatically):
//   node snap.mjs --game 201009 --selector '#swuIdentityBanner' \
//       --measure '#myLeaderSlot img,#myBaseSlot img' --out /tmp/banner
//
//   # An arbitrary URL, one engine, no login:
//   node snap.mjs --url http://localhost:3100/TCGEngine/SharedUI/Sites/SWUDeck/MainMenu.php \
//       --engines chromium --login --selector '.swu-deck-stack-frame'
//
// Flags:
//   --game <id>          SWUDeck deck gameName → opens the deck editor; implies --login.
//   --url <url>          Arbitrary page URL (mutually exclusive with --game).
//   --engines a,b,c      Subset of chromium,firefox,webkit (default: all three).
//   --selector <css>     Element to screenshot + anchor measurements to (default: full page).
//   --measure <css,...>  Extra selectors to measure (box + display/object-fit/height). Repeatable via commas.
//   --out <prefix>       Screenshot path prefix; writes <prefix>-<engine>.png (default: /tmp/uisnap).
//   --login              Log in before navigating (auto-on with --game).
//   --user <name>        Login username (default: Drixx — see CLAUDE.md `## Creds`).
//   --pass <pw>          Login password (default: pass). Override for other test users.
//   --base <url>         Base TCGEngine URL (default: http://localhost:3100/TCGEngine).
//   --viewport WxH       Viewport size (default: 1600x950).
//   --dpr <n>            Device scale factor (default: 2).
//   --wait <ms>          Settle delay after navigation before measuring (default: 1500).

import { chromium, firefox, webkit } from 'playwright';

const ENGINES = { chromium, firefox, webkit };

function parseArgs(argv) {
  const a = {};
  for (let i = 0; i < argv.length; i++) {
    const k = argv[i];
    if (!k.startsWith('--')) continue;
    const name = k.slice(2);
    const next = argv[i + 1];
    if (next === undefined || next.startsWith('--')) { a[name] = true; }
    else { a[name] = next; i++; }
  }
  return a;
}

const args = parseArgs(process.argv.slice(2));
const BASE = (args.base || 'http://localhost:3100/TCGEngine').replace(/\/$/, '');
const engineNames = (args.engines ? String(args.engines).split(',') : ['chromium', 'firefox', 'webkit'])
  .map(s => s.trim()).filter(Boolean);
const selector = args.selector || null;
const measureSelectors = args.measure ? String(args.measure).split(',').map(s => s.trim()).filter(Boolean) : [];
const outPrefix = args.out || '/tmp/uisnap';
const doLogin = !!args.login || !!args.game;
const user = args.user || 'Drixx';
const pass = args.pass || 'pass';
const [vw, vh] = String(args.viewport || '1600x950').split('x').map(Number);
const dpr = Number(args.dpr || 2);
const settle = Number(args.wait || 1500);

for (const name of engineNames) {
  if (!ENGINES[name]) { console.error(`Unknown engine "${name}". Use: chromium, firefox, webkit.`); process.exit(2); }
}
if (args.game && args.url) { console.error('Pass --game OR --url, not both.'); process.exit(2); }

const targetUrl = args.game
  ? `${BASE}/NextTurn.php?gameName=${args.game}&playerID=1&folderPath=SWUDeck`
  : (args.url || `${BASE}/SharedUI/MainMenu.php`);

async function login(page) {
  await page.goto(`${BASE}/SharedUI/LoginPage.php`);
  await page.fill('input[name="userID"]', user);
  await page.fill('input[name="password"]', pass);
  await Promise.all([page.waitForNavigation(), page.click('button[type="submit"]')]);
}

async function run(name) {
  const browser = await ENGINES[name].launch();
  try {
    const context = await browser.newContext({ viewport: { width: vw, height: vh }, deviceScaleFactor: dpr });
    const page = await context.newPage();
    if (doLogin) await login(page);
    await page.goto(targetUrl);
    await page.waitForTimeout(settle);

    const anchor = selector ? await page.$(selector) : null;
    const outPath = `${outPrefix}-${name}.png`;
    if (anchor) await anchor.screenshot({ path: outPath });
    else await page.screenshot({ path: outPath, fullPage: true });

    const measured = await page.evaluate(({ sels, anchorSel }) => {
      const round = n => Math.round(n);
      const anchorEl = anchorSel ? document.querySelector(anchorSel) : null;
      const ar = anchorEl ? anchorEl.getBoundingClientRect() : { top: 0, left: 0 };
      const describe = el => {
        const r = el.getBoundingClientRect();
        const cs = getComputedStyle(el);
        return {
          topRel: round(r.top - ar.top), h: round(r.height), w: round(r.width),
          display: cs.display, objectFit: cs.objectFit, height: cs.height, position: cs.position,
        };
      };
      const out = {};
      for (const sel of sels) {
        const els = Array.from(document.querySelectorAll(sel));
        out[sel] = els.map(describe);
      }
      return out;
    }, { sels: measureSelectors, anchorSel: selector });

    return { engine: name, url: targetUrl, screenshot: outPath, measured };
  } finally {
    await browser.close();
  }
}

const results = [];
for (const name of engineNames) {
  try { results.push(await run(name)); }
  catch (e) { results.push({ engine: name, error: String(e && e.message || e) }); }
}
console.log(JSON.stringify(results, null, 2));

// Convenience: flag when a measured selector's height differs across engines (the usual smell).
if (measureSelectors.length && results.filter(r => r.measured).length > 1) {
  for (const sel of measureSelectors) {
    const heights = results.filter(r => r.measured).map(r => ({ engine: r.engine, h: (r.measured[sel] || []).map(m => m.h).join(',') }));
    const distinct = new Set(heights.map(x => x.h));
    if (distinct.size > 1) {
      console.error(`\n⚠  Cross-engine height mismatch for "${sel}": ` + heights.map(x => `${x.engine}=[${x.h}]`).join('  '));
    }
  }
}
