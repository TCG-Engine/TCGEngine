// Arenabot's GROUPED bot pre-cons (owner 2026-10-01): one block per group ("ASH Meta September 2026",
// "Force Fam HMW Predictions"), each opening a picker <dialog> ON TOP of the Arenabot modal.
// Chromium, Firefox and WebKit, at a desktop and a phone width. Pinned:
//   · the blocks render with NO deck picked, and focus lands in the empty Deck Link box (owner 2026-10-01);
//   · a block opens ITS picker in the top layer, above Arenabot, which stays open underneath;
//   · a pointer pick checks the deck, closes the picker, moves "Selected" to that block, sets the bot
//     style from the deck (offline), and is the deck the submit path will play;
//   · arrow keys move the choice WITHOUT closing; Escape and the backdrop close only the picker;
//   · closing Arenabot closes an open picker (no orphan modal holding the page inert).
// Screenshots go to $SHOTS (default /tmp/precon-groups) for a look — geometry alone cannot see a bad render.
import { chromium, firefox, webkit } from 'playwright';
import fs from 'node:fs';
const URL   = process.env.MENU_URL || 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
const SHOTS = process.env.SHOTS || '/tmp/precon-groups';
fs.mkdirSync(SHOTS, { recursive: true });
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };
const want = (n, cond, m) => cond ? ok() : bad(n, m);

