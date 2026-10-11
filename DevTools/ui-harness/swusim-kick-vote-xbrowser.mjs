// Inactivity kick vote on live SWUSim boards, in Chromium, Firefox and WebKit.
//  2P: the opponent sees "Kick P1?", the target sees a warning instead of buttons, one Yes ends the game.
//  Twin Suns (4 seats): all three others see it, ONE Yes does not remove, the second does.
//  Team Suns: only the two opposing players see it; the target's teammate never does.
//  Wait: closes the prompt for everyone. Target acting: closes it for everyone.
//  Phone layout: the prompt fits and the page does not scroll sideways.
// Timeouts are reached by REWINDING the clock via SWUSim/DevTools/zz_presence_poke.php — never by sleeping.
// Spec: docs/superpowers/specs/2026-09-17-swusim-inactivity-timer-and-kick-design.md
// Usage: node swusim-kick-vote-xbrowser.mjs [baseURL]   ENGINES=chromium,firefox,webkit (default all)
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const ALL = { chromium, firefox, webkit };
const ENGINES = Object.entries(ALL).filter(([n]) => !process.env.ENGINES || process.env.ENGINES.split(',').includes(n));
const SHOTS = process.env.SHOTS_DIR || '/tmp';

const SCHEMA_2P = `## GIVEN
CommonSetup: bbw/rrk/{myResources:8; theirResources:8}
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP1GroundArena: [SOR_032:1:0]
WithP2GroundArena: [SOR_034:1:0]

## WHEN

## EXPECT
TURNPLAYER:1
`;
const SEATS4 = `CommonSetup: rrk/bbw/{myLeader:IBH_053; myLeader2:SHD_011; theirLeader:SHD_007; theirLeader2:SHD_010}
SkipPreGame: true
WithSeatOrder: 1234
WithLiveSeats: 1234
WithGamePhase: ActionPhase
WithActivePlayer: 1
WithP3Base: SOR_026:5
WithP3Leader:  SHD_014
WithP3Leader2: SHD_015
WithP4Base: SOR_026:8
WithP4Leader:  TWI_009
WithP4Leader2: TWI_010`;
const SCHEMA_TWIN = `## GIVEN\n${SEATS4}\n\n## WHEN\n\n## EXPECT\nTURNPLAYER:1\n`;
const SCHEMA_TEAM = `## GIVEN\n${SEATS4}\nWithP1GlobalEffect: SWU_MODE_TEAMS\n\n## WHEN\n\n## EXPECT\nTURNPLAYER:1\n`;

let allOk = true;
const results = [];
const ok = (e, n, cond, extra = '') => { if (!cond) allOk = false; results.push([e, n, !!cond, extra]); };
const report = () => { for (const [e, n, pass, extra] of results) console.log(`${pass ? 'PASS' : 'FAIL'}  ${e.padEnd(8)} ${n}${extra ? '  — ' + extra : ''}`); };
setTimeout(() => { console.log('WATCHDOG: timed out after 420s'); report(); process.exit(9); }, 420000).unref();

