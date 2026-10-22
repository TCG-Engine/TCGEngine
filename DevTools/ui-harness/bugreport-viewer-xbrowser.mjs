// Bug Report Viewer — Petranaki's own reports (LocalBugReportLib.php), Chromium + Firefox + WebKit (2026-10-22).
//
// zzBugReportViewer.php lists Petranaki reports as P-<n> beside the remote ones. This checks, at 1280px and 375px:
//   · the local rows render (P-<n>, the "unverified: <reason>" badge) and the page never scrolls sideways
//     (the twelve-column table scrolls inside .brv-table-wrap instead);
//   · "Download package" saves bugreport-P-<n>-game-<g>.zip — strict-mod only, so the run signs in as a moderator
//     through a throwaway dev session file (claudebot1 is not a moderator);
//   · Delete is permanent, so the FIRST click only arms it ("Confirm delete") and the report survives; the second
//     click deletes it.
// It saves its own two reports in the swusim container and deletes what is left at the end.
//
// Usage: node bugreport-viewer-xbrowser.mjs [BASE]   (exits non-zero on failure)
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const C = 'otmtcge-swusim-web-server-1';
const SID = 'bugreportviewerharness00000001';
const php = (code) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', C, 'php', '-r',
  'require "./Database/ConnectionManager.php"; require "./LocalBugReportLib.php"; $c = GetLocalMySQLConnection(); ' + code],
  { encoding: 'utf8', stdio: ['ignore', 'pipe', 'ignore'] }).trim();
const save = (verified) => php(`$r = LocalBugReportSave(["gameName" => "${verified ? 46201 : 999999}", "playerID" => "1", "verified" => ${verified ? 'true' : 'false'},
  "unverifiedReason" => "${verified ? '' : 'game not found'}", "reporter" => "harness", "accountId" => null, "accountName" => null,
  "description" => "viewer harness report (deleted after the run)", "gamestateHash" => "${verified ? 'e'.repeat(64) : ''}",
  "clientIp" => "192.0.2.${verified ? 61 : 62}", "createdAt" => time()], ${verified ? '"gs\\n"' : 'null'}, "vh-" . bin2hex(random_bytes(6)), $c);
  echo $r["reportNumber"];`);

let allOk = true;
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; console.log(`${cond ? '  ok  ' : '  FAIL'} ${name} ${extra}`); };

execFileSync('docker', ['exec', C, 'sh', '-c',
  `printf 'useruid|s:5:"ninin";userid|s:1:"6";' > /tmp/sess_${SID} && chown www-data:www-data /tmp/sess_${SID}`]);
const created = [];
try {
  for (const [engineName, engine] of Object.entries({ chromium, firefox, webkit })) {
    const browser = await engine.launch();
    try {
      for (const vp of [{ w: 1280, h: 900, tag: 'desktop' }, { w: 375, h: 812, tag: 'phone' }]) {
        const t = (s) => `${engineName}/${vp.tag}: ${s}`;
        const nv = save(true), nu = save(false);
        created.push(nv, nu);
        const ctx = await browser.newContext({ viewport: { width: vp.w, height: vp.h }, acceptDownloads: true });
        await ctx.addCookies([{ name: 'PHPSESSID', value: SID, url: new URL(BASE).origin }]);
        const page = await ctx.newPage();
        await page.goto(BASE + 'zzBugReportViewer.php?rootName=SWUSim', { waitUntil: 'load' });

        const overflow = await page.evaluate(() => document.documentElement.scrollWidth - document.documentElement.clientWidth);
        ok(t('the page does not scroll sideways'), overflow <= 0, `(${overflow}px)`);
        ok(t(`P-${nv} row rendered`), await page.locator(`td:text-is("P-${nv}")`).count() === 1);
        ok(t('unverified badge with its reason'), (await page.locator('.brv-badge-unverified').allTextContents()).includes('unverified: game not found'));

        const link = page.locator(`a.brv-dl[href*="report=P-${nv}"]`);
        await link.scrollIntoViewIfNeeded();
        const [dl] = await Promise.all([page.waitForEvent('download', { timeout: 15000 }), link.click()]);
        ok(t('Download saves the package'), dl.suggestedFilename() === `bugreport-P-${nv}-game-46201.zip`, dl.suggestedFilename());

        const del = page.locator('tr', { has: page.locator(`td:text-is("P-${nu}")`) }).locator('.brv-delete-btn');
        await del.scrollIntoViewIfNeeded();
        await del.click();
        await page.waitForTimeout(800);
        const stillThere = php(`echo is_dir(LocalBugReportStorageDir() . "/P-${nu}") ? "yes" : "no";`);
        const label = await del.textContent({ timeout: 2000 }).catch(() => '(row gone)');
        ok(t('first Delete click only arms the button'), stillThere === 'yes' && label === 'Confirm delete', `${stillThere} / ${label}`);
        await del.click();
        await page.waitForFunction((n) => ![...document.querySelectorAll('td')].some(td => td.textContent === `P-${n}`), nu, { timeout: 10000 }).catch(() => {});
        const gone = php(`echo is_dir(LocalBugReportStorageDir() . "/P-${nu}") ? "yes" : "no";`);
        ok(t('second click deletes it'), gone === 'no' && /deleted/.test(await page.locator('#brv-status').textContent()));
        await ctx.close();
      }
    } catch (e) {
      ok(`${engineName}: harness ran`, false, e.message);
    } finally {
      await browser.close();
    }
  }
} finally {
  for (const n of created) php(`LocalBugReportDelete($c, ${parseInt(n, 10)});`);
  execFileSync('docker', ['exec', C, 'rm', '-f', `/tmp/sess_${SID}`]);
}
console.log(allOk ? 'PASS' : 'FAIL');
process.exit(allOk ? 0 : 1);