for (const [bn, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  for (const [w, h] of [[1440, 950], [420, 860]]) {
    const n = `${bn}@${w}`;
    const p = await b.newPage({ viewport: { width: w, height: h } });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    const open = async () => {
      await p.goto(URL, { waitUntil: 'networkidle' });
      await p.click('a.mode[href="#setup-arenabot"]');
      await p.waitForTimeout(350);
    };
    const state = () => p.evaluate(() => {
      const host = document.getElementById('setup-arenabot');
      const blocks = [...host.querySelectorAll('button[data-pcgroup]')].map(b => ({
        id: b.getAttribute('data-pcgroup'),
        name: b.querySelector('.pcgroup__name').textContent.trim(),
        pick: b.querySelector('[data-pcgroup-pick]').hidden ? '' : b.querySelector('[data-pcgroup-pick]').textContent.trim(),
        on: b.classList.contains('pcgroup--on'),
        rect: (r => ({ w: r.width, h: r.height }))(b.getBoundingClientRect()),
      }));
      const openPickers = [...host.querySelectorAll('dialog.pcpick[open]')].map(d => d.id);
      const checked = host.querySelector('input[name="ab-precon"]:checked');
      const s = document.getElementById('ab-style');
      return { hostOpen: host.open, blocks, openPickers, checked: checked ? checked.id : null,
               style: s ? (s.options[s.selectedIndex] || {}).text : null };
    });

    // 1. the blocks
    await open();
    let s = await state();
    want(n, s.blocks.length === 2, `want 2 group blocks, got ${s.blocks.length}`);
    want(n, s.blocks[0]?.name === 'ASH Meta September 2026' && s.blocks[1]?.name === 'Force Fam HMW Predictions',
      `block names ${JSON.stringify(s.blocks.map(x => x.name))}`);
    // Owner 2026-10-01: "we shouldn't auto-pick a deck. it should default to focus on the empty deck input".
    want(n, s.checked === null, `no pre-con is picked by default (got ${s.checked})`);
    want(n, s.blocks.every(x => !x.on && x.pick === ''), `no block says Selected by default: ${JSON.stringify(s.blocks.map(x => [x.on, x.pick]))}`);
    const focused = await p.evaluate(() => { const a = document.activeElement; return a ? (a.id || a.className) : null; });
    want(n, focused === 'ab-link', `opening Arenabot focuses the empty Deck Link box (focused: ${focused})`);
    want(n, s.blocks.every(x => x.rect.h >= 44), `blocks at least 44px tall: ${JSON.stringify(s.blocks.map(x => x.rect))}`);
    await p.screenshot({ path: `${SHOTS}/${n}-1-blocks.png` });

    // 2. a block opens ITS picker, in the top layer, over Arenabot
    const hmw = s.blocks[1].id;
    await p.click(`button[data-pcgroup="${hmw}"]`);
    await p.waitForTimeout(300);
    s = await state();
    want(n, s.hostOpen && s.openPickers.length === 1 && s.openPickers[0] === hmw, `picker open: ${JSON.stringify(s.openPickers)}, host ${s.hostOpen}`);
    const top = await p.evaluate(id => {
      const d = document.getElementById(id), r = d.querySelector('.pcpick__pane').getBoundingClientRect();
      const el = document.elementFromPoint(r.left + r.width / 2, r.top + 30);
      const sc = d.querySelector('.pool__scroll');
      return { onTop: !!(el && d.contains(el)), inView: r.top >= 0 && r.bottom <= innerHeight + 1 && r.left >= 0 && r.right <= innerWidth + 1,
               rows: d.querySelectorAll('input[name="ab-precon"]').length,
               fits: sc.scrollHeight <= sc.clientHeight + 1, wellH: Math.round(sc.clientHeight), need: sc.scrollHeight };
    }, hmw);
    // The well is the picker's whole job: on a desktop window all 5 decks show with no scrolling. It was
    // once clipped to ~3 rows by Arenabot's own leftover-height cap, which also matched the nested picker.
    if (w >= 1000) want(n, top.fits, `all 5 HMW decks visible at ${w}x${h}: well ${top.wellH}px, content ${top.need}px`);
    want(n, top.onTop, 'the picker is the topmost thing at its own centre');
    want(n, top.inView, 'the picker fits in the viewport');
    want(n, top.rows === 5, `the HMW picker lists 5 decks, got ${top.rows}`);
    await p.screenshot({ path: `${SHOTS}/${n}-2-picker.png` });

    // 3. arrow keys move the choice without closing. Focus a deck first — with none of this group's
    // decks chosen, the picker opens with focus on its close X, where an arrow key does nothing.
    await p.focus(`#${hmw} input[name="ab-precon"]`);
    const before = (await state()).checked;
    await p.keyboard.press('ArrowDown');
    await p.waitForTimeout(250);
    s = await state();
    want(n, s.checked !== before, `the arrow key moved the choice (still ${s.checked})`);
    want(n, s.openPickers.length === 1, 'an arrow key does not close the picker');

    // 4. a pointer pick: Ninin's Ahsoka
    await p.click(`#${hmw} label.pc__row:has-text("By Ninin")`);
    await p.waitForTimeout(500);
    s = await state();
    want(n, s.openPickers.length === 0 && s.hostOpen, `a click picks and closes only the picker (pickers ${JSON.stringify(s.openPickers)}, host ${s.hostOpen})`);
    want(n, /ahsoka-tano_ash_yellow/.test(s.checked || '') && /force-fam/i.test(s.checked || ''), `Ninin's deck checked: ${s.checked}`);
    want(n, s.blocks[1].on && s.blocks[1].pick === 'Selected: Ahsoka Tano (ASH) Yellow By Ninin' && !s.blocks[0].on && s.blocks[0].pick === '',
      `Selected moved to the HMW block: ${JSON.stringify(s.blocks.map(x => [x.on, x.pick]))}`);
    want(n, s.style === 'Hyper Aggro', `bot style follows the deck (hyperaggro): ${s.style}`);
    const deck = await p.evaluate(() => {
      const d = document.getElementById('setup-arenabot');
      return typeof SETUP_DECK_FOR === 'function' ? SETUP_DECK_FOR(d, 'bot', d.querySelectorAll('input[type="url"]')[1]) : null;
    });
    want(n, typeof deck === 'string' && deck.includes('ASH_009') && deck.includes('HMW_033'), `submit path plays Ninin's deck: ${String(deck).slice(0, 80)}`);
    await p.screenshot({ path: `${SHOTS}/${n}-3-picked.png` });

    // 5. Escape closes only the picker
    await p.click(`button[data-pcgroup="${s.blocks[0].id}"]`);
    await p.waitForTimeout(300);
    await p.keyboard.press('Escape');
    await p.waitForTimeout(300);
    s = await state();
    want(n, s.openPickers.length === 0 && s.hostOpen, `Escape closes the picker only (host ${s.hostOpen})`);

    // 6. the backdrop closes only the picker
    await p.click(`button[data-pcgroup="${s.blocks[0].id}"]`);
    await p.waitForTimeout(300);
    await p.mouse.click(4, 4);
    await p.waitForTimeout(300);
    s = await state();
    want(n, s.openPickers.length === 0 && s.hostOpen, `a backdrop click closes the picker only (host ${s.hostOpen})`);

    // 7. closing Arenabot closes an open picker
    await p.click(`button[data-pcgroup="${s.blocks[0].id}"]`);
    await p.waitForTimeout(300);
    await p.evaluate(() => document.getElementById('setup-arenabot').close());
    await p.waitForTimeout(200);
    s = await state();
    want(n, !s.hostOpen && s.openPickers.length === 0, `closing Arenabot closes the picker: ${JSON.stringify(s.openPickers)}`);

    want(n, errs.length === 0, `page errors: ${errs.join(' | ')}`);
    await p.close();
  }
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} grouped pre-con checks failed` : `\nGROUPED PRE-CONS OK — ${checks} checks, 3 engines x 2 widths (shots in ${SHOTS})`);
process.exit(fails ? 1 : 0);
