// Cross-browser check for the SWUSim main-menu revamp (owner's 2026-09-21 mockup, recoloured to the in-game stone grey).
// Visual case: SWUSim/Tests/Visual/Menu_Revamp.md
//
// Structural assertions a screenshot alone cannot give: the header emblem renders; the panels are chamfered and carry
// the corner-glow layer; the new stylesheet actually won the cascade (stone-grey field fill, uppercase field labels);
// the action buttons KEEP THEIR ICON when applyFormatUI relabels them (a textContent rewrite would wipe it); the deck
// tabs toggle by class; the Active Games message follows the queue count; the Test Deck button is gone; and nothing
// overflows horizontally at desktop or phone width. Screenshots land in $OUT (default /tmp).
//
// Usage: node swusim-menu-revamp-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const OUT = process.env.OUT || '/tmp';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    for (const width of [1672, 390]) {
      const tag = `${width}px`;
      const page = await browser.newPage({ viewport: { width, height: width > 500 ? 941 : 844 } });
      await page.goto(MENU, { waitUntil: 'load' });
      await page.evaluate(() => { try { localStorage.removeItem('swu_deck_tab'); } catch (e) {} });
      await page.reload({ waitUntil: 'load' });
      await page.waitForTimeout(400);

      const s = await page.evaluate(() => {
        const cs = (el, p) => el ? getComputedStyle(el).getPropertyValue(p) : null;
        const logo = document.querySelector('.home-header .title-logo');
        const panel = document.querySelector('.swu-queue-card');
        const input = document.querySelector('#deck-link');
        const label = document.querySelector('label[for="swu-gametype-select"]');
        return {
          logo: !!logo && logo.getBoundingClientRect().width > 30 && logo.complete && logo.naturalWidth > 0,
          titleHasLogo: document.querySelector('.home-header .title').classList.contains('has-logo'),
          panelClip: getComputedStyle(panel, '::before').clipPath,   // the glass is clipped; the element is not (its shadow must fall outside)
          panelGlow: getComputedStyle(panel, '::before').backgroundImage,
          inputBg: cs(input, 'background-color'),
          labelTransform: cs(label, 'text-transform'),
          testDeck: /Test Deck/.test(document.body.textContent) || !!document.querySelector('[onclick="loadTestDeck()"]'),
          overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
          panels: document.querySelectorAll('.swu-menu-grid > .swu-panel').length,
        };
      });
      ok(engine, `${tag}: header emblem renders (branding.logo)`, s.logo && s.titleHasLogo, JSON.stringify(s));
      ok(engine, `${tag}: all three panels use the revamp frame`, s.panels === 3, String(s.panels));
      ok(engine, `${tag}: panels are chamfered`, /polygon/.test(s.panelClip || ''), s.panelClip);
      ok(engine, `${tag}: panels carry the gold corner-glow layers`, (s.panelGlow.match(/url\("data:/g) || []).length === 2, s.panelGlow.slice(0, 80));
      ok(engine, `${tag}: the menu stylesheet won the cascade (sunken stone field)`, /rgba\(14, 17, 22/.test(s.inputBg || ''), s.inputBg);
      ok(engine, `${tag}: field labels are uppercase`, s.labelTransform === 'uppercase', s.labelTransform);
      ok(engine, `${tag}: the Test Deck button is gone`, !s.testDeck);
      ok(engine, `${tag}: no horizontal overflow`, s.overflow <= 0, `overflow=${s.overflow}`);

      // Relabelled buttons keep their icon.
      const btn = async (id) => page.evaluate((i) => {
        const b = document.getElementById(i);
        return { text: b.textContent.trim(), icon: !!b.querySelector('svg.swu-ico'), shown: getComputedStyle(b).display !== 'none' };
      }, id);
      let solo = await btn('start-solo-btn');
      ok(engine, `${tag}: Arenabot default → "Start Arenabot" with its icon`, solo.shown && solo.text === 'Start Arenabot' && solo.icon, JSON.stringify(solo));
      await page.selectOption('#swu-gametype-select', 'solo');
      await page.selectOption('#swu-second-select', 'hotseat');
      solo = await btn('start-solo-btn');
      ok(engine, `${tag}: Hotseat relabel keeps the icon`, solo.text === 'Start Hotseat Game' && solo.icon, JSON.stringify(solo));
      await page.selectOption('#swu-gametype-select', 'constructed');
      await page.selectOption('#swu-second-select', 'pvp');
      const join = await btn('join-queue-btn'), create = await btn('create-private-game-btn');
      ok(engine, `${tag}: PvP shows Join Queue + Create Private Room, both with icons`,
         join.shown && join.icon && create.shown && create.icon && create.text === 'Create Private Room', JSON.stringify({ join, create }));
      const primary = await page.$eval('#join-queue-btn', b => getComputedStyle(b).color);
      ok(engine, `${tag}: Join Queue is the gold primary`, /rgb\(255, 217, 152\)/.test(primary), primary);

      // Deck tabs toggle by class.
      await page.click('#tab-text');
      const tabs = await page.evaluate(() => [document.getElementById('tab-link').className, document.getElementById('tab-text').className,
        getComputedStyle(document.getElementById('deck-input-text')).display]);
      ok(engine, `${tag}: Free Text tab becomes active and shows the textarea`, !/is-active/.test(tabs[0]) && /is-active/.test(tabs[1]) && tabs[2] !== 'none', tabs.join(' | '));
      await page.click('#tab-link');

      // The Games in Progress empty state: shown only with no games; filtered wording when a format is chosen.
      const empty = await page.evaluate(() => {
        const box = () => getComputedStyle(document.getElementById('swu-active-empty')).display;
        const title = () => document.getElementById('swu-active-empty-title').textContent;
        swuRenderActiveEmpty(0, false); const a = [box(), title()];
        swuRenderActiveEmpty(0, true);  const b = [box(), title()];
        swuRenderActiveEmpty(3, false); const c = [box()];
        swuRenderActiveEmpty(0, false); return [a, b, c];
      });
      ok(engine, `${tag}: the Games in Progress empty state follows the count and the filter`,
         empty[0][0] !== 'none' && empty[0][1] === 'No games in progress' && empty[1][1] === 'No games in this format' && empty[2][0] === 'none',
         JSON.stringify(empty));

      await page.selectOption('#swu-second-select', 'arenabot');
      await page.screenshot({ path: `${OUT}/menu-revamp-${engine}-${width}.png` });
      await page.close();
    }
    // Mid widths: the desktop nav chips must not cover the header plate (they did at 769–1180px before the plate
    // was dropped below them).
    for (const w of [800, 1000, 1180]) {
      const page = await browser.newPage({ viewport: { width: w, height: 700 } });
      await page.goto(MENU, { waitUntil: 'load' });
      const hit = await page.evaluate(() => {
        const t = document.querySelector('.home-header .title').getBoundingClientRect();
        return [...document.querySelectorAll('.nav-bar-user, .nav-bar-links')].map(e => e.getBoundingClientRect())
          .filter(r => r.width > 0).some(r => r.left < t.right && r.right > t.left && r.top < t.bottom && r.bottom > t.top);
      });
      ok(engine, `${w}px: the nav does not cover the header plate`, !hit);
      await page.close();
    }
  } catch (e) {
    ok(engine, 'harness ran', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}

for (const [engine, name, pass, extra] of results) {
  console.log(`${pass ? 'ok  ' : 'BAD '} [${engine}] ${name}${pass || !extra ? '' : '  ' + extra}`);
}
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
