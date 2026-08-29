// LIVE two-seat turn-model check for the action-close gate.
//
// Why this exists: the 10054-section schema suite is STRUCTURALLY BLIND to the bug this guards
// against — 1834 of its files use P1OnlyActions, which claims initiative so the opponent auto-passes,
// making a DOUBLE turn swap indistinguishable from a single one. That is how 393 double-closes sat
// green for months. So the receipt for a turn-model change is a real two-seat game, not the suite.
//
// The invariant: ONE action ⇒ the turn moves to the other seat EXACTLY once. A free extra action shows
// up as the turn coming straight back to the player who just acted.
//
// Usage: node turn-alternation-live.mjs [BASE]
import { chromium } from 'playwright';
import { execSync } from 'node:child_process';

const BASE  = process.argv[2] || 'http://localhost:3400/TCGEngine/';
const CRED  = { user: 'claudebot1', pass: 'pass' };
const CRED2 = { user: 'claudebot2', pass: 'pass' };

// Premier-legal (JTL/LOF/SEC/IBH/LAW/ASH). ASH_247 One Must Destroy to Create is the NESTED PLAY —
// it defeats a friendly unit then replays it from the discard, which is bug #997's exact shape.
const DECK = [
  'Leader', 'LAW_004', 'Base', 'JTL_026', 'Deck',
  '3 JTL_100', '3 LOF_100', '3 SEC_100', '3 LAW_100', '3 ASH_100', '3 IBH_010',
  '3 JTL_101', '3 LOF_101', '3 SEC_101', '3 LAW_101', '3 ASH_101', '3 IBH_011',
  '3 JTL_102', '3 LOF_102', '3 SEC_102', '3 ASH_247', '1 JTL_103', '1 LOF_103',
].join('\n');

let allOk = true;
const results = [];
const ok = (name, cond, extra) => { if (!cond) allOk = false; results.push([name, !!cond, extra]); };

async function login(page, cred) {
  await page.goto(BASE + 'SharedUI/LoginPage.php', { waitUntil: 'domcontentloaded' });
  await page.fill('input[name="userID"]', cred.user);
  await page.fill('input[name="password"]', cred.pass);
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'load' }).catch(() => {}),
    page.click('button[type="submit"]'),
  ]);
}

// Whose action is it — read from the SERVER's gamestate, not the DOM. The DOM has no stable
// turn-player hook (my first attempt scraped one and silently returned null for every sample, so the
// loop "passed" while measuring nothing). Line 1 of Gamestate.txt is the turn player; line 0 is the
// current player. This is also the same value the schema suite's TURNPLAYER asserts, so a live
// disagreement with the suite would be visible.
const CONTAINER = 'otmtcge-swusim-web-server-1';
const sh = (cmd) => execSync(cmd, { encoding: 'utf8' }).trim();
// ⚠ Read the turn and phase from the CLIENT globals the game itself uses — `window.TurnPlayerData`
// and `window.CurrentPhaseData` (GameLayoutShared.php:1011-1012 computes isMyTurn/isMainPhase from
// exactly these). Two earlier readers were wrong and both produced convincing nonsense:
//   • scraping the DOM returned null for every sample, so the loop "passed" while measuring nothing;
//   • `sed -n '2p' Gamestate.txt` returned a plausible seat in one game and `4` in the next — the
//     file is positional and that offset is not the turn player.
// The snapshot tool reads it correctly but costs a full PHP bootstrap per call, which made the run
// take longer than the harness timeout.
const serverVar = (game, key) => {
  try {
    const line = sh(`docker exec -w /var/www/html/TCGEngine ${CONTAINER} sh -c `
      + `"grep -o '\\\"${key}\\\":\\\"[^\\\"]*\\\"' SWUSim/Games/${game}/Gamestate.txt | head -1"`);
    const m = line.match(/:"([^"]*)"$/);
    return m ? m[1] : '';
  } catch { return ''; }
};

const state = (pg) => pg.evaluate(() => ({
  turn:  String(window.TurnPlayerData   ?? '').trim() || null,
  phase: String(window.CurrentPhaseData ?? '').trim() || null,
}));

