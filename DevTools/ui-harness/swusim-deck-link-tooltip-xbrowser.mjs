// Cross-browser check for the supported-deck-links info tooltip on the SWUSim main menu (owner, 2026-09-21: "move the
// 'supported deck links' block of text to be an info tooltip on the first 'Paste a deck link:' label").
// Visual case: SWUSim/Tests/Visual/Menu_DeckLinkSitesTooltip.md
//
// Asserts: the old always-visible hint line is gone; the bubble is HIDDEN until asked for; hover, keyboard focus and a
// click each open it; Escape and a click elsewhere close it; aria-expanded tracks the click state; and the open bubble
// stays inside the "Create a New Game" card and the viewport at desktop AND phone width.
//
// Usage: node swusim-deck-link-tooltip-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const OUT = process.env.OUT || '/tmp';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

const bubble = (page) => page.evaluate(() => {
  const b = document.querySelector('#deck-link-sites');
  const card = document.querySelector('.swu-queue-card').getBoundingClientRect();
  const r = b.getBoundingClientRect();
  const cs = getComputedStyle(b);
  return {
    shown: cs.visibility === 'visible' && parseFloat(cs.opacity) > 0.5,
    text: b.textContent.replace(/\s+/g, ' ').trim(),
    inCard: r.left >= card.left - 1 && r.right <= card.right + 1,
    inViewport: r.left >= 0 && r.right <= document.documentElement.clientWidth,
    expanded: document.querySelector('#deck-link-sites-btn').getAttribute('aria-expanded'),
    box: [Math.round(r.left), Math.round(r.right), Math.round(card.left), Math.round(card.right)].join(','),
  };
});
const settle = (page) => page.waitForTimeout(250);   // the fade is 120ms

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    for (const width of [1400, 390]) {
      const tag = `${width}px`;
      const page = await browser.newPage({ viewport: { width, height: 900 } });
      await page.goto(MENU, { waitUntil: 'load' });
      await page.evaluate(() => switchDeckTab('link'));

      const oldHint = await page.evaluate(() => [...document.querySelectorAll('#deck-input-link div')]
        .some(d => d.id !== 'deck-link-sites' && !d.querySelector('#deck-link-sites') && /Supported deck links/.test(d.textContent)));
      ok(engine, `${tag}: the always-visible hint line is gone`, !oldHint);
      const icon = await page.evaluate(() => {
        const btn = document.querySelector('#deck-link-sites-btn');
        const lab = document.querySelector('label[for="deck-link"]');
        const a = btn.getBoundingClientRect(), l = lab.getBoundingClientRect();
        const c = getComputedStyle(btn);
        return { right: a.left >= l.right - 1, sameLine: Math.abs((a.top + a.bottom) / 2 - (l.top + l.bottom) / 2) < 6, w: Math.round(a.width),
                 border: c.borderTopWidth, radius: c.borderTopLeftRadius, pad: c.paddingLeft, tt: c.textTransform };
      });
      ok(engine, `${tag}: the ⓘ icon sits right after the label, on its line`, icon.right && icon.sameLine, JSON.stringify(icon));
      // The shared menu button alias (components.css) once won the cascade and turned the icon into a borderless padded chip.
      ok(engine, `${tag}: the ⓘ icon is a bordered circle, not a menu-button chip`,
         icon.border === '1px' && icon.radius === '50%' && icon.pad === '0px' && icon.tt === 'none', JSON.stringify(icon));

      let s = await bubble(page);
      ok(engine, `${tag}: hidden at rest`, !s.shown, JSON.stringify(s));
      ok(engine, `${tag}: it lists the supported sites`, /Supported deck links:.*SWUDB.*melee\.gg.*SW-Unlimited-DB/.test(s.text), s.text);

      if (width === 1400) {
        await page.hover('#deck-link-sites-btn'); await settle(page);
        s = await bubble(page);
        ok(engine, `${tag}: hover opens it`, s.shown, JSON.stringify(s));
        await page.mouse.move(5, 5); await settle(page);
        ok(engine, `${tag}: moving away closes it`, !(await bubble(page)).shown);

        // Safari's default Tab order skips buttons; a keyboard user reaches one with Option+Tab, so WebKit is driven that way.
        const back = engine === 'webkit' ? 'Alt+Shift+Tab' : 'Shift+Tab';
        await page.focus('#deck-link');
        await page.keyboard.press(back); await settle(page);
        const focused = await page.evaluate(() => document.activeElement && document.activeElement.id);
        s = await bubble(page);
        ok(engine, `${tag}: keyboard focus (Shift+Tab from the input; Option+Shift+Tab on WebKit) opens it`, focused === 'deck-link-sites-btn' && s.shown, `${focused} ${JSON.stringify(s)}`);
        await page.keyboard.press(engine === 'webkit' ? 'Alt+Tab' : 'Tab'); await settle(page);
        ok(engine, `${tag}: tabbing away closes it`, !(await bubble(page)).shown);
      }

      // Tap / click: the path a phone and Safari use.
      await page.click('#deck-link-sites-btn'); await page.mouse.move(5, 5); await settle(page);
      s = await bubble(page);
      ok(engine, `${tag}: a click opens it and it stays open`, s.shown && s.expanded === 'true', JSON.stringify(s));
      ok(engine, `${tag}: the open bubble fits inside the card`, s.inCard, s.box);
      ok(engine, `${tag}: the open bubble fits inside the viewport`, s.inViewport, s.box);
      await page.$eval('.swu-queue-card', el => el.scrollIntoView());
      await page.screenshot({ path: `${OUT}/deck-link-tip-${engine}-${width}.png` });
      await page.click('.swu-queue-card h2'); await settle(page);
      s = await bubble(page);
      ok(engine, `${tag}: a click elsewhere closes it`, !s.shown && s.expanded === 'false', JSON.stringify(s));
      await page.click('#deck-link-sites-btn'); await page.mouse.move(5, 5);
      await page.keyboard.press('Escape'); await page.evaluate(() => document.activeElement && document.activeElement.blur()); await settle(page);
      ok(engine, `${tag}: Escape closes it`, !(await bubble(page)).shown);
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
