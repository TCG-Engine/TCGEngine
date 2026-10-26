// Profile → SWU Forge panel, signed in as the local "ninin" account, in Chromium, Firefox and WebKit:
//  - unlinked: SWU Forge's wording, and "Connect SWU Forge" sends the browser to Forge's /auth/petranaki/authorize with
//    our client id, the registered localhost callback and a state (the Forge page itself is not driven further);
//  - linked (a stand-in link row, removed again): "Linked as <profile>", Unlink, and the not-enabled notice;
//  - at desktop and phone width, the panel causes no horizontal scroll.
//
//   node DevTools/ui-harness/swusim-swuforge-link-xbrowser.mjs          ENGINES=chromium,firefox,webkit (default: all three)
//
// ⚠ If ninin is already linked for real, the linked-state stand-in is skipped so the real link is never touched.
// ⚠ Needs the panel on: 'swuforgeLink' => true in SharedUI/Sites/SWUSim/SiteDef.php (off until Forge production) — else SKIP.
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';
import fs from 'node:fs';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SITE = BASE + 'SharedUI/Sites/SWUSim/';
const CONTAINER = 'otmtcge-swusim-web-server-1';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let fails = 0, checks = 0;
const check = (engine, name, cond, extra = '') => { checks++; if (!cond) { fails++; console.log(`FAIL ${engine.padEnd(9)} ${name}${extra ? ' — ' + extra : ''}`); } else console.log(`PASS ${engine.padEnd(9)} ${name}`); };
const php = (code) => execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', CONTAINER, 'php', '-d', 'xdebug.mode=off', '-r',
  'require "Database/ConnectionManager.php"; $c = GetLocalMySQLConnection(); ' + code], { encoding: 'utf8' }).trim();
// The Forge address this box is configured with ($swuforgeBaseURL — there is no default).
const FORGE = php('require "SWUSim/SWUForgeLink.php"; echo SWUForgeConfig()["baseUrl"];');
const UID = Number(php('$r = $c->query("SELECT usersId FROM users WHERE usersUid = \'ninin\'")->fetch_row(); echo $r[0] ?? 0;'));
if (!UID) { console.log('no local "ninin" account'); process.exit(2); }
const realLink = php(`$r = $c->query("SELECT COUNT(*) FROM swuforge_links WHERE usersId = ${UID}")->fetch_row(); echo $r[0];`) !== '0';
const standIn = (on) => php(on
  ? `$c->query("REPLACE INTO swuforge_links (usersId, profileName, accessToken, refreshToken, accessExpires, linkedAt, problem) VALUES (${UID}, 'Mario Test Profile', 'harness', 'harness', 0, 0, 'not-enabled')");`
  : `$c->query("DELETE FROM swuforge_links WHERE usersId = ${UID} AND accessToken = 'harness'");`);

async function login(page) {
  await page.goto(SITE + 'LoginPage.php');
  await page.fill('input[name="userID"]', 'ninin'); await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation().catch(() => {}), page.click('button[type="submit"]')]);
}
const panel = (page) => page.evaluate(() => {
  const p = document.querySelector('.swuforge-link');
  const a = p && p.querySelector('a');
  const r = p && p.getBoundingClientRect();
  return p && { text: p.innerText, href: a ? a.getAttribute('href') : null, button: a ? a.textContent.trim() : null,
    visible: !!(r && r.width > 0 && r.height > 0), hScroll: document.documentElement.scrollWidth > document.documentElement.clientWidth + 1 };
});

fs.mkdirSync(new URL('./_shots/', import.meta.url), { recursive: true });
for (const [name, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const ctx = await browser.newContext({ viewport: { width: 1280, height: 900 } });
    const page = await ctx.newPage();
    await login(page);
    await page.goto(SITE + 'Profile.php');
    if (!(await panel(page))) { console.log('SKIP: no SWU Forge panel — SharedUI/Sites/SWUSim/SiteDef.php has swuforgeLink off'); await browser.close(); process.exit(2); }
    if (!realLink) {
      await page.goto(SITE + 'Profile.php');
      const u = await panel(page);
      check(name, 'unlinked: the SWU Forge panel is shown', !!u && u.visible, JSON.stringify(u));
      check(name, 'unlinked: SWU Forge\'s wording', !!u && u.text.includes('will be sent to SWU Forge and saved to your SWU Forge account'));
      check(name, 'unlinked: a Connect SWU Forge button', !!u && u.button === 'Connect SWU Forge', JSON.stringify(u));
      // Connect: the first navigation away from the site is Forge's authorize page.
      const forgeReq = page.waitForRequest((r) => r.url().includes('/auth/petranaki/authorize'), { timeout: 20000 }).catch(() => null);
      await page.click('.swuforge-link a').catch(() => {});
      const req = await forgeReq;
      const q = req ? new URL(req.url()) : null;
      check(name, 'Connect goes to the configured SWU Forge\'s authorize page', !!q && q.origin === FORGE, req ? req.url() : 'no request');
      check(name, 'with client "petranaki", the registered localhost callback and a state', !!q && q.searchParams.get('client_id') === 'petranaki'
        && q.searchParams.get('redirect_uri') === 'http://localhost:3400/TCGEngine/AccountFiles/SWUForgeOAuthCallback.php'
        && /^[0-9a-f]{64}$/.test(q.searchParams.get('state') || '') && q.searchParams.get('response_type') === 'code', req ? req.url() : '');
      standIn(true);
    }
    for (const [label, vp] of [['desktop', { width: 1280, height: 900 }], ['phone', { width: 390, height: 844 }]]) {
      await page.setViewportSize(vp);
      await page.goto(SITE + 'Profile.php');
      const l = await panel(page);
      if (!realLink) {
        check(name, `${label}: linked shows "Linked as" the Forge profile`, !!l && l.text.includes('Linked as Mario Test Profile'), JSON.stringify(l));
        check(name, `${label}: linked shows the not-enabled notice`, !!l && l.text.includes('not enabled for game delivery'));
        check(name, `${label}: linked offers Unlink SWU Forge`, !!l && l.button === 'Unlink SWU Forge' && (l.href || '').includes('DisconnectOAuth.php?type=swuforge'));
      }
      check(name, `${label}: no horizontal scroll`, !!l && !l.hScroll, JSON.stringify(l));
      await page.locator('.swuforge-link').screenshot({ path: new URL(`./_shots/swuforge-link-${label}-${name}.png`, import.meta.url).pathname }).catch(() => {});
    }
  } catch (e) {
    check(name, 'harness ran', false, String(e).slice(0, 300));
  } finally {
    if (!realLink) standIn(false);
    if (browser) await browser.close();
  }
}
console.log(realLink ? '(ninin is linked for real: only the layout checks ran)' : '');
console.log(fails === 0 ? `ALL PASS (${checks})` : `SOME FAILED (${fails} of ${checks})`);
process.exit(fails === 0 ? 0 : 1);