const main = async () => {
  const browser = await chromium.launch();
  const ctxA = await browser.newContext();
  const ctxB = await browser.newContext();
  const host = await ctxA.newPage();
  const join = await ctxB.newPage();
  const errs = [];
  host.on('pageerror', (e) => errs.push('host: ' + e.message));
  join.on('pageerror', (e) => errs.push('join: ' + e.message));

  try {
    await login(host, CRED);
    await login(join, CRED2);

    await host.goto(BASE + 'SharedUI/MainMenu.php', { waitUntil: 'load' });
    await host.waitForTimeout(600);
    await host.selectOption('#swu-format-select', 'premier');
    await host.click('#tab-text');
    await host.fill('#deck-text', DECK);
    await host.click('#create-private-game-btn');
    await host.waitForURL(/WaitingRoom\.php/, { timeout: 15000 }).catch(async () => {
      const msg = await host.evaluate(() => (document.body.innerText || '')
        .split('\n').filter((l) => /error|invalid|illegal|must|required|deck/i.test(l)).slice(0, 4).join(' | '));
      throw new Error('lobby creation did not navigate. page says: ' + msg);
    });
    await host.waitForTimeout(1600);
    // ⚠ Take the CODE, not the label. The element's textContent is "Invite: <code> Copy Invite Link";
    // passing that whole string as ?invite= produced a page where #wr-deck-input exists but is hidden,
    // which surfaces as an unrelated-looking fill() timeout.
    const invite = await host.evaluate(() => {
      const el = document.querySelector('#wr-invite, [data-invite]');
      if (!el) return null;
      const raw = el.getAttribute('data-invite') || el.textContent || '';
      const m = raw.match(/[0-9a-f]{16,}/i);
      return m ? m[0] : null;
    });
    ok('host created a lobby', !!invite, invite);

    await join.goto(BASE + 'SharedUI/WaitingRoom.php?invite=' + encodeURIComponent(invite),
                    { waitUntil: 'domcontentloaded' });
    await join.waitForTimeout(1500);
    await join.fill('#wr-deck-input', DECK);
    await join.click('#wr-deck-btn');
    await join.waitForTimeout(2600);

    await host.click('#wr-start');
    await Promise.all([
      host.waitForURL(/NextTurn\.php/, { timeout: 25000 }).catch(() => {}),
      join.waitForURL(/NextTurn\.php/, { timeout: 25000 }).catch(() => {}),
    ]);
    ok('both seats entered the game', /NextTurn\.php/.test(host.url()) && /NextTurn\.php/.test(join.url()),
       host.url() + ' | ' + join.url());

    const gameName = new URL(host.url()).searchParams.get('gameName');
    ok('game name captured', !!gameName, gameName);
    console.log('GAME=' + gameName);

    // ── the invariant ────────────────────────────────────────────────────────────────────────────
    // Take actions alternately and assert the turn moves to the OTHER seat each time, exactly once.
    const seats = { host: new URL(host.url()).searchParams.get('playerID'),
                    join: new URL(join.url()).searchParams.get('playerID') };
    console.log('SEATS=' + JSON.stringify(seats));

    // ── PREGAME ──────────────────────────────────────────────────────────────────────────────────
    // ⚠ A fresh game does NOT start in the action phase — it opens on the mulligan prompt, then
    // resource selection. My first version clicked Pass immediately, which is a no-op there: the turn
    // "never moved" for 8 straight actions and read exactly like a stuck-turn engine bug. Drive the
    // pregame to MAIN first, and ASSERT we got there, or the measurement below means nothing.
    await host.waitForTimeout(4000);
    for (let round = 0; round < 30; round++) {
      const st = await state(host);
      if (st.phase === 'MAIN') break;
      for (const pg of [host, join]) {
        const no = pg.locator('.yesno-decision-no');
        if (await no.count() && await no.first().isVisible().catch(() => false)) {
          await no.first().click({ timeout: 4000 }).catch(() => {}); continue;
        }
        // ⚠ THE PREGAME HAS A SECOND STEP: "Choose 2 cards to resource" (a multi-select with
        // SELECT ALL / DESELECT ALL / CONFIRM). APS cannot advance to MAIN while it is unanswered —
        // EvaluateTransition returns PENDING_DECISION whenever a queue is non-empty. Leaving it
        // unanswered is what pinned every earlier run at phase=APS and made the turn look stuck.
        const selAll = pg.locator('button:has-text("SELECT ALL")');
        if (await selAll.count() && await selAll.first().isVisible().catch(() => false)) {
          await selAll.first().click({ timeout: 4000 }).catch(() => {});
        }
        const confirm = pg.locator('button:has-text("CONFIRM")');
        if (await confirm.count() && await confirm.first().isVisible().catch(() => false)) {
          await confirm.first().click({ timeout: 4000 }).catch(() => {});
        }
      }
      await host.waitForTimeout(900);
      if (round % 5 === 0) console.log(`  pregame ${round + 1}: phase=${st.phase} turn=${st.turn}`);
    }
    const entered = await state(host);
    console.log(`  pregame done: phase=${entered.phase} turn=${entered.turn}`);
    ok('the game reached the MAIN action phase', entered.phase === 'MAIN', entered.phase);

    // ── THE INVARIANT ────────────────────────────────────────────────────────────────────────────
    // ⚠ DO NOT JUST PASS REPEATEDLY. Two CONSECUTIVE passes correctly end the action phase (CR: the
    // phase ends when both players pass in a row), so a pass-only loop measures exactly one swap and
    // then hammers Pass through the whole regroup phase — which reads as "9 stuck" and looks alarming
    // while being perfectly correct behaviour. Play a card when we can, and drive the regroup steps so
    // the next round's MAIN is reached; only MAIN actions count toward the invariant.
    let swaps = 0, violations = 0, rounds = 0, plays = 0;
    const pages = { [seats.host]: host, [seats.join]: join };

    const answerPrompts = async (pg) => {
      // ⚠ NEVER put the Pass control in here. This drains PROMPTS, and it runs on BOTH clients after
      // every action — including Pass made both players pass immediately, which correctly ended the
      // action phase after a single action and looked like "0 swaps". Pass is an ACTION, not a prompt.
      // ⚠ A card play can queue an ABILITY decision ("Deal 2 damage to a unit / PASS"). The turn
      // correctly does NOT advance while one is pending — leaving it unanswered pinned a live game at
      // turn=2 for 50+ iterations and looked exactly like a stuck-turn bug. Decline it via the prompt's
      // OWN pass, scoped INSIDE #selection-message so this can never hit the action Pass (#swuPassBtn),
      // which lives outside it and would end the action phase.
      for (const sel of ['button:has-text("SELECT ALL")', 'button:has-text("CONFIRM")',
                         '.yesno-decision-no']) {
        const l = pg.locator(sel);
        if (await l.count() && await l.first().isVisible().catch(() => false)) {
          await l.first().click({ timeout: 3500 }).catch(() => {});
        }
      }
      // ⚠ Decline an ability prompt ONLY when there is real prompt TEXT. A PASS control is present in
      // the layout at all times, so clicking `#selection-message button:has-text("PASS")` unconditionally
      // fired EXTRA PASSES between the scripted ones — which reset the consecutive-pass streak and made
      // a live game look like passes were not ending the action phase. They were: verified separately by
      // Tests/Cases/core/PassStreakEndsTheActionPhase.md, which is mutation-checked. Gate on the text.
      const txt = await pg.evaluate(() => {
        const s = document.querySelector('#selection-message');
        if (!s || s.offsetParent === null) return '';
        return (s.innerText || '').replace(/PASS|CONFIRM|SELECT ALL|DESELECT ALL/g, '').trim();
      }).catch(() => '');
      if (txt.length > 3) {
        // ⚠ UNSOLVED: an ABILITY prompt ("Deal 2 damage to a unit / PASS") is not reliably declined
        // here, so a game can stall on one — visible as a run of STUCK samples that is NOT an engine
        // fault (the turn correctly holds while a decision is pending; verified by inspecting the live
        // board). Do NOT "fix" it by clicking any element whose text is PASS: that matched a container
        // and/or the action Pass, which produced 49 stuck samples and ZERO card plays. Needs the real
        // decline control identified first.
        const p = pg.locator('#selection-message button:has-text("PASS")');
        if (await p.count() && await p.first().isVisible().catch(() => false)) {
          await p.first().click({ timeout: 3500 }).catch(() => {});
        }
      }
    };

    for (let i = 0; i < 90 && rounds < 9; i++) {
      const st = await state(host);
      if (st.phase !== 'MAIN') {                       // regroup steps: answer and let it advance
        await answerPrompts(host); await answerPrompts(join);
        // ⚠ The regroup's "Resource up to 1 card" step has its OWN PASS, rendered INSIDE
        // #selection-message. That is a different control from the action Pass (#swuPassBtn) — the
        // action one must never be auto-clicked (it ends the action phase), but this one MUST be, or
        // the game sits in RES forever and never reaches the next round. Scope the click to the prompt.
        for (const pg of [host, join]) {
          // ⚠ RESOURCE A CARD, don't decline. Declining the regroup's "Resource up to 1 card" every
          // round leaves both players on their opening 2 resources forever, so NOTHING is ever
          // affordable and the whole game is passes — 10 rounds with 0 card plays. Taking the resource
          // is what a real player does, and it is what makes cards playable later.
          const took = await pg.evaluate(() => {
            const c = document.querySelector('#myHand > span');
            if (c) { c.click(); return true; }
            return false;
          }).catch(() => false);
          if (!took) {
            const p = pg.locator('#selection-message button:has-text("PASS")');
            if (await p.count() && await p.first().isVisible().catch(() => false)) {
              await p.first().click({ timeout: 3500 }).catch(() => {});
            }
          }
        }
        await host.waitForTimeout(1200);
        const nx = await state(host);
        if (nx.phase === 'MAIN') { rounds++; console.log(`  -- round ${rounds + 1} MAIN --`); }
        continue;
      }
      const before = st.turn;
      const actor = pages[before];
      if (!actor) { ok('turn player is one of the two seats', false, String(before)); break; }

      // Prefer a real card play; fall back to Pass. A play and a pass close an action the same way,
      // but a play keeps the phase alive so we get many alternations instead of two.
      // ⚠ A hand card is `<span id="myHand-N" class="draggable selectable-card pulse">`; `.pulse` is the
      // affordability glow. My earlier guesses (.card.playable, [data-playable]) matched nothing, so the
      // loop silently fell through to Pass every time and never exercised a card play at all.
      // ⚠ `.pulse` / `.selectable-card` mean "selectable in the CURRENT PROMPT", not "affordable to
      // play" — they appear during the resource-selection step and are absent in MAIN, which is why a
      // .pulse selector found nothing across 10 rounds and every action degraded to a pass. There is no
      // reliable affordability class exposed, so just TRY hand cards in order: an illegal click is a
      // no-op server-side, and a legal one starts the play.
      const handBefore = await actor.evaluate(() => document.querySelectorAll('#myHand > span').length);
      let played = null;
      for (let h = 0; h < Math.min(handBefore, 6) && !played; h++) {
        await actor.evaluate((n) => {
          const c = document.querySelector(`#myHand-${n}`);
          if (c) c.click();
        }, h).catch(() => {});
        await host.waitForTimeout(700);
        const now = await actor.evaluate(() => document.querySelectorAll('#myHand > span').length);
        if (now < handBefore) played = 'myHand-' + h;    // the card left the hand ⇒ it was played
      }
      if (played) plays++; else await actor.evaluate(() => window.swuPassAction && window.swuPassAction());
      await host.waitForTimeout(1200);
      // A play can queue follow-up prompts (targets, optional riders). Drain them on BOTH clients —
      // a reaction can belong to the opponent.
      for (let k = 0; k < 4; k++) { await answerPrompts(actor); await answerPrompts(pages[before === seats.host ? seats.join : seats.host]); await host.waitForTimeout(500); }

      const after = await state(host);
      if (after.phase !== 'MAIN') { console.log(`  action ${i + 1}: seat ${before} ${played ? 'played ' + played : 'passed'} -> phase ${after.phase} (action phase ended)`); continue; }
      if (after.turn !== before) swaps++; else violations++;
      console.log(`  action ${i + 1}: seat ${before} ${played ? 'played ' + played : 'passed'} -> turn ${after.turn}` +
                  (after.turn === before ? '   <-- STUCK' : ''));
    }
    ok('every MAIN action moved the turn to the other seat', violations === 0 && swaps >= 2,
       `${swaps} swaps, ${violations} stuck, ${rounds} extra rounds, ${plays} card plays`);

    const dbl = serverVar(gameName, 'SWU_DOUBLE_CLOSE_N');
    console.log('SWU_DOUBLE_CLOSE_N=' + (dbl || '0'));
    ok('at least one real CARD PLAY happened (not just passes)', plays > 0, plays);
    ok('no double close was attempted in this live game', dbl === '' || dbl === '0', dbl);

    ok('no JS errors in either browser', errs.length === 0, errs.slice(0, 3));
  } catch (e) {
    ok('harness completed without throwing', false, String(e).slice(0, 200));
  } finally {
    await browser.close();
  }

  for (const [name, pass, extra] of results) {
    console.log(`${pass ? 'ok  ' : 'FAIL'}  ${name}${extra !== undefined ? '  [' + extra + ']' : ''}`);
  }
  console.log(allOk ? 'PASS' : 'FAIL');
  process.exit(allOk ? 0 : 1);
};
main();
