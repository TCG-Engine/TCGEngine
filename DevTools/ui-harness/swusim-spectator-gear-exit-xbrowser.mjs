// A spectator's gear menu has a way back to the Main Menu, in all three engines, desktop and phone.
//
// Owner, 2026-10-13: "the swusim spectator view in-game menu doesn't appear to have a 'return to main
// menu' button". The gear menu's Match section (Concede / Return to Main Menu) is player-only, so a
// spectator had no exit at all. GameLayoutShared.php now carries a spectator-only section.
//
// Checks, per engine and layout:
//   • spectator: exactly ONE visible "Return to Main Menu", on screen, and clicking it lands on the menu
//   • seated player: the spectator section stays hidden (they keep the Match section's own button)
//
//   node DevTools/ui-harness/swusim-spectator-gear-exit-xbrowser.mjs
//
// Needs the four-seat fixture:
//   docker exec -w /var/www/html/TCGEngine <container> php SWUSim/DevTools/make-fourseat-fixture.php --game=9932777
import { chromium, firefox, webkit } from 'playwright';
const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const GAME = process.env.GAME || '9932777';
const pageUrl = (pid, mobile) => BASE + 'NextTurn.php?folderPath=SWUSim&gameName=' + GAME + '&playerID=' + pid
  + (mobile ? '&swuLayout=mobile' : '');
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

// Every visible "Return to Main Menu" inside the open gear overlay.
const visibleExits = p => p.$$eval('#swuSettingsOverlay button', els => els
  .filter(e => (e.textContent || '').trim() === 'Return to Main Menu')
  .map(e => { const r = e.getBoundingClientRect(); return { w: r.width, h: r.height, x: r.x, y: r.y }; })
  .filter(r => r.w > 0 && r.h > 0));

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  for (const mobile of [false, true]) {
    const vp = mobile ? { width: 390, height: 844 } : { width: 1440, height: 1000 };
    const tag = `${name}/${mobile ? 'mobile' : 'desktop'}`;
    for (const pid of ['S', '1']) {
      const p = await b.newPage({ viewport: vp });
      const errs = []; p.on('pageerror', e => errs.push(e.message));
      await p.goto(pageUrl(pid, mobile), { waitUntil: 'domcontentloaded' });
      await p.waitForFunction(() => typeof window.swuOpenSettings === 'function', null, { timeout: 20000 });
      await p.evaluate(() => window.swuOpenSettings());
      await p.waitForSelector('#swuSettingsOverlay', { state: 'visible', timeout: 5000 });
      const exits = await visibleExits(p);
      const specShown = await p.$eval('#swuSettingsSpectatorSection', e => getComputedStyle(e).display !== 'none');
      if (pid === 'S') {
        if (exits.length !== 1) bad(tag, `spectator sees ${exits.length} Return to Main Menu buttons, want 1`); else ok();
        if (!specShown) bad(tag, 'spectator section hidden for a spectator'); else ok();
        if (exits[0]) {
          const e = exits[0];
          // The settings panel scrolls; on screen means inside the viewport once scrolled into view.
          await p.locator('#swuSettingsSpectatorSection button').scrollIntoViewIfNeeded();
          const r = await p.$eval('#swuSettingsSpectatorSection button', el => { const q = el.getBoundingClientRect(); return { x: q.x, y: q.y, w: q.width, h: q.height }; });
          if (r.x < 0 || r.y < 0 || r.x + r.w > vp.width || r.y + r.h > vp.height) bad(tag, `exit button off screen at (${Math.round(r.x)},${Math.round(r.y)}) ${Math.round(r.w)}x${Math.round(r.h)}`); else ok();
          if (e.w < 60 || e.h < 20) bad(tag, `exit button too small ${e.w}x${e.h}`); else ok();
          await Promise.all([
            p.waitForURL(/MainMenu\.php/, { timeout: 15000 }).catch(() => {}),
            p.click('#swuSettingsSpectatorSection button'),
          ]);
          if (!/MainMenu\.php/.test(p.url())) bad(tag, `click did not reach the main menu (at ${p.url()})`); else ok();
        }
        if (process.env.SHOTS && !/MainMenu/.test(p.url())) await p.screenshot({ path: `${process.env.SHOTS}/${name}-${mobile ? 'm' : 'd'}-spec.png` });
      } else {
        if (specShown) bad(tag, 'spectator section shown to seated player 1'); else ok();
        if (exits.length !== 1) bad(tag, `seat 1 sees ${exits.length} Return to Main Menu buttons, want 1`); else ok();
      }
      if (errs.length) bad(tag, `page errors (${pid}): ${errs.join(' | ')}`); else ok();
      await p.close();
    }
  }
  await b.close();
}
console.log(`${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
