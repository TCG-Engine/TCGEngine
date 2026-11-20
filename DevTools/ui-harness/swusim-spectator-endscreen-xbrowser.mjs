// SWUSim end screen as a SPECTATOR (owner, 2026-11-04: spectators read "You Lost"). Chromium + Firefox + WebKit, desktop
// (1280x800) and phone (390x844, swuLayout=mobile):
//   · a game won by P2, Bo1 and Bo3 game 1: the spectator reads "<P2's name> Won" in the win colours, no "You";
//     the seats still read "You Won!" / "You Lost";
//   · P2 wins game 1 of a Bo3 and forfeits between games: the spectator reads "<P1's name> Won", "<P2> forfeited the match";
//   · a drawn game: the spectator reads "Draw".
// The games come from SWUSim/DevTools/tests/draw_endscreen_fixture.php (public, so a spectator may open the board).
//
// Usage: node swusim-spectator-endscreen-xbrowser.mjs [BASE] [engines]
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ONLY = (process.argv[3] || 'chromium,firefox,webkit').split(',');
const ENGINES = Object.fromEntries(Object.entries({ chromium, firefox, webkit }).filter(([n]) => ONLY.includes(n)));
const SIZES = [{ name: 'desktop', w: 1280, h: 800, q: '' }, { name: 'phone', w: 390, h: 844, q: '&swuLayout=mobile' }];
const CASES = [
  { name: 'bo1 p2won', q: 'scenario=p2won', spectator: 'Guest P2 Won', seats: { 1: 'You Lost', 2: 'You Won!' } },
  { name: 'bo3 game 1 p2won', q: 'queue=bo3&scenario=p2won', spectator: 'Guest P2 Won', seats: {} },
  // P2 won game 1, then forfeited the Bo3 between games: the MATCH went to P1, so the spectator reads P1's name.
  { name: 'bo3 p2 forfeits', q: 'queue=bo3&scenario=p2won', forfeit: 2, spectator: 'Guest P1 Won', subtitle: 'Guest P2 forfeited the match', seats: {} },
  { name: 'draw', q: '', spectator: 'Draw', seats: {} },
];

let failures = 0;
const ok  = (where, msg) => console.log(`  ok   ${where}: ${msg}`);
const bad = (where, msg) => { failures++; console.log(`  FAIL ${where}: ${msg}`); };
const expect = (where, cond, msg, detail) => cond ? ok(where, msg) : bad(where, msg + ' — ' + JSON.stringify(detail));

async function endTitle(browser, size, fx, seat) {
  const ctx = await browser.newContext({ viewport: { width: size.w, height: size.h } });
  const page = await ctx.newPage();
  const key = seat === 'S' ? '' : `&authKey=${fx.keys[seat]}`;
  await page.goto(`${BASE}NextTurn.php?gameName=${fx.gameName}&playerID=${seat}${key}&folderPath=SWUSim${size.q}`, { waitUntil: 'domcontentloaded', timeout: 90000 });
  // The title settles once EndGameInfo answers (the overlay may first draw, then be retitled).
  let v = null;
  const t0 = Date.now();
  while (Date.now() - t0 < 30000) {
    v = await page.evaluate(() => {
      const ov = document.getElementById('game-over-overlay'), t = document.getElementById('game-over-title');
      return ov && t ? { title: t.textContent, cls: ov.className, buttons: document.querySelectorAll('#game-over-buttons button').length } : null;
    }).catch(() => null);
    if (v && v.buttons > 0) break;
    await page.waitForTimeout(400);
  }
  await page.waitForTimeout(1500);   // a later redraw must not undo it
  v = await page.evaluate(() => {
    const ov = document.getElementById('game-over-overlay'), t = document.getElementById('game-over-title');
    const sub = document.getElementById('game-over-subtitle');
    return ov && t ? { title: t.textContent, cls: ov.className, subtitle: sub ? sub.textContent : '' } : null;
  }).catch(() => null);
  await ctx.close();
  return v;
}

for (const [eng, type] of Object.entries(ENGINES)) {
  const browser = await type.launch();
  try {
    const api = await browser.newContext();
    for (const c of CASES) {
      const fx = await (await api.request.get(`${BASE}SWUSim/DevTools/tests/draw_endscreen_fixture.php?${c.q}`)).json();
      if (!fx.gameName) { bad(`${eng}/${c.name}`, 'fixture failed: ' + JSON.stringify(fx)); continue; }
      if (c.forfeit) {
        const r = await (await api.request.post(`${BASE}SWUSim/SubmitSideboard.php`, { form: { matchId: fx.matchId, playerID: String(c.forfeit), authKey: fx.keys[c.forfeit], action: 'forfeit' } })).json();
        if (!r.success) { bad(`${eng}/${c.name}`, 'forfeit failed: ' + JSON.stringify(r)); continue; }
      }
      for (const size of SIZES) {
        const where = `${eng}/${size.name}/${c.name}`;
        const s = await endTitle(browser, size, fx, 'S');
        expect(where, s && s.title === c.spectator, `the spectator reads "${c.spectator}"`, s);
        if (c.subtitle) expect(where, s && s.subtitle === c.subtitle, `under it: "${c.subtitle}"`, s);
        if (c.spectator !== 'Draw') expect(where, s && /\bwon\b/.test(s.cls) && !/\blost\b/.test(s.cls), 'in the win colours', s);
        for (const [seat, title] of Object.entries(c.seats)) {
          const p = await endTitle(browser, size, fx, seat);
          expect(where, p && p.title === title, `seat ${seat} still reads "${title}"`, p);
        }
      }
    }
  } catch (e) { bad(eng, String(e && e.stack || e)); }
  await browser.close();
}
console.log(failures ? `FAIL (${failures})` : 'PASS');
process.exit(failures ? 1 : 0);
