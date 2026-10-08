// Base health bar: every surface, three engines.
// Spec: docs/superpowers/specs/2026-10-08-swusim-base-health-bar-design.md
//
//   node DevTools/ui-harness/swusim-base-health-bar-xbrowser.mjs          every section
//   ONLY=wire,builder node DevTools/ui-harness/swusim-base-health-bar-xbrowser.mjs
//
// Seeds its own boards. Expected HP and aspect come from the container's card dictionary, and expected
// damage from the page's own data, so no literal here can go stale.
import { chromium, firefox, webkit } from 'playwright';
import { execFileSync } from 'node:child_process';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const CONTAINER = 'otmtcge-swusim-web-server-1';
const ONLY = (process.env.ONLY || '').split(',').map(s => s.trim()).filter(Boolean);

let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok = () => { checks++; };

// Twin Suns: a base of every colour family, Lake Country (no aspect) nearly dead, your own base damaged.
const TWINSUNS = [
  '## GIVEN',
  'CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010; myBase:SOR_029; myBaseDamage:9; theirBase:SOR_019}',
  'SkipPreGame: true', 'WithSeatOrder: 1234', 'WithLiveSeats: 1234',
  'WithGamePhase: ActionPhase', 'WithActivePlayer: 1',
  'WithP3Base: JTL_031:30', 'WithP3Leader:  SHD_014', 'WithP3Leader2: SHD_015',
  'WithP4Base: LOF_024:12', 'WithP4Leader:  TWI_009', 'WithP4Leader2: TWI_010',
  'WithP2GroundArena: SOR_095:1:0', 'WithP3GroundArena: SOR_095:1:0', 'WithP4GroundArena: SOR_095:1:0',
  '## WHEN', '## EXPECT', 'SEATCOUNT:4',
].join('\n');
// 1v1: red vs green, 9 damage each (the owner's sketch).
const ONEVONE = [
  '## GIVEN',
  'CommonSetup: rrk/bbw/{myLeader:IBH_053; theirLeader:SHD_007; myBase:SOR_026; myBaseDamage:9; theirBase:SOR_023; theirBaseDamage:9}',
  'SkipPreGame: true', 'WithGamePhase: ActionPhase', 'WithActivePlayer: 1',
  'WithP1GroundArena: SOR_095:1:0', 'WithP2GroundArena: SOR_095:1:0',
  '## WHEN', '## EXPECT', 'TURNPLAYER:1',
].join('\n');

async function seed(schema) {
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', {
    method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: new URLSearchParams({ schema }).toString(),
  });
  const j = await r.json();
  if (j.error) throw new Error(`TestSchemaSetup refused the schema: ${j.error}`);
  return String(j.gameName);
}

// Printed HP and aspect, straight from the engine's dictionary.
function dictionary(ids) {
  const out = execFileSync('docker', ['exec', '-w', '/var/www/html/TCGEngine', CONTAINER, 'php', '-d', 'xdebug.mode=off', '-r',
    'require "SWUSim/GeneratedCode/GeneratedCardDictionaries.php"; foreach (array_slice($argv, 1) as $c) echo $c, "|", intval(CardHp($c)), "|", (string)CardAspect($c), "\\n";',
    ...ids], { encoding: 'utf8' });
  return Object.fromEntries(out.trim().split('\n').map(l => l.split('|'))
    .map(([c, h, a]) => [c, { hp: Number(h), aspect: (a || '').split(',')[0].trim() }]));
}
const FILL = { Vigilance: '#3b7dd8', Command: '#2e9e4f', Aggression: '#c0392b', Cunning: '#e2b13c', '': '#c9ced6' };
const DICT = dictionary(['SOR_029', 'SOR_019', 'JTL_031', 'LOF_024', 'SOR_026', 'SOR_023']);

// The bases as the client holds them: {CardID, Damage, PrintedHP, Aspect, PlayerID, ...}.
async function pageBases(p) {
  return p.evaluate(() => {
    const parse = (s) => { s = String(s || ''); const i = s.indexOf('{'); if (i < 0) return null; try { return JSON.parse(s.slice(i)); } catch { return null; } };
    const seen = new Map();
    const add = (o) => { if (o && o.CardID && o.CardID !== '-') seen.set(o.CardID + '/' + o.PlayerID, o); };
    add(parse(window.myBaseData)); add(parse(window.theirBaseData));
    const arr = window.swuLastResponseArr || [];
    for (let seat = 1; seat <= 4; seat++) add(parse(arr[6 + (seat - 1) * 31]));
    return [...seen.values()];
  });
}

