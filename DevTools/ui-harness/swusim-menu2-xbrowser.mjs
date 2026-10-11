// Cross-browser gate for the SWUSim menu redesign.
//
// Exits non-zero on any defect, so it can front a deploy. Checks every :target state at desktop
// and phone in Chromium, Firefox and WebKit.
//
// ⚠ Two measurement traps this script deliberately avoids, both of which produced FALSE results
//   during the redesign:
//   1. `loading="lazy"` images below the fold are not decoded when you screenshot, so they read
//      as broken. Every image is forced eager and awaited before anything is judged.
//   2. Pixel-diffing this page is meaningless — it rotates a tip every 8s and polls games every
//      20s. Two captures seconds apart differed by 63% with no code change. Geometry only.
import { chromium, firefox, webkit } from 'playwright';

const URL = process.env.MENU_URL || 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
const STATES = ['', '#setup-pvp', '#setup-twin-suns', '#setup-arenabot', '#setup-solo'];
const ENGINES = [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]];
const WIDTHS = [1440, 390];

let fails = 0;
let checks = 0;

for (const [name, launcher] of ENGINES) {
  const browser = await launcher.launch();
  for (const width of WIDTHS) {
    for (const state of STATES) {
      const page = await browser.newPage({ viewport: { width, height: 900 } });
      const errs = [];
      page.on('pageerror', e => errs.push('pageerror: ' + e.message));
      page.on('crash', () => errs.push('RENDERER CRASH'));
      page.on('response', r => { if (r.status() >= 400) errs.push(`HTTP ${r.status()} ${r.url().split('/').pop()}`); });

      try {
        await page.goto(URL + state, { waitUntil: 'networkidle', timeout: 25000 });
      } catch (e) {
        fails++; checks++;
        console.log(`FAIL ${name} ${width} ${state || '#splash'} :: navigation ${e.message.split('\n')[0]}`);
        await page.close();
        continue;
      }

      await page.evaluate(async () => {
        document.querySelectorAll('img[loading="lazy"]').forEach(i => { i.loading = 'eager'; });
        await Promise.all([...document.images].map(i => i.decode().catch(() => {})));
      });

      const r = await page.evaluate(() => {
        const de = document.documentElement;
        const visible = e => e.offsetParent !== null || getComputedStyle(e).position === 'fixed';
        const small = [...document.querySelectorAll('button, a[href], input:not(.u-vh), select, textarea')]
          .filter(visible)
          .map(e => {
            const b = e.getBoundingClientRect();
            return { id: (e.id || e.className || e.tagName).toString().slice(0, 28), w: Math.round(b.width), h: Math.round(b.height) };
          })
          .filter(x => x.w > 0 && (x.w < 44 || x.h < 44));
        const clipped = [...document.querySelectorAll('*')]
          .filter(e => getComputedStyle(e).textOverflow === 'ellipsis' && e.scrollWidth > e.clientWidth + 1 && e.offsetParent)
          .map(e => e.textContent.trim().slice(0, 30));
        const broken = [...document.images].filter(i => i.naturalWidth === 0).map(i => i.src.split('/').pop());
        const unlabelled = [...document.querySelectorAll('input:not([type=hidden]), select, textarea')]
          .filter(visible)
          .filter(e => !e.labels?.length && !e.getAttribute('aria-label') && !e.getAttribute('aria-labelledby'))
          .map(e => e.id || e.name || e.tagName);
        // ⚠ HITTABILITY. Twice now the nav — absolutely positioned across the header band to
        // centre its links — has covered things with a transparent sheet: once the whole
        // viewport (block-size:100% resolves against the initial containing block, not the
        // header), once the full width, which swallowed every click on the home logo and
        // wordmark. Both LOOKED perfect and measured perfect. A control that cannot be clicked
        // is the defect, so ask the browser who actually receives the click.
        // When a dialog is open the background is correctly inert, so only the dialog's own
        // controls are checked.
        const dlg = document.querySelector('dialog[open]');
        const scope = dlg || document;
        // ⚠ Probe the centre of the element's VISIBLE part, not of the element. A tall card
        // that starts on screen and runs past the fold has its true centre outside the
        // viewport, where elementFromPoint returns null — which reads as "unclickable" for a
        // control that is perfectly fine. The 390px mode cards did exactly that.
        const probe = (e) => {
          const b = e.getBoundingClientRect();
          const x = Math.min(Math.max(b.left + b.width / 2, 1), innerWidth - 1);
          const top = Math.max(b.top, 0), bot = Math.min(b.bottom, innerHeight);
          if (bot - top < 4) return null;
          return { el: document.elementFromPoint(x, (top + bot) / 2), x, y: (top + bot) / 2 };
        };
        const unclickable = [...scope.querySelectorAll('a[href], button')]
          .filter(visible)
          .filter(e => {
            const b = e.getBoundingClientRect();
            if (b.width < 4 || b.height < 4) return false;
            if (b.bottom < 0 || b.top > innerHeight || b.right < 0 || b.left > innerWidth) return false;
            const p = probe(e);
            if (!p) return false;
            const hit = p.el;
            return !hit || (hit !== e && !e.contains(hit) && !hit.contains(e));
          })
          .map(e => {
            const hit = (probe(e) || {}).el;
            const who = (e.id || e.className || e.tagName).toString().trim().slice(0, 24);
            const by = hit ? (hit.tagName + '.' + (hit.className || '').toString().trim().split(/\s+/)[0]) : 'nothing';
            return `${who} <- ${by}`;
          });

        // A dead band is content-height beyond the last painted element — the symptom of an
        // absolutely-positioned child escaping its container. ⚠ Only meaningful when the page
        // ACTUALLY SCROLLS: scrollHeight floors at the viewport height, so a page shorter than
        // the window always shows a gap that is just unfilled viewport, not a defect.
        const footer = document.querySelector('footer');
        const scrolls = de.scrollHeight > innerHeight + 1;
        const dead = (footer && scrolls)
          ? Math.round(de.scrollHeight - (footer.getBoundingClientRect().bottom + scrollY))
          : 0;
        return {
          sw: de.scrollWidth, cw: de.clientWidth, small, clipped, broken, unlabelled, dead,
          unclickable,
          compat: document.compatMode, lang: de.lang,
          h1: document.querySelectorAll('h1').length,
          missingLandmarks: ['header', 'nav', 'main', 'footer'].filter(t => !document.querySelector(t)),
        };
      });

      const bad = [];
      if (r.sw > r.cw + 1)          bad.push(`H-OVERFLOW ${r.sw}/${r.cw}`);
      if (r.small.length)           bad.push(`SUB-44 ${JSON.stringify(r.small.slice(0, 3))}`);
      if (r.clipped.length)         bad.push(`CLIPPED ${JSON.stringify(r.clipped.slice(0, 2))}`);
      if (r.broken.length)          bad.push(`BROKEN-IMG ${r.broken.slice(0, 3).join(',')}`);
      if (r.unlabelled.length)      bad.push(`UNLABELLED ${r.unlabelled.slice(0, 3).join(',')}`);
      if (r.unclickable.length)     bad.push(`UNCLICKABLE ${JSON.stringify(r.unclickable.slice(0, 3))}`);
      if (r.dead > 12)              bad.push(`DEAD-BAND ${r.dead}px`);
      if (r.compat !== 'CSS1Compat') bad.push('QUIRKS-MODE');
      if (r.lang !== 'en')          bad.push(`LANG="${r.lang}"`);
      if (r.h1 !== 1)               bad.push(`H1 x${r.h1}`);
      if (r.missingLandmarks.length) bad.push(`NO-LANDMARK ${r.missingLandmarks.join(',')}`);
      if (errs.length)              bad.push(errs[0]);

      checks++;
      if (bad.length) { fails++; console.log(`FAIL ${name} ${width} ${(state || '#splash').padEnd(16)} :: ${bad.join(' | ')}`); }
      await page.close();
    }
  }
  await browser.close();
}

