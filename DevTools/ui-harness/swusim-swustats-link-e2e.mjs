// Petranaki ↔ SWUStats, end to end on the local stack (docs/superpowers/specs/2026-10-10-petranaki-swustats-link-design.md).
// claudebot1 (Petranaki :3400) connects to Drixx (SWUStats, reached at 127.0.0.1:3100 — NOT localhost, so the two sites'
// session cookies stay apart), sees Drixx's hearted decks in the PvP picker, and disconnects at the end unless KEEP_LINK=1.
// Chromium only: this proves the server flow; the UI is covered cross-browser by the two -xbrowser harnesses.
// Needs: the dev OAuth client `petranaki-dev` registered in the local swudeck DB, and Drixx's decks 104 + 107 hearted.
import { chromium } from 'playwright';

const P = 'http://localhost:3400/TCGEngine/';
const S = 'http://127.0.0.1:3100/TCGEngine/';
const SHOT = process.env.SHOT || '/tmp/swustats-e2e-menu.png';
let allOk = true;
const ok = (n, cond, extra = '') => { if (!cond) allOk = false; console.log(`${cond ? 'PASS' : 'FAIL'}  ${n}${extra ? '  — ' + extra : ''}`); };

const browser = await chromium.launch();
const ctx = await browser.newContext({ viewport: { width: 1440, height: 1000 } });
const page = await ctx.newPage();
try {
  // sign in to SWUStats as Drixx first (its own cookie jar: 127.0.0.1)
  await page.goto(S + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'Drixx');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);

  // sign in to Petranaki as claudebot1
  await page.goto(P + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);

  // Profile → Connect → SWUStats consent → back to Profile
  await page.goto(P + 'SharedUI/Sites/SWUSim/Profile.php', { waitUntil: 'load' });
  ok('Profile offers Connect SWUStats', await page.locator('text=Connect SWUStats').count() === 1);
  await Promise.all([page.waitForURL(/127\.0\.0\.1:3100\/TCGEngine\/APIs\/OAuth\/authorize\.php/), page.click('text=Connect SWUStats')]);
  await Promise.all([page.waitForURL(/localhost:3400\/TCGEngine\/SharedUI\/Sites\/SWUSim\/Profile\.php/, { timeout: 20000 }), page.click('button[name="approve"]')]);
  const err = await page.locator('.swustats-link__err').textContent({ timeout: 1000 }).catch(() => '');
  ok('no flow error', !err, err || '');
  ok('Profile says Connected as Drixx', await page.locator('.swustats-link >> text=Connected as').count() === 1
    && (await page.locator('.swustats-link b').textContent()) === 'Drixx');
  ok('still signed in to Petranaki after the round trip', await page.locator('text=Disconnect SWUStats').count() === 1);

  // the endpoint, for real
  const res = await page.evaluate(async (p) => (await fetch(p + 'SWUSim/SWUStatsDecks.php?refresh=1', { credentials: 'same-origin' })).json(), P);
  const keys = (res.decks || []).map((d) => d.key);
  ok('SWUStatsDecks: ok with the two hearted decks', res.status === 'ok' && keys.includes('ss104') && keys.includes('ss107'), JSON.stringify(res).slice(0, 300));

  // the picker, for real
  await page.goto(P + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  await page.waitForTimeout(1500);
  const opts = await page.evaluate(() => [...(document.getElementById('pvp-saved-ss') || { options: [] }).options].map((o) => o.value));
  ok('PvP SWUStats list shows the hearted decks', opts.includes('ss104') && opts.includes('ss107'), opts.join(','));
  await page.evaluate(() => document.getElementById('setup-pvp').showModal());
  await page.waitForTimeout(500);
  await page.screenshot({ path: SHOT });
} finally {
  if (process.env.KEEP_LINK !== '1') {
    await page.goto(P + 'SharedUI/Sites/SWUSim/Profile.php', { waitUntil: 'load' }).catch(() => {});
    await page.click('text=Disconnect SWUStats').catch(() => {});
    await page.waitForLoadState('load').catch(() => {});
    ok('Disconnect returns the Connect button', await page.locator('text=Connect SWUStats').count() === 1);
  }
  await browser.close();
}
console.log(allOk ? 'ALL PASS' : 'SOME FAILED');
process.exit(allOk ? 0 : 1);
