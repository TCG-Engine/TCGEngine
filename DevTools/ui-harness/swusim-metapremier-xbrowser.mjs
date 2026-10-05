// Meta Premier in the SWUSim menu + profile — docs/superpowers/specs/2026-10-03-swusim-metapremier-ratings-design.md §2.2, §5.3.
// Chromium, Firefox and WebKit, at desktop and phone width. Screenshots land in $OUT for a visual pass.
//
// What it protects:
//  • PvP's card pool offers "Meta Premier"; choosing it leaves Match Type with Best of 3 only (Bo1 switched off),
//    hides Create Private Room (queue only), and — logged out — disables Join Queue with a "Log in" hint
//  • switching back to Premier restores both match types and Create Private Room (Premier untouched)
//  • a Premier-legal deck link pasted while on Meta Premier keeps Meta Premier (detection must not yank the pool)
//  • the profile shows the player's own Meta Premier pane
//
// Usage: node DevTools/ui-harness/swusim-metapremier-xbrowser.mjs     ENGINES=chromium,firefox,webkit (default: all)
//
// FEATFLAG_GLICKO2 — the rated queue is switched OFF (Formats.php 'enabled' => false; profile pane removed from SiteDef).
// By default this checks the OFF state: no Meta Premier pool in PvP and no Meta Premier profile pane. Once the flag is on,
// run it with FEATFLAG_GLICKO2=on to get the full menu / profile checks back (and make that the default).
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE || 'http://localhost:3400/TCGEngine/';
const OUT = process.env.OUT || '/tmp/metapremier-shots';
fs.mkdirSync(OUT, { recursive: true });
const PREM = 'https://swudb.com/deck/LImIrpIS';      // Boba Fett, Premier-legal (also used by swusim-menu2-detect.mjs)
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
let fails = 0, checks = 0;
const GLICKO2_ON = process.env.FEATFLAG_GLICKO2 === 'on';   // FEATFLAG_GLICKO2 — see the header
const ok = (engine, name, cond, extra = '') => { checks++; if (!cond) { fails++; console.log(`FAIL ${engine} :: ${name}${extra ? '  [' + extra + ']' : ''}`); } };

async function login(page, user) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', user);
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}
async function openPvp(page) {
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'networkidle' });
  await page.locator('.mode').first().click();
  await page.waitForSelector('dialog#setup-pvp[open]');
}
// Pick a card pool the way a player does: open the chip (an enhanced listbox over the hidden <select>) and click the row.
const POOL_LABEL = { metapremier: 'Meta Premier (Bo3)', premier: 'Premier' };
const esc = (t) => t.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
async function pickPool(page, fmt) {
  await page.click('#pvp-pool-btn');
  await page.locator('#pvp-pool-list [role="option"]').filter({ hasText: new RegExp('^\\s*' + esc(POOL_LABEL[fmt]) + '\\s*$') }).click();
  await page.waitForTimeout(150);
}
const state = (page) => page.evaluate(() => {
  const d = document.getElementById('setup-pvp');
  const vis = (el) => { if (!el) return false; const cs = getComputedStyle(el); const r = el.getBoundingClientRect();
                        return cs.display !== 'none' && cs.visibility !== 'hidden' && r.width > 0 && r.height > 0; };
  const join = d.querySelector('button[data-act="join"]');
  const priv = d.querySelector('button[data-act="private"]');
  const hint = d.querySelector('.mp-loginhint');
  return {
    pool: d.querySelector('#pvp-pool').value,
    match: [...d.querySelectorAll('#pvp-match option')].map(o => o.text),
    matchValue: d.querySelector('#pvp-match').value,
    joinVisible: vis(join), joinDisabled: !!(join && join.disabled),
    privVisible: vis(priv),
    hintVisible: vis(hint), hintText: hint ? hint.textContent.trim() : '',
  };
});

