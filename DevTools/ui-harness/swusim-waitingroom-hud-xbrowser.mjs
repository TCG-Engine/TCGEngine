// Cross-browser check: the SWUSim waiting room in the "New Petranaki HUD" style (owner, 2026-09-21).
// Visual case: SWUSim/Tests/Visual/WaitingRoom_NewPetranakiHud.md
//
// Builds REAL rooms (a guest host creates a private Twin Suns room from the menu; guests join by the invite link) and
// asserts the style actually won the cascade — the shared renderer (SharedUI/Render/WaitingRoom.php, also FaBSim's)
// prints its own <style> AFTER the site CSS, so a rule that merely exists proves nothing:
//   the panel is glass (unpainted element, chamfer-clipped ::before with the two gold corner glows);
//   the title is spaced uppercase; your seat has the gold rim, an empty seat is dashed; the deck field is a sunken well;
//   buttons carry whole-px 2px rims; Start is the gold primary once 3 players are in, and plain when disabled;
//   no horizontal overflow at desktop or phone width.
// Usage: node swusim-waitingroom-hud-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const OUT = process.env.OUT || '/tmp';
const DECK = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/twinsuns_deck_a.txt', import.meta.url), 'utf8');
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.fromEntries(Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n)));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

async function room(browser, width, joiners) {
  const host = await (await browser.newContext({ viewport: { width, height: width > 500 ? 900 : 844 } })).newPage();
  await host.goto(BASE + 'SharedUI/Sites/SWUSim/MainMenu.php', { waitUntil: 'load' });
  await host.selectOption('#swu-gametype-select', 'twinsuns'); await host.selectOption('#swu-second-select', 'ffa');
  await host.evaluate(() => switchDeckTab('text')); await host.fill('#deck-text', DECK);
  await Promise.all([host.waitForURL(/WaitingRoom/, { timeout: 30000 }), host.click('#create-private-game-btn')]);
  await host.waitForFunction(() => /[0-9a-f]{16,}/i.test((document.getElementById('wr-invite') || {}).textContent || ''), null, { timeout: 15000 });
  const invite = await host.evaluate(() => document.getElementById('wr-invite').textContent.match(/[0-9a-f]{16,}/i)[0]);
  for (let j = 0; j < joiners; j++) {
    const g = await (await browser.newContext()).newPage();
    await g.goto(BASE + 'SharedUI/Sites/SWUSim/MainMenu.php?privateInvite=' + invite, { waitUntil: 'load' });
    await g.waitForTimeout(1000);
    await g.evaluate(() => switchDeckTab('text')); await g.fill('#deck-text', DECK);
    await Promise.all([g.waitForURL(/WaitingRoom/, { timeout: 30000 }).catch(() => {}), g.click('#join-private-invite-btn')]);
  }
  await host.waitForTimeout(2500);   // two 1.5s polls: every seat rendered
  return host;
}

const probe = (page) => page.evaluate(() => {
  const cs = (el, pseudo) => el ? getComputedStyle(el, pseudo || null) : null;
  const panel = document.querySelector('.row-wrapper > .card.wr-panel');
  const glass = cs(panel, '::before');
  const mine = document.querySelector('#wr-root .wr-seat-mine');
  const empty = document.querySelector('#wr-root .wr-seat-empty');
  const start = document.getElementById('wr-start');
  const anyBtn = document.querySelector('#wr-root .btn');
  return {
    panelBg: cs(panel).backgroundColor, panelBlur: cs(panel).backdropFilter || cs(panel).webkitBackdropFilter || 'none',
    glassClip: glass.clipPath, glassGlows: (glass.backgroundImage.match(/url\("data:/g) || []).length,
    title: cs(document.getElementById('wr-title')).textTransform,
    mineBorder: mine ? cs(mine).borderTopColor : null,
    emptyStyle: empty ? cs(empty).borderTopStyle : null,
    deckBg: cs(document.getElementById('wr-deck-input')).backgroundColor,
    rim: anyBtn ? cs(anyBtn).getPropertyValue('--btn-rim-width').trim() : null,
    start: start ? { disabled: start.disabled, color: cs(start).color } : null,
    overflow: document.documentElement.scrollWidth - document.documentElement.clientWidth,
  };
});

for (const [engine, driver] of Object.entries(ENGINES)) {
  let browser;
  try {
    browser = await driver.launch();
    for (const width of [1728, 390]) {
      const tag = `${width}px`;
      // 1 player: Start disabled. 3 players: Start enabled.
      for (const joiners of [0, 2]) {
        const page = await room(browser, width, joiners);
        const s = await probe(page);
        const n = joiners + 1;
        if (joiners === 0) {
          ok(engine, `${tag}: the panel element is unpainted (the glass is ::before)`, /rgba\(0, 0, 0, 0\)|transparent/.test(s.panelBg) && s.panelBlur === 'none', `${s.panelBg} ${s.panelBlur}`);
          ok(engine, `${tag}: the glass is chamfer-clipped`, /polygon/.test(s.glassClip || ''), s.glassClip);
          ok(engine, `${tag}: the glass carries both gold corner glows`, s.glassGlows === 2, String(s.glassGlows));
          ok(engine, `${tag}: the title is spaced uppercase`, s.title === 'uppercase', s.title);
          ok(engine, `${tag}: your seat has the gold rim`, /rgba\(233, 184, 102/.test(s.mineBorder || ''), s.mineBorder);
          ok(engine, `${tag}: an empty seat is dashed`, s.emptyStyle === 'dashed', s.emptyStyle);
          ok(engine, `${tag}: the deck field is a sunken well`, /rgba\(14, 17, 22, 0\.58\)/.test(s.deckBg || ''), s.deckBg);
          ok(engine, `${tag}: buttons use whole-px 2px rims`, s.rim === '2px', s.rim);
          ok(engine, `${tag}: with ${n} player Start is disabled and not gold`, s.start && s.start.disabled && !/rgb\(255, 217, 152\)/.test(s.start.color), JSON.stringify(s.start));
        } else {
          ok(engine, `${tag}: with ${n} players Start is enabled and gold`, s.start && !s.start.disabled && /rgb\(255, 217, 152\)/.test(s.start.color), JSON.stringify(s.start));
          await page.screenshot({ path: `${OUT}/waitingroom-hud-${engine}-${width}.png`, fullPage: true });
        }
        ok(engine, `${tag} (${n}p): no horizontal overflow`, s.overflow <= 0, String(s.overflow));
        await page.context().close();
      }
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
