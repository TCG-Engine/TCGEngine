// A WHOLE-DECK search (SOR_042 Search Your Feelings) in the top-deck search panel.
// Bug report 2026-10-01 (game 1438045): "Search Your Feelings when played gives a terrible UI/UX". The panel was
// built for a handful of top cards; given the whole deck it drew ~45 cards as 180px art in an unbounded box, so
// its title and its confirm button were OFF-SCREEN, and the deck came in raw order.
// Core/UILibraries20260928.js ShowTopDeckSearchPanel + the 'deck' scope segment (SWUSim/Custom/GameLogic.php
// _topDeckSearchBegin). Pinned, Chromium / Firefox / WebKit, desktop 1600x1000 and phone 390x844:
//   · title "SEARCH YOUR DECK"; the box fits the viewport; title, filter and CONFIRM are on-screen; only the grid scrolls;
//   · tiles sorted by cost; the filter narrows in place and keeps focus while typing;
//   · a pick keeps the filter AND the grid's scroll position; confirm answers with the picked CardID;
//   · hovering a tile opens the card preview (desktop);
//   · CONTROL: a 5-card top search keeps "SEARCH THE TOP CARDS", 180px art and no filter.
//   node DevTools/ui-harness/swusim-deck-search-panel-xbrowser.mjs     (seeds its own board)
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '/tmp/deck-search';
fs.mkdirSync(SHOTS, { recursive: true });
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };
const want = (n, c, m) => c ? ok() : bad(n, m);

// A real 50-card list (each copy its own entry), in fixture order — NOT sorted, so the sort is what is tested.
const fx = fs.readFileSync(new URL('../../SWUSim/Tests/BotFixtures/ash-meta-2026-09/director-krennic_law_blue.txt', import.meta.url), 'utf8');
const deckIDs = [];
let inDeck = false;
for (const line of fx.split('\n')) {
  const t = line.trim();
  if (t === 'Deck') { inDeck = true; continue; }
  if (t === 'Sideboard') break;
  const m = inDeck && /^(\d+)\s+(\S+)/.exec(t);
  if (m) for (let i = 0; i < +m[1]; i++) deckIDs.push(m[2]);
}

