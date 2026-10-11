// A Games in Progress chip must be the APPROVED chip.
//
// The stylesheet's MATCH CHIPS section was ported with the redesign and then sat unused for days,
// because swuGameChip() still emitted the older .swu-game-chip / .swu-idstack markup. The CSS was
// right and nothing was wearing it — which is exactly the "dead twin" failure the redesign audit
// already found elsewhere, and it is invisible to a stylesheet-only check. So this gate asserts
// the LIVE DOM, against a real running game.
//
// Owner's list, 2026-09-25, each pinned below:
//   1 leader + base art and the VS split rule     5 the Spectate button's size
//   2 the format and the round                    6 set codes on the leader labels
//   3 elapsed time (nice to have)                 7 leader and base the SAME size
//   4 the format label
//
// ⚠ Requires at least one public game in progress. With none, the panel correctly shows its empty
// state and there is nothing to assert — the gate says so and exits 2 rather than passing on air.
import { chromium, firefox, webkit } from 'playwright';
const MENU = process.env.MENU_URL || 'http://localhost:3400/TCGEngine/SharedUI/MainMenu.php';
let fails = 0, checks = 0;
const bad = (n, m) => { fails++; checks++; console.log(`FAIL ${n} :: ${m}`); };
const ok  = () => { checks++; };

for (const [name, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]]) {
  const b = await launcher.launch();
  const p = await b.newPage({ viewport: { width: 1440, height: 1000 } });
  const errs = []; p.on('pageerror', e => errs.push(e.message));
  await p.goto(MENU, { waitUntil: 'networkidle' });
  await p.waitForTimeout(2500);

  const d = await p.evaluate(() => {
    const li = document.querySelector('.games .match');
    if (!li) return { none: true, legacy: !!document.querySelector('.swu-game-chip, .swu-idstack') };
    const q = s => li.querySelector(s);
    const box = e => e ? { w: Math.round(e.getBoundingClientRect().width), h: Math.round(e.getBoundingClientRect().height) } : null;
    const lead = q('.tc--leader'), base = q('.tc--base'), btn = q('.swu-spectate-btn');
    return {
      none: false,
      seats: li.querySelectorAll('.seat').length,
      vs: !!q('.match__vs'),
      leadImg: !!q('.tc--leader img'), baseImg: !!q('.tc--base img'),
      lead: box(lead), base: box(base),
      set: q('.seat__set') ? q('.seat__set').textContent.trim() : '',
      sub: q('.seat__sub') ? q('.seat__sub').textContent.trim() : '',
      fmt: q('.match__fmt') ? q('.match__fmt').textContent.trim() : '',
      meta: q('.match__meta') ? q('.match__meta').textContent.trim() : '',
      btn: box(btn),
      btnHref: btn ? btn.getAttribute('data-href') || '' : '',
      overflow: li.scrollWidth - li.clientWidth,
      legacy: !!document.querySelector('.swu-game-chip, .swu-idstack'),
    };
  });

  if (d.none) {
    console.log(`SKIP ${name} :: no public game in progress` + (d.legacy ? ' (and LEGACY markup is present)' : ''));
    await b.close();
    if (name === 'webkit' && checks === 0) { console.log('\nNO GAME TO TEST — start a public game and re-run'); process.exit(2); }
    continue;
  }

  // the old markup must be gone, not merely unused
  if (d.legacy) bad(name, 'legacy .swu-game-chip/.swu-idstack markup is still being emitted'); else ok();

  // 1 — art + the VS rule
  if (!d.leadImg) bad(name, 'no leader card art'); else ok();
  if (!d.baseImg) bad(name, 'no base card art'); else ok();
  if (d.seats < 2) bad(name, `only ${d.seats} seat(s) rendered`); else ok();
  if (d.seats === 2 && !d.vs) bad(name, 'a 2-seat chip has no VS rule'); else ok();

  // 7 — leader and base the same size (the owner's one change FROM the mockup)
  if (!d.lead || !d.base) bad(name, 'missing a thumbnail');
  else if (d.lead.w !== d.base.w || d.lead.h !== d.base.h)
    bad(name, `leader ${d.lead.w}x${d.lead.h} != base ${d.base.w}x${d.base.h} — they must match`);
  else ok();

  // 6 — the set code, in parentheses, beside the leader name
  if (!/^\([A-Z]{2,5}\d*\)$/.test(d.set)) bad(name, `no set code on the leader label (got "${d.set}")`); else ok();

  // 2 + 4 — the format label and the round
  if (!d.fmt) bad(name, 'no format label'); else ok();

  // ⚠ Assert the WIRING, not the value. The round rides on the active-game index, an APCu cache
  // with a 60s TTL, so it is legitimately 0 for a game that has not acted since the index was last
  // built — and a flat "there must be a round" check goes red on a quiet board. Comparing the chip
  // against the payload that drew it is stable and still catches a chip that drops the round.
  const api = await p.evaluate(async () => {
    const r = await fetch('/TCGEngine/SWUSim/PublicGames.php');
    const j = await r.json();
    return (j.games || [])[0] || null;
  });
  if (!api) bad(name, 'the endpoint returned no game while one is on screen');
  else if (api.round > 0 && !new RegExp('round\\s*' + api.round, 'i').test(d.meta))
    bad(name, `payload says round ${api.round}, the chip says "${d.meta}"`);
  else if (api.round === 0 && /round/i.test(d.meta))
    bad(name, `the chip invented a round ("${d.meta}") when the payload has none`);
  else ok();

  // 3 — elapsed time (wanted, not required: reported, never failed)
  checks++;
  if (!/\d+\s*m/i.test(d.meta)) console.log(`NOTE ${name} :: no elapsed time in "${d.meta}" (optional)`);

  // 5 — the Spectate button, at the theme's 44px tap size, and it still knows where to go
  if (!d.btn) bad(name, 'no Spectate button');
  else if (d.btn.h < 40 || d.btn.h > 48) bad(name, `Spectate is ${d.btn.h}px tall, off the 44px tap size`);
  else ok();
  if (!/NextTurn\.php/.test(d.btnHref)) bad(name, `Spectate has no spectate URL ("${d.btnHref}")`); else ok();

  // the rail is narrow; a chip that overflows it is the bug the container queries exist to avoid
  if (d.overflow > 1) bad(name, `the chip overflows its rail by ${d.overflow}px`); else ok();

  if (errs.length) bad(name, `pageerror ${errs[0]}`);
  await b.close();
}
console.log(fails ? `\n${fails}/${checks} game-chip checks failed` : `\nGAME CHIP IS THE APPROVED CHIP — ${checks} checks`);
process.exit(fails ? 1 : 0);
