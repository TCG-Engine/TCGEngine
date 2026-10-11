// Card language (localized card art) on a live SWUSim board, in Chromium, Firefox and WebKit.
//  1. English board: no card image points at /i18n/.
//  2. Choose Español in the gear menu: every on-board card that the es manifest lists switches to its
//     /i18n/es/ URL and actually loads (naturalWidth > 0); no request under /i18n/ returns 404.
//  3. The choice survives a reload (browser layer).
//  4. Choose English again: no /i18n/ URL remains, without a reload.
// Precondition: Spanish images exist for the board's cards. Run with LIST=1 to print the command.
//
// Usage: node card-language-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const REPLAY = JSON.parse(fs.readFileSync(new URL('./fixtures/replay-optionchoose-goldfish.json', import.meta.url), 'utf8'));
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
// Watchdog: a hung engine (e.g. WebKit newPage()) reads as a labelled timeout, not a silent hang.
setTimeout(() => { console.log('WATCHDOG: timed out after 240s'); for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`); process.exit(9); }, 240000).unref();

async function importReplay() {
  const res = await fetch(BASE + 'APIs/MatchReplay.php?action=import', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ replay: REPLAY }),
  });
  const j = await res.json();
  if (!j.success) throw new Error('import failed: ' + JSON.stringify(j));
  return j;
}

const artUrls = (page) => page.evaluate(() => {
  const out = [];
  document.querySelectorAll('img').forEach((img) => {
    const s = img.getAttribute('src') || '';
    // rendered: a lazy <img> inside a display:none container (e.g. the collapsed resources slot) never loads in any
    // engine, English included — so "loaded" is only required of rendered images; hidden ones are fetch-checked.
    const r = img.getBoundingClientRect();
    if (s.includes('AppCore/SWU/Images/')) out.push({ src: s, w: img.naturalWidth, complete: img.complete, rendered: r.width > 0 && r.height > 0 });
  });
  return out;
});
const stemOf = (src) => { const m = /\/(WebpImages|concat|crops)\/([^\/?#]+?)(_cropped)?\.(webp|png)/.exec(src); return m ? [m[1], m[2]] : null; };

const manifest = await fetch(BASE + 'AppCore/SWU/Images/i18n/es/manifest.json').then((r) => (r.ok ? r.json() : null)).catch(() => null);

for (const [name, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const context = await browser.newContext({ viewport: { width: 1400, height: 900 } });
    const page = await context.newPage();
    const notFound = [];
    page.on('response', (r) => { if (r.url().includes('/i18n/') && r.status() === 404) notFound.push(r.url()); });

    const imp = await importReplay();
    const gameUrl = BASE + imp.nextTurnUrl.replace(/^\.\//, '');
    await page.goto(gameUrl, { waitUntil: 'load' });
    await page.waitForTimeout(3000);

    const english = await artUrls(page);
    if (process.env.LIST === '1') {
      const ids = [...new Set(english.map((u) => stemOf(u.src)).filter(Boolean).map(([, s]) => s.replace(/_back$/, '')).filter((s) => !s.startsWith('mock_')))];
      console.log(`docker exec -w /var/www/html/TCGEngine otmtcge-swusim-web-server-1 php -d xdebug.mode=off zzCardI18nImageGenerator.php rootName=SWUSim locale=es cards=${ids.join(',')}`);
      process.exit(0);
    }
    if (!manifest) throw new Error('no es manifest — run with LIST=1 and execute the printed generator command first');
    ok(name, 'english board renders SWU card art', english.length > 0, String(english.length));
    ok(name, 'english board has no /i18n/ url', english.every((u) => !u.src.includes('/i18n/')));

    const expected = english.map((u) => stemOf(u.src)).filter((p) => p && (manifest[p[0]] || []).includes(p[1]));
    ok(name, 'at least one on-board card has a Spanish image', expected.length > 0, String(expected.length));

    await page.evaluate(() => { const el = document.getElementById('swuSetCardLanguage'); el.value = 'es'; el.dispatchEvent(new Event('change', { bubbles: true })); });
    await page.waitForTimeout(2500);
    const spanish = await artUrls(page);
    const localized = spanish.filter((u) => u.src.includes('/i18n/es/'));
    ok(name, 'listed cards switched to /i18n/es/', localized.length >= expected.length, `${localized.length} of ${expected.length}`);
    const shown = localized.filter((u) => u.rendered);
    ok(name, 'every rendered localized image loaded', shown.length > 0 && shown.every((u) => u.complete && u.w > 0), `${shown.length} rendered; ` + JSON.stringify(shown.filter((u) => !(u.w > 0)).slice(0, 3)));
    const hiddenSrcs = [...new Set(localized.filter((u) => !u.rendered).map((u) => u.src))];
    // Decoded off-DOM rather than header-checked: local Apache sends no Content-Type for .webp (English art too).
    const hiddenStatus = await page.evaluate((srcs) => Promise.all(srcs.map((s) => new Promise((res) => {
      const im = new Image(); im.onload = () => res([s, im.naturalWidth]); im.onerror = () => res([s, 0]); im.src = s;
    }))), hiddenSrcs);
    ok(name, 'every hidden (lazy, not rendered) localized image decodes', hiddenStatus.every(([, w]) => w > 0), JSON.stringify(hiddenStatus));
    ok(name, 'unlisted cards stayed English', spanish.filter((u) => !u.src.includes('/i18n/')).every((u) => { const p = stemOf(u.src); return !p || !(manifest[p[0]] || []).includes(p[1]); }));
    ok(name, 'the gear select reads Español', (await page.evaluate(() => window.SWUCardI18n.effectiveLanguage())) === 'es');
    await page.screenshot({ path: `/tmp/card-language-es-${name}.png` });

    await page.reload({ waitUntil: 'load' });
    await page.waitForTimeout(3500);
    ok(name, 'the choice survives a reload', (await artUrls(page)).some((u) => u.src.includes('/i18n/es/')));

    await page.evaluate(() => { const el = document.getElementById('swuSetCardLanguage'); el.value = 'en'; el.dispatchEvent(new Event('change', { bubbles: true })); });
    await page.waitForTimeout(1500);
    const back = await artUrls(page);
    ok(name, 'switching to English removes every /i18n/ url without a reload', back.length > 0 && back.every((u) => !u.src.includes('/i18n/')));
    ok(name, 'no 404 under /i18n/', notFound.length === 0, notFound.slice(0, 3).join(' | '));
    await page.screenshot({ path: `/tmp/card-language-en-${name}.png` });
  } catch (e) {
    ok(name, 'engine ran', false, String(e).slice(0, 240));
  } finally {
    if (browser) await browser.close();
  }
}
for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
