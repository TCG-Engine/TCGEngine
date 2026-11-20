// Alternate Formats home tile — Chromium + Firefox + WebKit, desktop (1440x900) and phone (390x844).
// Spec: docs/superpowers/specs/2026-11-12-swusim-dualing-suns-design.md (Product). Plan Task 6.
//
// Checks, logged in as claudebot1:
//   • the home screen has an Alternate Formats tile and no standalone High Ground tile;
//   • its dialog offers High Ground and Dualing Suns;
//   • High Ground → the PvP setup in High Ground mode ("Create a High Ground Lobby");
//   • Dualing Suns → the Twin Suns setup in Dualing Suns mode: the blurb, the deck pickers, ONLY "Create a Dualing Suns
//     Room" (no Join Queue / Create Private Room / pool / arrangement);
//   • the Twin Suns tile afterwards is plain Twin Suns again (the mode does not stick);
//   • #setup-highground still deep-links to High Ground;
//   • (desktop) Create with a Twin Suns pre-con lands in a waiting room that offers "Fill Seat with Bot". The room is left
//     again at the end (LeaveQueue), so no stray lobby is kept.
// Saves screenshots — geometry cannot see a screenshot.
// Usage: node swusim-alternate-formats-xbrowser.mjs [BASE]   ENGINES=chromium,firefox,webkit   OUT=/tmp
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const MENU = BASE + 'SharedUI/Sites/SWUSim/MainMenu.php';
const OUT  = process.env.OUT || '/tmp';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const LAYOUTS = [['desktop', { width: 1440, height: 900 }], ['phone', { width: 390, height: 844 }]];

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

// Visible = in the layout, not display:none / [hidden], with a real box.
const shown = (page, sel) => page.evaluate((s) => {
  const el = document.querySelector(s);
  return !!el && !el.hidden && getComputedStyle(el).display !== 'none' && el.getBoundingClientRect().width > 0;
}, sel);
const openDialog = (page) => page.evaluate(() => {
  const d = document.querySelector('dialog.setup[open]');
  return d ? { id: d.id, mode: d.getAttribute('data-mode'), title: (d.querySelector('.setup__title') || {}).textContent } : null;
});
const closeDialogs = (page) => page.evaluate(() => document.querySelectorAll('dialog[open]').forEach((d) => d.close()));
const clickAct = async (page, act) => { await page.locator(`dialog.setup[open] button[data-act="${act}"]`).click(); await page.waitForTimeout(400); };

