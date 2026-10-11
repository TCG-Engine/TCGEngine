// In a Twin Suns "you vs P{n}" matchup, opening the opponent's discard must show P{n}'s pile.
//
// THE BUG (mobile report 2026-10-03: "When I switch to another player and click the discard it always shows
// the same discard"; desktop identical). The matchup view repaints `their…` zones client-side from the
// viewed seat, so the slot drew P3's top card — but the popup is fetched from GetPopupContent.php by NAME,
// and the server resolves `theirDiscard` to the HOME view's opponent (P2). Every matchup opened P2's pile.
// Fix: SimRemapPopupZone (SWUSim/Custom/GameLayoutShared.php) renames a PUBLIC zone to its seat-tagged
// form (`p3Discard`) when the view's seat differs from the server's.
//
// Seeds Tests/Visual/TwinSuns_4P_AllZonesFilled.md — its discards are one SET per seat (P1 SOR, P2 SHD,
// P3 TWI, P4 JTL), so "whose pile is this" is a prefix check. Run:
//   node DevTools/ui-harness/swusim-twinsuns-matchup-discard-xbrowser.mjs [chromium firefox webkit]
// Exit 0 = pass, 1 = regression, 2 = environment not ready.
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, join } from 'node:path';

const HERE = dirname(fileURLToPath(import.meta.url));
const FIXTURE = readFileSync(join(HERE, '..', '..', 'SWUSim', 'Tests', 'Visual', 'TwinSuns_4P_AllZonesFilled.md'), 'utf8');
const BASE = 'http://localhost:3400/TCGEngine/';
const SET = { 1: 'SOR', 2: 'SHD', 3: 'TWI', 4: 'JTL' };

const engines = process.argv.slice(2).length ? process.argv.slice(2) : ['chromium', 'firefox', 'webkit'];
let fails = 0, checks = 0;
const ok = (name, cond, extra) => {
  checks++;
  if (!cond) { fails++; console.log(`FAIL ${name}${extra === undefined ? '' : '  ' + JSON.stringify(extra)}`); }
};

async function seedGame() {
  const fd = new FormData();
  fd.append('schema', FIXTURE);
  let data;
  try { data = await (await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: fd })).json(); }
  catch (e) { console.log('ENVIRONMENT NOT READY: TestSchemaSetup.php failed — ' + e.message); process.exit(2); }
  if (!data || !data.gameName) { console.log('ENVIRONMENT NOT READY: no gameName — ' + JSON.stringify(data)); process.exit(2); }
  return data.gameName;
}

// The open popup: its id and the set prefixes of the cards it shows (waits for the async fetch).
async function openPopup(page) {
  await page.waitForFunction(() => [...document.querySelectorAll('[id$="Popup"]')]
    .some(e => e.style.display !== 'none' && e.querySelector('img')), null, { timeout: 8000 }).catch(() => {});
  return page.evaluate(() => {
    const e = [...document.querySelectorAll('[id$="Popup"]')].find(x => x.style.display !== 'none' && x.getBoundingClientRect().width > 0);
    if (!e) return { id: null, sets: [] };
    const ids = [...e.querySelectorAll('img')].map(i => i.src.split('/').pop().replace('.webp', '')).filter(s => /^[A-Z0-9]+_\d+$/.test(s));
    return { id: e.id, sets: [...new Set(ids.map(s => s.split('_')[0]))], n: ids.length };
  });
}
const closePopups = page => page.evaluate(() => {
  if (typeof ClosePopup === 'function') ClosePopup();
  document.querySelectorAll('[id$="Popup"]').forEach(e => { e.style.display = 'none'; });
  if (typeof HideCardDetail === 'function') HideCardDetail(true);
});

for (const engine of engines) {
  const game = await seedGame();
  const browser = await ({ chromium, firefox, webkit })[engine].launch();
  for (const [layout, width, height] of [['mobile', 390, 844], ['desktop', 1728, 1000]]) {
    const tag = `${engine}@${layout}`;
    const page = await (await browser.newContext({ viewport: { width, height } })).newPage();
    page.on('pageerror', e => { fails++; console.log(`FAIL ${tag} pageerror ${e.message}`); });
    await page.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${game}&playerID=1&authKey=testschema`
      + (layout === 'mobile' ? '&swuLayout=mobile' : ''), { waitUntil: 'load' });
    try { await page.waitForSelector('#swuHomeStrips [data-zone="p3Discard"]', { timeout: 15000 }); }
    catch { console.log(`ENVIRONMENT NOT READY: ${tag} home tiles never rendered`); process.exit(2); }
    await page.waitForTimeout(800);

    // 1. Home tile chips (already correct before the fix — a CONTROL that the remap leaves them alone).
    for (const seat of [2, 3, 4]) {
      await closePopups(page);
      await page.evaluate(s => document.querySelector(`#swuHomeStrips [data-zone="p${s}Discard"]`).click(), seat);
      const p = await openPopup(page);
      ok(`${tag} home chip P${seat} opens P${seat}'s discard`, p.sets.length === 1 && p.sets[0] === SET[seat], p);
    }

    // 2. Each matchup: the opponent's discard SLOT and its count bubble must open THAT seat's pile, and
    //    your own discard must still be yours. Visit P3 first so a stale-cache reading can't pass.
    for (const [i, seat] of [[1, 3], [2, 4], [0, 2], [1, 3]]) {
      await closePopups(page);
      await page.evaluate(n => document.querySelectorAll('.swu-mb-zoom, .swu-sr-zoom')[n].click(), i);
      await page.waitForFunction(s => window.swuView && window.swuView.oppSeat === s, seat, { timeout: 8000 });
      await page.waitForTimeout(500);
      const top = await page.evaluate(() => (document.querySelector('#theirDiscardSlot img') || {}).src || '');
      ok(`${tag} vs P${seat} slot draws P${seat}'s top card (control)`, top.includes('/' + SET[seat] + '_'), top.split('/').pop());

      await page.evaluate(() => document.querySelector('#theirDiscardSlot a').click());
      let p = await openPopup(page);
      ok(`${tag} vs P${seat} opponent discard popup is P${seat}'s pile`, p.sets.length === 1 && p.sets[0] === SET[seat], p);
      ok(`${tag} vs P${seat} popup shows the whole pile`, p.n === 6, p);
      // The default opponent keeps its legacy popup id (playable-from-popup discards key on theirDiscard-N).
      if (seat === 2) ok(`${tag} vs P2 keeps the theirDiscardPopup id`, p.id === 'theirDiscardPopup', p);
      await closePopups(page);

      await page.evaluate(() => document.querySelector('#myDiscardSlot a').click());
      p = await openPopup(page);
      ok(`${tag} vs P${seat} my discard popup is still mine`, p.sets.length === 1 && p.sets[0] === SET[1], p);
      await closePopups(page);

      await page.evaluate(() => { const b = [...document.querySelectorAll('button, a, div')].find(e => /^\s*←?\s*Go back\s*$/i.test(e.textContent)); if (b) b.click(); });
      await page.waitForFunction(() => window.swuView && window.swuView.mode === 'home', null, { timeout: 8000 }).catch(() => {});
      await page.waitForTimeout(400);
    }
    await page.close();
  }
  await browser.close();
}
console.log(`${checks - fails}/${checks} checks passed`);
process.exit(fails ? 1 : 0);
