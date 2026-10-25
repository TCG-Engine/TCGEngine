// OPTIONCHOOSE over CardIDs renders each option as its CARD with a button under it — Chromium + Firefox + WebKit.
//
// Owner request 2026-10-19: SEC_017 Sabé's "Discard 1 of the top 2 (other stays on top)" showed two text buttons
// ("SHD 043" / "LOF 093"). Core/OptionChooseUI.js now draws each CardID option as its card with a DISCARD button
// beneath it; one click answers. UI ONLY: each button submits the same raw CardID the text button did.
//
// The schema suite never renders the page, so this is the only evidence. Spec + fixture:
// SWUSim/Tests/Visual/SabePeek_CardChoice.md
//
// Usage: node swusim-optchoose-card-choice-xbrowser.mjs [BASE] [SHOTS_DIR]
//        ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS  = process.argv[3] || '/tmp';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/SabePeek_CardChoice.md'), 'utf8');
// The state AFTER clicking DISCARD under LAW_075: only the EXPECT section is evaluated (read-only).
const AFTER  = SCHEMA.replace(/## EXPECT[\s\S]*$/, '## EXPECT\nP1NODECISION\nP2DECKCOUNT:3\nP2DISCARDCOUNT:1\nP2DISCARDUNIT:0:CARDID:LAW_075\nP2DECKTOPCARD:TWI_110\n');

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

async function buildGame(page) {
  const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  for (const step of setup.whenSteps) {
    const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
      { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
    if (r.error) throw new Error(`step "${step.raw}": ${r.error}`);
  }
  return setup.gameName;
}

// Measure the live banner: tiles, card art, each button's box + whether it is topmost at its centre.
async function probe(page) {
  return page.evaluate(async () => {
    const el = document.querySelector('.optchoose-banner');
    if (!el) return { missing: '.optchoose-banner' };
    // An undecoded <img> has no intrinsic width — wait (bounded) before measuring, or a layout bug is invented.
    await Promise.all(Array.from(el.querySelectorAll('img')).map((im) => im.complete ? null : new Promise((res) => {
      const done = () => res(); im.addEventListener('load', done, { once: true });
      im.addEventListener('error', done, { once: true }); setTimeout(done, 4000);
    })));
    // ⚠ A page load flashes the gamestate's empty flash placeholder ("-") in a full-screen z-index 10000 overlay
    // for ~750ms (generated showFlashMessage). It sits above EVERY banner, so a hit test inside that window
    // fails for a reason that has nothing to do with this layout. Wait for it to go (bounded).
    for (let i = 0; i < 40; i++) {
      const flash = Array.from(document.body.children).some((e) => getComputedStyle(e).zIndex === '10000' && e !== el);
      if (!flash) break;
      await new Promise((r) => setTimeout(r, 100));
    }
    const vw = window.innerWidth, vh = window.innerHeight;
    const inView = (b) => b.left >= -0.5 && b.right <= vw + 0.5 && b.top >= -0.5 && b.bottom <= vh + 0.5;
    const tiles = Array.from(el.querySelectorAll('.optchoose-choice')).map((t) => {
      const img = t.querySelector('img.optchoose-choice-card');
      const btn = t.querySelector('button');
      const ib = img ? img.getBoundingClientRect() : null;
      const bb = btn ? btn.getBoundingClientRect() : null;
      const cx = bb ? bb.left + bb.width / 2 : -1, cy = bb ? bb.top + bb.height / 2 : -1;
      const hit = (bb && cx >= 0 && cy >= 0 && cx <= vw && cy <= vh) ? document.elementFromPoint(cx, cy) : null;
      return {
        option: btn ? btn.getAttribute('data-option') : null,
        text: btn ? (btn.textContent || '').trim() : null,
        shown: btn ? getComputedStyle(btn).textTransform : null,
        img: ib ? { w: ib.width, h: ib.height, left: ib.left, right: ib.right, top: ib.top, bottom: ib.bottom,
                    loaded: img.complete && img.naturalWidth > 0, src: img.getAttribute('src'), inView: inView(ib) } : null,
        btn: bb ? { left: bb.left, right: bb.right, top: bb.top, bottom: bb.bottom, inView: inView(bb) } : null,
        hittable: !!hit && (hit === btn || btn.contains(hit)),
      };
    });
    const label = el.querySelector('.optchoose-label');
    return {
      vw, vh,
      label: label ? label.textContent : null,
      allButtons: el.querySelectorAll('button').length,
      tiles,
      banner: (() => { const r = el.getBoundingClientRect(); return { left: r.left, right: r.right, top: r.top, bottom: r.bottom, inView: inView(r) }; })(),
    };
  });
}

// The OTHER OPTIONCHOOSE shapes must render as before: text buttons, and the "@CardID" art beside them.
async function controls(page) {
  return page.evaluate(() => {
    const shape = (param) => {
      window.ShowOptionChooseUI(param, 'Choose', 7, function () {});
      const el = document.querySelector('.optchoose-banner');
      const out = {
        choiceTiles: el.querySelectorAll('.optchoose-choice').length,
        artCards: el.querySelectorAll('.optchoose-card').length,
        buttons: Array.from(el.querySelectorAll('button')).map((b) => b.getAttribute('data-option')),
      };
      window.HideOptionChooseUI();
      return out;
    };
    return { arena: shape('Ground&Space'), art: shape('@SOR_046&Play&Discard&Leave'), players: shape('P2&P3') };
  });
}

for (const [engineName, engine] of ENGINES) {
  let browser;
  try { browser = await engine.launch(); }
  catch (e) { ok(`${engineName}: browser launched`, false, String(e.message).split('\n')[0]); continue; }
  try {
    for (const layout of ['desktop', 'mobile']) {
      const tag = `${engineName}/${layout}`;
      const ctx = await browser.newContext(layout === 'mobile'
        ? { viewport: { width: 390, height: 844 } }
        : { viewport: { width: 1600, height: 900 } });
      const page = await ctx.newPage();
      await login(page);
      let gameName;
      try { gameName = await buildGame(page); }
      catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema`
        + `&viewerPerspective=1&opponentID=2${layout === 'mobile' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'load' });
      await page.waitForSelector('.optchoose-choice-btn', { timeout: 10000 }).catch(() => {});

      const m = await probe(page);
      await page.screenshot({ path: `${SHOTS}/swusim-optchoose-cardchoice-${engineName}-${layout}.png` }).catch(() => {});
      if (m.missing) { ok(`${tag}: Sabé peek banner rendered`, false, 'missing ' + m.missing); await ctx.close(); continue; }
      ok(`${tag}: prompt text kept`, /Discard 1 of the top 2/.test(m.label || ''), m.label);
      ok(`${tag}: one tile per option, in deck order`, m.tiles.map((t) => t.option).join(',') === 'LAW_075,TWI_110',
         m.tiles.map((t) => t.option).join(','));
      ok(`${tag}: the ONLY buttons are the tiles' (no leftover text buttons)`, m.allButtons === m.tiles.length, `${m.allButtons} buttons`);
      for (const t of m.tiles) {
        const id = t.option;
        ok(`${tag}: ${id} button reads Discard (shown uppercase)`, t.text === 'Discard', `${t.text} / ${t.shown}`);
        ok(`${tag}: ${id} card art decoded at a usable size, on screen`,
           !!t.img && t.img.loaded && t.img.w > 60 && t.img.h > 90 && t.img.inView,
           t.img ? `${Math.round(t.img.w)}x${Math.round(t.img.h)} loaded=${t.img.loaded} ${t.img.src}` : 'no img');
        // The WHOLE card (rules text included), not the square board crop from concat/.
        ok(`${tag}: ${id} shows the full portrait card`,
           !!t.img && /\/WebpImages\//.test(t.img.src || '') && t.img.h > t.img.w * 1.2,
           t.img ? `${Math.round(t.img.w)}x${Math.round(t.img.h)} ${t.img.src}` : 'no img');
        ok(`${tag}: ${id} button sits UNDER its own card`,
           !!t.img && !!t.btn && t.btn.top >= t.img.bottom - 0.5 && t.btn.left < t.img.right && t.btn.right > t.img.left,
           t.btn && t.img ? `img bottom ${Math.round(t.img.bottom)}, btn ${Math.round(t.btn.top)}..${Math.round(t.btn.bottom)}` : '');
        ok(`${tag}: ${id} button on screen and topmost at its centre`, !!t.btn && t.btn.inView && t.hittable,
           t.btn ? `${Math.round(t.btn.left)}..${Math.round(t.btn.right)} x ${Math.round(t.btn.top)}..${Math.round(t.btn.bottom)} (vw=${m.vw} vh=${m.vh})` : '');
      }
      // Both choices visible at once, side by side, so the player compares them without scrolling.
      ok(`${tag}: the two cards sit side by side`,
         m.tiles.length === 2 && !!m.tiles[0].img && !!m.tiles[1].img
           && Math.abs(m.tiles[0].img.top - m.tiles[1].img.top) < 1 && m.tiles[0].img.right <= m.tiles[1].img.left,
         m.tiles.map((t) => t.img ? `${Math.round(t.img.left)}..${Math.round(t.img.right)}@${Math.round(t.img.top)}` : '-').join(' | '));
      ok(`${tag}: banner within viewport`, m.banner.inView,
         `${Math.round(m.banner.left)}..${Math.round(m.banner.right)} x ${Math.round(m.banner.top)}..${Math.round(m.banner.bottom)}`);

      // One click answers: discard LAW_075, TWI_110 stays on top.
      // Any banner button carrying the option — so a broken card view reports its FAIL rows instead of hanging.
      const clicked = await page.click('.optchoose-banner button[data-option="LAW_075"]', { timeout: 5000 })
        .then(() => true, (e) => String(e.message).split('\n')[0]);
      ok(`${tag}: clicked the LAW_075 button`, clicked === true, clicked === true ? '' : clicked);
      await page.waitForTimeout(1500);
      const gone = await page.evaluate(() => !document.querySelector('.optchoose-choice'));
      ok(`${tag}: banner closes on the click (no confirm step)`, gone);
      const exp = await (await page.request.post(BASE + 'SWUSim/TestSchemaExpect.php',
        { multipart: { gameName: String(gameName), schema: AFTER } })).json();
      ok(`${tag}: server: LAW_075 discarded, TWI_110 still on top, decision answered`, exp.passed === true,
         exp.error || (exp.failures || []).join(' | ') || `${exp.total} checks`);

      const c = await controls(page);
      ok(`${tag}: control "Ground&Space" still text buttons`, c.arena.choiceTiles === 0 && c.arena.buttons.join(',') === 'Ground,Space', JSON.stringify(c.arena));
      ok(`${tag}: control "@CardID&Play&…" still art + text buttons`, c.art.choiceTiles === 0 && c.art.artCards === 1 && c.art.buttons.join(',') === 'Play,Discard,Leave', JSON.stringify(c.art));
      ok(`${tag}: control "P2&P3" still text buttons`, c.players.choiceTiles === 0 && c.players.buttons.length === 2, JSON.stringify(c.players));
      await ctx.close();
    }
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