const SCHEMA = ['## GIVEN', 'CommonSetup: rrk/bbw/{myLeader:LAW_008; theirLeader:ASH_009}', 'SkipPreGame: true',
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

// Set the filter the way a PLAYER does — click the field, select what is there, type. ⚠ Not page.fill(): on
// WebKit, fill() on this type=search field left the page swallowing the NEXT click on a tile (the pick never
// registered), while click-and-type — the real path — works in every engine.
const setFilter = async (p, text) => {
  await p.click('.topdecksearch-filter', { clickCount: 3 });
  await p.keyboard.press('Backspace');
  if (text) await p.keyboard.type(text, { delay: 20 });
};
// Opens the panel exactly as the engine's TOPDECKSEARCH decision does (param from _topDeckSearchBegin).
const openPanel = (p, ids, scope) => p.evaluate(([ids, scope]) => {
  window.__answer = undefined;
  const costMap = ids.map(id => id + ':' + ((typeof Cardcost === 'function' && Cardcost(id)) || 0)).join(',');
  const param = [ids.join(','), ids.join(','), 'count:1', costMap, 'cards', 'Take', scope].join('|');
  ShowTopDeckSearchPanel({ Param: param }, 0, (a) => { window.__answer = a; });
}, [ids, scope]);
const state = (p) => p.evaluate(() => {
  const ov = document.getElementById('topdecksearch-panel');
  if (!ov) return { open: false };
  const box = ov.querySelector('.topdecksearch-box'), grid = ov.querySelector('.topdecksearch-grid');
  const title = box.firstElementChild && box.firstElementChild.firstElementChild, confirm = [...box.querySelectorAll('button')].pop();
  const filter = box.querySelector('.topdecksearch-filter');
  const inView = (el) => { if (!el) return false; const r = el.getBoundingClientRect(); return r.top >= 0 && r.bottom <= innerHeight + 1 && r.left >= 0 && r.right <= innerWidth + 1 && r.height > 0; };
  const imgs = [...grid.querySelectorAll('img')];
  return { open: true, title: title?.textContent, titleIn: inView(title), confirmIn: inView(confirm), filterIn: filter ? inView(filter) : null,
    boxIn: inView(box), gridScrolls: grid.scrollHeight > grid.clientHeight + 2, scrollTop: grid.scrollTop,
    tileH: imgs[0] ? Math.round(imgs[0].getBoundingClientRect().height) : 0,
    visibleIDs: imgs.filter(i => i.parentElement.style.display !== 'none').map(i => i.getAttribute('src').split('/').pop().replace(/\.webp.*/, '')),
    confirmText: confirm?.textContent, hasFilter: !!filter, filterFocused: document.activeElement === filter, filterValue: filter?.value };
});

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  for (const [label, vw, vh] of [['desktop', 1600, 1000], ['phone', 390, 844]]) {
    const n = `${engine}/${label}`;
    const p = await b.newPage({ viewport: { width: vw, height: vh } });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${GAME}&playerID=1&authKey=testschema${label === 'phone' ? '&swuLayout=mobile' : ''}`, { waitUntil: 'domcontentloaded' });
    await p.waitForFunction(() => typeof ShowTopDeckSearchPanel === 'function' && typeof Cardcost === 'function', null, { timeout: 20000 });
    await p.waitForTimeout(1200);

    await openPanel(p, deckIDs, 'deck');
    await p.waitForTimeout(800);
    let s = await state(p);
    want(n, s.open && s.title === 'SEARCH YOUR DECK', `title: ${s.title}`);
    want(n, s.boxIn && s.titleIn && s.confirmIn && s.filterIn, `box/title/filter/confirm all on-screen (box ${s.boxIn}, title ${s.titleIn}, filter ${s.filterIn}, confirm ${s.confirmIn})`);
    want(n, s.gridScrolls, 'only the card grid scrolls (it overflows inside a capped box)');
    want(n, s.visibleIDs.length === deckIDs.length, `all ${deckIDs.length} cards listed (${s.visibleIDs.length})`);
    const costs = await p.evaluate((ids) => ids.map(id => Cardcost(id) || 0), s.visibleIDs);
    want(n, costs.every((c, i) => i === 0 || costs[i - 1] <= c), `sorted by cost: ${costs.slice(0, 12).join(',')}…`);
    await p.screenshot({ path: `${SHOTS}/${engine}-${label}-open.png` });

    // filter, in place, focus kept
    const target = await p.evaluate(() => Cardtitle('ASH_052') ? 'Chimaera' : null);   // Krennic Blue runs Chimaera
    await p.click('.topdecksearch-filter');
    await p.keyboard.type('chim', { delay: 40 });
    s = await state(p);
    want(n, s.filterFocused && s.filterValue === 'chim', `the filter keeps focus while typing (${s.filterFocused}, "${s.filterValue}")`);
    want(n, s.visibleIDs.length >= 1 && s.visibleIDs.every(id => id === 'ASH_052'), `"chim" leaves only Chimaera: ${JSON.stringify(s.visibleIDs)}`);
    // clear, scroll the grid down, pick a card low in the list — the scroll and the filter must survive the re-render
    await setFilter(p, '');
    await p.evaluate(() => { const g = document.querySelector('.topdecksearch-grid'); g.scrollTop = g.scrollHeight; });
    await p.waitForTimeout(200);
    const before = (await state(p)).scrollTop;
    const last = p.locator('.topdecksearch-grid img').last();
    const lastID = (await last.getAttribute('src')).split('/').pop().replace(/\.webp.*/, '');
    await last.click({ force: true });
    await p.waitForTimeout(300);
    s = await state(p);
    want(n, before > 0 && Math.abs(s.scrollTop - before) <= 2, `a pick keeps the grid's scroll position (${before} -> ${s.scrollTop})`);
    want(n, /1 card/.test(s.confirmText || ''), `confirm reflects the pick: "${s.confirmText}"`);
    await setFilter(p, 'chim');
    await p.locator('.topdecksearch-grid img:visible').first().click({ force: true });   // swap the pick to Chimaera
    await p.waitForTimeout(300);
    s = await state(p);
    want(n, s.filterValue === 'chim' && s.visibleIDs.every(id => id === 'ASH_052'), `a pick keeps the filter ("${s.filterValue}")`);
    await p.screenshot({ path: `${SHOTS}/${engine}-${label}-filtered.png` });
    // Confirm, from a CLEAN panel: filter to Chimaera, pick it, commit. (A toggle history — pick, swap,
    // un-pick — made this step depend on count:1's no-op-at-limit rule and flaked on WebKit phone.)
    await openPanel(p, deckIDs, 'deck'); await p.waitForTimeout(500);
    await setFilter(p, 'chim');
    await p.locator('.topdecksearch-grid img:visible').first().click({ force: true });
    await p.waitForTimeout(300);
    want(n, /1 card/.test((await state(p)).confirmText || ''), 'the Chimaera pick registered');
    await p.locator('#topdecksearch-panel button').last().click();
    await p.waitForTimeout(300);
    const answer = await p.evaluate(() => window.__answer);
    want(n, answer === 'ASH_052', `confirm answers with the picked card: ${JSON.stringify(answer)} (target ${target})`);

    // hover preview (desktop only — the phone uses long-press)
    if (label === 'desktop') {
      await openPanel(p, deckIDs, 'deck'); await p.waitForTimeout(600);
      await p.locator('.topdecksearch-grid img').nth(3).hover({ force: true });
      await p.waitForTimeout(1600);
      const prev = await p.evaluate(() => { const el = document.getElementById('cardDetail'); return !!el && el.style.display !== 'none' && !!el.querySelector('img'); });
      want(n, prev, 'hovering a tile opens the card preview');
      await p.screenshot({ path: `${SHOTS}/${engine}-${label}-hover.png` });
      await p.mouse.move(2, 2);
      await p.evaluate(() => document.getElementById('topdecksearch-panel')?.remove());
    }

    // CONTROL: a small top-5 search keeps its look
    await openPanel(p, deckIDs.slice(0, 5), 'top');
    await p.waitForTimeout(600);
    s = await state(p);
    want(n, s.title === 'SEARCH THE TOP CARDS' && !s.hasFilter && s.tileH >= 170, `top-5 search unchanged (title "${s.title}", filter ${s.hasFilter}, tile ${s.tileH}px)`);
    want(n, s.confirmIn, 'top-5 search: confirm on-screen');
    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await p.close();
  }
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} deck-search checks failed` : `\nDECK SEARCH PANEL OK — ${checks} checks, 3 engines x 2 widths (shots in ${SHOTS})`);
process.exit(fails ? 1 : 0);
