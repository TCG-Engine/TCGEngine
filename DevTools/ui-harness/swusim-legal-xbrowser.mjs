// Terms of Use and Privacy Policy must be READABLE.
//
// templates/TermsOfUse.tmpl and PrivacyPolicy.tmpl are bare prose — <h1>, <h2>, <p>, <ul> with
// nothing around them, because every site is expected to supply the frame. SWUSim supplied none,
// so the prose landed as direct children of <body>: every line ran the full 1440px of the viewport,
// hard against both screen edges, with no panel. Measured, not assumed.
//
// SiteDef legal.layout = 'arena' now frames it (Render/Template.php RenderLegalPage) in the
// redesign's own .row-wrapper + .card.ga-glass-card, so the panel comes from the interior-panel
// recipe and only the prose is styled here.
import { chromium, firefox, webkit } from 'playwright';
// ⚠ THE ROOT URL, not the site-scoped one. Every footer, signup notice and profile
// disclaimer links to /TCGEngine/SharedUI/TermsOfUse.php -- and that path was still the
// original hand-written page with "SWU Stats" hardcoded in it, because the generator emitted
// the legal pages PER SITE but never as root pointers. This gate passed against
// Sites/SWUSim/TermsOfUse.php while every real visitor got another product's terms.
// Test the URL production links to.
const B = process.env.SITE_URL || 'http://localhost:3400/TCGEngine/SharedUI/';
const PAGES = ['TermsOfUse.php', 'PrivacyPolicy.php'];
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  // a narrow window as well as a wide one: the measure must hold at both
  for (const vw of [1440, 760]) {
    const p = await b.newPage({ viewport: { width: vw, height: 1000 } });
    const errs = []; p.on('pageerror', e => errs.push(e.message));

    for (const page of PAGES) {
      await p.goto(B + page, { waitUntil: 'networkidle' });
      await p.waitForTimeout(500);
      const d = await p.evaluate(() => {
        const box = document.querySelector('.legal-page');
        if (!box) return { framed: false };
        const cs = getComputedStyle(box);
        // ⚠ Measure the PROSE BLOCK, not a <p>. PrivacyPolicy.tmpl has no paragraphs at all --
        // bare text nodes and <br><br> -- so demanding one tests the template's markup, not the
        // readability this gate is about. The wrapper is what bounds the copy either way.
        const para = box.querySelector('.legal-page__prose') || box.querySelector('p');
        const h1 = box.querySelector('h1');
        const h2 = box.querySelector('h2');
        const g = e => e ? { w: Math.round(e.getBoundingClientRect().width),
                             font: getComputedStyle(e).fontFamily.split(',')[0].replace(/"/g, ''),
                             size: parseFloat(getComputedStyle(e).fontSize) } : null;
        // the prose must not be a direct child of <body> any more
        const loose = [...document.body.children].some(e => /^(H1|H2|P|UL|OL)$/.test(e.tagName));
        return {
          framed: true, loose,
          panelPad: parseFloat(cs.paddingLeft),
          p: g(para), h1: g(h1), h2: g(h2),
          hScroll: document.documentElement.scrollWidth - document.documentElement.clientWidth,
          docW: document.documentElement.clientWidth,
        };
      });

      const tag = `${name} @${vw} ${page.replace('.php', '')}`;
      // the page must be THIS site's, not whichever product the template was first written for
      const body = await p.evaluate(() => document.body.innerText);
      if (/SWU Stats/i.test(body)) bad(tag, 'the page still names "SWU Stats"'); else ok();
      if (!/Petranaki Arena/i.test(body)) bad(tag, 'the page never names Petranaki Arena'); else ok();
      if (!d.framed) { bad(tag, 'no .legal-page frame — the prose is unwrapped'); continue; }
      ok();
      if (d.loose) bad(tag, 'prose is STILL a direct child of <body>'); else ok();
      if (d.hScroll > 1) bad(tag, `page scrolls horizontally by ${d.hScroll}px`); else ok();
      if (!(d.panelPad >= 16)) bad(tag, `panel padding ${d.panelPad}px — text is against the edge`); else ok();

      // THE MEASURE. A paragraph wider than ~95ch is the thing this page was broken by; at 15px
      // Archivo a character averages ~0.5em, so 95ch is ~715px. The wide viewport is the real test.
      if (d.p) {
        const ch = d.p.w / (d.p.size * 0.5);
        if (ch > 95) bad(tag, `paragraph is ~${Math.round(ch)}ch wide (${d.p.w}px) — past a readable measure`);
        else ok();
        if (d.p.w >= d.docW - 2) bad(tag, 'paragraph spans the whole viewport'); else ok();
      } else bad(tag, 'no prose block found');

      // the redesign is set in Archivo; a heading in Barlow means a legacy rule still owns it
      for (const [k, el] of [['h1', d.h1], ['h2', d.h2]]) {
        if (!el) { checks++; continue; }
        if (el.font !== 'Archivo') bad(tag, `${k} renders in ${el.font}, not Archivo`); else ok();
      }
      if (d.h1 && d.h2 && !(d.h1.size > d.h2.size)) bad(tag, `h1 (${d.h1.size}px) is not larger than h2 (${d.h2.size}px)`);
      else ok();
    }
    if (errs.length) bad(`${name} @${vw}`, `pageerror ${errs[0]}`);
    await p.close();
  }
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} legal-page checks failed` : `\nLEGAL PAGES READABLE — ${checks} checks`);
process.exit(fails ? 1 : 0);
