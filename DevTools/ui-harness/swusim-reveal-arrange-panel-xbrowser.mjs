// The REVEALARRANGE panel ("discard some, put the rest back on top in any order") with a DISCARD LIMIT.
// Bug report 2026-10-01: LAW_237 Qui-Gon Jinn, Influencing Chance — "not letting me choose the order to put back
// on top". Qui-Gon now raises ONE REVEALARRANGE with Param "ids|1" (discard up to 1). Core/UILibraries20261001.js
// ShowRevealArrangePanel. Pinned, Chromium / Firefox / WebKit, desktop 1600x1000 and phone 390x844:
//   · limit 1: after one Discard, no card offers Discard any more and the hint reads "Discard up to 1 (1 used)";
//   · DUPLICATE COPIES: two SOR_095 revealed together — taking one leaves the other on the panel (the old
//     remove-by-CardID dropped both while recording one);
//   · each Top puts that card ONTO the deck (owner 2026-10-02: "to simulate real game play") — the LAST card put
//     back is the top card, so the answer "keptTopFirst|discarded" is the Top clicks REVERSED;
//   · CONTROL: no limit (SOR_152 For a Cause I Believe In) still offers Discard on every card.
//   node DevTools/ui-harness/swusim-reveal-arrange-panel-xbrowser.mjs     (seeds its own board)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '/tmp/reveal-arrange';
fs.mkdirSync(SHOTS, { recursive: true });
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };
const want = (n, c, m) => c ? ok() : bad(n, m);

const SCHEMA = ['## GIVEN', 'CommonSetup: yyk/bgw/{}', 'SkipPreGame: true',
  'WithGamePhase: ActionPhase', 'WithActivePlayer: 1', '## WHEN', '## EXPECT'].join('\n');
