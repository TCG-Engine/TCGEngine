// SWUSim Smuggle-refill slide — Chromium + Firefox + WebKit (2026-09-11, SSOT #3).
//
// Why this exists: SSOT #3 routed the Smuggle / Plot / Hunter / Frontier Trader / Bail Organa / Citadel
// resource refills through SWUResourceTopOfDeck, which queues the deck → resource-zone slide the ramp cards
// already had. The schema suite stubs every animation, and so does the Test Schema Editor's STEP endpoint —
// so this builds the board from SWUSim/Tests/Visual/ResourceTopOfDeck_SmuggleRefillSlides.md (GIVEN only),
// then performs the Smuggle through the page's OWN input path (SubmitInput — what the resource click calls),
// which runs the real request with real animations. It asserts:
//   · a response carries a ZONE_MOVE from P1's DECK to P1's RESOURCES (the refill), scoped to seat 1;
//   · the client builds a .tcg-zone-move-clone (a card actually flies);
//   · the refill's log line is on the page.
// Usage: node swusim-refill-slide-xbrowser.mjs [BASE]
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const here = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/ResourceTopOfDeck_SmuggleRefillSlides.md'), 'utf8');

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

for (const [engineName, engine] of Object.entries({ chromium, firefox, webkit })) {
  const browser = await engine.launch();
  const tag = engineName;
  try {
    const ctx = await browser.newContext({ viewport: { width: 1600, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
    if (setup.error) { ok(`${tag}: board built`, false, setup.error); continue; }
    await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${setup.gameName}&playerID=1&authKey=testschema`
      + '&viewerPerspective=1&opponentID=2', { waitUntil: 'load' });
    await page.waitForTimeout(3000);

    // Every response body after the click, and every flying clone the client creates.
    const bodies = [];
    page.on('response', async (r) => { try { bodies.push(await r.text()); } catch {} });
    await page.evaluate(() => {
      window.__clones = [];
      new MutationObserver((recs) => {
        for (const rec of recs) for (const n of rec.addedNodes) {
          if (n.classList && n.classList.contains('tcg-zone-move-clone')) window.__clones.push(Date.now());
        }
      }).observe(document.body, { childList: true, subtree: true });
      SubmitInput('10001', '&cardID=' + encodeURIComponent('myResources-0!CustomInput!Smuggle'));
    });
    await page.waitForTimeout(4000);

    const all = bodies.join('\n');
    // A ZONE_MOVE whose source is a DECK slot and destination a RESOURCES slot (absolute ids name the seat).
    const zm = all.match(/"type"\s*:\s*"ZONE_MOVE"[^}]*?"source"\s*:\s*"([^"]*Deck[^"]*)"[^}]*?"destination"\s*:\s*"([^"]*Resources[^"]*)"[^}]*/);
    ok(`${tag}: a deck → resources ZONE_MOVE is sent`, !!zm, zm ? `${zm[1]} → ${zm[2]}` : 'none in the responses');
    ok(`${tag}: it is scoped to seat 1`, !!zm && /"onlySeat"\s*:\s*1/.test(zm[0]), zm ? (zm[0].match(/"onlySeat"\s*:\s*\d+/) || ['no onlySeat'])[0] : '');
    const clones = await page.evaluate(() => window.__clones.length);
    ok(`${tag}: the client flies a card (.tcg-zone-move-clone)`, clones > 0, `${clones} clone(s)`);
    const board = await page.evaluate(() => ({
      log: Array.from(document.querySelectorAll('.swu-log-entry')).map(e => e.textContent.trim()),
    }));
    ok(`${tag}: refill logged`, board.log.some(l => /resourced the top card of their deck \(Smuggle slot refill\)/.test(l)),
      board.log.filter(l => /resourced/.test(l)).join(' | ') || 'no line');
    await ctx.close();
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nSOME CHECKS FAILED');
process.exit(allOk ? 0 : 1);