// ── Modal contract (Task 14) ────────────────────────────────────────────────
// A mode card must open a real modal dialog over the splash: the splash stays visible behind,
// focus moves in, Escape closes it and RETURNS FOCUS to the card that opened it, and the page
// behind must not scroll. showModal() does NOT lock background scroll on its own.
for (const [name, launcher] of ENGINES) {
  const browser = await launcher.launch();
  for (const width of WIDTHS) {
    const page = await browser.newPage({ viewport: { width, height: 900 } });
    await page.goto(URL, { waitUntil: 'networkidle' });
    await page.locator('.mode').first().click();
    await page.waitForTimeout(350);
    const open = await page.evaluate(() => {
      const d = document.querySelector('dialog[open]');
      const splash = document.querySelector('.modes__grid');
      return { open: !!d, modal: d ? d.matches(':modal') : false,
               splashVisible: !!(splash && splash.offsetParent !== null),
               labelled: d ? !!(d.getAttribute('aria-labelledby') || d.getAttribute('aria-label')) : false,
               focusInside: d ? d.contains(document.activeElement) : false,
               lock: getComputedStyle(document.documentElement).overflow };
    });
    await page.keyboard.press('Escape');
    await page.waitForTimeout(300);
    const closed = await page.evaluate(() => ({
      closed: !document.querySelector('dialog[open]'),
      focusOnCard: !!(document.activeElement && document.activeElement.closest('.mode')),
      lock: getComputedStyle(document.documentElement).overflow }));
    const bad = [];
    if (!open.open)           bad.push('card did not open a dialog');
    if (!open.modal)          bad.push('dialog is not :modal (showModal not used)');
    if (!open.splashVisible)  bad.push('splash hidden behind the modal');
    if (!open.labelled)       bad.push('dialog has no accessible name');
    if (!open.focusInside)    bad.push('focus did not move into the dialog');
    if (open.lock !== 'hidden') bad.push(`background not scroll-locked (${open.lock})`);
    if (!closed.closed)       bad.push('Escape did not close');
    if (!closed.focusOnCard)  bad.push('focus not returned to the opener');
    if (closed.lock === 'hidden') bad.push('scroll lock not released');
    checks++;
    if (bad.length) { fails++; console.log(`FAIL ${name} ${width} modal-contract :: ${bad.join(' | ')}`); }
    await page.close();
  }
  await browser.close();
}

console.log(fails ? `\n${fails}/${checks} checks failed` : `\nALL CLEAN — ${checks} checks`);
process.exit(fails ? 1 : 0);