async function seedBoard() {
  const form = (o) => new URLSearchParams(o).toString();
  const lr = await fetch(BASE + 'AccountFiles/AttemptPasswordLogin.php', { method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, body: form({ submit: '1', userID: 'claudebot1', password: 'pass' }), redirect: 'manual' });
  const cookie = (lr.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded', cookie }, body: form({ schema: SCHEMA }), redirect: 'manual' });
  const j = JSON.parse(await r.text());
  if (j.error) throw new Error(j.error);
  return String(j.gameName);
}
let GAME = process.env.GAME;
if (!GAME) { try { GAME = await seedBoard(); console.log(`seeded board ${GAME}`); } catch (e) { console.log(`FAIL :: seed :: ${e.message}`); process.exit(1); } }

// Opens the panel exactly as the engine's REVEALARRANGE decision does.
const openPanel = (p, param, tooltip) => p.evaluate(([param, tooltip]) => {
  window.__answer = undefined;
  ShowRevealArrangePanel({ Param: param, Tooltip: tooltip }, 0, (a) => { window.__answer = a; });
}, [param, tooltip]);
const state = (p) => p.evaluate(() => {
  const ov = document.getElementById('revealarrange-panel');
  if (!ov) return { open: false };
  const wraps = [...ov.querySelectorAll('img')].map(i => i.parentElement);
  const panel = ov.firstElementChild, r = panel.getBoundingClientRect();
  return { open: true,
    cards: wraps.map(w => w.querySelector('img').getAttribute('src').split('/').pop().replace(/\.webp.*/, '')),
    discardBtns: [...ov.querySelectorAll('button')].filter(b => b.textContent === 'Discard').length,
    topBtns: [...ov.querySelectorAll('button')].filter(b => b.textContent === 'Top').length,
    hint: panel.children[1]?.textContent || '',
    imgH: Math.round(ov.querySelector('img')?.getBoundingClientRect().height || 0),
    fits: r.left >= -1 && r.right <= innerWidth + 1 && r.top >= -1 && r.bottom <= innerHeight + 1,
    // Nothing on the panel overlaps: each card's art and each button clear their neighbours (a phone once had
    // every card on-screen yet pressed into the next, with four Discard buttons wider than their cards).
    overlaps: (() => { const els = [...ov.querySelectorAll('img, button')].map(e => e.getBoundingClientRect());
      let k = 0; for (let i = 0; i < els.length; i++) for (let j = i + 1; j < els.length; j++) {
        const x = els[i], y = els[j]; if (x.left < y.right - 0.5 && y.left < x.right - 0.5 && x.top < y.bottom - 0.5 && y.top < x.bottom - 0.5) k++; }
      return k; })() };
});
// Click a button on the Nth card still on the panel.
const press = async (p, slot, label) => {
  await p.locator('#revealarrange-panel img').nth(slot).locator('xpath=..').getByRole('button', { name: label, exact: true }).click();
  await p.waitForTimeout(150);
};

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  for (const [label, vw, vh] of [['desktop', 1600, 1000], ['phone', 390, 844]]) {
    const n = `${engine}/${label}`;
    const p = await b.newPage({ viewport: { width: vw, height: vh } });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${GAME}&playerID=1&authKey=testschema${label === 'phone' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'domcontentloaded' });
    await p.waitForFunction(() => typeof ShowRevealArrangePanel === 'function', null, { timeout: 20000 });
    await p.waitForTimeout(1200);

    // Qui-Gon: top 3 with a duplicate, discard up to 1.
    await openPanel(p, 'SOR_095,SOR_046,SOR_095|1', 'Discard_up_to_1_then_put_the_rest_back_on_top_in_any_order');
    await p.waitForFunction(() => [...document.querySelectorAll('#revealarrange-panel img')].every(i => i.complete && i.naturalWidth > 0), null, { timeout: 10000 });   // geometry needs the art loaded
    let s = await state(p);
    want(n, s.open && s.cards.length === 3 && s.discardBtns === 3 && s.topBtns === 3, `opens with 3 cards, each offering Top + Discard (${JSON.stringify(s)})`);
    want(n, /Discard up to 1/.test(s.hint), `hint names the limit: "${s.hint}"`);
    want(n, s.fits, 'the panel fits the viewport');
    want(n, s.overlaps === 0, `nothing on the panel overlaps (${s.overlaps} overlapping pairs)`);
    // Desktop keeps the panel's original 200px-tall cards; the phone sizing must not shrink them.
    if (label === 'desktop') want(n, s.imgH >= 195 && s.imgH <= 205, `desktop cards stay 200px tall (${s.imgH}px)`);
    await p.screenshot({ path: `${SHOTS}/${engine}-${label}-open.png` });
    await press(p, 2, 'Top');                       // the BOTTOM card (second SOR_095) goes on top
    s = await state(p);
    want(n, JSON.stringify(s.cards) === '["SOR_095","SOR_046"]', `taking one SOR_095 leaves the other copy: ${JSON.stringify(s.cards)}`);
    await press(p, 0, 'Discard');                   // discard the other SOR_095 — the limit is now reached
    s = await state(p);
    want(n, JSON.stringify(s.cards) === '["SOR_046"]', `after the discard one card remains: ${JSON.stringify(s.cards)}`);
    want(n, s.discardBtns === 0 && s.topBtns === 1, `at the limit no Discard is offered (discard ${s.discardBtns}, top ${s.topBtns})`);
    want(n, /\(1 used\)/.test(s.hint), `hint shows the limit used: "${s.hint}"`);
    want(n, /Top of deck now: Battlefield Marine/.test(s.hint), `hint names the card now on top: "${s.hint}"`);
    await p.screenshot({ path: `${SHOTS}/${engine}-${label}-at-limit.png` });
    await press(p, 0, 'Top');
    const answer = await p.evaluate(() => window.__answer);
    // Put back SOR_095 first, then SOR_046 ON it — SOR_046 is the top card.
    want(n, answer === 'SOR_046,SOR_095|SOR_095', `the last card put back is on top: ${JSON.stringify(answer)}`);
    want(n, !(await state(p)).open, 'the panel closes on the last card');

    // CONTROL: no limit (For a Cause) — Discard stays offered after a discard, and discard-all is reachable.
    // FOUR cards — For a Cause's look, the widest this panel gets — must still fit a phone.
    await openPanel(p, 'SOR_237,SOR_046,SOR_128,SOR_225', '-');
    await p.waitForTimeout(300);
    s = await state(p);
    want(n, s.fits && s.overlaps === 0, `four cards fit the viewport without overlapping (fits ${s.fits}, ${s.overlaps} overlaps)`);
    await p.screenshot({ path: `${SHOTS}/${engine}-${label}-four.png` });
    await press(p, 0, 'Discard');
    s = await state(p);
    want(n, s.discardBtns === 3 && !/Discard up to/.test(s.hint), `no limit: Discard still on every remaining card (${s.discardBtns}), no limit hint`);
    await press(p, 0, 'Discard'); await press(p, 0, 'Discard'); await press(p, 0, 'Discard');
    want(n, (await p.evaluate(() => window.__answer)) === '|SOR_237,SOR_046,SOR_128,SOR_225', 'no limit: discard-all answers "|all"');
    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await p.close();
  }
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} reveal-arrange checks failed` : `\nREVEAL ARRANGE PANEL OK — ${checks} checks, 3 engines x 2 widths (shots in ${SHOTS})`);
process.exit(fails ? 1 : 0);
