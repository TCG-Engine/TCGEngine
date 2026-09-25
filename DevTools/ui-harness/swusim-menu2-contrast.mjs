// Contrast gate for the redesigned SWUSim menu.
//
// Samples the REAL COMPOSITED BACKDROP, not declared colours: the panels are translucent glass
// over a photographic arena plate, so the effective background of a string depends on what the
// artwork is doing at that spot. Screenshot once normally, once with all text forced transparent,
// then composite each text colour over the pixels actually behind it.
//
// ⚠ Two traps that have each produced a confidently WRONG answer in this project:
//   1. `oklch()` now SERIALIZES as `oklch()`. Parsing computed colour as rgb() reads
//      "oklch(0.818 0.02 250)" as near-black and reports ~1.3:1 on text that is fine. Resolve
//      colours through a canvas instead of parsing the string.
//   2. Text painted with `background-clip: text` computes `color: transparent`, which composites
//      to a ratio of exactly 1.00 against anything. Those are excluded and must be judged by eye.
import { chromium } from 'playwright';

const URL = process.env.MENU_URL || 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
const WIDTHS = [1440, 390];

const browser = await chromium.launch();
let worstOverall = { margin: 99 };
let fails = 0;

for (const width of WIDTHS) {
  const page = await browser.newPage({ viewport: { width, height: 900 } });
  await page.goto(URL, { waitUntil: 'networkidle' });
  await page.evaluate(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach(i => { i.loading = 'eager'; });
    await Promise.all([...document.images].map(i => i.decode().catch(() => {})));
  });

  // Collect text boxes + their resolved colour. Resolve through a canvas so oklch() is handled.
  const nodes = await page.evaluate(() => {
    // Resolve by PAINTING and reading the pixel back. Reading c.fillStyle is not enough:
    // it returns "oklch(0.818 0.02 250)" verbatim, which any rgb parser reads as near-black
    // and turns a perfectly good 6:1 string into a confident 1.3:1. Verified 2026-09-25.
    const _c = document.createElement('canvas').getContext('2d', { willReadFrequently: true });
    const toRGBA = (css) => {
      _c.clearRect(0, 0, 1, 1);
      _c.fillStyle = css;
      _c.fillRect(0, 0, 1, 1);
      const d = _c.getImageData(0, 0, 1, 1).data;
      return [d[0], d[1], d[2], d[3] / 255];
    };
    const out = [];
    const walk = document.createTreeWalker(document.body, NodeFilter.SHOW_TEXT);
    let n;
    while ((n = walk.nextNode())) {
      const s = n.nodeValue.trim();
      if (!s) continue;
      const el = n.parentElement;
      if (!el) continue;
      const cs = getComputedStyle(el);
      if (cs.visibility === 'hidden' || cs.display === 'none' || +cs.opacity === 0) continue;
      if (cs.webkitBackgroundClip === 'text' || cs.backgroundClip === 'text') continue; // trap 2
      if (el.closest('[hidden], .u-vh, [aria-hidden="true"]')) continue;
      const r = document.createRange(); r.selectNodeContents(n);
      const b = r.getBoundingClientRect();
      if (b.width < 4 || b.height < 4) continue;
      if (b.bottom < 0 || b.top > innerHeight) continue;
      const px = parseFloat(cs.fontSize), wt = parseInt(cs.fontWeight) || 400;
      out.push({
        x: Math.max(0, Math.round(b.x)), y: Math.max(0, Math.round(b.y)),
        w: Math.round(b.width), h: Math.round(b.height),
        fg: toRGBA(cs.color),
        need: (px >= 24 || (px >= 18.66 && wt >= 700)) ? 3 : 4.5,
        txt: s.slice(0, 34),
      });
    }
    return out;
  });

  await page.addStyleTag({ content:
    '*,*::before,*::after{color:transparent !important;-webkit-text-fill-color:transparent !important;text-shadow:none !important}' });
  await page.waitForTimeout(250);
  const shot = (await page.screenshot({ clip: { x: 0, y: 0, width, height: 900 } })).toString('base64');

  const res = await page.evaluate(async ({ shot, nodes }) => {
    const img = new Image(); img.src = 'data:image/png;base64,' + shot; await img.decode();
    const c = document.createElement('canvas');
    c.width = img.width; c.height = img.height;
    const ctx = c.getContext('2d', { willReadFrequently: true });
    ctx.drawImage(img, 0, 0);
    const scale = img.width / innerWidth;                       // deviceScaleFactor safety
    const lin = v => { v /= 255; return v <= 0.03928 ? v / 12.92 : Math.pow((v + 0.055) / 1.055, 2.4); };
    const L = (r, g, b) => 0.2126 * lin(r) + 0.7152 * lin(g) + 0.0722 * lin(b);
    const out = [];
    for (const n of nodes) {
      const x = Math.round(n.x * scale), y = Math.round(n.y * scale);
      const w = Math.max(1, Math.round(n.w * scale)), h = Math.max(1, Math.round(n.h * scale));
      if (x + w > c.width || y + h > c.height) continue;
      const d = ctx.getImageData(x, y, w, h).data;
      const [fr, fg2, fb, fa] = n.fg;
      let lo = 99;
      for (let i = 0; i < d.length; i += 4 * 5) {               // sample every 5th pixel
        const br = d[i], bg = d[i + 1], bb = d[i + 2];
        const r = fr * fa + br * (1 - fa), g = fg2 * fa + bg * (1 - fa), b = fb * fa + bb * (1 - fa);
        const l1 = L(r, g, b), l2 = L(br, bg, bb);
        const ratio = (Math.max(l1, l2) + 0.05) / (Math.min(l1, l2) + 0.05);
        if (ratio < lo) lo = ratio;
      }
      out.push({ ratio: +lo.toFixed(2), need: n.need, margin: lo - n.need, txt: n.txt });
    }
    return out;
  }, { shot, nodes });

  const bad = res.filter(r => r.margin < 0).sort((a, b) => a.margin - b.margin);
  const worst = res.reduce((a, b) => (b.margin < a.margin ? b : a), { margin: 99 });
  if (worst.margin < worstOverall.margin) worstOverall = worst;
  console.log(`\n${width}px — ${res.length} strings measured, ${bad.length} below AA`);
  for (const b of bad.slice(0, 8)) console.log(`  ${String(b.ratio).padStart(5)}:1 (needs ${b.need})  "${b.txt}"`);
  if (!bad.length) console.log(`  worst ${worst.ratio}:1 (needs ${worst.need}) "${worst.txt}"`);
  fails += bad.length;
  await page.close();
}

await browser.close();
console.log(fails ? `\n${fails} strings below AA` : `\nALL AA — worst margin ${worstOverall.ratio}:1 vs ${worstOverall.need} required`);
process.exit(fails ? 1 : 0);
