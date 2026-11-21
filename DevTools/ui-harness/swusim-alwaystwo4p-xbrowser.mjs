// 4P Always Two on screen (docs/superpowers/specs/2026-11-20-swusim-always-two-design.md "## 4P Always Two"; plan
// docs/superpowers/plans/2026-11-21-swusim-always-two-4p.md Task 6): the Fun Formats option and its setup, the 4-seat team room with no
// bots, the partner's "<name> wants to play <card>. Accept?" prompt, the proposer's waiting line and refusal, Accept playing the card,
// and a teammate's hand face-up. Checked in Chromium, Firefox and WebKit, desktop and phone. ENGINES=chromium narrows an iteration.
//   node DevTools/ui-harness/swusim-alwaystwo4p-xbrowser.mjs          (seeds its own games; claudebot1 must be a mod)
import fs from 'fs';
import { chromium, firefox, webkit } from 'playwright';

const BASE = process.env.BASE_URL || 'http://localhost:3400/TCGEngine/';
const SHOTS = process.env.SHOTS || '';
const ENGINES = (process.env.ENGINES || 'chromium,firefox,webkit').split(',');
const form = (o) => new URLSearchParams(o).toString();
const post = async (path, body, cookie) => fetch(BASE + path, { method: 'POST',
  headers: { 'Content-Type': 'application/x-www-form-urlencoded', ...(cookie ? { cookie } : {}) }, body: form(body), redirect: 'manual' });
const login = await post('AccountFiles/AttemptPasswordLogin.php', { submit: '1', userID: 'claudebot1', password: 'pass' });
const cookie = (login.headers.getSetCookie?.() || []).map(c => c.split(';')[0]).join('; ');
async function seed(schema) {
  const json = JSON.parse(await (await post('SWUSim/TestSchemaSetup.php', { schema }, cookie)).text());
  if (json.error) throw new Error(`TestSchemaSetup refused: ${json.error}`);
  for (const st of json.whenSteps || []) await post('SWUSim/TestSchemaStep.php', { gameName: json.gameName, step: st.raw }, cookie);
  return String(json.gameName);
}
const VOTE = fs.readFileSync(new URL('../../SWUSim/Tests/Visual/AlwaysTwo_4pVote.md', import.meta.url), 'utf8');
const TEAMSUNS = VOTE.replace('WithAlwaysTwo: 4p', 'WithTeams: true');

let fails = 0, checks = 0;
const check = (ok, name, msg) => { checks++; if (!ok) { fails++; console.log(`FAIL ${name} :: ${msg}`); } };
const SURFACES = [
  { label: 'desktop', viewport: { width: 1600, height: 1000 } },
  { label: 'phone',   viewport: { width: 390,  height: 844  } },
];
const url = (g, seat) => `${BASE}NextTurn.php?folderPath=SWUSim&gameName=${g}&playerID=${seat}&authKey=testschema`;
const shown = (page, sel) => page.evaluate((s) => { const el = document.querySelector(s);
  return !!el && !el.hidden && getComputedStyle(el).display !== 'none' && el.getBoundingClientRect().width > 0; }, sel);
const ready = (p) => p.waitForFunction(() => document.querySelectorAll('#myHand > span').length > 0, null, { timeout: 90000 }).catch(() => {});
const sleep = (ms) => new Promise(r => setTimeout(r, ms));