// ── wire: every base carries Aspect and PrintedHP matching the dictionary ──────────────────────────
async function wire({ p, name, s }) {
  if (!s.label.startsWith('desktop')) return;            // the wire is identical on both layouts
  const bases = await pageBases(p);
  const want = s.board === 'ts' ? 4 : 2;
  if (bases.length !== want) { bad(name, `expected ${want} bases on the wire, found ${bases.length}`); return; }
  ok();
  for (const b of bases) {
    const d = DICT[b.CardID];
    if (!d) { bad(name, `unexpected base ${b.CardID}`); continue; }
    b.Aspect === d.aspect ? ok() : bad(name, `${b.CardID}: Aspect ${JSON.stringify(b.Aspect)}, dictionary says ${JSON.stringify(d.aspect)}`);
    Number(b.PrintedHP) === d.hp ? ok() : bad(name, `${b.CardID}: PrintedHP ${b.PrintedHP}, dictionary says ${d.hp}`);
  }
}

// ── builder: the bar's markup and depth, from synthetic bases (runs on every surface: CSS is per layout) ──
async function builder({ p, name }) {
  const r = await p.evaluate(() => {
    if (typeof window.swuBaseHealthBar !== 'function') return { missing: true };
    const mk = (o, v) => {
      const d = document.createElement('div');
      d.style.cssText = 'position:fixed;left:0;top:0;width:200px';
      d.innerHTML = window.swuBaseHealthBar(o, v || 'full');
      document.body.appendChild(d);
      return d;
    };
    const read = (d) => {
      const hb = d.querySelector('.swu-hb'); if (!hb) { d.remove(); return null; }
      const f = hb.querySelector('.swu-hb-fill'), pl = hb.querySelector('.swu-hb-plane'), rim = hb.querySelector('.swu-hb-rim');
      const out = {
        width: f.style.width, color: getComputedStyle(hb).getPropertyValue('--hb-c').trim(), card: hb.getAttribute('data-hb-card'),
        planeClip: getComputedStyle(pl).clipPath, rimBg: getComputedStyle(rim).backgroundImage, rimDisplay: getComputedStyle(rim).display,
        fillBg: getComputedStyle(f).backgroundImage, fillShadow: getComputedStyle(f).boxShadow, lift: getComputedStyle(hb).filter,
      };
      d.remove();
      return out;
    };
    const C = (Damage, extra) => Object.assign({ CardID: 'SOR 023', PrintedHP: 30, Damage, Aspect: 'Command' }, extra || {});
    return {
      mid: read(mk(C(9))), dead: read(mk(C(35))), full: read(mk(C(0))),
      none: read(mk(C(10, { Aspect: '' }))), missingAspect: read(mk(C(10, { Aspect: undefined }))),
      multi: read(mk(C(10, { Aspect: 'Command,Heroism' }))),
      noHp: window.swuBaseHealthBar(C(9, { PrintedHP: 0 })), thin: read(mk(C(9), 'thin')),
      // Prod before regeneration: neither PrintedHP nor Aspect is on the wire. No bar, and no error.
      unregenerated: window.swuBaseHealthBar({ CardID: 'SOR 023', Damage: 9 }),
    };
  });
  if (r.missing) { bad(name, 'window.swuBaseHealthBar is not defined'); return; }
  const eq = (label, got, want) => (got === want ? ok() : bad(name, `${label}: got ${JSON.stringify(got)}, want ${JSON.stringify(want)}`));
  eq('70% at 9 of 30', r.mid && r.mid.width, '70%');
  eq('empty past printed HP (35 of 30)', r.dead && r.dead.width, '0%');
  eq('full at 0 damage', r.full && r.full.width, '100%');
  eq('Command colour', r.mid && r.mid.color, FILL.Command);
  eq('no aspect -> light grey', r.none && r.none.color, FILL['']);
  eq('missing Aspect field (prod not regenerated) -> light grey', r.missingAspect && r.missingAspect.color, FILL['']);
  eq('first of several aspects', r.multi && r.multi.color, FILL.Command);
  eq('no printed HP -> no bar', r.noHp, '');
  eq('prod not regenerated (no PrintedHP, no Aspect) -> no bar', r.unregenerated, '');
  eq('card id gets its underscore back', r.mid && r.mid.card, 'SOR_023');
  // Depth, read from COMPUTED styles: a lost stylesheet rule fails here instead of quietly flattening the bar.
  const m = r.mid || {};
  String(m.planeClip).startsWith('polygon') ? ok() : bad(name, `plane is not chamfered (clip-path ${m.planeClip})`);
  /rgba\(196, 214, 238/.test(m.rimBg || '') ? ok() : bad(name, `full bar has no steel rim (${m.rimBg})`);
  /linear-gradient/.test(m.fillBg || '') ? ok() : bad(name, `fill is not shaded (${m.fillBg})`);
  /inset/.test(m.fillShadow || '') ? ok() : bad(name, `fill has no lit edges (${m.fillShadow})`);
  /drop-shadow/.test(m.lift || '') ? ok() : bad(name, `bar has no lift shadow (${m.lift})`);
  eq('thin bar has no rim', r.thin && r.thin.rimDisplay, 'none');
}

// ── full: full-size bars sit OUTSIDE the card on the midline side, at the card's width, with the right share ──
async function measureFull(p) {
  return p.evaluate(() => {
    const one = (which) => {
      const box = document.getElementById(which + 'BaseHealth');
      if (!box) return { which, missing: 'container' };
      // The CARD ELEMENT is what the player sees: it clips the 1.1x-scaled art and its damage token.
      const hb = box.querySelector('.swu-hb'), img = document.getElementById(which + 'Base-0');
      const shown = !!(hb && hb.getClientRects().length && img && img.getClientRects().length);
      if (!hb || !img) return { which, missing: hb ? 'card' : 'bar', shown };
      const br = hb.getBoundingClientRect(), cr = img.getBoundingClientRect();
      const pl = hb.querySelector('.swu-hb-plane').getBoundingClientRect(), f = hb.querySelector('.swu-hb-fill').getBoundingClientRect();
      return { which, shown, card: hb.getAttribute('data-hb-card'), h: br.height, bw: br.width, cw: cr.width,
        barTop: br.top, barBottom: br.bottom, cardTop: cr.top, cardBottom: cr.bottom,
        dx: Math.abs((br.left + br.width / 2) - (cr.left + cr.width / 2)),
        ratio: pl.width ? f.width / pl.width : -1, color: getComputedStyle(hb).getPropertyValue('--hb-c').trim() };
    };
    const hit = (a, b) => a.left < b.right - 0.5 && b.left < a.right - 0.5 && a.top < b.bottom - 0.5 && b.top < a.bottom - 0.5;
    const bars = [...document.querySelectorAll('.swu-base-health .swu-hb')].filter(e => e.getClientRects().length);
    // Visible card elements (they clip their art and counters), the pass cluster, the base tabs.
    const things = [...document.querySelectorAll('#myLeaderSlot [data-mzid], #theirLeaderSlot [data-mzid], #myBase-0, #theirBase-0, #swuPassBtn, .swu-base-tab')]
      .filter(e => e.getClientRects().length);
    const overlaps = [];
    for (const b of bars) for (const t of things) if (hit(b.getBoundingClientRect(), t.getBoundingClientRect())) overlaps.push(t.id || t.className || t.tagName);
    return { bars: [one('their'), one('my')], overlaps };
  });
}
async function full({ p, name, s }) {
  const phone = s.label.startsWith('phone');
  const H = phone ? 7 : 12;
  const check = async (tag) => {
    const { bars, overlaps } = await measureFull(p);
    const bases = await pageBases(p);
    for (const b of bars) {
      // Twin Suns Home view: only YOUR full-size base is on screen; the opponent's half is the tiles.
      if (s.board === 'ts' && b.which === 'their') { b.shown ? bad(name, `${tag} their full-size bar shows on the Home view`) : ok(); continue; }
      if (b.missing) { bad(name, `${tag} ${b.which}: no ${b.missing}`); continue; }
      const d = DICT[b.card], data = bases.find(x => x.CardID === b.card);
      if (!d || !data) { bad(name, `${tag} ${b.which}: bar names unknown base ${b.card}`); continue; }
      const want = Math.max(0, (d.hp - data.Damage) / d.hp);
      Math.abs(b.ratio - want) <= 0.02 ? ok() : bad(name, `${tag} ${b.which} ${b.card}: fill ${(b.ratio * 100).toFixed(1)}%, want ${(want * 100).toFixed(1)}%`);
      b.color === FILL[d.aspect] ? ok() : bad(name, `${tag} ${b.which} ${b.card}: colour ${b.color}, want ${FILL[d.aspect]}`);
      Math.abs(b.h - H) <= 0.5 ? ok() : bad(name, `${tag} ${b.which}: ${b.h}px tall, want ${H}`);
      (Math.abs(b.bw - b.cw) <= 2 && b.dx <= 2) ? ok()
        : bad(name, `${tag} ${b.which}: bar ${b.bw.toFixed(1)}px wide, off-centre ${b.dx.toFixed(1)}px, card ${b.cw.toFixed(1)}px`);
      const above = phone || b.which === 'my';     // desktop: under theirs, over mine; phone: over both
      const clear = above ? b.barBottom <= b.cardTop + 0.5 : b.barTop >= b.cardBottom - 0.5;
      clear ? ok() : bad(name, `${tag} ${b.which}: bar overlaps the card or sits on the wrong side `
        + `(bar ${b.barTop.toFixed(1)}–${b.barBottom.toFixed(1)}, card ${b.cardTop.toFixed(1)}–${b.cardBottom.toFixed(1)})`);
    }
    overlaps.length ? bad(name, `${tag} a bar covers: ${overlaps.join(', ')}`) : ok();
  };
  await check('[as loaded]');
  // Review Focus 4: a short window, and a resize, must keep the bar on the card's width and off everything.
  if (s.label === 'desktop-1v1') {
    await p.setViewportSize({ width: 1366, height: 768 }); await p.waitForTimeout(700);
    await check('[1366x768]');
    // ≤680px tall switches the centre column to ROWS (leader beside base): the bar must still follow its base.
    await p.setViewportSize({ width: 1280, height: 660 }); await p.waitForTimeout(700);
    await check('[1280x660 row layout]');
    await p.setViewportSize(s.viewport); await p.waitForTimeout(700);
  }
}

// ── tiles: thin bar on each tile's top edge, flush with the badge, short of the aspect icon, above the title ──
async function tiles({ p, name, s }) {
  if (s.board !== 'ts') return;
  const phone = s.label.startsWith('phone');
  const H = phone ? 2 : 3;
  const sel = phone ? '#swuHomeStrips .swu-sr-base' : '#swuHomeStrips .swu-mb-base';
  const r = await p.$$eval(sel, els => els.map(el => {
    const hb = el.querySelector('.swu-hb'), pip = el.querySelector('.swu-mb-basehp');
    if (!hb) return { missing: true };
    const cr = el.getBoundingClientRect(), br = hb.getBoundingClientRect(), pr = pip ? pip.getBoundingClientRect() : null;
    const pl = hb.querySelector('.swu-hb-plane').getBoundingClientRect(), f = hb.querySelector('.swu-hb-fill').getBoundingClientRect();
    return {
      card: hb.getAttribute('data-hb-card'), thin: hb.classList.contains('swu-hb--thin'), h: br.height,
      // Measured against the ART box (inside the thumbnail's border: the phone row's base has a 1px one).
      top: br.top - (cr.top + el.clientTop), left: br.left - cr.left, right: br.right - cr.left, cardW: cr.width, cardH: el.clientHeight,
      pipRight: pr ? pr.right - cr.left : null, ratio: pl.width ? f.width / pl.width : -1,
      // the bar must come BEFORE the badge in the DOM, so the badge paints over the bar's end
      beforePip: pip ? !!(hb.compareDocumentPosition(pip) & Node.DOCUMENT_POSITION_FOLLOWING) : false,
    };
  }));
  const bases = await pageBases(p);
  r.length === 3 ? ok() : bad(name, `expected 3 tile bases, found ${r.length}`);
  for (const t of r) {
    if (t.missing) { bad(name, 'a tile base has no bar'); continue; }
    const d = DICT[t.card], data = bases.find(x => x.CardID === t.card);
    if (!d || !data) { bad(name, `tile bar names unknown base ${t.card}`); continue; }
    t.thin ? ok() : bad(name, `${t.card}: not the thin variant`);
    Math.abs(t.h - H) <= 0.5 ? ok() : bad(name, `${t.card}: ${t.h}px tall, want ${H}`);
    // above the title plate, which starts at 7.4% of the card's height
    (t.top >= -0.5 && t.top + t.h <= 0.074 * t.cardH + 0.5) ? ok() : bad(name, `${t.card}: bar ${t.top.toFixed(1)}–${(t.top + t.h).toFixed(1)}px reaches the title (starts ${(0.074 * t.cardH).toFixed(1)}px)`);
    Math.abs(t.left - t.pipRight) <= 1 ? ok() : bad(name, `${t.card}: bar starts at ${t.left.toFixed(1)}px, badge ends at ${t.pipRight}px`);
    t.right <= 0.88 * t.cardW ? ok() : bad(name, `${t.card}: bar runs into the aspect icon (ends at ${(t.right / t.cardW * 100).toFixed(1)}% of the card)`);
    t.beforePip ? ok() : bad(name, `${t.card}: bar is after the badge in the DOM`);
    const want = Math.max(0, (d.hp - data.Damage) / d.hp);
    Math.abs(t.ratio - want) <= 0.04 ? ok() : bad(name, `${t.card}: fill ${(t.ratio * 100).toFixed(1)}%, want ${(want * 100).toFixed(1)}%`);
  }
}

// ── motion: damage and heals SLIDE; overkill empties; reduced motion is instant; a no-change poll is still ──
async function slideMine(p, to) {
  return p.evaluate(async (to) => {
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    // The longest RUNNING width transition on the fill (ms), 0 if none. ⚠ Headless Firefox updates layout
    // only ~4x a second, so a 140ms rect sample can miss a slide that is really running; this can't.
    const widthAnim = (el) => el ? Math.max(0, ...el.getAnimations().filter(a => a.transitionProperty === 'width' && a.playState === 'running').map(a => Number(a.effect.getTiming().duration) || 0)) : 0;
    const ratio = () => {
      const f = document.querySelector('#myBaseHealth .swu-hb-fill'), pl = document.querySelector('#myBaseHealth .swu-hb-plane');
      return f && pl ? f.getBoundingClientRect().width / pl.getBoundingClientRect().width : null;
    };
    if (typeof window.swuRenderBaseHealth !== 'function') return { missing: true };
    const before = ratio();
    window.myBaseData = String(window.myBaseData).replace(/"Damage":\d+/, '"Damage":' + to);
    window.swuRenderBaseHealth('my');
    await sleep(140); const mid = ratio(), anim = widthAnim(document.querySelector('#myBaseHealth .swu-hb-fill'));
    await sleep(700); const after = ratio();
    return { before, mid, anim, after };
  }, to);
}
async function slideTile(p, seat, to) {
  return p.evaluate(async ({ seat, to }) => {
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    // The longest RUNNING width transition on the fill (ms), 0 if none. ⚠ Headless Firefox updates layout
    // only ~4x a second, so a 140ms rect sample can miss a slide that is really running; this can't.
    const widthAnim = (el) => el ? Math.max(0, ...el.getAnimations().filter(a => a.transitionProperty === 'width' && a.playState === 'running').map(a => Number(a.effect.getTiming().duration) || 0)) : 0;
    if (typeof window.swuRenderHomeStrips !== 'function') return { missing: true };
    const arr = window.swuLastResponseArr, i = 6 + (seat - 1) * 31;
    const card = String(arr[i]).split(' ')[0];
    const ratio = () => {
      const hb = document.querySelector(`#swuHomeStrips .swu-hb[data-hb-card="${card}"]`);
      return hb ? hb.querySelector('.swu-hb-fill').getBoundingClientRect().width / hb.querySelector('.swu-hb-plane').getBoundingClientRect().width : null;
    };
    const before = ratio();
    window.swuRenderHomeStrips(); await sleep(60);
    const sameAfterIdlePoll = ratio();                 // a poll with no change must not move the bar
    arr[i] = String(arr[i]).replace(/"Damage":\d+/, '"Damage":' + to);
    window.swuRenderHomeStrips();
    // Wait for swuHbSettle to move the fill to its target (it waits two animation frames, and headless
    // Firefox runs frames ~4x a second), THEN look for the slide.
    const hbNow = () => document.querySelector(`#swuHomeStrips .swu-hb[data-hb-card="${card}"]`);
    for (let w = 0; w < 80; w++) {
      const h = hbNow();
      if (h && h.querySelector('.swu-hb-fill').style.width === h.getAttribute('data-hb-to') + '%') break;
      await sleep(10);
    }
    await sleep(60); const mid = ratio();
    const anim = widthAnim(hbNow() && hbNow().querySelector('.swu-hb-fill'));
    await sleep(700); const after = ratio();
    return { card, before, sameAfterIdlePoll, mid, anim, after };
  }, { seat, to });
}
async function motion({ p, name, s }) {
  const mine = (await pageBases(p)).find(x => x.PlayerID === 1);
  const hp = DICT[mine.CardID].hp;
  const share = dmg => Math.max(0, (hp - dmg) / hp);
  const expectSlide = (label, r, from, to, tol) => {
    if (!r || r.missing) { bad(name, `${label}: renderer not exported`); return; }
    Math.abs(r.before - from) <= tol ? ok() : bad(name, `${label}: started at ${r.before}, want ${from}`);
    Math.abs(r.after - to) <= tol ? ok() : bad(name, `${label}: ended at ${r.after}, want ${to}`);
    const lo = Math.min(from, to) + tol, hi = Math.max(from, to) - tol;
    // A slide is either SEEN mid-way, or a >=300ms width transition is running on the fill at that moment.
    ((r.mid > lo && r.mid < hi) || r.anim >= 300) ? ok()
      : bad(name, `${label}: no slide (mid-transition ${r.mid}, running width transition ${r.anim}ms)`);
  };
  const d0 = Number(mine.Damage);
  expectSlide('hit', await slideMine(p, d0 + 12), share(d0), share(d0 + 12), 0.02);
  expectSlide('heal (Review Focus 3)', await slideMine(p, 3), share(d0 + 12), share(3), 0.02);
  const over = await slideMine(p, hp + 10);           // Review Focus 2: overkill empties, never negative
  Math.abs(over.after) <= 0.01 ? ok() : bad(name, `overkill: fill ${over.after}, want 0`);
  if (s.board === 'ts') {
    const t = await slideTile(p, 4, 24);              // P4 Starlight Temple 12 -> 24 of 28
    if (t.missing) { bad(name, 'swuRenderHomeStrips not exported'); }
    else {
      const thp = DICT[t.card].hp;
      Math.abs(t.sameAfterIdlePoll - t.before) <= 0.01 ? ok() : bad(name, `tile: an idle poll moved the bar (${t.before} -> ${t.sameAfterIdlePoll})`);
      expectSlide('tile hit', t, (thp - 12) / thp, (thp - 24) / thp, 0.05);
    }
  }
  // Reduced motion (TCGCardMotion's default follows it): the change is instant.
  await p.emulateMedia({ reducedMotion: 'reduce' });
  const still = await slideMine(p, 0);
  if (!still.missing) (Math.abs(still.mid - still.after) <= 0.02 && still.anim === 0) ? ok()
    : bad(name, `reduced motion still slides (mid ${still.mid}, end ${still.after}, running transition ${still.anim}ms)`);
  await p.emulateMedia({ reducedMotion: 'no-preference' });
}

// ── zoom: opening a Twin Suns opponent's board shows THAT opponent's bar (runs last: it leaves Home) ──
async function zoom({ p, name, s }) {
  if (s.label !== 'desktop-ts') return;
  const buttons = await p.$$('#swuHomeStrips .swu-home-strip .swu-mb-zoom');
  if (buttons.length < 3) { bad(name, `expected 3 Zoom in buttons, found ${buttons.length}`); return; }
  const want = (await pageBases(p)).find(b => b.PlayerID === 4).CardID;
  await Promise.all([p.waitForLoadState('domcontentloaded').catch(() => {}), buttons[2].click()]);
  await p.waitForTimeout(1500);
  const got = await p.evaluate(() => {
    const hb = document.querySelector('#theirBaseHealth .swu-hb');
    return hb && hb.getClientRects().length ? hb.getAttribute('data-hb-card') : null;
  });
  got === want ? ok() : bad(name, `zoomed into P4 (${want}) but their bar shows ${got}`);
}

// ── resilience (final review): same base on another seat rebuilds (no fake slide); broken art still places ──
async function resilience({ p, name, s }) {
  if (s.board !== '1v1') return;                    // needs the opponent's full-size bar on screen
  // A DIFFERENT seat with the SAME base card (Twin Suns opponents may share a base, and switching the
  // zoomed board swaps theirBaseData) must rebuild the bar, not slide seat A's HP into seat B's.
  const running = await p.evaluate(async () => {
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    const fill = () => document.querySelector('#theirBaseHealth .swu-hb-fill');
    if (!fill()) return null;
    window.theirBaseData = String(window.theirBaseData).replace(/"PlayerID":\d+/, '"PlayerID":3').replace(/"Damage":\d+/, '"Damage":27');
    window.swuRenderBaseHealth('their');
    await sleep(40);
    return fill().getAnimations().filter(a => a.transitionProperty === 'width' && a.playState === 'running').length;
  });
  running === 0 ? ok() : bad(name, `another seat with the same base played a slide (${running} running transitions)`);
  // Base art that fails to load (e.g. a preview card with no image yet) must still get a placed bar.
  const width = await p.evaluate(async () => {
    const sleep = ms => new Promise(r => setTimeout(r, ms));
    const img = document.querySelector('#theirBaseSlot img'), box = document.getElementById('theirBaseHealth');
    box.innerHTML = ''; box.removeAttribute('style');
    await new Promise(r => { img.addEventListener('error', r, { once: true }); img.src = img.src.replace(/\.webp.*$/, '-missing.webp'); setTimeout(r, 4000); });
    window.swuRenderBaseHealth('their');
    await sleep(150);
    const hb = box.querySelector('.swu-hb'), card = document.getElementById('theirBase-0');
    return { bar: hb ? hb.getBoundingClientRect().width : -1, card: card ? card.getBoundingClientRect().width : -1 };
  });
  // PLACED means sized to the card (the unplaced fallback is the container's own width, e.g. 89px vs 53px).
  (width.card > 0 && Math.abs(width.bar - width.card) <= 2) ? ok()
    : bad(name, `broken base art: bar ${width.bar}px vs card ${width.card}px (never placed)`);
}

// ── setting: "Turn off health bars" (gear menu, guest = browser layer) hides every bar; the HP badge stays ──
async function barState(p) {
  return p.evaluate(() => {
    const vis = e => !!(e && e.getClientRects().length && getComputedStyle(e).visibility !== 'hidden');
    const t = document.getElementById('theirBase-0'), m = document.getElementById('myBase-0');
    const tr = t && vis(t) ? t.getBoundingClientRect() : null, mr = m ? m.getBoundingClientRect() : null;
    return {
      bars: [...document.querySelectorAll('.swu-hb')].filter(vis).length,
      badges: [...document.querySelectorAll('#swuHomeStrips .swu-mb-basehp')].filter(vis).length,
      gap: tr && mr ? mr.top - tr.bottom : null,                 // between the two 1v1 bases (desktop)
      box: !!document.getElementById('swuSetHideHealthBars'),
    };
  });
}
async function setting({ p, name, s }) {
  const before = await barState(p);
  if (!before.box) { bad(name, 'no "Turn off health bars" checkbox (#swuSetHideHealthBars) in the gear menu'); return; }
  const flip = (on) => p.evaluate((on) => {
    const box = document.getElementById('swuSetHideHealthBars');
    box.checked = on; box.dispatchEvent(new Event('change', { bubbles: true }));
  }, on);
  before.bars > 0 ? ok() : bad(name, 'no bars before turning them off — the check is vacuous');
  await flip(true); await p.waitForTimeout(300);
  const off = await barState(p);
  off.bars === 0 ? ok() : bad(name, `turned off, but ${off.bars} bar(s) still show`);
  off.badges === before.badges ? ok() : bad(name, `the HP badges changed (${before.badges} -> ${off.badges}); they must stay`);
  if (s.label === 'desktop-1v1') {
    // The bars' own height opened the gap between the bases; with them off it closes back (was 9px before the bars).
    (off.gap !== null && off.gap < before.gap - 20) ? ok() : bad(name, `gap between the bases didn't close (${before.gap} -> ${off.gap})`);
  }
  // It persists: a reload in this browser keeps them hidden (browser layer — works logged out).
  await p.reload({ waitUntil: 'domcontentloaded' });
  await p.waitForSelector('#myBaseSlot img', { timeout: 20000 }); await p.waitForTimeout(1500);
  const reloaded = await barState(p);
  reloaded.bars === 0 ? ok() : bad(name, `after a reload ${reloaded.bars} bar(s) show again`);
  // Opening the gear menu (as a player does) shows the row ticked: it reflects the EFFECTIVE value.
  const synced = await p.evaluate(() => {
    if (typeof swuOpenSettings === 'function') swuOpenSettings();
    const on = !!document.getElementById('swuSetHideHealthBars').checked;
    if (typeof swuCloseSettings === 'function') swuCloseSettings();
    return on;
  });
  synced ? ok() : bad(name, 'after a reload the gear menu shows "Turn off health bars" unticked');
  await flip(false); await p.waitForTimeout(600);
  const back = await barState(p);
  back.bars === before.bars ? ok() : bad(name, `turned back on: ${back.bars} bar(s), expected ${before.bars}`);
}

// ⚠ Order matters: `resilience` breaks the opponent's base art and `zoom` leaves the Home view, so both run last.
const SECTIONS = { wire, builder, full, tiles, motion, setting, resilience, zoom };

const SURFACES = [
  { label: 'desktop-ts',  board: 'ts',  viewport: { width: 1600, height: 1000 }, query: '' },
  { label: 'desktop-1v1', board: '1v1', viewport: { width: 1600, height: 1000 }, query: '' },
  { label: 'phone-ts',    board: 'ts',  viewport: { width: 430,  height: 932 },  query: '&swuLayout=mobile' },
  { label: 'phone-1v1',   board: '1v1', viewport: { width: 430,  height: 932 },  query: '&swuLayout=mobile' },
];

let games;
try { games = { ts: await seed(TWINSUNS), '1v1': await seed(ONEVONE) }; console.log('seeded', JSON.stringify(games)); }
catch (e) { console.log(`FAIL :: could not seed boards :: ${e.message}`); process.exit(1); }

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  let b;
  try { b = await launcher.launch(); } catch (e) { bad(engine, `could not launch: ${e.message}`); continue; }
  for (const s of SURFACES) {
    const name = `${engine}/${s.label}`;
    const p = await b.newPage({ viewport: s.viewport });
    const errs = []; p.on('pageerror', e => errs.push(e.message));
    await p.goto(`${BASE}NextTurn.php?folderPath=SWUSim&gameName=${games[s.board]}&playerID=1&authKey=testschema${s.query}`,
                 { waitUntil: 'domcontentloaded' });
    try { await p.waitForSelector('#myBaseSlot img', { timeout: 20000 }); }
    catch { bad(name, 'the board did not render'); await p.close(); continue; }
    // The base art is loading="lazy": the bar sizes itself from the LOADED card, so wait for it.
    await p.waitForFunction(() => [...document.querySelectorAll('#myBaseSlot img, #theirBaseSlot img')]
      .filter(i => i.getClientRects().length).every(i => i.complete && i.naturalWidth > 0), null, { timeout: 15000 }).catch(() => {});
    await p.waitForTimeout(500);
    for (const [key, fn] of Object.entries(SECTIONS)) {
      if (ONLY.length && !ONLY.includes(key)) continue;
      try { await fn({ p, name: `${name}/${key}`, s, engine }); }
      catch (e) { bad(`${name}/${key}`, `threw: ${e.message}`); }
    }
    errs.length ? bad(name, `page errors: ${errs.slice(0, 2).join(' | ')}`) : ok();
    await p.close();
  }
  await b.close();
}
console.log(fails === 0 ? `PASS (${checks} checks, 3 engines)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
