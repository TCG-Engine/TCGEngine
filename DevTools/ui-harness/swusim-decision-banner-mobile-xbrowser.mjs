// SWUSim fixed decision BANNERS must stay on screen at phone width — Chromium + Firefox + WebKit.
//
// Reported 2026-09-25 (game 1310334, 3-seat Twin Suns): ASH_220 Remnant Lookouts asked "Look at which
// opponent's hand?" and the picker's buttons ran off the right edge of the phone, leaving the prompt
// unanswerable. .optchoose-banner is a centered fixed flex row whose children are both flex-shrink:0
// with no flex-wrap, so anything wider than its max-width overflows BOTH edges symmetrically.
// Core/NumberChooseUI.js's .numchoose-banner is the same shape (and had no max-width at all).
//
// The schema suite never renders the page, so this is the only evidence for the fix. Spec:
// SWUSim/Tests/Visual/DecisionBanners_MobileFit.md
//
// Usage: node swusim-decision-banner-mobile-xbrowser.mjs [BASE] [SHOTS_DIR]
//        ENGINES=chromium,firefox,webkit (default: all three)
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS  = process.argv[3] || '/tmp';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/DecisionBanners_MobileFit.md'), 'utf8');

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

const LOGIN_USER = 'claudebot1';

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', LOGIN_USER);
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

// Render one banner and measure it. Returns the banner box, each button's box, and a hit test.
// ⚠ The hit test is the load-bearing half: a button can be inside the viewport and still be covered.
async function probeBanner(page, kind) {
  return page.evaluate(async (kind) => {
    // An <img> that has not decoded yet has NO intrinsic width, and `flex: 0 0 auto` then collapses it
    // to its borders (~2px). Measuring before the art lands reports a layout bug that does not exist,
    // so wait for it — but bounded, so a genuinely broken src still fails loudly rather than hanging.
    const settle = async (root) => {
      const imgs = Array.from(root.querySelectorAll('img'));
      await Promise.all(imgs.map((im) => im.complete ? null : new Promise((res) => {
        const done = () => res(); im.addEventListener('load', done, { once: true });
        im.addEventListener('error', done, { once: true }); setTimeout(done, 4000);
      })));
    };
    // Realistic names, not seat tokens — the picker humanises P<n> and usernames are what makes the
    // row wide. These are the widths that actually ship.
    window.SWU_SEAT_USERNAMES = { '2': 'claudebot2', '3': 'ninin', '4': 'Drixx' };
    if (typeof window.HideOptionChooseUI === 'function') window.HideOptionChooseUI();
    if (typeof window.HideNumberChooseUI === 'function') window.HideNumberChooseUI();

    let sel;
    if (kind === 'optchoose') {
      if (typeof window.ShowOptionChooseUI !== 'function') return { missing: 'ShowOptionChooseUI' };
      window.ShowOptionChooseUI('P2&P3&P4', "Look at which opponent's hand?", 7, function () {});
      sel = '.optchoose-banner';
    } else if (kind === 'optchoose-cards') {
      // The SAME banner in its OTHER shape: a leading "@CardID" renders the card being acted on beside
      // the prompt (LAW_125 Watchful, LAW_242 Improvise, the JTL_041 Annihilator reveal). This is the
      // branch the mobile fix did not target but DID change (.optchoose-card shrinks, the row wraps), so
      // it gets its own rows rather than being assumed safe.
      if (typeof window.ShowOptionChooseUI !== 'function') return { missing: 'ShowOptionChooseUI' };
      window.ShowOptionChooseUI('@SOR_046&Play&Discard&Leave', 'Play the top card of your deck?', 7, function () {});
      sel = '.optchoose-banner';
    } else {
      if (typeof window.ShowNumberChooseUI !== 'function') return { missing: 'ShowNumberChooseUI' };
      window.ShowNumberChooseUI('0|12', 'How much damage do you want to deal to that unit?', 7, function () {});
      sel = '.numchoose-banner';
    }

    const el = document.querySelector(sel);
    if (!el) return { missing: sel };
    await settle(el);
    const vw = window.innerWidth, vh = window.innerHeight;
    const r = el.getBoundingClientRect();
    const btns = Array.from(el.querySelectorAll('button')).map((b) => {
      const bb = b.getBoundingClientRect();
      const cx = bb.left + bb.width / 2, cy = bb.top + bb.height / 2;
      const hit = (cx >= 0 && cy >= 0 && cx <= vw && cy <= vh) ? document.elementFromPoint(cx, cy) : null;
      return {
        text: (b.textContent || '').trim(),
        left: bb.left, right: bb.right, top: bb.top, bottom: bb.bottom,
        inView: bb.left >= -0.5 && bb.right <= vw + 0.5 && bb.top >= -0.5 && bb.bottom <= vh + 0.5,
        // true when the point hits the button itself or something inside it (a pseudo-element skin)
        hittable: !!hit && (hit === b || b.contains(hit)),
      };
    });
    // The card strip, when this banner is showing the card being acted on. A layout fix must not
    // shrink it to nothing — "fits on screen" is trivially satisfiable by rendering no art at all.
    const cardEls = Array.from(el.querySelectorAll('.optchoose-card'));
    const cards = cardEls.map((c) => {
      const cb = c.getBoundingClientRect();
      return {
        w: cb.width, h: cb.height,
        loaded: c.complete === true && c.naturalWidth > 0,
        inView: cb.left >= -0.5 && cb.right <= vw + 0.5 && cb.top >= -0.5 && cb.bottom <= vh + 0.5,
      };
    });
    return {
      vw, vh,
      left: r.left, right: r.right, top: r.top, bottom: r.bottom, width: r.width, height: r.height,
      inView: r.left >= -0.5 && r.right <= vw + 0.5 && r.top >= -0.5 && r.bottom <= vh + 0.5,
      btns, cards,
    };
  }, kind);
}

