// Profile → Change Password must work — Chromium + Firefox + WebKit.
//
// Reported 2026-10-25 on swustats.net: "request keeps failing when i try to change my password from SWUStats profile page".
// The page script called a HARDCODED https://www.swustats.net/… while the player was on swustats.net: cross-origin, so the
// login cookie was not sent and the browser blocked the reply → "Request failed." It also sent the new password in a GET
// query string (access logs, history). Fix: root-relative POST (SharedUI/Render/Profile.php → APIs/ResetPassword.php).
//
// Checks: the change succeeds and says so; the request is a same-origin POST with no password in the URL; the account still
// logs in with the password afterwards. The test account's password is changed TO ITS CURRENT VALUE, so it stays usable.
//
// Usage: node swudeck-profile-password-xbrowser.mjs [BASE]   ENGINES=chromium,firefox,webkit
//   BASE default http://localhost:3100/TCGEngine/  (SWUDeck dev). Credentials: Drixx / pass (CLAUDE.md test account).
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3100/TCGEngine/';
const USER = process.env.USER_ID || 'Drixx';
const PASS = process.env.USER_PASS || 'pass';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', USER);
  await page.fill('input[name="password"]', PASS);
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    await page.goto(BASE + 'SharedUI/Profile.php', { waitUntil: 'load' });
    const hasForm = await page.locator('#selfResetPasswordBtn').count();
    ok(`${engineName}: the profile page shows Change Password`, hasForm === 1);
    if (!hasForm) { await ctx.close(); continue; }

    const reqs = [];
    page.on('request', r => { if (/ResetPassword\.php/.test(r.url())) reqs.push({ url: r.url(), method: r.method(), body: r.postData() || '' }); });
    await page.fill('#selfNewPassword', PASS);
    await page.fill('#selfConfirmPassword', PASS);
    await page.click('#selfResetPasswordBtn');
    await page.waitForFunction(() => {
      const t = document.getElementById('selfResetPasswordResult')?.innerText || '';
      return t && t !== 'Processing...';
    }, null, { timeout: 15000 }).catch(() => {});
    const result = await page.locator('#selfResetPasswordResult').innerText();
    ok(`${engineName}: the change succeeds and says so`, /updated successfully/i.test(result), result);

    const r = reqs[0];
    const pageOrigin = new URL(page.url()).origin;
    ok(`${engineName}: exactly one request, a POST`, reqs.length === 1 && r.method === 'POST', JSON.stringify(reqs.map(x => x.method)));
    ok(`${engineName}: same origin as the page`, !!r && new URL(r.url).origin === pageOrigin, r ? `${new URL(r.url).origin} vs ${pageOrigin}` : 'no request');
    ok(`${engineName}: the password is not in the URL`, !!r && !new URL(r.url).search.includes('newPass') && !r.url.includes(encodeURIComponent(PASS) + '&'), r ? new URL(r.url).pathname + new URL(r.url).search : '');
    ok(`${engineName}: the password travels in the POST body`, !!r && r.body.includes('newPass='));

    // A GET (the old shape) is refused by the endpoint now.
    const getRes = await page.evaluate(async () => (await fetch('/TCGEngine/APIs/ResetPassword.php?newPass=x', { credentials: 'same-origin' })).json());
    ok(`${engineName}: a GET is refused`, getRes && getRes.success === false && /POST/i.test(getRes.error || ''), JSON.stringify(getRes));

    // Still logs in with the (unchanged) password.
    await ctx.clearCookies();
    await login(page);
    await page.goto(BASE + 'SharedUI/Profile.php', { waitUntil: 'load' });
    ok(`${engineName}: logs in again with the password afterwards`, (await page.locator('#selfResetPasswordBtn').count()) === 1);
    await ctx.close();
  } catch (e) {
    ok(`${engineName}: engine ran`, false, String(e).slice(0, 200));
  }
  await browser.close();
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
