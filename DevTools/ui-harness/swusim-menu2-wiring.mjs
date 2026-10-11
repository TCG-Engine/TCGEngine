// Task 17 gate: the modal's primary actions must reach the page's existing game-start functions.
//
// The submission path (getDeckSubmission -> joinQueue/createPrivateGame/startSoloGame) is proven
// and load-bearing, so it is NOT rewritten. Instead the open modal's values are synced into the
// legacy hidden fields it already reads. This asserts that sync, and that every primary button
// is actually bound to something.
import { chromium, firefox, webkit } from 'playwright';
const URL = process.env.MENU_URL || 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
const LINK = 'https://swudb.com/deck/eeFFtweXI';
let fails = 0, checks = 0;

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 950 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(URL, { waitUntil: 'networkidle' });

  // every primary action in every modal must be bound
  const unbound = await p.evaluate(() => {
    const out = [];
    for (const d of document.querySelectorAll('dialog.setup')) {
      for (const btn of d.querySelectorAll('button')) {
        const t = btn.textContent.trim();
        if (!/join queue|create private room|start arenabot|start 1p|save deck/i.test(t)) continue;
        if (!btn.dataset.act && !btn.onclick && !btn.getAttribute('onclick')) out.push(d.id + ':' + t.slice(0, 20));
      }
    }
    return out;
  });
  checks++;
  if (unbound.length) { fails++; console.log(`FAIL ${name} unbound primary actions :: ${unbound.join(', ')}`); }

  // the open modal's deck link and pool must reach the legacy submission fields
  await p.locator('.mode').first().click();
  await p.waitForTimeout(350);
  await p.fill('dialog[open] input[data-detect]', LINK);
  const sync = await p.evaluate(() => {
    if (typeof window.SYNC_ACTIVE_SETUP !== 'function') return { missing: true };
    window.SYNC_ACTIVE_SETUP();
    const g = (id) => (document.getElementById(id) || {}).value;
    return { deck: g('deck-link'), format: g('swu-format-select'), queue: g('swu-queuetype-select') };
  });
  checks++;
  if (sync.missing)            { fails++; console.log(`FAIL ${name} :: SYNC_ACTIVE_SETUP not defined`); }
  else if (sync.deck !== LINK) { fails++; console.log(`FAIL ${name} :: deck link did not reach #deck-link (got ${JSON.stringify(sync.deck)})`); }
  else if (!sync.format)       { fails++; console.log(`FAIL ${name} :: format did not reach #swu-format-select`); }

  if (errs.length) { fails++; checks++; console.log(`FAIL ${name} :: pageerror ${errs[0]}`); }
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} wiring checks failed` : `\nALL WIRED — ${checks} checks`);
process.exit(fails ? 1 : 0);
