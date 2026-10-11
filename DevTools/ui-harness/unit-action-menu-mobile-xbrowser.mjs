// The Attack / Ability chooser for a unit that has an Action must be usable on the PHONE layout.
//
// Reported 2026-09-17 (game 505707): "can't attack with Poe on mobile — the ability/attack menu doesn't show".
// Tapping a ready unit with an Action (.unit-action) opens #swuUnitActionMenu (GameLayoutShared.php). Its CSS lived
// only in the DESKTOP layout (GameLayout.php), and GameLayoutMobile.php never loads that file, so on a phone the
// menu rendered unstyled: position static at the top-left of <body>, under the fixed top control band, and both
// buttons were covered.
//
// Fixture (fixtures/unit-action-menu-mobile.json): P1's Bail Organa (SOR_094, "Action [Exhaust]") ready beside
// Consular Security Force, P2 has one unit. Imported as a replay with no actions, so nothing mutates.
//
// Checks, for an iPhone-sized touch device in each engine (plus a desktop control):
//   - the menu is position:fixed, inside the viewport, and each button is the topmost element at its centre;
//   - tapping Attack closes the menu and sends the unit's normal attack request (mode 10002, "<mzid>!FSM!").
//
// Usage: node unit-action-menu-mobile-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit, devices } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const FIXTURE = JSON.parse(fs.readFileSync(new URL('./fixtures/unit-action-menu-mobile.json', import.meta.url), 'utf8'));
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };

async function importFixture() {
  const res = await fetch(BASE + 'APIs/MatchReplay.php?action=import', {
    method: 'POST', headers: { 'Content-Type': 'application/json' }, body: JSON.stringify({ replay: FIXTURE }),
  });
  const j = await res.json();
  if (!j.success) throw new Error('import failed: ' + JSON.stringify(j));
  return j;
}

const menuState = (page) => page.evaluate(() => {
  const menu = document.getElementById('swuUnitActionMenu');
  if (!menu) return null;
  const cs = getComputedStyle(menu);
  const r = menu.getBoundingClientRect();
  const buttons = [...menu.querySelectorAll('button')].map((b) => {
    const br = b.getBoundingClientRect();
    const top = document.elementFromPoint(br.x + br.width / 2, br.y + br.height / 2);
    return { text: b.textContent.trim(), rect: [br.x, br.y, br.width, br.height], topmost: !!top && (top === b || b.contains(top)) };
  });
  return { position: cs.position, rect: [r.x, r.y, r.width, r.height], vw: innerWidth, vh: innerHeight, buttons };
});

// Chromium and WebKit take the iPhone descriptor (touch + mobile UA). Firefox has no isMobile support in
// Playwright, so it gets the same viewport and a phone user agent and is driven by mouse clicks; the phone
// layout is forced with swuLayout=mobile either way.
function contextOptions(name, layout) {
  if (layout === 'desktop') return { viewport: { width: 1400, height: 900 } };
  const phone = devices['iPhone 13'];
  if (name === 'firefox') return { viewport: phone.viewport, userAgent: phone.userAgent };
  return { ...phone };
}

async function press(page, name, layout, x, y) {
  if (layout === 'mobile' && name !== 'firefox') await page.touchscreen.tap(x, y);
  else await page.mouse.click(x, y);
}

for (const [name, launcher] of ENGINES) {
  for (const layout of ['mobile', 'desktop']) {
    const label = `${name}/${layout}`;
    let browser;
    try {
      browser = await launcher.launch();
      const context = await browser.newContext(contextOptions(name, layout));
      const page = await context.newPage();
      const imp = await importFixture();
      const url = new URL(BASE + imp.nextTurnUrl.replace(/^\.\//, ''));
      url.searchParams.set('swuLayout', layout);
      await page.goto(url.toString(), { waitUntil: 'load' });
      await page.waitForFunction(() => document.querySelector('[id="myGroundArena-0"].unit-action'), null, { timeout: 15000 });
      // The .unit-action glow is applied before the shared layout wires its click handler; a tap in that gap
      // falls through to the plain unit click and no menu opens. Let the board settle first.
      await page.waitForTimeout(3000);

      const box = await page.locator('[id="myGroundArena-0"]').boundingBox();
      await press(page, name, layout, box.x + box.width / 2, box.y + box.height / 2);
      await page.waitForTimeout(800);

      const m = await menuState(page);
      ok(label, 'tapping a unit with an Action opens the Attack/Ability menu', m !== null);
      if (m) {
        ok(label, 'the menu is position:fixed', m.position === 'fixed', m.position);
        const [x, y, w, h] = m.rect;
        ok(label, 'the menu is inside the viewport', x >= 0 && y >= 0 && x + w <= m.vw && y + h <= m.vh, JSON.stringify(m.rect));
        ok(label, 'both buttons are shown', m.buttons.map((b) => b.text).join('|') === 'Attack|Ability', m.buttons.map((b) => b.text).join('|'));
        for (const b of m.buttons) ok(label, `the ${b.text} button is not covered by anything`, b.topmost, JSON.stringify(b.rect));
        await page.screenshot({ path: `/tmp/unit-action-menu-${name}-${layout}.png` });

        const attack = m.buttons.find((b) => b.text === 'Attack');
        if (attack && attack.topmost) {
          // Attack hands off to the normal unit-click flow: ProcessInput mode 10002 with "<mzid>!FSM!". The
          // imported board runs in replay mode, which does not draw the follow-up target prompt, so the request
          // is the observable contract here.
          const sent = [];
          page.on('request', (r) => { if (r.url().includes('ProcessInput.php')) sent.push(decodeURIComponent(r.url())); });
          const [ax, ay, aw, ah] = attack.rect;
          await press(page, name, layout, ax + aw / 2, ay + ah / 2);
          await page.waitForTimeout(1500);
          ok(label, 'tapping Attack closes the menu', await page.evaluate(() => !document.getElementById('swuUnitActionMenu')));
          ok(label, "tapping Attack sends the unit's attack request",
            sent.some((u) => u.includes('mode=10002') && u.includes('cardID=myGroundArena-0!FSM!')), sent.join(' | '));
        }
      }
    } catch (e) {
      ok(label, 'engine ran', false, String(e).slice(0, 240));
    } finally {
      if (browser) await browser.close();
    }
  }
}
for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(16)} ${n}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
