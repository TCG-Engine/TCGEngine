// The sidebar chamfer on a REAL Arenabot board — the companion to swusim-sidebar-hud-xbrowser.mjs,
// which runs on a TestSchemaSetup game whose board art happens to be near-black at that corner and
// therefore hid the black-wedge bug for a whole round (owner report, 2026-09-22).
//
// Here the board art is forced WHITE, which is harsher than the owner's light cosmetic board (their
// corner measures rgb(85,91,98) after the vignette), and it captures BOTH geometries in one run:
// a -BEFORE- pair with --swu-board-bleed-r pushed back to the sidebar edge (the wedge) and the live
// one (the table). Answers two things the schema board cannot: is the wedge gone, and does the 14px
// bleed leave a seam where the glass blurs board art into the panel's left edge.
//
// Usage: OUT=/tmp/cutprobe ENGINES=chromium,firefox,webkit node _cutprobe.mjs
// Reads: cut-vs-board delta per engine, then a seam scan down the panel's left edge (bleedStrip vs
// just past it). Over a WHITE board the seam runs ~6-8 levels, smeared by blur(18px); over real art
// it measured 5/3/2/0. Look at the -corner- PNGs too — numbers cannot see a screenshot.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.env.BASE || 'http://localhost:3400/TCGEngine/';
const OUT = process.env.OUT || '/tmp/cutprobe';
const ENGINES = { chromium, firefox, webkit };
const PICK = (process.env.ENGINES || 'chromium').split(',');
fs.mkdirSync(OUT, { recursive: true });
const DECK = fs.readFileSync('/Users/mt/Documents/GitHub/Karabast-SWU/OTMTCGE/SWUSim/Tests/BotFixtures/meta-2026-09/aggro_vader_yellow.txt', 'utf8')
  .split('\n').filter(l => !l.startsWith('#')).join('\n').trim();

for (const name of PICK) {
  const browser = await ENGINES[name].launch();
  const ctx = await browser.newContext({ viewport: { width: 1430, height: 1000 }, deviceScaleFactor: 2 });
  const page = await ctx.newPage();
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
  await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
  await page.selectOption('#swu-gametype-select', 'constructed');
  await page.selectOption('#swu-second-select', 'arenabot');
  await page.selectOption('#swu-pool-select', 'premier');
  await page.evaluate(() => switchDeckTab('text'));
  await page.fill('#deck-text', DECK);
  await page.click('#start-solo-btn');
  await page.waitForURL(/NextTurn\.php/, { timeout: 40000 });
  await page.waitForSelector('#swuSidebar', { timeout: 30000 });
  await page.waitForTimeout(4000);

  // a LIGHT board, like the owner's cosmetic one — this is the condition that makes the wedge visible.
  // White, so it survives the vignette at the extreme corner and lands near the owner's rgb(85,91,98).
  await page.evaluate(() => {
    document.querySelector('.swu-board-bg').style.setProperty('background', '#ffffff', 'important');
  });
  await page.waitForTimeout(500);

  // BEFORE: put the art layers back where they were, to prove this probe sees the reported wedge
  const rr = await page.evaluate(() => document.getElementById('swuSidebar').getBoundingClientRect().toJSON());
  await page.evaluate(() => document.documentElement.style.setProperty('--swu-board-bleed-r', 'var(--swu-sidebar-w)'));
  await page.waitForTimeout(400);
  await page.screenshot({ path: `${OUT}/${name}-BEFORE-top.png`, clip: { x: rr.left - 30, y: 0, width: 110, height: 70 } });
  await page.screenshot({ path: `${OUT}/${name}-BEFORE-bot.png`, clip: { x: rr.left - 30, y: 930, width: 110, height: 70 } });
  await page.evaluate(() => document.documentElement.style.removeProperty('--swu-board-bleed-r'));
  await page.waitForTimeout(400);

  const r = await page.evaluate(() => document.getElementById('swuSidebar').getBoundingClientRect().toJSON());
  await page.screenshot({ path: `${OUT}/${name}-corner-top.png`, clip: { x: r.left - 30, y: 0, width: 110, height: 70 } });
  await page.screenshot({ path: `${OUT}/${name}-corner-bot.png`, clip: { x: r.left - 30, y: 930, width: 110, height: 70 } });
  await page.screenshot({ path: `${OUT}/${name}-full.png` });

  const shot = await page.screenshot({ clip: { x: r.left - 30, y: 0, width: 110, height: 1000 } });
  const m = await page.evaluate(async ([b64, w]) => {
    const img = new Image();
    await new Promise(res => { img.onload = res; img.src = 'data:image/png;base64,' + b64; });
    const cv = document.createElement('canvas'); cv.width = img.width; cv.height = img.height;
    const cx = cv.getContext('2d'); cx.drawImage(img, 0, 0);
    const s = img.width / w;
    const at = (x, y) => { const d = cx.getImageData(Math.round(x * s), Math.round(y * s), 1, 1).data; return [d[0], d[1], d[2]]; };
    const L = 30;                       // local x of the panel's left edge
    // the cut triangle vs the board beside it
    let worst = null, off = -1;
    for (let x = 1; x < 12; x++) for (let y = 1; y < 12; y++) {
      if (x + y > 11) continue;
      const c = at(L + x, y), ref = at(L - 4, y);
      const d = Math.max(...[0, 1, 2].map(i => Math.abs(c[i] - ref[i])));
      if (d > off) { off = d; worst = { at: [x, y], rgb: c, ref }; }
    }
    // SEAM: inside the panel, is the strip over the bleed (x<14) different from the strip beyond it?
    const seam = [];
    for (const y of [120, 300, 500, 700, 860]) {
      const a = at(L + 6, y), b = at(L + 22, y), c = at(L + 40, y);
      seam.push({ y, overBleed: a, justPast: b, deeper: c,
                  d: Math.max(...[0, 1, 2].map(i => Math.abs(a[i] - b[i]))) });
    }
    return { worst, off, seam };
  }, [shot.toString('base64'), 110]);
  console.log(name, 'cut vs board: off by', m.off, JSON.stringify(m.worst));
  for (const s of m.seam) console.log(' ', name, 'seam y=' + s.y, 'bleedStrip', s.overBleed, 'past', s.justPast, 'deeper', s.deeper, 'delta', s.d);
  await browser.close();
}
console.log('shots in', OUT);
