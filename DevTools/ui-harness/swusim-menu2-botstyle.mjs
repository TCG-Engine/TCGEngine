// Arenabot's bot-style auto-picker, in the REDESIGNED modal.
//
// The legacy swuAutoPickBotStyle() keyed on hidden legacy fields (swu-deck2-input, deck-link,
// deck-text) that the modals only write at SUBMIT time, and wrote the hidden #swu-botstyle-select
// — so in the redesign it never fired and never touched the visible control. This gate pins the
// replacement: the BOT slot's deck drives the visible #ab-style, pre-cons resolve with no network,
// and every pick is ANNOUNCED (the legacy silent overwrite read as a bug).
//
// Owner ruling 2026-09-22: every deck load re-picks, even over a manual change; a failed lookup
// changes nothing.
import { chromium, firefox, webkit } from 'playwright';
const URL  = process.env.MENU_URL || 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
const BASE = URL.replace(/SharedUI\/MainMenu\.php.*$/, '');
const BOTLINK = 'https://swudb.com/deck/rYBmXPaxDUaSY';   // real list; the API decides its archetype
const OWNLINK = 'https://swudb.com/deck/LImIrpIS';        // real list, different deck
let fails = 0, checks = 0;
const slug = s => String(s || '').toLowerCase().replace(/[^a-z0-9]+/g, '');
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

