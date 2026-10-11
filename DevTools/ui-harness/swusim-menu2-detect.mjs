// Task 16b gate: detection must use the REAL backend, not the mockup's fixture table.
// The link below is a genuine Twin Suns list that is NOT one of the four mockup samples, so it
// only works if the client actually asks ValidateDeck.php.
import { chromium, firefox, webkit } from 'playwright';
const URL = process.env.MENU_URL || 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
const TWIN = 'https://swudb.com/deck/oNDdHLCHkyz';   // Kylo Ren / Major Vonreg, 80 cards
const PREM = 'https://swudb.com/deck/LImIrpIS';      // Boba Fett, 51 cards, premier
let fails = 0, checks = 0;

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 950 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));

  // a real Twin Suns link pasted into PvP must move the player, with a reason
  await p.goto(URL, { waitUntil: 'networkidle' });
  await p.locator('.mode').first().click();
  await p.waitForTimeout(300);
  await p.fill('dialog[open] input[data-detect]', TWIN);
  await p.dispatchEvent('dialog[open] input[data-detect]', 'change');
  await p.waitForTimeout(4000);                      // the backend fetches the deck live
  const sw = await p.evaluate(() => {
    const d = document.querySelector('dialog[open]');
    const db = d && d.getAttribute('aria-describedby');
    const desc = db ? db.split(/\s+/).map(i => (document.getElementById(i) || {}).textContent || '').join(' ').trim() : '';
    return { id: d && d.id, link: d && (d.querySelector('input[data-detect]') || {}).value, desc };
  });
  checks++;
  if (sw.id !== 'setup-twin-suns')      { fails++; console.log(`FAIL ${name} :: real Twin Suns link did not switch (in ${sw.id})`); }
  else if (sw.link !== TWIN)            { fails++; console.log(`FAIL ${name} :: deck link not carried across`); }
  else if (!/two leaders/i.test(sw.desc)) { fails++; console.log(`FAIL ${name} :: no reason in the accessible description ("${sw.desc}")`); }

  // a real Premier link must NOT switch, and must set the chip
  await p.goto(URL, { waitUntil: 'networkidle' });
  await p.locator('.mode').first().click();
  await p.waitForTimeout(300);
  await p.fill('dialog[open] input[data-detect]', PREM);
  await p.dispatchEvent('dialog[open] input[data-detect]', 'change');
  await p.waitForTimeout(4000);
  const stay = await p.evaluate(() => {
    const d = document.querySelector('dialog[open]');
    return { id: d && d.id, chip: (d.querySelector('.lb--chip .lb__btn') || {}).textContent?.trim(),
             note: (d.querySelector('.poolnote') || {}).textContent?.trim() || '' };
  });
  checks++;
  if (stay.id !== 'setup-pvp')            { fails++; console.log(`FAIL ${name} :: a Premier link wrongly switched to ${stay.id}`); }
  else if (!/premier/i.test(stay.chip || '')) { fails++; console.log(`FAIL ${name} :: chip not set to Premier (got "${stay.chip}")`); }
  else if (!/detected/i.test(stay.note))  { fails++; console.log(`FAIL ${name} :: no detected note`); }

  if (errs.length) { fails++; checks++; console.log(`FAIL ${name} :: pageerror ${errs[0]}`); }
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} detection checks failed` : `\nDETECTION LIVE — ${checks} checks`);
process.exit(fails ? 1 : 0);
