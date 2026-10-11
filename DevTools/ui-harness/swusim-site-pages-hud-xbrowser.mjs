// Cross-browser check: Login, Signup, Previews and Profile in the "New Petranaki HUD" style (owner, 2026-09-21).
// Visual case: SWUSim/Tests/Visual/SitePages_NewPetranakiHud.md
//
// The panels are three SHARED classes (.container.bg-black, .container.bg-blue, .card.ga-glass-card) restyled from
// SWUSim's own stylesheet, so this checks the result per page rather than trusting the rule exists:
//   every TOP-LEVEL panel is glass (unpainted element; chamfer-clipped ::before with both gold corner glows);
//   a panel NESTED in a panel is not (no glass-in-glass) — Profile's sections, Signup's notices;
//   text fields are sunken wells; a form's submit is the gold primary;
//   the disclaimer footer never overlaps a panel (Signup used to run over it); no horizontal overflow.
// Usage: node swusim-site-pages-hud-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SITE = BASE + 'SharedUI/Sites/SWUSim/';
const OUT = process.env.OUT || '/tmp';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

const probe = (page) => page.evaluate(() => {
  const PANEL = '.container.bg-black, .container.bg-blue, .card.ga-glass-card';
  const all = [...document.querySelectorAll(PANEL)].filter(e => getComputedStyle(e).display !== 'none');
  const top = all.filter(e => !e.parentElement.closest(PANEL));
  const nested = all.filter(e => e.parentElement.closest(PANEL));
  const isGlass = (e) => { const c = getComputedStyle(e), b = getComputedStyle(e, '::before');
    return /rgba\(0, 0, 0, 0\)|transparent/.test(c.backgroundColor) && /polygon/.test(b.clipPath) && (b.backgroundImage.match(/url\("data:/g) || []).length === 2; };
  const fields = [...document.querySelectorAll('.container input[type=text], .container input[type=password], .container input[type=email], .container select')];
  const submit = document.querySelector('.container button[type=submit]');
  const disc = document.querySelector('.disclaimer');
  const lowest = Math.max(0, ...top.map(e => e.getBoundingClientRect().bottom));
  return {
    top: top.length, topGlass: top.filter(isGlass).length,
    nested: nested.length, nestedGlass: nested.filter(e => getComputedStyle(e, '::before').content !== 'none' && /polygon/.test(getComputedStyle(e, '::before').clipPath)).length,
    fields: fields.length, sunken: fields.filter(f => /rgba\(14, 17, 22, 0\.58\)/.test(getComputedStyle(f).backgroundColor)).length,
    submitColor: submit ? getComputedStyle(submit).color : null,
    footerClear: disc ? disc.getBoundingClientRect().top >= lowest - 1 : true,
    // No panel may hide its own content (Profile's panes were clipped at 320px on phones).
    clipped: top.filter(e => getComputedStyle(e).overflow !== 'visible' && e.scrollHeight > e.clientHeight + 2).map(e => e.className.split(' ')[0] + ':' + e.scrollHeight + '/' + e.clientHeight),
    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
  };
});

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    for (const width of [1440, 390]) {
      const tag = `${width}px`;
      const ctx = await browser.newContext({ viewport: { width, height: 900 } });
      const page = await ctx.newPage();
      const check = async (name, url, { submitGold = false, expectNested = false } = {}) => {
        await page.goto(url, { waitUntil: 'load' }); await page.waitForTimeout(500);
        const s = await probe(page);
        ok(engine, `${tag} ${name}: every top-level panel is glass`, s.top > 0 && s.topGlass === s.top, `${s.topGlass}/${s.top}`);
        if (expectNested) ok(engine, `${tag} ${name}: nested panels are not glass-in-glass`, s.nested > 0 && s.nestedGlass === 0, `${s.nestedGlass}/${s.nested}`);
        if (s.fields) ok(engine, `${tag} ${name}: text fields are sunken wells`, s.sunken === s.fields, `${s.sunken}/${s.fields}`);
        if (submitGold) ok(engine, `${tag} ${name}: the submit is the gold primary`, /rgb\(255, 217, 152\)/.test(s.submitColor || ''), s.submitColor);
        ok(engine, `${tag} ${name}: the footer does not overlap a panel`, s.footerClear);
        ok(engine, `${tag} ${name}: no panel clips its own content`, s.clipped.length === 0, s.clipped.join(', '));
        ok(engine, `${tag} ${name}: no horizontal overflow`, s.overflow <= 0, String(s.overflow));
        await page.screenshot({ path: `${OUT}/site-hud-${engine}-${width}-${name}.png`, fullPage: true });
      };
      await check('login', SITE + 'LoginPage.php', { submitGold: true });
      await check('signup', SITE + 'Signup.php', { submitGold: true, expectNested: true });
      await check('previews', SITE + 'Previews.php');
      await page.goto(SITE + 'LoginPage.php'); await page.fill('input[name="userID"]', 'claudebot1'); await page.fill('input[name="password"]', 'pass');
      await Promise.all([page.waitForNavigation().catch(() => {}), page.click('button[type="submit"]')]);
      await check('profile', SITE + 'Profile.php', { expectNested: true });
      await ctx.close();
    }
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}
for (const [engine, name, pass, extra] of results) console.log(`${pass ? 'ok  ' : 'BAD '} [${engine}] ${name}${pass || !extra ? '' : '  ' + extra}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
