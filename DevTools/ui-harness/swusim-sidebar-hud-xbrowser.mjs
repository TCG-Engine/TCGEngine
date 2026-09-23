// The in-game right sidebar as a New Petranaki HUD panel, in Chromium, Firefox and WebKit.
//   The glass recipe comes from SharedUI/Sites/SWUSim/css/petranaki-glass.css, which NextTurn.php
//   links for SWUSim — the board never loads swusim-overrides.css. The chamfer is MIRRORED (both
//   visible corners are on the LEFT, facing the board) and the fill is darkened per-surface, because
//   the stock glass is tuned for the sandy site backdrop and LIGHTENS a near-black board.
// Visual spec: SWUSim/Tests/Visual/SidebarHud_InGame.md
// Usage: node swusim-sidebar-hud-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit
import { chromium, firefox, webkit } from 'playwright';
import os from 'node:os';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SHOTS = process.env.SHOTS_DIR || os.tmpdir();

const SCHEMA = `## GIVEN
CommonSetup: bbw/rrk/{myResources:5; theirResources:5}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_032:1:0 SOR_033:1:2]
WithP2GroundArena: [SOR_034:1:0 SOR_035:1:2]

## WHEN

## EXPECT
TURNPLAYER:1
`;

let allOk = true;
const results = [];
const ok = (e, n, cond, extra = '') => { if (!cond) allOk = false; results.push([e, n, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 300s'); report(); process.exit(9); }, 300000).unref();

async function makeGame() {
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: new URLSearchParams({ schema: SCHEMA }) });
  const j = await r.json();
  if (!j.gameName) throw new Error('TestSchemaSetup failed: ' + JSON.stringify(j).slice(0, 200));
  return String(j.gameName);
}

async function run(engineName, launcher) {
  const browser = await launcher.launch();
  try {
    const gn = await makeGame();
    const ctx = await browser.newContext({ viewport: { width: 1700, height: 1050 } });
    await ctx.request.post(BASE + 'AccountFiles/AttemptPasswordLogin.php',
      { form: { submit: '1', userID: 'claudebot1', password: 'pass' } });
    const page = await ctx.newPage();
    const errs = [];
    page.on('pageerror', e => errs.push(String(e)));
    await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${gn}&playerID=1&authKey=testschema`);
    await page.waitForSelector('#swuSidebar', { timeout: 25000 });
    await page.waitForTimeout(2500);

    const v = await page.evaluate(() => {
      const el = document.getElementById('swuSidebar');
      const before = getComputedStyle(el, '::before');
      const after = getComputedStyle(el, '::after');
      const inp = document.getElementById('chatText');
      return {
        glassContent: before.content,
        clip: before.clipPath || '',
        urlLayers: (before.backgroundImage.match(/url\(/g) || []).length,
        shadowContent: after.content,
        elBg: getComputedStyle(el).backgroundColor,
        composerBg: inp ? getComputedStyle(inp).backgroundColor : '',
        composerRadius: inp ? getComputedStyle(inp).borderRadius : '',
        rect: el.getBoundingClientRect().toJSON(),
        vw: window.innerWidth, vh: window.innerHeight
      };
    });

    ok(engineName, 'the glass layer is painted', v.glassContent !== 'none' && /polygon/.test(v.clip), v.clip.slice(0, 44));
    ok(engineName, 'the drop-shadow layer is painted', v.shadowContent !== 'none');
    ok(engineName, 'the panel itself is unpainted so the chamfer is not squared off',
       /rgba\(0, 0, 0, 0\)|transparent/.test(v.elBg), v.elBg);
    // ⚠ BOTH LEFT CORNERS. The stock recipe glows top-left + bottom-right; this surface re-places
    // them. A count of 2 glow urls (plus none from the gradients) is the cheapest proof the
    // bottom-left artwork is actually wired — reusing --pa-glow-tl there paints a gold WEDGE.
    ok(engineName, 'two corner glows are wired (both on the LEFT)', v.urlLayers >= 2, `${v.urlLayers} url() layers`);
    // The chamfer polygon must cut the LEFT side, not the stock top-left + bottom-right pair.
    ok(engineName, 'the chamfer is mirrored to face the board',
       /0(px)? calc\(100% - var\(--pa-cut\)\)|0px calc\(100% - 14px\)|0 calc\(100% - 14px\)/.test(v.clip)
       || /14px 100%/.test(v.clip), v.clip.slice(0, 80));

    ok(engineName, 'the sidebar still spans the full height at the right edge',
       Math.round(v.rect.right) >= v.vw - 1 && v.rect.height >= v.vh - 1,
       `right ${Math.round(v.rect.right)}/${v.vw}, h ${Math.round(v.rect.height)}/${v.vh}`);

    ok(engineName, 'the composer is a sunken well, not the old flat bar',
       v.composerBg === 'rgba(14, 17, 22, 0.42)' && v.composerRadius === '4px',
       `${v.composerBg} r=${v.composerRadius}`);

    // ★ THE DARK-BACKGROUND CHECK. The stock glass is tuned for the sandy SITE backdrop, where it
    // darkens what is behind it; over a near-black board the same fill LIGHTENS, and the sidebar came
    // out paler than the table beside it. Canvas cannot read composited backdrop-filter output, so
    // the RESULT is judged from the screenshot by eye (see the visual spec) — what is pinned here is
    // that the per-surface override is still in force, which is the thing that would silently vanish.
    const fill = await page.evaluate(() =>
      getComputedStyle(document.getElementById('swuSidebar')).getPropertyValue('--pa-glass').trim());
    ok(engineName, '★ the fill is the DARK per-surface override, not the sandy-page default',
       /rgba\(26, ?32, ?41/.test(fill), fill.slice(0, 56));

    // ★★ THE CHAMFER, MEASURED IN PIXELS — the owner's own test, and the only kind that holds.
    //
    // Reported FOUR times on 2026-09-22. The clip-path was correct every time; what was wrong was
    // what the cut EXPOSED. Three earlier versions of this check all passed while a bug was present:
    //   1. "the cut differs from the panel" — the in-panel sample landed on the gold UNDO button, so
    //      the delta stayed large.
    //   2. "the cut matches document.body's background" — passed when the fix was an opaque strip
    //      PAINTING that colour into the cut. It proved the colour, not the transparency.
    //   3. "the cut tracks the PAGE backdrop (body painted magenta)" — passed on the black wedge.
    //      The cut WAS tracking the page canvas; that was the bug. Every board art layer stopped
    //      dead at the panel's edge, so the only thing behind the 14px cut was --swu-bg (#0b0f14) —
    //      invisible where the board art is dark and a BLACK WEDGE where it is light. This harness
    //      ran green because TestSchemaSetup's board happens to be near-black at that corner.
    // So the assertion is now about CONTINUITY, which is what a cut corner actually promises: the
    // cut must show the same table as the board immediately beside it, whatever that table is.
    //
    // Screenshot -> data URL -> canvas in the page, so there is no image dependency to install.
    //
    // ⚠ The clip starts 20px LEFT of the panel so the same image carries the board reference sample.
    // Local x = 20 is the panel's left edge.
    // ⚠ BOTH CUTS. The bottom-left one sits beside the chat composer — the corner the owner was
    // looking at when they reported the wedge — and nothing about the top corner proves it.
    const REF_DX = 20;
    const sampleCut = async (corner = 'top') => {
      const top = corner === 'top' ? v.rect.top : v.rect.bottom - 60;
      const shot = await page.screenshot({
        clip: { x: v.rect.left - REF_DX, y: top, width: 60 + REF_DX, height: 60 }
      });
      return page.evaluate(async ([b64, refDx, w, isTop]) => {
        const img = new Image();
        await new Promise(r => { img.onload = r; img.src = 'data:image/png;base64,' + b64; });
        const cv = document.createElement('canvas');
        cv.width = img.width; cv.height = img.height;
        const cx = cv.getContext('2d');
        cx.drawImage(img, 0, 0);
        const scale = img.width / w;   // deviceScaleFactor
        const at = (x, y) => {
          const d = cx.getImageData(Math.round(x * scale), Math.round(y * scale), 1, 1).data;
          return [d[0], d[1], d[2]];
        };
        // ⚠ SAMPLE THE WHOLE TRIANGLE, NOT ONE PIXEL. A single probe at (3,3) passes while a SPIKE
        // pokes through elsewhere in the cut — which is exactly what the panel's own children and
        // its ::after shadow did once the chamfer was mirrored (.pa-glass clips the ::before GLASS;
        // a clip-path on a pseudo-element does nothing to the element's children). Walk every pixel
        // strictly inside the diagonal and keep the WORST one, each against the board at ITS OWN y
        // — the board art is a gradient, so one shared reference would slacken the tolerance.
        let worst = null, off = -1;
        for (let x = 1; x < 12; x++) {
          for (let d0 = 1; d0 < 12; d0++) {
            if (x + d0 > 11) continue;              // strictly inside the 14px cut
            const y = isTop ? d0 : 59 - d0;         // the bottom cut mirrors about the panel's floor
            const c = at(refDx + x, y);
            const ref = at(refDx - 4, y);           // the board, 4px left of the panel, same row
            const d = Math.max(...[0, 1, 2].map(i => Math.abs(c[i] - ref[i])));
            if (d > off) { off = d; worst = { at: [x, y], rgb: c, ref }; }
          }
        }
        const my = isTop ? 45 : 14;                 // a row well clear of both cuts
        const inside = at(refDx + 45, my);          // deep in the panel: must NOT match the board
        const board = at(refDx - 4, my);
        return { worst, off, inside, board,
                 panelOff: Math.max(...[0, 1, 2].map(i => Math.abs(inside[i] - board[i]))) };
      }, [shot.toString('base64'), REF_DX, 60 + REF_DX, corner === 'top']);
    };

    for (const corner of ['top', 'bottom']) {
      const cut = await sampleCut(corner);
      ok(engineName, `★★ the ${corner}-left chamfer reveals the TABLE, continuous with the board beside it`,
         cut.off <= 8,
         `worst pixel in the cut is rgb(${cut.worst.rgb}) at ${cut.worst.at} vs board rgb(${cut.worst.ref}) — off by ${cut.off}`);
      // Sanity: the panel must NOT be see-through, or the check above would pass for a missing panel.
      // ⚠ TOP ONLY, and it is not laziness. At the BOTTOM the vignette's floor band is essentially
      // opaque black, so the board there reads rgb(0,0,0) and the panel rgb(6,10,15) — a real,
      // painted panel that this test cannot tell from a hole. The bottom corner gets its sanity from
      // the magenta probe below instead, where the two are guaranteed to differ.
      if (corner === 'top') {
        ok(engineName, 'and the panel itself is not transparent', cut.panelOff > 20,
           `panel shows rgb(${cut.inside}) over board rgb(${cut.board})`);
      }
    }

    // ★★ AND IT IS GENUINELY CUT, NOT PAINTED. Continuity alone would also pass for an opaque strip
    // behind the panel in the board's colour — the fix that was shipped and then failed the moment
    // the owner set a white background. So move the thing that is ACTUALLY behind the cut
    // (.swu-board-bg, the board art layer) to a garish colour and require the cut to follow it.
    // A transparent cut tracks its backdrop; a painted one cannot.
    const PROBE = [255, 0, 255];   // magenta: not in the Petranaki palette, not in the board art
    // ⚠ INLINE setProperty(..., 'important'), not addStyleTag — an injected stylesheet rule lost to
    // the board's own rules once already and the check then ran against a probe never applied.
    await page.evaluate((probe) => {
      const c = `rgb(${probe.join(',')})`;
      document.querySelector('.swu-board-bg').style.setProperty('background', c, 'important');
      // the two translucent layers on top would tint the probe unevenly across the 24px being
      // compared; this assertion is about .swu-board-bg reaching the cut at all.
      for (const sel of ['.swu-starfield', '.swu-vignette']) {
        const n = document.querySelector(sel);
        if (n) n.style.setProperty('display', 'none', 'important');
      }
    }, PROBE);
    await page.waitForTimeout(400);
    const isProbe = (c) => Math.max(...[0, 1, 2].map(i => Math.abs(c[i] - PROBE[i]))) <= 6;
    for (const corner of ['top', 'bottom']) {
      const probed = await sampleCut(corner);
      ok(engineName, `★★ the ${corner}-left cut is genuinely CUT — it follows the board layer, it is not painted`,
         isProbe(probed.worst.rgb) && isProbe(probed.worst.ref) && probed.off <= 6,
         `cut rgb(${probed.worst.rgb}) / board rgb(${probed.worst.ref}) against a rgb(${PROBE}) board layer`);
      // …and the panel beside the cut is NOT following it, or a missing panel would pass the above.
      ok(engineName, `and the panel beside the ${corner} cut is opaque, not a hole`,
         !isProbe(probed.inside), `panel shows rgb(${probed.inside})`);
    }
    await page.evaluate(() => {
      document.querySelector('.swu-board-bg').style.removeProperty('background');
      for (const sel of ['.swu-starfield', '.swu-vignette']) {
        const n = document.querySelector(sel);
        if (n) n.style.removeProperty('display');
      }
    });
    await page.waitForTimeout(300);

    // ★★ STRUCTURAL: HOW FAR EACH BACKDROP LAYER MAY REACH UNDER THE PANEL.
    // This is the owner's own console probe turned into an assertion, and it is the guard that
    // actually holds — a pixel check only fires when the layer in question happens to be OPAQUE and
    // DIFFERENT at the sampled corner. `.swu-starfield` overhung by 172px on the owner's board and
    // was invisible to a pixel check here, because its radial gradients are near-transparent at that
    // corner in THIS viewport. My headless environment does not reproduce the owner's; a structural
    // rule does not care.
    // Two different rules, because the layers are two different kinds of thing:
    //  • the three ART layers (.swu-board-bg / .swu-starfield / .swu-vignette) must reach EXACTLY
    //    --pa-cut past the edge — far enough that the cut shows the table, no further, or the glass
    //    blurs a band of board art into the panel's left edge;
    //  • everything else (the flat .theirStuffWrapper / .myStuffWrapper / #theirStuff / #myStuff
    //    slabs, and anything added later) must not cross the edge at all. One stray `inset: 0`
    //    refills the chamfer with featureless grey and the corner reads as uncut again.
    const ART = ['.swu-board-bg', '.swu-starfield', '.swu-vignette'];
    const layers = await page.evaluate((art) => {
      const sb = document.getElementById('swuSidebar');
      const r = sb.getBoundingClientRect();
      const sbZ = parseInt(getComputedStyle(sb).zIndex, 10) || 0;
      const cut = parseFloat(getComputedStyle(document.documentElement).getPropertyValue('--pa-cut')) || 14;
      const name = n => (n.id ? '#' + n.id : n.tagName.toLowerCase()) +
        (n.className ? '.' + n.className.toString().trim().split(/\s+/).join('.') : '');
      const rows = [];
      for (const n of document.querySelectorAll('*')) {
        if (n === sb || sb.contains(n) || n === document.body || n === document.documentElement) continue;
        const cs = getComputedStyle(n);
        if (cs.visibility === 'hidden' || cs.display === 'none') continue;
        const z = parseInt(cs.zIndex, 10);
        if (!isNaN(z) && z >= sbZ) continue;                      // in front of the panel: not a backdrop
        if (cs.backgroundColor === 'rgba(0, 0, 0, 0)' && cs.backgroundImage === 'none') continue;
        const b = n.getBoundingClientRect();
        if (b.width === 0 || b.height === 0) continue;
        if (!(b.right > r.left + 0.5 && b.left < r.left)) continue;   // does not cross the edge
        rows.push({ el: name(n), past: Math.round(b.right - r.left), isArt: art.some(s => n.matches(s)) });
      }
      const artReach = art.map(s => {
        const n = document.querySelector(s);
        return { el: s, past: n ? Math.round(n.getBoundingClientRect().right - r.left) : null };
      });
      return { rows, artReach, cut };
    }, ART);
    const strays = layers.rows.filter(l => !l.isArt);
    ok(engineName, '★★ no FLAT backdrop slab overhangs the panel edge (that is what reads as uncut)',
       strays.length === 0,
       strays.map(o => `${o.el} +${o.past}px`).join(', ') || 'none');
    const artWrong = layers.artReach.filter(a => a.past !== layers.cut);
    ok(engineName, `★★ the board art bleeds exactly ${layers.cut}px under the panel, so the cut shows the table`,
       artWrong.length === 0,
       layers.artReach.map(a => `${a.el} +${a.past}px`).join(', '));

    // ⚠ THE BLEED MUST COLLAPSE WHEN THERE IS NO SIDEBAR. Below 800px this layout sets
    // --swu-sidebar-w: 0 and hides #swuSidebar, so the art layers should span the full viewport
    // again. --swu-board-bleed-r guards that with max(0px, …); without it the subtraction lands on
    // right:-14px and three fixed layers hang past the right edge.
    await page.setViewportSize({ width: 700, height: 900 });
    await page.waitForTimeout(400);
    const narrow = await page.evaluate((art) => art.map(s => {
      const n = document.querySelector(s);
      return { el: s, right: n ? Math.round(n.getBoundingClientRect().right) : null };
    }).concat([{ el: 'viewport', right: window.innerWidth }]), ART);
    const vw = narrow.find(n => n.el === 'viewport').right;
    ok(engineName, 'with no sidebar (<800px) the bleed collapses — no layer hangs past the viewport',
       narrow.every(n => n.right === vw), narrow.map(n => `${n.el} ${n.right}`).join(', '));
    await page.setViewportSize({ width: 1700, height: 1050 });
    await page.waitForTimeout(400);

    ok(engineName, 'no page errors', errs.length === 0, errs.slice(0, 1).join(''));

    await page.locator('#swuSidebar').screenshot({ path: `${SHOTS}/sidebar-hud-${engineName}.png` });
    await page.screenshot({ path: `${SHOTS}/sidebar-hud-${engineName}-full.png` });
    await ctx.close();
  } finally {
    await browser.close();
  }
}

for (const [name, launcher] of ENGINES) {
  try { await run(name, launcher); }
  catch (e) { allOk = false; results.push([name, 'ENGINE ERROR', false, String(e.message || e).slice(0, 200)]); }
}
report();
console.log(allOk ? '\nALL PASS' : '\nFAILURES ABOVE');
process.exit(allOk ? 0 : 1);