// What the classifier itself says for a link — the gate must not hard-code an archetype, or it
// would go red whenever the fixture set is retuned rather than when the wiring breaks.
async function apiStyle(page, link) {
  return page.evaluate(async ([base, deck]) => {
    const r = await fetch(base + 'APIs/SWUBotDeckStyle.php', {
      method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: 'rootName=SWUSim&deckLink=' + encodeURIComponent(deck),
    });
    const j = await r.json();
    return j && j.ok ? j.style : null;
  }, [BASE, link]);
}

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 950 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  let apiHits = 0;
  p.on('request', r => { if (r.url().includes('SWUBotDeckStyle.php')) apiHits++; });

  const open = async () => {
    await p.goto(URL, { waitUntil: 'networkidle' });
    await p.click('a.mode[href="#setup-arenabot"]');
    await p.waitForTimeout(350);
  };
  const style = () => p.evaluate(() => {
    const s = document.getElementById('ab-style');
    return s ? (s.options[s.selectedIndex] || {}).text || s.value : null;
  });
  // Deliberately agnostic about WHERE the announcement lives: the contract is that a visible
  // status message in the modal says a style was set, not that it sits in a particular box.
  const botMsg = () => p.evaluate(() => [...document.querySelectorAll('#setup-arenabot [role="status"]')]
    .filter(m => !m.hidden).map(m => (m.textContent || '').trim()).join(' | '));

  // ── 1. A bot PRE-CON sets the style with NO network call ───────────────────
  await open();
  const pre = await p.evaluate(() => {
    // pick a row whose style differs from what is selected, so a pass cannot be a coincidence
    const cur = (() => { const s = document.getElementById('ab-style');
                         return (s.options[s.selectedIndex] || {}).text.toLowerCase().replace(/[^a-z0-9]+/g, ''); })();
    const rows = [...document.querySelectorAll('#setup-arenabot input[name="ab-precon"]')];
    const hasAttr = rows.some(r => r.hasAttribute('data-style'));
    const want = rows.find(r => (r.getAttribute('data-style') || '') && r.getAttribute('data-style') !== cur);
    return { hasAttr, id: want ? want.id : null, style: want ? want.getAttribute('data-style') : null, was: cur };
  });
  if (!pre.hasAttr) bad(name, 'bot pre-con rows carry no data-style — the picker has no offline source');
  else if (!pre.id)  bad(name, 'no bot pre-con with a style differing from the default');
  else {
    const before = apiHits;
    await p.evaluate(id => document.getElementById(id).click(), pre.id);
    await p.waitForTimeout(600);
    const got = slug(await style());
    if (got !== pre.style) bad(name, `pre-con style not applied: want ${pre.style}, got ${got} (was ${pre.was})`);
    else ok();
    if (apiHits !== before) bad(name, `pre-con hit the classifier API ${apiHits - before}x — its style is already local`);
    else ok();
    const msg = await botMsg();
    if (!/style/i.test(msg)) bad(name, `pre-con pick not announced as a style change (msg: "${msg}")`);
    else ok();
  }

  // ── 2. A bot deck LINK asks the classifier and applies its answer ──────────
  await open();
  const want2 = await apiStyle(p, BOTLINK);
  if (!want2) bad(name, 'classifier API gave no style for the fixture link — cannot test the wiring');
  else {
    const before = apiHits;
    await p.fill('#ab-bot-link', BOTLINK);
    await p.dispatchEvent('#ab-bot-link', 'change');
    await p.waitForTimeout(5000);
    if (apiHits <= before) bad(name, 'a bot deck link did not ask the classifier');
    else ok();
    const got = slug(await style());
    if (got !== want2) bad(name, `bot link style not applied: API says ${want2}, control says ${got}`);
    else ok();
    if (!/style/i.test(await botMsg())) bad(name, 'bot link pick not announced');
    else ok();
  }

  // ── 3. With the bot slot EMPTY, the own deck drives it ────────────────────
  // JoinQueue.php falls back to the host's deck when no bot deck is given, so the style must
  // follow the deck the bot will actually play.
  await open();
  const want3 = await apiStyle(p, OWNLINK);
  if (!want3) bad(name, 'classifier API gave no style for the own-deck fixture');
  else {
    // Empty the bot slot the way a player does: type in the bot box (which drops the default
    // pre-con), then clear it. Without this the default pre-con still answers and the fallback
    // is never exercised — the first version of this check passed with the fallback DELETED,
    // because ahsoka_blue and this link happen to share an archetype.
    await p.fill('#ab-bot-link', 'x');
    await p.dispatchEvent('#ab-bot-link', 'input');
    await p.fill('#ab-bot-link', '');
    await p.dispatchEvent('#ab-bot-link', 'input');
    const empty = await p.evaluate(() =>
      document.querySelectorAll('#setup-arenabot input[data-slot="bot"][data-deck-input]:checked').length);
    if (empty !== 0) bad(name, `bot slot not empty (${empty} pre-con still checked) — fallback untested`);
    else ok();
    // a sentinel the answer cannot be, so a no-op leaves evidence instead of looking right
    const sentinel = ['hyperaggro', 'hardcontrol'].find(s => s !== want3);
    await p.evaluate(s => {
      const sel = document.getElementById('ab-style');
      sel.selectedIndex = [...sel.options].findIndex(o => o.text.toLowerCase().replace(/[^a-z0-9]+/g, '') === s);
    }, sentinel);
    await p.fill('#ab-link', OWNLINK);
    await p.dispatchEvent('#ab-link', 'change');
    await p.waitForTimeout(5000);
    const got = slug(await style());
    if (got !== want3) bad(name, `own-deck fallback not applied: API says ${want3}, control says ${got}`);
    else ok();
  }

  // ── 4. A failed lookup changes NOTHING ────────────────────────────────────
  await open();
  await p.selectOption('#ab-style', { label: 'Hard Control' });
  await p.fill('#ab-bot-link', 'https://swudb.com/deck/ZZnotarealdeck');
  await p.dispatchEvent('#ab-bot-link', 'change');
  await p.waitForTimeout(5000);
  if (slug(await style()) !== 'hardcontrol')
    bad(name, `an unreadable deck overwrote the hand-picked style (now ${await style()})`);
  else ok();

  // ── 5. The pick reaches the SUBMIT path ───────────────────────────────────
  // The visible control is only half the job: SYNC_ACTIVE_SETUP has to carry it to the hidden
  // field the queue join reads.
  await open();
  await p.evaluate(() => {
    const r = [...document.querySelectorAll('#setup-arenabot input[name="ab-precon"]')]
      .find(x => x.getAttribute('data-style') === 'hardcontrol');
    if (r) r.click();
  });
  await p.waitForTimeout(500);
  const synced = await p.evaluate(() => {
    window.SYNC_ACTIVE_SETUP && window.SYNC_ACTIVE_SETUP();
    const h = document.getElementById('swu-botstyle-select');
    return h ? h.value : null;
  });
  if (synced !== 'hardcontrol') bad(name, `submit path carried "${synced}", not the picked style`);
  else ok();

  if (errs.length) bad(name, `pageerror ${errs[0]}`);
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} bot-style checks failed` : `\nBOT STYLE WIRED — ${checks} checks`);
process.exit(fails ? 1 : 0);
