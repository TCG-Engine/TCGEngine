// The setup modals' deck PICKERS must change the deck that is played, not just the preview.
//
// Before this gate the pickers were decoration: SYNC_ACTIVE_SETUP() read only the typed deck
// link, so choosing a pre-con or a saved deck moved the highlight and submitted whatever was
// (or was not) in the text field. Every assertion here compares what a control ADVERTISES
// (data-deck-input) against what actually lands in the legacy submission fields that
// getDeckSubmission() reads.
//
// Runs as a guest, which is the strictest case: a guest has no saved decks, so the pre-con
// wells are the only way to pick a deck at all.
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.SWU_BASE || 'http://localhost:3400/TCGEngine';
const URL = process.env.MENU_URL || BASE + '/SharedUI/MainMenu.php';
const ENGINES = [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]];

let fails = 0, checks = 0;
const ok = (name, cond, detail) => {
  checks++;
  if (!cond) { fails++; console.log(`FAIL ${name}${detail ? ' :: ' + detail : ''}`); }
};

for (const [eng, launcher] of ENGINES) {
  const browser = await launcher.launch();
  const page = await browser.newPage({ viewport: { width: 1440, height: 1000 } });
  await page.goto(URL, { waitUntil: 'networkidle' });

  // ── Twin Suns: a pre-con is the player's OWN deck ──────────────────────────
  let r = await page.evaluate(() => {
    const dlg = document.getElementById('setup-twin-suns');
    dlg.showModal();
    const radios = [...dlg.querySelectorAll('input.pc__in[data-deck-input]')];
    if (radios.length < 2) return { err: 'fewer than 2 Twin Suns pre-cons rendered: ' + radios.length };
    // pick the SECOND one, so a stale "first row wins" would not pass by luck
    const pick = radios[1];
    pick.checked = true;
    pick.dispatchEvent(new Event('change', { bubbles: true }));
    window.SYNC_ACTIVE_SETUP();
    return {
      count: radios.length,
      want: pick.getAttribute('data-deck-input'),
      got: document.getElementById('deck-link').value,
      fmt: document.getElementById('swu-format-select').value,
    };
  });
  if (r.err) { ok(`${eng} twin-suns pre-cons render`, false, r.err); }
  else {
    ok(`${eng} all four Twin Suns pre-cons render`, r.count === 4, `got ${r.count}`);
    ok(`${eng} a Twin Suns pre-con becomes the played deck`, r.got === r.want,
       `deck-link got ${r.got.slice(0, 60)}…`);
    ok(`${eng} a Twin Suns pre-con resolves as a real deck`, r.want.includes('secondleader'),
       'the chosen input carries no second leader');
    ok(`${eng} Twin Suns submits as twinsuns`, r.fmt === 'twinsuns', r.fmt);
  }

  // ── a typed link beats a stale pre-con ─────────────────────────────────────
  r = await page.evaluate(() => {
    const dlg = document.getElementById('setup-twin-suns');
    const link = dlg.querySelector('input[data-detect]');
    link.value = 'https://swudb.com/deck/TYPEDWINS';
    link.dispatchEvent(new Event('change', { bubbles: true }));
    window.SYNC_ACTIVE_SETUP();
    return { got: document.getElementById('deck-link').value };
  });
  ok(`${eng} a typed link beats a previously chosen pre-con`,
     r.got === 'https://swudb.com/deck/TYPEDWINS', r.got.slice(0, 60));

  // ── and choosing a pre-con afterwards clears that typed link ───────────────
  r = await page.evaluate(() => {
    const dlg = document.getElementById('setup-twin-suns');
    const pick = [...dlg.querySelectorAll('input.pc__in[data-deck-input]')][2];
    pick.checked = true;
    pick.dispatchEvent(new Event('change', { bubbles: true }));
    window.SYNC_ACTIVE_SETUP();
    return {
      want: pick.getAttribute('data-deck-input'),
      got: document.getElementById('deck-link').value,
      leftover: dlg.querySelector('input[data-detect]').value,
    };
  });
  ok(`${eng} choosing a pre-con afterwards wins`, r.got === r.want, r.got.slice(0, 60));
  ok(`${eng} choosing a pre-con clears the stale typed link`, r.leftover === '',
     `left "${r.leftover}"`);
  await page.evaluate(() => document.getElementById('setup-twin-suns').close());

  // ── Arenabot: a bot pre-con is the BOT's deck, never the player's ──────────
  r = await page.evaluate(() => {
    const dlg = document.getElementById('setup-arenabot');
    dlg.showModal();
    const own = dlg.querySelector('input[data-detect]');
    own.value = 'https://swudb.com/deck/MYOWNDECK';
    const radios = [...dlg.querySelectorAll('input.pc__in[data-deck-input]')];
    const pick = radios[4];
    pick.checked = true;
    pick.dispatchEvent(new Event('change', { bubbles: true }));
    window.SYNC_ACTIVE_SETUP();
    return {
      count: radios.length,
      slot: pick.getAttribute('data-slot'),
      want: pick.getAttribute('data-deck-input'),
      bot: document.getElementById('swu-deck2-input').value,
      mine: document.getElementById('deck-link').value,
      style: document.getElementById('swu-botstyle-select').value,
    };
  });
  ok(`${eng} all 23 bot pre-cons render`, r.count === 23, `got ${r.count}`);
  ok(`${eng} a bot pre-con is tagged for the bot slot`, r.slot === 'bot', r.slot);
  ok(`${eng} a bot pre-con becomes the BOT's deck`, r.bot === r.want, r.bot.slice(0, 60));
  ok(`${eng} a bot pre-con does NOT replace the player's deck`,
     r.mine === 'https://swudb.com/deck/MYOWNDECK', r.mine.slice(0, 60));
  await page.evaluate(() => document.getElementById('setup-arenabot').close());

  // ── a guest has no saved decks, so no picker may claim one ─────────────────
  r = await page.evaluate(() => {
    const out = [];
    for (const id of ['setup-pvp', 'setup-twin-suns', 'setup-arenabot', 'setup-solo']) {
      const dlg = document.getElementById(id);
      out.push({
        id,
        empties: dlg.querySelectorAll('.deckpick[data-empty]').length,
        selects: dlg.querySelectorAll('.deckpick select').length,
        invented: [...dlg.querySelectorAll('option[data-deck-input]')].length,
      });
    }
    return out;
  });
  for (const d of r) {
    ok(`${eng} ${d.id} shows an empty saved-deck state for a guest`, d.empties > 0,
       `${d.empties} empty, ${d.selects} selects`);
    ok(`${eng} ${d.id} invents no saved deck for a guest`, d.invented === 0,
       `${d.invented} options carry a deck`);
  }

  // ── GUEST saved decks live in localStorage (owner, 2026-09-25) ────────────
  // Seeded directly rather than saved through the UI, so this stays independent of the network.
  await page.evaluate(() => {
    localStorage.setItem('tcgengine:savedDecks:SWUSim', JSON.stringify([
      { key: 'gA', name: 'Krennic Control', leaders: ['SOR_001'], base: 'SOR_024',
        subtitle: 'Director Krennic · Echo Base', count: 50,
        input: 'https://swudb.com/deck/eeFFtweXI', format: 'premier' },
      { key: 'gB', name: 'Jabba Combo', leaders: ['SHD_006'], base: 'JTL_021',
        subtitle: 'Jabba the Hutt · Colossus', count: 50,
        input: 'https://swudb.com/deck/kWzBQPfCopFMV', format: 'twinsuns' }
    ]));
  });
  await page.goto(URL, { waitUntil: 'networkidle' });

  r = await page.evaluate(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach(i => { i.loading = 'eager'; });
    const dlg = document.getElementById('setup-pvp');
    dlg.showModal();
    await Promise.all([...document.images].map(i => i.decode().catch(() => {})));
    const opts = [...dlg.querySelectorAll('option[data-deck-input]')];
    const prev = dlg.querySelector('.lb__val .deckprev, .deckpick > .deckprev');
    const b = prev ? prev.getBoundingClientRect() : null;
    // the guest picker must carry the SAME contract the PHP one does
    const pick = dlg.querySelector('.deckpick');
    return {
      opts: opts.length,
      empties: dlg.querySelectorAll('.deckpick[data-empty]').length,
      hasSlot: !!(pick && pick.getAttribute('data-slot')),
      selSlot: !!dlg.querySelector('select[data-slot="own"]'),
      enhanced: !!(pick && pick.classList.contains('lb')),
      w: b ? Math.round(b.width) : 0, h: b ? Math.round(b.height) : 0,
      hasMsg: !!dlg.querySelector('[data-pickmsg="own"]'),
    };
  });
  ok(`${eng} a guest's localStorage decks are offered`, r.opts === 2, `${r.opts} options`);
  ok(`${eng} the guest picker replaced the empty state`, r.empties === 0, `${r.empties} left`);
  ok(`${eng} the guest picker PAINTS`, r.w > 200 && r.h > 24, `${r.w}x${r.h}`);
  ok(`${eng} the guest picker keeps the PHP contract (data-slot)`, r.hasSlot && r.selSlot);
  ok(`${eng} the guest picker gets the listbox enhancement`, r.enhanced);
  ok(`${eng} the guest picker has a status line`, r.hasMsg);

  // ── choosing a saved deck shows its SOURCE LINK (owner, 2026-09-25) ───────
  r = await page.evaluate(async () => {
    const dlg = document.getElementById('setup-pvp');
    const sel = dlg.querySelector('select[data-slot="own"]');
    const link = dlg.querySelector('input[data-detect]');
    link.value = '';
    sel.value = 'gB';
    sel.dispatchEvent(new Event('change', { bubbles: true }));
    await new Promise(res => setTimeout(res, 2000));   // detection round-trip
    const open = document.querySelector('dialog.setup[open]');
    const ol = open && open.querySelector('input[data-detect]');
    const om = open && open.querySelector('[data-pickmsg="own"]');
    const osel = open && open.querySelector('select[data-slot="own"]');
    window.SYNC_ACTIVE_SETUP();
    return {
      link: ol ? ol.value : '',
      openId: open ? open.id : '',
      msg: (om && !om.hidden) ? om.textContent.trim() : '',
      selValue: osel ? osel.value : '',
      stillChecked: open ? open.querySelectorAll('input[data-slot="own"][data-deck-input]:checked').length : -1,
      submitted: document.getElementById('deck-link').value,
    };
  });
  ok(`${eng} choosing a saved deck fills the Deck Link with its source link`,
     r.link === 'https://swudb.com/deck/kWzBQPfCopFMV', `link box "${r.link}"`);
  ok(`${eng} choosing a saved deck says what was loaded`, /Jabba Combo/.test(r.msg), `msg "${r.msg}"`);
  ok(`${eng} a saved Twin Suns deck still moves the player to Twin Suns`,
     r.openId === 'setup-twin-suns', r.openId);
  ok(`${eng} the destination picker agrees with the link box`, r.selValue === 'gB', r.selValue);
  ok(`${eng} no pre-con is left looking chosen beside it`, r.stillChecked === 0,
     `${r.stillChecked} still checked`);
  ok(`${eng} the chosen saved deck is what gets played`,
     r.submitted === 'https://swudb.com/deck/kWzBQPfCopFMV', r.submitted);

  // ── only LINKS may be saved (owner, 2026-09-25) ───────────────────────────
  r = await page.evaluate(async () => {
    const before = JSON.parse(localStorage.getItem('tcgengine:savedDecks:SWUSim') || '[]').length;
    const open = document.querySelector('dialog.setup[open]');
    if (open) open.close();
    const dlg = document.getElementById('setup-pvp');
    dlg.showModal();
    const link = dlg.querySelector('input[data-detect]');
    link.value = '{"leader":{"id":"SOR_001","count":1}}';
    dlg.querySelector('[data-act=save]').click();
    await new Promise(res => setTimeout(res, 2500));
    const m = dlg.querySelector('[data-pickmsg="own"]');
    return {
      before,
      after: JSON.parse(localStorage.getItem('tcgengine:savedDecks:SWUSim') || '[]').length,
      msg: (m && !m.hidden) ? m.textContent.trim() : '',
    };
  });
  ok(`${eng} pasted JSON is REFUSED, with a reason`, /Only deck links can be saved/.test(r.msg),
     `msg "${r.msg}"`);
  ok(`${eng} a refused save stores nothing`, r.after === r.before,
     `${r.before} -> ${r.after}`);

  await page.evaluate(() => { try { localStorage.removeItem('tcgengine:savedDecks:SWUSim'); } catch (e) {} });

  // ── LOGGED IN: the saved-deck picker must actually PAINT ──────────────────
  // A guest never sees this branch, which is how it shipped broken once: a .deckprev row is
  // display:none until a CSS rule for ITS key reveals it, and real keys are per-account hashes,
  // not the mockup's ten fixture names. The deck was in the DOM and the picker rendered as a
  // blank bar. Geometry, not presence, is therefore the assertion.
  // Fixture: the claudebot1 test account must own at least one saved deck.
  await page.goto(BASE + '/SharedUI/Sites/SWUSim/LoginPage.php', { waitUntil: 'networkidle' });
  await page.fill('input.username', 'claudebot1');
  await page.fill('input.password', 'pass');
  await page.click('button[type=submit], input[type=submit]').catch(() => page.keyboard.press('Enter'));
  await page.waitForTimeout(1200);
  await page.goto(URL, { waitUntil: 'networkidle' });

  r = await page.evaluate(async () => {
    document.querySelectorAll('img[loading="lazy"]').forEach(i => { i.loading = 'eager'; });
    const dlg = document.getElementById('setup-pvp');
    dlg.showModal();
    await Promise.all([...document.images].map(i => i.decode().catch(() => {})));
    const opts = [...dlg.querySelectorAll('option[data-deck-input]')];
    const shown = dlg.querySelector('.lb__val .deckprev, .deckpick > .deckprev');
    const box = shown ? shown.getBoundingClientRect() : null;
    const name = dlg.querySelector('.lb__val .deck__name, .deckpick > .deckprev .deck__name');
    const nbox = name ? name.getBoundingClientRect() : null;
    const before = window.SYNC_ACTIVE_SETUP && (document.getElementById('deck-link').value = '');
    const sel = dlg.querySelector('select[data-slot="own"]');
    let synced = '';
    if (sel) {
      sel.dispatchEvent(new Event('change', { bubbles: true }));
      window.SYNC_ACTIVE_SETUP();
      synced = document.getElementById('deck-link').value;
    }
    return {
      opts: opts.length,
      want: opts[0] ? opts[0].getAttribute('data-deck-input') : '',
      synced,
      prevW: box ? Math.round(box.width) : 0, prevH: box ? Math.round(box.height) : 0,
      nameW: nbox ? Math.round(nbox.width) : 0,
      nameTxt: name ? name.textContent.trim().slice(0, 40) : '',
      clipped: [...dlg.querySelectorAll('.deck__name, .deck__sub')]
        .filter(e => e.scrollWidth > e.clientWidth + 1).map(e => e.textContent.trim().slice(0, 34)),
    };
  });
  ok(`${eng} the test account has a saved deck to show`, r.opts > 0,
     'claudebot1 owns none — save one before running this gate');
  ok(`${eng} the saved-deck picker PAINTS its preview`, r.prevW > 200 && r.prevH > 24,
     `preview measured ${r.prevW}x${r.prevH} — a blank bar means no rule reveals this deck's key`);
  ok(`${eng} the saved deck's name is rendered`, r.nameW > 40 && r.nameTxt !== '',
     `name "${r.nameTxt}" measured ${r.nameW}px`);
  ok(`${eng} a saved deck becomes the played deck`, r.synced === r.want && r.synced !== '',
     `deck-link got "${String(r.synced).slice(0, 50)}"`);
  ok(`${eng} no saved-deck text is clipped`, r.clipped.length === 0, JSON.stringify(r.clipped));

  await browser.close();
}

console.log(fails ? `\n${fails}/${checks} checks failed` : `\nPICKERS WIRED — ${checks} checks`);
process.exit(fails ? 1 : 0);