const makeGame = async (schema) => {
  const r = await fetch(BASE + 'SWUSim/TestSchemaSetup.php', { method: 'POST', body: new URLSearchParams({ schema }) });
  const j = await r.json();
  if (!j.gameName) throw new Error('TestSchemaSetup failed: ' + JSON.stringify(j));
  return String(j.gameName);
};
const poke = (gn, seat, back) => fetch(`${BASE}SWUSim/DevTools/zz_presence_poke.php?gameName=${gn}&seat=${seat}&back=${back}&actedOnly=1`).then((r) => r.json());
const url = (gn, pid, mobile = false) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${gn}&playerID=${pid}`
  + (pid === 'S' ? '' : '&authKey=testschema') + (mobile ? '&swuLayout=mobile' : '');

async function open(browser, gn, pid, mobile = false) {
  const ctx = await browser.newContext({ viewport: mobile ? { width: 400, height: 860 } : { width: 1700, height: 1050 } });
  const page = await ctx.newPage();
  await page.goto(url(gn, pid, mobile), { waitUntil: 'domcontentloaded' });
  await page.waitForTimeout(1800);
  return { ctx, page };
}
const promptText = (page) => page.evaluate(() => {
  const el = document.getElementById('swuKickVote');
  return el && el.classList.contains('is-open') ? el.innerText.replace(/\s+/g, ' ').trim() : '';
});
const waitFor = async (page, pred, ms = 15000) => {
  const end = Date.now() + ms;
  while (Date.now() < end) { if (pred(await promptText(page))) return true; await page.waitForTimeout(400); }
  return false;
};
const hasKickButton = (page) => page.evaluate(() => !!document.getElementById('swuKickYes'));

for (const [engine, launcher] of ENGINES) {
  let browser;
  try {
    browser = await launcher.launch();

    // ── 2P ──
    const gn = await makeGame(SCHEMA_2P);
    const s1 = await open(browser, gn, '1');
    const s2 = await open(browser, gn, '2');
    const sp = await open(browser, gn, 'S');
    ok(engine, '2P: no prompt while the clock runs', (await promptText(s2.page)) === '');
    await poke(gn, 1, 200);
    ok(engine, '2P: opponent sees the kick prompt', await waitFor(s2.page, (t) => /Kick/.test(t)));
    ok(engine, '2P: opponent has a Kick button', await hasKickButton(s2.page));
    ok(engine, '2P: target warned, not offered', await waitFor(s1.page, (t) => /vote to remove you/.test(t))
                                                  && !(await hasKickButton(s1.page)));
    ok(engine, '2P: spectator gets no button', !(await hasKickButton(sp.page)));
    await s2.page.screenshot({ path: `${SHOTS}/kick-2p-opponent-${engine}.png` }).catch(() => {});
    await s1.page.screenshot({ path: `${SHOTS}/kick-2p-target-${engine}.png` }).catch(() => {});
    // Wait closes it for everyone, then it returns once the extension lapses.
    await s2.page.click('#swuKickWait');
    ok(engine, '2P: wait closes the prompt', await waitFor(s2.page, (t) => !/Kick/.test(t)));
    await poke(gn, 1, 200);
    ok(engine, '2P: prompt returns after the wait', await waitFor(s2.page, (t) => /Kick/.test(t)));
    // One Yes ends the game.
    await s2.page.click('#swuKickYes');
    ok(engine, '2P: kick ends the game', await waitFor(s2.page, (t) => !/Kick/.test(t), 20000));
    for (const s of [s1, s2, sp]) await s.ctx.close();

    // ── Twin Suns: 2 of 3 ──
    const gt = await makeGame(SCHEMA_TWIN);
    const t = {};
    for (const pid of ['1', '2', '3', '4']) t[pid] = await open(browser, gt, pid);
    await poke(gt, 1, 400);
    ok(engine, 'TwinSuns: all three others prompted',
       (await waitFor(t['2'].page, (x) => /Kick/.test(x)))
       && (await waitFor(t['3'].page, (x) => /Kick/.test(x)))
       && (await waitFor(t['4'].page, (x) => /Kick/.test(x))));
    ok(engine, 'TwinSuns: tally shows 0 of 2', /0 of 2/.test(await promptText(t['2'].page)), await promptText(t['2'].page));
    await t['2'].page.click('#swuKickYes');
    ok(engine, 'TwinSuns: voter sees it is waiting', await waitFor(t['2'].page, (x) => /waiting for the others/i.test(x)));
    ok(engine, 'TwinSuns: one Yes does not remove',
       await waitFor(t['3'].page, (x) => /1 of 2/.test(x)), await promptText(t['3'].page));
    await t['3'].page.click('#swuKickYes');
    ok(engine, 'TwinSuns: second Yes removes the seat', await waitFor(t['3'].page, (x) => !/Kick/.test(x), 20000));
    await t['2'].page.screenshot({ path: `${SHOTS}/kick-twinsuns-${engine}.png` }).catch(() => {});
    for (const s of Object.values(t)) await s.ctx.close();

    // ── Team Suns: only the opposing team ──
    const gm = await makeGame(SCHEMA_TEAM);
    const m = {};
    for (const pid of ['1', '2', '3', '4']) m[pid] = await open(browser, gm, pid);
    await poke(gm, 1, 400);
    ok(engine, 'TeamSuns: both blues prompted',
       (await waitFor(m['2'].page, (x) => /Kick/.test(x))) && (await waitFor(m['4'].page, (x) => /Kick/.test(x))));
    ok(engine, 'TeamSuns: red teammate not prompted', !(await hasKickButton(m['3'].page)));
    await m['2'].page.click('#swuKickYes');
    ok(engine, 'TeamSuns: one blue is not enough',
       await waitFor(m['4'].page, (x) => /1 of 2/.test(x)), await promptText(m['4'].page));
    await m['4'].page.click('#swuKickYes');
    ok(engine, 'TeamSuns: both blues win it', await waitFor(m['4'].page, (x) => !/Kick/.test(x), 20000));
    for (const s of Object.values(m)) await s.ctx.close();

    // ── acting cancels, and the phone layout ──
    const gc = await makeGame(SCHEMA_2P);
    const c1 = await open(browser, gc, '1');
    const c2 = await open(browser, gc, '2');
    await poke(gc, 1, 200);
    ok(engine, 'cancel: prompt is open', await waitFor(c2.page, (x) => /Kick/.test(x)));
    await c1.page.evaluate(() => { SubmitInput(10002, '&cardID=' + encodeURIComponent('myGroundArena-0!FSM!')); });
    await c1.page.waitForTimeout(1200);
    await c1.page.evaluate(() => { SubmitInput('DECISION', '&decisionIndex=0&cardID=' + encodeURIComponent('theirGroundArena-0')); });
    ok(engine, 'cancel: acting closes it everywhere', await waitFor(c2.page, (x) => !/Kick/.test(x), 20000));
    await c1.ctx.close(); await c2.ctx.close();

    const gp = await makeGame(SCHEMA_2P);
    const p2 = await open(browser, gp, '2', true);
    await poke(gp, 1, 200);
    ok(engine, 'mobile: prompt appears', await waitFor(p2.page, (x) => /Kick/.test(x)));
    const overflow = await p2.page.evaluate(() => document.documentElement.scrollWidth > window.innerWidth + 1);
    ok(engine, 'mobile: no horizontal page scroll', !overflow);
    await p2.page.screenshot({ path: `${SHOTS}/kick-mobile-${engine}.png` }).catch(() => {});
    await p2.ctx.close();
  } catch (e) {
    ok(engine, 'engine run completed', false, String((e && e.message) || e));
  } finally {
    if (browser) await browser.close().catch(() => {});
  }
}
report();
console.log(allOk ? '\nALL PASS' : '\nSOME FAILED');
process.exit(allOk ? 0 : 1);
