// Face-down cards must not blink when the board updates — Chromium + Firefox + WebKit, desktop + phone.
// Visual case: SWUSim/Tests/Visual/CardBacks_NoFlickerOnRepaint.md
//
// Bug report P-4 (2026-10-23): "when doing stuff the screen flickers". Every board update rebuilds the
// card <img>s; face-down cards were born as the generic concat/CardBack.webp and ApplyCosmeticCardBacks
// swapped each src to the cosmetic back a frame later. WebKit painted every back BLANK for one frame on
// every update (measured: 8 backs × 1 frame × every repaint); Chromium and Firefox did not.
//
// The check repaints the board from the client's cached response (RenderUpdate(swuLastResponseArr) — the
// same entry point a server update and a Twin Suns view switch use) and, after each painted frame
// (rAF → setTimeout 0), counts visible card images that are not yet loaded. A POSITIVE CONTROL points
// each back at a missing URL after the repaint and must register blanks, so a sampler that cannot see a
// blank card fails loudly instead of passing. (An uncached-but-real URL is not enough: headless Firefox
// paints ~4×/s, so the reload finished before its first sample and the control raced.)
//
// Usage: node swusim-cardback-flicker-xbrowser.mjs [BASE]   ENGINES=chromium,firefox,webkit
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE   = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const here   = dirname(fileURLToPath(import.meta.url));
const SCHEMA = readFileSync(resolve(here, '../../SWUSim/Tests/Visual/CardBacks_NoFlickerOnRepaint.md'), 'utf8');
const REPAINTS = 20;

const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));

let allOk = true;
const results = [];
const ok = (name, cond, extra = '') => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function buildGame(request) {
  const setup = await (await request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema: SCHEMA } })).json();
  if (setup.error) throw new Error('setup: ' + setup.error);
  return setup.gameName;
}

// Repaint REPAINTS times; after each, sample 8 painted frames. control=true busts each back's URL.
const repaintAndSample = (page, control) => page.evaluate(async ({ reps, control }) => {
  const visible = (img) => { const r = img.getBoundingClientRect(); return r.width > 4 && r.height > 4 && r.bottom > 0 && r.right > 0 && r.top < innerHeight && r.left < innerWidth; };
  const postPaint = () => new Promise(res => requestAnimationFrame(() => setTimeout(res, 0)));
  let blankSamples = 0, blankFrames = 0;
  for (let r = 0; r < reps; r++) {
    RenderUpdate(window.swuLastResponseArr, window.__lastRenderedGameUpdate || 0);
    if (control) for (const b of document.querySelectorAll('img[data-cos-back]')) b.src = b.getAttribute('data-cos-back') + '.missing?bust=' + Math.random();
    for (let f = 0; f < 8; f++) {
      await postPaint();
      let blank = 0;
      for (const img of document.images) if (img.getAttribute('src') && visible(img) && (!img.complete || img.naturalWidth === 0)) blank++;
      if (blank) blankFrames++;
      blankSamples += blank;
    }
    await new Promise(res => setTimeout(res, 250));
  }
  return { blankSamples, blankFrames };
}, { reps: REPAINTS, control });

// Every visible face-down card shows its OWNER's cosmetic back, marked with data-cos-back.
const backState = (page) => page.evaluate(() => {
  const c = window.SWU_COSMETICS || {};
  const want = (owner) => (typeof SwuCosmeticCardBackFor === 'function' ? SwuCosmeticCardBackFor(owner) : '');
  const backs = [...document.querySelectorAll('span[data-mzid] img')].filter(i => /CardBack/.test(i.getAttribute('src') || ''));
  const wrong = backs.filter(i => {
    const owner = /^my/.test(i.closest('span[data-mzid]').getAttribute('data-mzid')) ? 'my' : 'their';
    return i.getAttribute('src') !== want(owner) || i.getAttribute('data-cos-back') !== want(owner);
  }).map(i => i.closest('span[data-mzid]').getAttribute('data-mzid') + '=' + i.getAttribute('src'));
  const pile = document.getElementById('swuTheirHandPileImg');
  return { count: backs.length, theirHand: backs.filter(i => /^theirHand-/.test(i.closest('span[data-mzid]').getAttribute('data-mzid'))).length,
           haveCosmetics: !!c.myCardBack, wrong,
           pileSrc: pile ? pile.getAttribute('src') : null, pileWant: want('their') };
});

for (const [engineName, engine] of ENGINES) {
  const browser = await engine.launch();
  try {
    for (const layout of ['desktop', 'mobile']) {
      const tag = `${engineName}/${layout}`;
      const ctx = await browser.newContext(layout === 'mobile'
        ? { viewport: { width: 390, height: 844 }, isMobile: engineName !== 'firefox', hasTouch: true }
        : { viewport: { width: 1600, height: 900 } });
      const page = await ctx.newPage();
      let gameName;
      try { gameName = await buildGame(ctx.request); }
      catch (e) { ok(`${tag}: board built`, false, e.message); await ctx.close(); continue; }
      await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${gameName}&playerID=1&authKey=testschema&viewerPerspective=1`
        + (layout === 'mobile' ? '&swuLayout=mobile' : ''), { waitUntil: 'load' });
      await page.waitForFunction(() => !!window.swuLastResponseArr && document.querySelectorAll('img[data-cos-back]').length > 0, null, { timeout: 20000 }).catch(() => {});
      await page.waitForTimeout(1500);

      const s = await backState(page);
      ok(`${tag}: the fixture renders the opponent's 4 face-down hand cards`, s.theirHand === 4, `${s.theirHand} (all backs: ${s.count})`);
      ok(`${tag}: cosmetics are configured (the path under test)`, s.haveCosmetics);
      ok(`${tag}: every back is its owner's cosmetic back from the render`, s.count > 0 && s.wrong.length === 0, s.wrong.slice(0, 3).join(' | '));
      if (layout === 'mobile') ok(`${tag}: the opponent's hand pile shows their back`, !!s.pileSrc && s.pileSrc === s.pileWant, `${s.pileSrc} vs ${s.pileWant}`);

      const run = await repaintAndSample(page, false);
      ok(`${tag}: no card is blank after any painted frame across ${REPAINTS} repaints`, run.blankSamples === 0, `${run.blankSamples} blank samples in ${run.blankFrames} frames`);
      const after = await backState(page);
      ok(`${tag}: backs still correct after the repaints`, after.count > 0 && after.wrong.length === 0, after.wrong.slice(0, 3).join(' | '));

      const control = await repaintAndSample(page, true);
      ok(`${tag}: CONTROL — a forced reload of the backs IS seen as blank (the sampler can fail)`, control.blankSamples > 0, `${control.blankSamples} blank samples`);
      await ctx.close();
    }
  } catch (e) {
    ok(`${engineName}: engine ran`, false, String(e).slice(0, 200));
  }
  await browser.close();
}

for (const [name, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${name}${extra ? '  — ' + extra : ''}`);
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