for (const [engine, launcher] of ENGINES) {
  for (const width of [1440, 390]) {
    const tag = `${engine} ${width}px`;
    const browser = await launcher.launch();
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    const errs = []; page.on('pageerror', e => errs.push(e.message));

    // ── FEATFLAG_GLICKO2 off: hidden everywhere ────────────────────────────────────────────────────────
    if (!GLICKO2_ON) {
      await openPvp(page);
      const offPools = await page.$$eval('#pvp-pool option', os => os.map(o => o.value));
      ok(tag, 'FEATFLAG_GLICKO2 off: PvP offers no Meta Premier pool', !offPools.includes('metapremier'), offPools.join(','));
      await login(page, 'claudebot1');
      await page.goto(BASE + 'SharedUI/Sites/SWUSim/Profile.php', { waitUntil: 'load' });
      ok(tag, 'FEATFLAG_GLICKO2 off: no Meta Premier profile pane', (await page.$('.metaPremierRating')) === null);
      ok(tag, 'no page errors', errs.length === 0, errs[0] || '');
      await browser.close();
      continue;
    }

    // ── logged out ───────────────────────────────────────────────────────────────────────────────────
    await openPvp(page);
    const pools = await page.$$eval('#pvp-pool option', os => os.map(o => o.value));
    ok(tag, 'PvP offers a Meta Premier pool', pools.includes('metapremier'), pools.join(','));
    await pickPool(page, 'metapremier');
    let s = await state(page);
    ok(tag, 'logged out: Match Type offers Best of 3 only', JSON.stringify(s.match) === '["Best of 3"]', JSON.stringify(s.match));
    ok(tag, 'logged out: Join Queue disabled', s.joinDisabled);
    ok(tag, 'logged out: the "Log in" hint shows', s.hintVisible && /log in/i.test(s.hintText), s.hintText);
    ok(tag, 'logged out: Create Private Room hidden', !s.privVisible);
    await page.screenshot({ path: `${OUT}/${engine}-${width}-loggedout-metapremier.png` });
    await pickPool(page, 'premier');
    s = await state(page);
    ok(tag, 'logged out: back on Premier, both match types return', JSON.stringify(s.match) === '["Best of 1","Best of 3"]', JSON.stringify(s.match));
    ok(tag, 'logged out: back on Premier, Join Queue enabled, no hint', !s.joinDisabled && !s.hintVisible);
    ok(tag, 'logged out: back on Premier, Create Private Room shown', s.privVisible);

    // ── logged in ────────────────────────────────────────────────────────────────────────────────────
    await login(page, 'claudebot1');
    await openPvp(page);
    await pickPool(page, 'metapremier');
    s = await state(page);
    ok(tag, 'logged in: Best of 3 only', JSON.stringify(s.match) === '["Best of 3"]', JSON.stringify(s.match));
    ok(tag, 'logged in: Join Queue enabled, no hint', s.joinVisible && !s.joinDisabled && !s.hintVisible);
    ok(tag, 'logged in: Create Private Room hidden', !s.privVisible);
    await page.screenshot({ path: `${OUT}/${engine}-${width}-loggedin-metapremier.png` });
    // the submission path reads the modal: Meta Premier + Best of 3 → metapremier / bo3
    const synced = await page.evaluate(() => { window.SYNC_ACTIVE_SETUP();
      return [document.getElementById('swu-format-select').value, document.getElementById('swu-queuetype-select').value]; });
    ok(tag, 'the submission carries metapremier / bo3', synced.join('/') === 'metapremier/bo3', synced.join('/'));

    if (width === 1440) {   // deck detection hits the network; once per engine is enough
      await page.fill('dialog[open] input[data-detect]', PREM);
      await page.dispatchEvent('dialog[open] input[data-detect]', 'change');
      await page.waitForTimeout(4000);
      s = await state(page);
      ok(tag, 'a Premier-legal link keeps Meta Premier selected', s.pool === 'metapremier', s.pool);
      ok(tag, '... and Match Type stays Best of 3 only', JSON.stringify(s.match) === '["Best of 3"]', JSON.stringify(s.match));
    }

    // ── profile ──────────────────────────────────────────────────────────────────────────────────────
    await page.goto(BASE + 'SharedUI/Sites/SWUSim/Profile.php', { waitUntil: 'load' });
    const pane = await page.evaluate(() => { const el = document.querySelector('.metaPremierRating');
      if (!el) return null; const r = el.getBoundingClientRect(); return { w: r.width, h: r.height, text: el.textContent }; });
    ok(tag, 'profile shows the Meta Premier pane', !!pane && pane.w > 0 && /Meta Premier/.test(pane.text), JSON.stringify(pane));
    const overflow = await page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    ok(tag, 'profile has no horizontal scroll', !overflow);
    await page.screenshot({ path: `${OUT}/${engine}-${width}-profile.png`, fullPage: true });

    ok(tag, 'no page errors', errs.length === 0, errs[0] || '');
    await browser.close();
  }
}
console.log(fails ? `\n${fails}/${checks} FAILED` : `\nALL PASS (${checks} checks) — screenshots in ${OUT}`);
process.exit(fails ? 1 : 0);
