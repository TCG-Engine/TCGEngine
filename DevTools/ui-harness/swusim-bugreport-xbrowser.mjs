// SWUSim "Report a Bug" modal — Chromium + Firefox + WebKit (2026-10-22).
//
// Petranaki stores its own bug reports (docs/superpowers/specs/2026-10-22-petranaki-local-bug-reports-design.md).
// Two players said a report they sent never arrived, so for SWUSim the modal now:
//   · retries a network error / timeout / HTTP 5xx (4 attempts, 2s/5s/10s apart) with the SAME clientToken,
//     so a retry that reaches the server twice is one report; never retries a 400 or a 429;
//   · keeps the description as a draft in localStorage until the server confirms, so a refresh loses nothing;
//   · on success shows a receipt with the report number (P-<n>) and a Close button, instead of a 6s flash.
// The server is mocked with page.route for the failure cases; one last submit goes to the real endpoint and the
// report it creates is deleted afterwards.
//
// Usage: node swusim-bugreport-xbrowser.mjs [BASE]   (exits non-zero on failure)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const here = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/GameLog_UndoneAndDeckLines.md'), 'utf8');
const SUBMIT = '**/APIs/SubmitBugReport.php';

let allOk = true;
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; console.log(`${cond ? '  ok  ' : '  FAIL'} ${name} ${extra}`); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}
async function buildGame(page) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  return String(setup.gameName);
}
async function openGame(page, game, mobile) {
  await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema`
    + `&viewerPerspective=1&opponentID=2${mobile ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
  await page.waitForFunction(() => typeof window.SWUReportBug === 'function', null, { timeout: 15000 });
}
async function openModal(page) {
  await page.evaluate(() => window.SWUReportBug());
  await page.waitForSelector('#bugReportDescription', { timeout: 5000 });
}
// Mock the endpoint with a list of answers ('abort' or {status, json}); records every request body.
async function mock(page, answers) {
  const bodies = [];
  await page.unroute(SUBMIT).catch(() => {});
  await page.route(SUBMIT, async (route) => {
    bodies.push(JSON.parse(route.request().postData() || '{}'));
    const a = answers[Math.min(bodies.length - 1, answers.length - 1)];
    if (a === 'abort') return route.abort('failed');
    return route.fulfill({ status: a.status, contentType: 'application/json', body: JSON.stringify(a.json) });
  });
  return bodies;
}
const saved = (n) => ({ status: 200, json: { success: true, report_number: `P-${n}`, verified: true, duplicate: false, message: `Bug report P-${n} saved.` } });
const statusText = (page) => page.locator('#bugReportStatus').textContent().catch(() => '');
const draftKey = (game) => `tcg:bugReportDraft:SWUSim:${game}`;

for (const [engineName, engine] of Object.entries({ chromium, firefox, webkit })) {
  const browser = await engine.launch();
  try {
    const ctx = await browser.newContext({ viewport: { width: 1600, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    const game = await buildGame(page);
    await openGame(page, game, false);
    const t = (s) => `${engineName}: ${s}`;

    // ── two network failures, then the server answers ────────────────────────────────────────────
    let bodies = await mock(page, ['abort', 'abort', saved(9)]);
    await openModal(page);
    await page.fill('#bugReportDescription', 'retry case: shield popped early');
    await page.click('#submitBugReportButton');
    await page.waitForFunction(() => /retrying \(attempt 2 of 4\)/.test(document.getElementById('bugReportStatus')?.textContent || ''), null, { timeout: 8000 })
      .then(() => ok(t('retry: says it is retrying'), true)).catch(() => ok(t('retry: says it is retrying'), false, JSON.stringify(bodies.length)));
    await page.waitForSelector('#bugReportReceipt', { timeout: 30000 }).catch(() => {});
    const receipt = await page.locator('#bugReportReceipt').textContent().catch(() => '');
    ok(t('retry: two aborted attempts then 200 → receipt shows P-9'), /Bug report P-9 saved\./.test(receipt || ''), JSON.stringify(receipt));
    ok(t('retry: 3 requests, all with the same clientToken'), bodies.length === 3 && !!bodies[0].clientToken
      && bodies.every(b => b.clientToken === bodies[0].clientToken), JSON.stringify(bodies.map(b => b.clientToken)));
    ok(t('draft: removed after the 200'), await page.evaluate((k) => localStorage.getItem(k), draftKey(game)) === null);
    await page.click('#bugReportReceiptClose');
    ok(t('receipt: Close closes the modal'), await page.locator('#bugReportOverlay').count() === 0);

    // ── the server keeps failing ─────────────────────────────────────────────────────────────────
    bodies = await mock(page, [{ status: 503, json: { error: 'down' } }]);
    await openModal(page);
    await page.fill('#bugReportDescription', 'outage case');
    await page.click('#submitBugReportButton');
    await page.waitForFunction(() => /after 4 tries/.test(document.getElementById('bugReportStatus')?.textContent || ''), null, { timeout: 40000 }).catch(() => {});
    const failText = await statusText(page);
    ok(t('retry: four 503s → final failure copy'), /Couldn't send the report after 4 tries\. Your text is saved here/.test(failText || ''), JSON.stringify(failText));
    ok(t('retry: four 503s → exactly 4 requests, modal open, text intact'), bodies.length === 4
      && await page.locator('#bugReportOverlay').count() === 1 && await page.inputValue('#bugReportDescription') === 'outage case');

    // ── a refresh keeps the draft ────────────────────────────────────────────────────────────────
    await page.unroute(SUBMIT);
    await page.reload({ waitUntil: 'load' });
    await page.waitForFunction(() => typeof window.SWUReportBug === 'function', null, { timeout: 15000 });
    await openModal(page);
    ok(t('draft: type, reload, reopen → description restored'), await page.inputValue('#bugReportDescription') === 'outage case');

    // ── rate limited: no retry ───────────────────────────────────────────────────────────────────
    bodies = await mock(page, [{ status: 429, json: { error: 'Please wait 12 seconds before sending another report.', retry_after: 12 } }]);
    await page.click('#submitBugReportButton');
    await page.waitForFunction(() => /Please wait 12 seconds/.test(document.getElementById('bugReportStatus')?.textContent || ''), null, { timeout: 8000 }).catch(() => {});
    await page.waitForTimeout(3000); // a retry would have fired by now (first backoff is 2s)
    ok(t('429: one request only, server message shown'), bodies.length === 1 && /Please wait 12 seconds/.test(await statusText(page) || ''), String(bodies.length));

    // ── the real endpoint, on a phone-sized screen ───────────────────────────────────────────────
    await page.unroute(SUBMIT);
    await page.setViewportSize({ width: 375, height: 812 });
    await page.fill('#bugReportDescription', 'harness real submit (deleted after the run)');
    await page.click('#submitBugReportButton');
    await page.waitForSelector('#bugReportReceipt', { timeout: 30000 }).catch(() => {});
    const real = await page.locator('#bugReportReceipt').textContent().catch(() => '');
    const m = /Bug report (P-\d+) saved\./.exec(real || '');
    ok(t('real submit: receipt with a P- number'), !!m, JSON.stringify(real));
    const box = await page.locator('#bugReportReceiptClose').boundingBox().catch(() => null);
    ok(t('receipt: Close button visible and inside the viewport at 375px'), !!box && box.x >= 0 && box.x + box.width <= 375
      && box.y >= 0 && box.y + box.height <= 812, JSON.stringify(box));
    if (m) {
      execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', 'otmtcge-swusim-web-server-1', 'php', '-r',
        `require "./Database/ConnectionManager.php"; require "./LocalBugReportLib.php"; LocalBugReportDelete(GetLocalMySQLConnection(), ${parseInt(m[1].slice(2), 10)});`]);
    }
    await ctx.close();
  } catch (e) {
    ok(`${engineName}: harness ran`, false, e.message);
  } finally {
    await browser.close();
  }
}
console.log(allOk ? 'PASS' : 'FAIL');
process.exit(allOk ? 0 : 1);
