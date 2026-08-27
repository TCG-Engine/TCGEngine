#!/usr/bin/env node
// Matchup Breakout modal — cross-browser check for the DeckMetaStats drilldown.
//
//   node matchup-modal-xbrowser.mjs [--engine chromium|firefox|webkit] [--base http://localhost:3100]
//
// Covers two paths:
//   1. HAPPY  — click a "Matchups" button, assert the modal replaces "Loading..." with a real table.
//   2. FAILURE— stub the endpoint with a PHP-fatal-shaped HTML body (HTTP 200, JSON content type)
//               and assert the modal shows an error instead of spinning on "Loading..." forever.
//               That second case is the bug as reported; it is invisible if you only test the happy path.
import { chromium, firefox, webkit } from 'playwright';

const arg = (n, d) => { const i = process.argv.indexOf(n); return i > -1 ? process.argv[i + 1] : d; };
const ENGINE = arg('--engine', 'chromium');
const BASE = arg('--base', 'http://localhost:3100');
const URL = `${BASE}/TCGEngine/Stats/DeckMetaStats.php`;
const ENGINES = { chromium, firefox, webkit };

// macOS has no `timeout` binary and WebKit can hang past launch(), so watchdog the whole run.
setTimeout(() => { console.log(`FAIL [${ENGINE}] watchdog: run exceeded 120s`); process.exit(9); }, 120000).unref();

const results = [];
const check = (name, ok, note = '') => results.push({ name, ok, note });

const browser = await ENGINES[ENGINE].launch();
const page = await (await browser.newContext()).newPage();

async function openModal() {
  await page.locator('.drilldown-btn').first().click();
  await page.waitForSelector('#matchupModal', { state: 'visible' });
}

// ── 1. Happy path ──────────────────────────────────────────────────────────
await page.goto(URL, { waitUntil: 'domcontentloaded' });
await page.waitForSelector('.drilldown-btn', { timeout: 60000 });
await openModal();

let body = page.locator('#matchupModalBody');
let settled = true;
try {
  await page.waitForFunction(
    () => !/^\s*Loading\.\.\.\s*$/.test(document.getElementById('matchupModalBody').textContent),
    null, { timeout: 30000 });
} catch { settled = false; }

check('happy: modal leaves "Loading..."', settled,
      settled ? '' : 'still showing Loading... after 30s — this is the reported bug');
const rowCount = await body.locator('table tbody tr').count();
check('happy: renders a matchup table with rows', rowCount > 0, `rows=${rowCount}`);
check('happy: no error message', !/Could not load|Error loading/.test(await body.textContent()));

// ── 2. Failure path — endpoint returns a PHP fatal ─────────────────────────
await page.route('**/DeckMetaMatchupStatsAPI.php*', route => route.fulfill({
  status: 200,
  contentType: 'application/json',
  body: '<br /><b>Fatal error</b>: Uncaught Error: simulated<br />',
}));
await page.reload({ waitUntil: 'domcontentloaded' });
await page.waitForSelector('.drilldown-btn', { timeout: 60000 });
await openModal();

body = page.locator('#matchupModalBody');
let failSettled = true;
try {
  await page.waitForFunction(
    () => /Could not load matchup data/.test(document.getElementById('matchupModalBody').textContent),
    null, { timeout: 15000 });
} catch { failSettled = false; }
check('failure: shows an error instead of hanging', failSettled,
      failSettled ? '' : `body was: ${(await body.textContent()).slice(0, 80)}`);

await browser.close();

const failed = results.filter(r => !r.ok);
for (const r of results) console.log(`  ${r.ok ? 'ok  ' : 'FAIL'} ${r.name}${r.note ? ' — ' + r.note : ''}`);
console.log(failed.length ? `FAIL [${ENGINE}] ${failed.length}/${results.length}` : `PASS [${ENGINE}] ${results.length} checks`);
process.exit(failed.length ? 1 : 0);