function assertBanner(tag, kind, m, { expectFit }) {
  if (m.missing) { ok(`${tag}: ${kind} rendered`, false, 'missing ' + m.missing); return; }
  ok(`${tag}: ${kind} rendered`, m.width > 0 && m.btns.length > 0, `${Math.round(m.width)}px, ${m.btns.length} buttons`);
  if (!expectFit) return;   // desktop-only sanity rows are reported below by the caller
  ok(`${tag}: ${kind} banner within viewport`, m.inView,
     `left=${Math.round(m.left)} right=${Math.round(m.right)} vw=${m.vw}`);
  const out = m.btns.filter((b) => !b.inView);
  ok(`${tag}: ${kind} every button within viewport`, out.length === 0,
     out.length ? out.map((b) => `"${b.text}" ${Math.round(b.left)}..${Math.round(b.right)} (vw=${m.vw})`).join('; ') : `${m.btns.length} buttons`);
  const dead = m.btns.filter((b) => !b.hittable);
  ok(`${tag}: ${kind} every button hit-testable`, dead.length === 0,
     dead.length ? dead.map((b) => `"${b.text}"`).join('; ') : 'all clickable');
  if (kind === 'optchoose-cards') {
    // ⚠ Without this, "fits on screen" is satisfiable by rendering no card at all.
    ok(`${tag}: ${kind} card art still rendered at a usable size`,
       m.cards.length > 0 && m.cards.every((c) => c.w > 40 && c.h > 60 && c.inView),
       m.cards.length ? m.cards.map((c) => `${Math.round(c.w)}x${Math.round(c.h)} loaded=${c.loaded} inView=${c.inView}`).join('; ') : 'no .optchoose-card');
  }
}

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    for (const layout of ['mobile', 'desktop']) {
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
      await page.waitForTimeout(2500);

      for (const kind of ['optchoose', 'optchoose-cards', 'numchoose']) {
        const m = await probeBanner(page, kind);
        // Both layouts must FIT — a 1600px desktop trivially does, which makes it the negative control:
        // if the fix ever broke desktop centering these same rows would catch it.
        assertBanner(tag, kind, m, { expectFit: true });
        if (!m.missing) {
          await page.screenshot({ path: `${SHOTS}/swusim-banner-${kind}-${engineName}-${layout}.png` }).catch(() => {});
        }
      }
      await ctx.close();
    }
  } finally {
    await browser.close();
  }
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES');
process.exit(allOk ? 0 : 1);