for (const [engine, launcher] of [['chromium', chromium], ['firefox', firefox], ['webkit', webkit]].filter(([e]) => ENGINES.includes(e))) {
  let b;
  try { b = await launcher.launch(); } catch (e) { check(false, engine, `could not launch: ${e.message}`); continue; }
  for (const s of SURFACES) {
    const name = `${engine}/${s.label}`;
    // ── The partner's prompt, the proposer's wait, Accept ──────────────────────────────────────────────────────────────────────
    const g = await seed(VOTE);
    const ctx = await b.newContext({ viewport: s.viewport });
    const p1 = await ctx.newPage(), p3 = await ctx.newPage();
    await p1.goto(url(g, 1), { waitUntil: 'domcontentloaded' }); await p3.goto(url(g, 3), { waitUntil: 'domcontentloaded' });
    await ready(p1); await ready(p3);
    await p3.waitForSelector('#yesno-decision-modal', { timeout: 30000 }).catch(() => {});
    const prompt = await p3.evaluate(() => { const m = document.getElementById('yesno-decision-modal'); return m ? m.innerText.replace(/\s+/g, ' ').trim() : ''; });
    check(/P1 wants to play Battlefield Marine\. Accept\?/.test(prompt), name, `seat 3 is asked "P1 wants to play Battlefield Marine. Accept?" ("${prompt.slice(0, 120)}")`);
    if (SHOTS) await p3.screenshot({ path: `${SHOTS}/a24p-prompt-${engine}-${s.label}.png` });
    const wait = await p1.evaluate(() => document.body.innerText);
    check(/Waiting for P3/.test(wait), name, 'seat 1 sees it is waiting for P3');
    // A second hand card while the proposal is pending → refused, and the refusal is on screen.
    await p1.evaluate(() => { const c = document.querySelector('#myHand > span:nth-child(2) img') || document.querySelector('#myHand > span img'); if (c) c.click(); });
    // The refusal is a short-lived notice — watch the page text for it rather than one element.
    const refused = await p1.waitForFunction(() => /A proposal is pending\./.test(document.body.innerText), null, { timeout: 8000 }).then(() => true).catch(() => false);
    check(refused, name, "seat 1's second play is refused on screen (\"A proposal is pending.\")");
    if (SHOTS) await p1.screenshot({ path: `${SHOTS}/a24p-waiting-${engine}-${s.label}.png` });
    // Teammate hand face-up from seat 1 (board 3 = Zoom In on the teammate), and not playable.
    const tm = await p1.evaluate(async () => {
      const views = window.swuViews || []; const i = views.findIndex(v => v.mode === 'matchup' && +v.oppSeat === 3);
      if (i < 0) return { i };
      window.swuSetView(i); await new Promise(r => setTimeout(r, 800));
      const imgs = [...document.querySelectorAll('#theirHand > span img')];
      return { i, n: imgs.length, faceUp: imgs.filter(im => !/CardBack|cardback|card-back/i.test(im.getAttribute('src') || '')).length };
    });
    check(tm.n === 3 && tm.faceUp === 3, name, `seat 1 sees board 3's 3 hand cards face-up: ${JSON.stringify(tm)}`);
    await p1.evaluate(() => { const c = document.querySelector('#theirHand > span img'); if (c) c.click(); }); await sleep(1500);
    await p1.evaluate(() => { const v = (window.swuViews || []).findIndex(x => x.mode === 'home'); if (v >= 0) window.swuSetView(v); });
    // Accept on seat 3 → board 1's Marine is in play on every screen.
    await p3.evaluate(() => { const y = document.querySelector('#yesno-decision-modal .yesno-decision-yes'); if (y) y.click(); });
    const units = () => p1.evaluate(() => document.querySelectorAll('[id^="myGroundArena-"]').length);
    await p1.waitForFunction(() => document.querySelectorAll('[id^="myGroundArena-"]').length >= 1, null, { timeout: 30000 }).catch(() => {});
    check(await units() === 1, name, `after Accept, board 1's Marine is in play (seat 1's view: ${await units()} unit)`);
    check(await p1.evaluate(() => document.querySelectorAll('#myHand > span img').length) === 1, name, "the clicked teammate card did nothing; board 1's hand is 1 (only the accepted play left it)");
    check(!(await p3.evaluate(() => !!document.getElementById('yesno-decision-modal') && getComputedStyle(document.getElementById('yesno-decision-modal')).display !== 'none')), name, "seat 3's prompt is gone");
    check(!(await p3.evaluate(() => document.documentElement.scrollWidth > document.documentElement.clientWidth + 1)), name, 'no horizontal page scroll');
    await ctx.close();
    // Control: Team Suns has no vote — the same play happens at once and seat 3 is asked nothing.
    const t = await seed(TEAMSUNS);
    const c3 = await (await b.newContext({ viewport: s.viewport })).newPage();
    await c3.goto(url(t, 3), { waitUntil: 'domcontentloaded' }); await ready(c3); await sleep(1500);
    check(!(await c3.$('#yesno-decision-modal')), name, 'control: a Team Suns teammate is never asked to accept');
    await c3.close();
  }
  // ── The menu and the room (logged in) ─────────────────────────────────────────────────────────────────────────────────────
  for (const s of SURFACES) {
    const name = `${engine}/${s.label}/menu`;
    const page = await (await b.newContext({ viewport: s.viewport })).newPage();
    await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
    await page.fill('input[name="userID"]', 'claudebot1'); await page.fill('input[name="password"]', 'pass');
    await Promise.all([page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}), page.click('button[type="submit"]')]);
    await page.goto(BASE + 'SharedUI/Sites/SWUSim/MainMenu.php', { waitUntil: 'load' });
    await page.locator('a.mode[href="#setup-alternate"]').scrollIntoViewIfNeeded();
    await page.locator('a.mode[href="#setup-alternate"]').click(); await sleep(400);
    const lab = page.locator('#setup-alternate label[for="alt-fmt-4"]');
    const offered = await lab.count();
    check(offered === 1 && (await lab.textContent()).trim() === '4P Always Two', name, 'Fun Formats offers "4P Always Two"');
    if (offered) {
      await lab.click(); await sleep(200);
      const note = await page.evaluate(() => { const n = document.querySelector('#setup-alternate [data-alt-note="setup-alwaystwo4p"]'); return n && getComputedStyle(n).display !== 'none' ? n.innerText : ''; });
      check(/two players per team/.test(note), name, `its rules note shows: "${note.slice(0, 80)}"`);
      await page.locator('dialog[open] button[data-act="alternate"]').click(); await sleep(500);
      const d = await page.evaluate(() => { const d = document.querySelector('dialog.setup[open]'); return d ? { id: d.id, mode: d.getAttribute('data-mode'), title: d.querySelector('.setup__title').textContent } : null; });
      check(d && d.id === 'setup-pvp' && d.mode === 'alwaystwo4p' && d.title === '4P Always Two', name, `Continue opens the PvP setup in its 4P mode: ${JSON.stringify(d)}`);
      check(await shown(page, '#setup-pvp button[data-act="alwaystwo4p"]') && !(await shown(page, '#setup-pvp .a2-deck2')), name, '"Create a 4P Always Two Room", one deck');
      check(!(await shown(page, '#setup-pvp button[data-act="join"]')) && !(await shown(page, '#setup-pvp button[data-act="private"]')), name, 'no Join Queue / plain private room in this mode');
      const pools = await page.evaluate(() => [...document.querySelectorAll('#pvp-pool option')].filter(o => !o.hidden).map(o => o.value));
      check(JSON.stringify(pools) === JSON.stringify(['premier', 'eternal']), name, `only Premier and Eternal: ${pools}`);
      await sleep(1500);   // let deck detection (the remembered last deck) settle — it may pick another pool first
      const picked = await page.evaluate(() => document.getElementById('pvp-pool').value);
      check(['premier', 'eternal'].includes(picked), name, `the picker shows Premier or Eternal after deck detection (shows ${picked})`);
      if (SHOTS) await page.screenshot({ path: `${SHOTS}/a24p-menu-${engine}-${s.label}.png` });
    }
    // The room: a 4-seat team room, no bots offered.
    const DECK = fs.readFileSync(new URL('../../SWUSim/DevTools/tests/fixtures/premier_deck.txt', import.meta.url), 'utf8').trim();
    await page.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
    const r = await page.evaluate(async ({ deck }) => {
      const body = new URLSearchParams({ deckLink: deck, game_type: 'casual', preconstructedDeck: '', rootName: 'SWUSim', format: 'at4p-eternal', queueType: 'bo1', createPrivate: '1' });
      return (await fetch('../APIs/Lobbies/JoinQueue.php', { method: 'POST', body })).json();
    }, { deck: DECK });
    check(!!(r && r.success && r.lobbyID), `${name}/room`, `a 4P Always Two room is created: ${JSON.stringify(r).slice(0, 160)}`);
    if (r && r.lobbyID) {
      await page.evaluate(({ id, key }) => localStorage.setItem('tcg:lobbyAuth:' + id, JSON.stringify({ authKey: key, ts: Date.now() })), { id: r.lobbyID, key: r.authKey });
      await page.goto(BASE + 'SharedUI/WaitingRoom.php?lobby=' + encodeURIComponent(r.lobbyID), { waitUntil: 'domcontentloaded' }); await sleep(2500);
      const txt = await page.evaluate(() => document.body.innerText);
      check(/red/i.test(txt) && /blue/i.test(txt), `${name}/room`, 'the room seats Red and Blue teams');
      check(!/Fill Seat with Bot/.test(txt), `${name}/room`, 'no "Fill Seat with Bot"');
      check(/Need 4 players to start\./.test(txt), `${name}/room`, 'it cannot start with fewer than 4');
      if (SHOTS) await page.screenshot({ path: `${SHOTS}/a24p-room-${engine}-${s.label}.png`, fullPage: true });
      await page.request.post(BASE + 'APIs/Lobbies/LeaveQueue.php', { form: { rootName: 'SWUSim', playerID: String(r.playerID || 1), lobbyID: r.lobbyID, authKey: r.authKey } }).catch(() => {});
    }
    await page.close();
  }
  await b.close();
}
console.log(fails === 0 ? `PASS (${checks} checks)` : `${fails} FAILED of ${checks}`);
process.exit(fails === 0 ? 0 : 1);