async function openAlternate(page, pick) {
  await page.locator('a.mode[href="#setup-alternate"]').scrollIntoViewIfNeeded();
  await page.locator('a.mode[href="#setup-alternate"]').click();
  await page.waitForTimeout(400);
  if (pick) { await page.locator(`#setup-alternate label[for="${pick}"]`).click(); await page.waitForTimeout(200); }
}

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    for (const [layout, viewport] of LAYOUTS) {
      const ctx = await browser.newContext({ viewport });
      const page = await ctx.newPage();
      const tag = `${engineName}/${layout}`;
      await login(page);
      await page.goto(MENU, { waitUntil: 'load' });
      await page.waitForTimeout(800);

      ok(`${tag}: Alternate Formats tile is present`, await shown(page, 'a.mode[href="#setup-alternate"]'));
      ok(`${tag}: no standalone High Ground tile`, (await page.locator('a.mode[href="#setup-highground"]').count()) === 0);
      // The title must sit inside its tile (owner, 2026-11-13: "ALTERNATE FORMATS" ran past the tile's right edge).
      const fit = await page.evaluate(() => {
        const a = document.querySelector('a.mode[href="#setup-alternate"]');
        const t = a && a.querySelector('.mode__title');
        if (!t) return null;
        const r = document.createRange(); r.selectNodeContents(t);
        return { text: t.textContent, textRight: r.getBoundingClientRect().right, tileRight: a.getBoundingClientRect().right };
      });
      ok(`${tag}: the tile title fits inside the tile`, fit && fit.text === 'Fun Formats' && fit.textRight <= fit.tileRight - 8, JSON.stringify(fit));

      await openAlternate(page, null);
      let d = await openDialog(page);
      ok(`${tag}: the tile opens Alternate Formats`, d && d.id === 'setup-alternate', JSON.stringify(d));
      const choices = await page.locator('#setup-alternate .seg__opt').allTextContents();
      ok(`${tag}: it offers High Ground and Dualing Suns`, choices.join('|') === 'High Ground|Dualing Suns', choices.join('|'));
      await page.screenshot({ path: `${OUT}/altformats-${engineName}-${layout}-dialog.png` });

      await clickAct(page, 'alternate');   // High Ground is the default pick
      d = await openDialog(page);
      ok(`${tag}: High Ground opens the PvP setup in High Ground mode`, d && d.id === 'setup-pvp' && d.mode === 'highground', JSON.stringify(d));
      ok(`${tag}: …offering Create a High Ground Lobby`, await shown(page, '#setup-pvp button[data-act="highground"]'));
      await closeDialogs(page);

      await openAlternate(page, 'alt-fmt-2');
      ok(`${tag}: the Dualing Suns note shows once picked`, await shown(page, '#setup-alternate [data-alt-note="setup-dualingsuns"]')
        && !(await shown(page, '#setup-alternate [data-alt-note="setup-highground"]')));
      await clickAct(page, 'alternate');
      d = await openDialog(page);
      ok(`${tag}: Dualing Suns opens the Twin Suns setup in its mode`, d && d.id === 'setup-twin-suns' && d.mode === 'dualingsuns' && d.title === 'Dualing Suns', JSON.stringify(d));
      ok(`${tag}: …with the rules blurb`, await shown(page, '#setup-twin-suns .ds-blurb'));
      ok(`${tag}: …the deck pickers and pre-cons`, await shown(page, '#ts-link') && (await page.locator('#setup-twin-suns input[name="ts-precon"]').count()) > 0);
      ok(`${tag}: …and only Create a Dualing Suns Room`, await shown(page, '#setup-twin-suns button[data-act="dualingsuns"]')
        && !(await shown(page, '#setup-twin-suns button[data-act="join"]')) && !(await shown(page, '#setup-twin-suns button[data-act="private"]'))
        && !(await shown(page, '#setup-twin-suns .setup__tools')) && !(await shown(page, '#setup-twin-suns .prow')));
      await page.screenshot({ path: `${OUT}/altformats-${engineName}-${layout}-dualingsuns.png` });
      await closeDialogs(page);

      await page.locator('a.mode[href="#setup-twin-suns"]').scrollIntoViewIfNeeded();
      await page.locator('a.mode[href="#setup-twin-suns"]').click();
      await page.waitForTimeout(400);
      d = await openDialog(page);
      ok(`${tag}: the Twin Suns tile is plain Twin Suns again`, d && d.id === 'setup-twin-suns' && d.mode === 'twinsuns' && d.title === 'Twin Suns'
        && await shown(page, '#setup-twin-suns button[data-act="join"]') && !(await shown(page, '#setup-twin-suns button[data-act="dualingsuns"]')), JSON.stringify(d));
      await closeDialogs(page);

      // Leave the page first: a goto that only changes the hash is a same-document navigation and never re-runs the
      // page's load-time deep-link handling.
      await page.goto('about:blank');
      await page.goto(MENU + '#setup-highground', { waitUntil: 'load' });
      await page.waitForTimeout(800);
      d = await openDialog(page);
      ok(`${tag}: #setup-highground still deep-links to High Ground`, d && d.id === 'setup-pvp' && d.mode === 'highground', JSON.stringify(d));
      await closeDialogs(page);

      if (layout === 'desktop') {
        await openAlternate(page, 'alt-fmt-2');
        await clickAct(page, 'alternate');
        await page.locator('#setup-twin-suns label[for="ts-ts1"]').click();
        await page.waitForTimeout(300);
        await Promise.all([page.waitForURL(/WaitingRoom\.php/, { timeout: 30000 }).catch(() => {}), clickAct(page, 'dualingsuns')]);
        const url = page.url();
        ok(`${tag}: Create lands in a waiting room`, /WaitingRoom\.php\?lobby=/.test(url), url);
        await page.waitForFunction(() => /fill seat with bot/i.test(document.body.innerText), null, { timeout: 15000 }).catch(() => {});
        ok(`${tag}: …that offers Fill Seat with Bot`, /fill seat with bot/i.test(await page.evaluate(() => document.body.innerText)));
        await page.screenshot({ path: `${OUT}/altformats-${engineName}-${layout}-waitingroom.png` });
        // Leave the room so the run keeps no stray lobby.
        const lobbyID = new URL(url).searchParams.get('lobby');
        if (lobbyID) {
          const authKey = await page.evaluate((id) => { try { return JSON.parse(localStorage.getItem('tcg:lobbyAuth:' + id) || '{}').authKey || ''; } catch (e) { return ''; } }, lobbyID);
          await page.request.post(BASE + 'APIs/Lobbies/LeaveQueue.php', { form: { rootName: 'SWUSim', playerID: '1', lobbyID, authKey } });
        }
      }
      await ctx.close();
    }
  } finally { await browser.close(); }
}

for (const [n, c, x] of results) console.log(`${c ? 'PASS' : 'FAIL'}: ${n}${x && !c ? '  ' + x : ''}`);
console.log(allOk ? 'ALL PASS' : 'FAILURES');
process.exit(allOk ? 0 : 1);
