// Twin Suns HOME view: picking an UPGRADE/TOKEN target from a preview tile, in Chromium, Firefox and WebKit.
// Visual spec: SWUSim/Tests/Visual/TwinSuns_HomeView_SubcardTargetFromPreview.md
// Bug #1068 — LAW_078 Sabine's "defeat an upgrade" offered a Shield that exists ONLY in a preview tile;
// the tile submitted its HOST mzID and the server answered "Invalid selection."
// Usage: node swusim-preview-subcard-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SCHEMA = fs.readFileSync(
  new URL('../../SWUSim/Tests/Visual/TwinSuns_HomeView_SubcardTargetFromPreview.md', import.meta.url), 'utf8');

let allOk = true;
const results = [];
const ok = (engine, name, cond, extra = '') => { if (!cond) allOk = false; results.push([engine, name, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 600s'); report(); process.exit(9); }, 600000).unref();

// Build the board AND run its `## WHEN` (playing Sabine), so the page loads with the subcard offer pending.
async function makeGame() {
  const res = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: new URLSearchParams({ schema: SCHEMA }) });
  const j = await res.json();
  if (!j.gameName) throw new Error('TestSchemaSetup failed: ' + JSON.stringify(j));
  for (const step of (j.whenSteps || [])) {
    await fetch(BASE + 'SWUSim/TestSchemaStep.php', {
      method: 'POST', body: new URLSearchParams({ gameName: String(j.gameName), step: step.raw }),
    });
  }
  return String(j.gameName);
}
const url = (gn, pid) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${gn}&playerID=${pid}&authKey=testschema`;

// One tile's state: the two target cues and the two count badges.
const readTiles = (page) => page.evaluate(() => {
  const out = {};
  for (const el of document.querySelectorAll('#swuHomeStrips .swu-mb-card[data-mz]')) {
    const mz = el.getAttribute('data-mz');
    const upg = el.querySelector('.swu-mb-upgcount'), cap = el.querySelector('.swu-mb-capcount');
    out[mz] = {
      selectable: el.classList.contains('mini-selectable'),
      subcardHost: el.classList.contains('mini-subcard-host'),
      upg: upg ? upg.textContent.trim() : null,
      cap: cap ? cap.textContent.trim() : null,
      // The captive circle must actually BE a circle, and goldenrod — it is the tile echo of the
      // base's ARRESTED chip, and shape is what keeps it readable for a colour-blind player.
      capStyle: cap ? (() => { const s = getComputedStyle(cap); return { radius: s.borderRadius, bg: s.backgroundColor }; })() : null,
    };
  }
  return out;
});

const panelState = (page) => page.evaluate(() => {
  const p = document.getElementById('ga-lineage-popup');
  if (!p || !p.classList.contains('visible')) return { open: false };
  return {
    open: true,
    title: (p.querySelector('.ga-lineage-popup-title') || {}).textContent || '',
    entries: [...p.querySelectorAll('.ga-lineage-popup-card')].map((c) => ({
      mzid: c.getAttribute('data-mzid'),
      pickable: c.classList.contains('ga-lineage-popup-pickable'),
      cursor: getComputedStyle(c).cursor,
    })),
  };
});

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();
    const gn = await makeGame();
    const ctx = await browser.newContext({ viewport: { width: 1700, height: 1050 } });
    const page = await ctx.newPage();
    const errors = [];
    page.on('pageerror', (e) => errors.push(String(e)));
    await page.goto(url(gn, '1'), { waitUntil: 'domcontentloaded' });
    await page.waitForSelector('#swuHomeStrips .swu-home-strip', { timeout: 90000 });
    await page.waitForFunction(() => !!(window.SelectionMode && window.SelectionMode.active), null, { timeout: 60000 })
      .catch(() => {});

    // ── The cues ────────────────────────────────────────────────────────────────────────────────
    const tiles = await readTiles(page);
    const p3 = tiles['p3GroundArena-0'] || {}, p4 = tiles['p4GroundArena-0'] || {}, p2 = tiles['p2GroundArena-0'] || {};
    ok(engine, 'P3 tile is flagged a subcard host', p3.selectable === true && p3.subcardHost === true, JSON.stringify(p3));
    ok(engine, 'P4 tile is flagged a subcard host', p4.selectable === true && p4.subcardHost === true, JSON.stringify(p4));
    ok(engine, 'P2 (bare unit) stays dark', p2.selectable === false && p2.subcardHost === false, JSON.stringify(p2));
    // The split badges: a captive must not inflate the upgrade count.
    ok(engine, 'P3 upgrade pill counts the Shield only', p3.upg === '1', 'upg=' + p3.upg);
    ok(engine, 'P3 captive circle counts the captive', p3.cap === '1', 'cap=' + p3.cap);
    ok(engine, 'the captive badge is a goldenrod circle',
      !!p3.capStyle && /50%|9999px/.test(p3.capStyle.radius) && /218,\s*165,\s*32/.test(p3.capStyle.bg),
      JSON.stringify(p3.capStyle));

    // ── Clicking the tile opens the panel instead of submitting the host ────────────────────────
    await page.click('#swuHomeStrips .swu-mb-card[data-mz="p3GroundArena-0"]');
    await page.waitForTimeout(250);
    const panel = await panelState(page);
    ok(engine, 'the tile click opens the attached-upgrades panel', panel.open === true && /Attached Upgrades/.test(panel.title), JSON.stringify(panel).slice(0, 200));
    const shield = (panel.entries || []).find((e) => e.mzid === 'p3GroundArena-0.u0');
    ok(engine, 'the offered Shield entry is pickable', !!shield && shield.pickable === true && shield.cursor === 'pointer', JSON.stringify(shield));
    const captiveEntry = (panel.entries || []).find((e) => e.mzid === 'p3GroundArena-0.u1');
    ok(engine, 'the captive entry is NOT pickable under an upgrade offer', !captiveEntry || captiveEntry.pickable === false, JSON.stringify(captiveEntry));
    // The decision must still be pending — a tile click that submitted anything is the bug itself.
    ok(engine, 'nothing was submitted by the tile click', await page.evaluate(() => !!(window.SelectionMode && window.SelectionMode.active)));

    // ── Picking it in the panel resolves the ability ────────────────────────────────────────────
    await page.click('#ga-lineage-popup .ga-lineage-popup-card[data-mzid="p3GroundArena-0.u0"]');
    await page.waitForTimeout(1200);
    const after = await readTiles(page);
    const p3after = after['p3GroundArena-0'] || {};
    ok(engine, 'the Shield is gone from P3 (upgrade pill cleared, captive kept)',
      p3after.upg === null && p3after.cap === '1', JSON.stringify(p3after));
    const flash = await page.evaluate(() => (document.body.innerText.match(/Invalid selection\.?/) || [null])[0]);
    ok(engine, 'no "Invalid selection." anywhere on the page', flash === null, String(flash));
    ok(engine, 'no uncaught page errors', errors.length === 0, errors.slice(0, 2).join(' | '));

    await ctx.close();
  } catch (e) {
    ok(engine, 'harness completed', false, String(e && e.message ? e.message : e));
  } finally {
    if (browser) await browser.close();
  }
}

report();
process.exit(allOk ? 0 : 1);
