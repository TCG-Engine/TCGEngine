// SWUSim desktop board — the centre lane (my base + leader) must clear the KEEP INITIATIVE / PASS cluster.
// Chromium + Firefox + WebKit.
//
// Why this exists: bug report 2026-10-29 (1536x738 @ DPR 1.25) — KEEP INITIATIVE sat on the bottom of the
// player's leader card. Measured, it did so at EVERY desktop size (~20px at 1920x1080 too): the lane cap in
// GameLayout.php guessed the lane's height and the guess went stale. The lane's shape is now measured
// (GameLayoutShared syncLaneCapVars → --swu-lane-k / --swu-lane-fixed-h) and the pass reserve counts the
// Twin Suns counter rows (window.SWU_PASS_ROWS). The schema suite never renders a page, so it cannot see this.
//
// Per board × viewport × engine:
//   · the topmost visible pass-cluster button sits at or below the leader card's bottom edge — UNLESS the
//     lane is at its 92px floor (owner ruling: below that the FORTIFIED/ARRESTED labels clip, which is worse
//     than the cluster touching the leader; reported as FLOOR, not a failure);
//   · no FORTIFIED/ARRESTED tab label is clipped;
//   · no horizontal page overflow.
// Boards: a plain 2-player board, one Fortified tab, both tabs (Visual/BaseTabs_FortifiedAndArrested_2P.md),
// a Twin Suns house-rules board with the fourth counter (Visual/HouseRules_Board.md), and full 3- and 4-seat
// Twin Suns boards (Visual/TwinSuns_3P_FullArenas.md, Visual/TwinSuns_4P_AllZonesFilled.md).
//
// Usage: node swusim-center-lane-xbrowser.mjs [BASE] [SHOTS_DIR]
import { chromium, firefox, webkit } from 'playwright';
import { readFileSync } from 'node:fs';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.argv[3] || '';
const here  = dirname(fileURLToPath(import.meta.url));
const visual = (f) => readFileSync(resolve(here, '../../SWUSim/Tests/Visual/' + f), 'utf8');
const LANE_FLOOR = 92;   // GameLayout.php's max(92px, …)

const plain = (extra) => `# centre-lane board
## GIVEN
CommonSetup: bgk/bgk/{myResources:10;theirResources:10;myLeader:JTL_005;theirLeader:HMW_003;myBaseDamage:15;theirBaseDamage:12}
SkipPreGame: true
WithActivePlayer: 1
WithGamePhase: ActionPhase
WithInitiativePlayer: 1
WithInitiativeClaimed: false
WithP1Hand: [SOR_095 SOR_046 SOR_095]
WithP1SpaceArena: SOR_237:1:2
WithP1GroundArena: SOR_046:1:0
WithP2SpaceArena: [SOR_225:1:0 SOR_237:1:0]
WithP2GroundArena: [SOR_095:1:0 SOR_046:1:0]
${extra}
## WHEN
## EXPECT
`;
const boards = {
  plain:      plain(''),
  fortified:  plain('WithP1BaseUpgrade: HMW_081'),
  twoTabs:    visual('BaseTabs_FortifiedAndArrested_2P.md'),
  houseRules: visual('HouseRules_Board.md'),
  ts3:        visual('TwinSuns_3P_FullArenas.md'),
  ts4:        visual('TwinSuns_4P_AllZonesFilled.md'),
};
const sizes = [
  { name: '1536x738@1.25', w: 1536, h: 738,  dpr: 1.25, all: true },   // the bug report
  { name: '1920x1080',     w: 1920, h: 1080, dpr: 1,    all: true },
  { name: '1477x831',      w: 1477, h: 831,  dpr: 1 },                  // the 2026 report the old cap was built for
  { name: '1366x700',      w: 1366, h: 700,  dpr: 1 },
  { name: '2560x1300',     w: 2560, h: 1300, dpr: 1 },
];

async function login(page) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', 'claudebot1');
  await page.fill('input[name="password"]', 'pass');
  await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
}

let failures = 0;
for (const [ename, engine] of Object.entries({ chromium, firefox, webkit })) {
  const browser = await engine.launch();
  for (const [bname, schema] of Object.entries(boards)) for (const s of sizes) {
    if (bname !== 'plain' && !s.all) continue;
    const ctx = await browser.newContext({ viewport: { width: s.w, height: s.h }, deviceScaleFactor: s.dpr });
    const page = await ctx.newPage();
    await login(page);
    const setup = await (await page.request.post(BASE + 'SWUSim/TestSchemaSetup.php', { multipart: { schema } })).json();
    if (setup.error) throw new Error(`${bname} setup: ${setup.error}`);
    for (const step of setup.whenSteps || []) {
      const r = await (await page.request.post(BASE + 'SWUSim/TestSchemaStep.php',
        { multipart: { gameName: String(setup.gameName), step: step.raw } })).json();
      if (r.error) throw new Error(`${bname} step "${step.raw}": ${r.error}`);
    }
    await page.goto(BASE + `NextTurn.php?folderPath=SWUSim&gameName=${setup.gameName}&playerID=1&authKey=testschema`
      + '&viewerPerspective=1&opponentID=2', { waitUntil: 'load' });
    await page.waitForTimeout(4500);   // card art loads, then syncLaneCapVars re-measures
    const m = await page.evaluate(() => {
      const vis = (el) => el && !el.hidden && el.getBoundingClientRect().height > 0;
      const leaderWrap = document.getElementById('myLeaderSlot')?.closest('.swu-leader-slot-wrap');
      const top = Array.from(document.querySelectorAll('#swuPassControl .swu-init-pass-btn')).filter(vis)
        .sort((a, b) => a.getBoundingClientRect().top - b.getBoundingClientRect().top)[0];
      const clipped = Array.from(document.querySelectorAll('#myBaseTabs .swu-base-tab')).filter((t) => {
        const w = t.getBoundingClientRect().width; const old = t.style.width; t.style.width = 'max-content';
        const need = t.getBoundingClientRect().width; t.style.width = old; return need > w + 0.5;
      }).length;
      return {
        lane: parseFloat(getComputedStyle(document.querySelector('.swu-center-col-bot')).width),
        leaderBottom: leaderWrap ? leaderWrap.getBoundingClientRect().bottom : NaN,
        clusterTop: top ? top.getBoundingClientRect().top : NaN,
        clipped, overflow: document.documentElement.scrollWidth > innerWidth,
      };
    });
    const gap = Math.round(m.clusterTop - m.leaderBottom);
    const atFloor = m.lane <= LANE_FLOOR + 0.5;
    let verdict = 'OK';
    if (gap < 0) verdict = atFloor ? 'FLOOR' : 'FAIL';
    if (m.clipped || m.overflow || isNaN(gap)) verdict = 'FAIL';
    if (verdict === 'FAIL') failures++;
    console.log(`${verdict.padEnd(5)} ${ename} ${bname} ${s.name}: lane=${Math.round(m.lane)} gap=${gap}`
      + `${m.clipped ? ` CLIPPED-TABS=${m.clipped}` : ''}${m.overflow ? ' H-OVERFLOW' : ''}`);
    if (SHOTS) await page.screenshot({ path: `${SHOTS}/center-lane-${ename}-${bname}-${s.name}.png` });
    await ctx.close();
  }
  await browser.close();
}
console.log(failures ? `${failures} FAILED` : 'ALL PASSED');
process.exit(failures ? 1 : 0);
