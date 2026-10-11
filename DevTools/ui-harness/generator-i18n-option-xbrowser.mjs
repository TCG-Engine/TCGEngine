// Generator admin page: the "Localized card images" step (zzCardI18nImageGenerator.php).
//  - its own "Localized card images" section (language + replace + only-this-set) shows for SWUSim only;
//  - overwriteImages on each step's URL: absent when unticked, 1 for all sets, or one set code (uppercased),
//    and the English and localized replace controls never leak into each other's step;
//  - "Run build pipeline" never runs it.
// The generator itself is never executed here: fetch is stubbed in the page, and requests are recorded.
//
// Usage: node generator-i18n-option-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
//        MOD_USER / MOD_PASS: a moderator login (the page is mod-only). Defaults: claudebot1 / pass.
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const USER = process.env.MOD_USER || 'claudebot1';
const PASS = process.env.MOD_PASS || 'pass';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

// Matches the login flow used by other ui-harness scripts (e.g. waiting-room-xbrowser.mjs):
// SharedUI/LoginPage.php is a generated pointer to the active site's own LoginPage.php,
// whose form fields are input[name="userID"] / input[name="password"] with a plain submit button.
async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'load' }).catch(() => {});
  const user = page.locator('input[name="userID"]').first();
  if (await user.count()) {
    await user.fill(USER);
    await page.locator('input[name="password"]').first().fill(PASS);
    await Promise.all([page.waitForLoadState('load'), page.locator('button[type="submit"]').first().click()]);
  }
}

for (const [name, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const page = await browser.newPage();
    await login(page);
    const resp = await page.goto(BASE + 'zzCodeGeneratorMain.php', { waitUntil: 'load' });
    if (!resp || resp.status() === 403) throw new Error(`zzCodeGeneratorMain.php HTTP ${resp && resp.status()} — set MOD_USER/MOD_PASS to a moderator (or run with DEVENV=true)`);
    // Record generator requests instead of running them.
    await page.evaluate(() => {
      window.__runs = [];
      const real = window.fetch;
      window.fetch = (input, init) => {
        const u = String(input && input.url ? input.url : input);
        if (/zz\w+Generator\.php|ProcessKeywords|GenerateSites/.test(u)) { window.__runs.push(u); return Promise.resolve(new Response('ok', { status: 200 })); }
        return real(input, init);
      };
    });
    await page.evaluate(() => selectApp('SWUSim'));
    ok(name, 'the Localized card images section is visible for SWUSim', await page.locator('#i18n-options').isVisible());
    ok(name, 'the language select is inside that section, not the English options',
      (await page.locator('#i18n-options #card-image-locale').count()) === 1 && (await page.locator('#card-options #card-image-locale').count()) === 0);
    const hasStep = await page.evaluate(() => selectedApp.actions.some(a => a.id === 'i18n-images' && a.kind === 'i18n'));
    ok(name, 'SWUSim has the i18n-images step', hasStep);

    const urlFor = (id) => page.evaluate((actionId) => actionUrl(selectedApp.actions.find(a => a.id === actionId)).toString(), id);
    const overwriteOf = (u) => new URL(u).searchParams.get('overwriteImages');

    // English card step: unticked -> no param; ticked -> 1; ticked + set -> that set (uppercased).
    ok(name, 'set field is disabled until Replace is ticked', await page.locator('#overwrite-images-set').isDisabled());
    ok(name, 'cards: unticked sends no overwriteImages', overwriteOf(await urlFor('cards')) === null);
    await page.check('#overwrite-images');
    ok(name, 'set field enables once Replace is ticked', await page.locator('#overwrite-images-set').isEnabled());
    ok(name, 'cards: ticked, no set -> overwriteImages=1', overwriteOf(await urlFor('cards')) === '1');
    await page.fill('#overwrite-images-set', 'hmw');
    ok(name, 'cards: ticked + hmw -> overwriteImages=HMW', overwriteOf(await urlFor('cards')) === 'HMW');
    ok(name, 'English replace does not leak into the localized step', overwriteOf(await urlFor('i18n-images')) === null);

    // Localized step: its own locale + its own replace controls.
    await page.selectOption('#card-image-locale', 'it');
    const i18nUrl = await urlFor('i18n-images');
    ok(name, 'the step URL carries locale=it', /[?&]locale=it(&|$)/.test(i18nUrl), i18nUrl);
    await page.check('#i18n-overwrite-images');
    ok(name, 'i18n: ticked, no set -> overwriteImages=1', overwriteOf(await urlFor('i18n-images')) === '1');
    await page.fill('#i18n-overwrite-images-set', 'TWI');
    ok(name, 'i18n: ticked + TWI -> overwriteImages=TWI', overwriteOf(await urlFor('i18n-images')) === 'TWI');
    await page.uncheck('#overwrite-images');
    ok(name, 'localized replace does not leak into the English step', overwriteOf(await urlFor('cards')) === null);
    await page.screenshot({ path: `/tmp/generator-i18n-swusim-${name}.png`, fullPage: false });
    await page.uncheck('#i18n-overwrite-images');

    await page.evaluate(() => runPipeline());
    await page.waitForFunction(() => !pipelineRunning, null, { timeout: 30000 });
    const runs = await page.evaluate(() => window.__runs);
    ok(name, 'the pipeline ran at least one step', runs.length > 0, String(runs.length));
    ok(name, 'the pipeline did not run the i18n step', !runs.some(u => u.includes('zzCardI18nImageGenerator.php')), runs.join(' | '));

    const other = await page.evaluate(() => apps.map(a => a.rootName).find(r => r !== 'SWUSim' && apps.find(x => x.rootName === r).actions.length > 0));
    if (other) {
      await page.evaluate((r) => selectApp(r), other);
      ok(name, `the Localized card images section is hidden for ${other}`, !(await page.locator('#i18n-options').isVisible()));
    }
    await page.screenshot({ path: `/tmp/generator-i18n-${name}.png`, fullPage: false });
  } catch (e) {
    ok(name, 'engine ran', false, String(e).slice(0, 240));
  } finally {
    if (browser) await browser.close();
  }
}
for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
